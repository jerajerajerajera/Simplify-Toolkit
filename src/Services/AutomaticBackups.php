<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\BackupHost;
use App\Models\Server;
use App\Services\Backups\DeleteBackupService;
use App\Services\Backups\InitiateBackupService;
use Illuminate\Support\Facades\Cache;
use Simplify\Toolkit\Models\BackupPlan;
use Throwable;

/**
 * A backup every so many minutes per server, keeping a number of recovery points, as PelicanSimplify 0.7.0 did.
 * Pelican's scheduler runs tick() every minute: one query until a backup is due. Only the backups made here are ever
 * deleted, and a new one is made before the oldest goes, so a failed backup never replaces a recovery point.
 */
final class AutomaticBackups
{
    /** How often a server's running state is looked at for "skip while the server has not run". */
    private const PROBE_SECONDS = 600;

    /** The panel's own row: whether backups run at all, and what new servers get. */
    public static function panel(): BackupPlan
    {
        return BackupPlan::query()->whereNull('server_id')->first()
            ?? new BackupPlan(['enabled' => true, 'every_minutes' => 360, 'keep' => 2, 'only_when_used' => true]);
    }

    /** A server's plan; a server without one shows the panel's values, switched off. */
    public static function for(Server $server): BackupPlan
    {
        return BackupPlan::query()->where('server_id', $server->id)->first()
            ?? new BackupPlan(['server_id' => $server->id, ...self::values(self::panel()), 'enabled' => false]);
    }

    /** New servers start with the panel's values. */
    public static function start(Server $server): void
    {
        BackupPlan::query()->firstOrCreate(['server_id' => $server->id], [...self::values(self::panel()), 'enabled' => true]);
    }

    /** @param  array<string, mixed>  $values */
    public static function save(?Server $server, array $values): void
    {
        $values = validator($values, [
            'enabled' => ['required', 'boolean'], 'every_minutes' => ['required', 'integer', 'min:30', 'max:10080'],
            'keep' => ['required', 'integer', 'min:1', 'max:100'], 'only_when_used' => ['required', 'boolean'],
        ])->validate();
        BackupPlan::query()->updateOrCreate(['server_id' => $server?->id], $values);
    }

    /** Whether a backup host takes this server's backups. */
    public static function storage(Server $server): bool
    {
        try {
            return $server->node->backupHosts()->exists() || BackupHost::doesntHave('nodes')->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public static function tick(): void
    {
        if (! self::panel()->enabled) {
            return;
        }
        foreach (BackupPlan::query()->whereNotNull('server_id')->where('enabled', true)->with('server')->get() as $plan) {
            try {
                if ($plan->server !== null) {
                    Cache::lock('simplify-toolkit.backups.'.$plan->server_id, 60)->get(fn () => self::run($plan, $plan->server));
                }
            } catch (Throwable $error) {
                report($error);
            }
        }
    }

    private static function run(BackupPlan $plan, Server $server): void
    {
        if (! $server->isInstalled() || $server->status !== null || $server->backup_limit < 1) {
            return;
        }
        $now = time();
        if ($plan->only_when_used && $now - $plan->last_probe >= self::PROBE_SECONDS) {
            $plan->last_probe = $now;
            try {
                $running = $server->retrieveStatus()->value !== 'offline';
            } catch (Throwable) {
                $running = true; // An unknown state never skips a backup.
            }
            $plan->last_active = $running ? $now : $plan->last_active;
        }
        $mine = fn () => $server->backups()->where('is_scheduled', true)->where('name', 'like', __('simplify-toolkit::backups.name').' · %');
        // After a new backup finished, the oldest recovery points beyond the plan go, once.
        if ($plan->pruned_at < $plan->last_attempt && ! $server->backups()->whereNull('completed_at')->exists()) {
            foreach ($mine()->where('is_successful', true)->where('is_locked', false)->orderByDesc('created_at')->get()->slice($plan->keep) as $old) {
                app(DeleteBackupService::class)->handle($old);
            }
            $plan->pruned_at = $now;
        }
        $due = $now - $plan->last_attempt >= $plan->every_minutes * 60;
        if ($due && ! $server->backups()->whereNull('completed_at')->exists()) {
            $latest = $server->backups()->where('is_successful', true)->max('created_at');
            $latestAt = $latest ? (int) strtotime((string) $latest) : 0;
            $unused = $plan->only_when_used && $latestAt > 0 && $plan->last_active < $latestAt;
            if ($unused || ! self::storage($server) || $now - $latestAt < $plan->every_minutes * 60) {
                // Looked at again in five minutes, not every minute.
                $plan->last_attempt = max($plan->last_attempt, $now - $plan->every_minutes * 60 + 300);
            } else {
                $plan->last_attempt = $now;
                $plan->save();
                // One slot more for the new backup, so the oldest recovery point only goes once it is done.
                $slot = clone $server;
                $slot->backup_limit = $server->backups()->nonFailed()->count() + 1;
                app(InitiateBackupService::class)->setIsScheduled(true)->setIsLocked(false)
                    ->handle($slot, __('simplify-toolkit::backups.name').' · '.now()->format('Y-m-d H:i'));
            }
        }
        $plan->save();
    }

    /** @return array{every_minutes: int, keep: int, only_when_used: bool} */
    private static function values(BackupPlan $plan): array
    {
        return ['every_minutes' => $plan->every_minutes, 'keep' => $plan->keep, 'only_when_used' => $plan->only_when_used];
    }
}
