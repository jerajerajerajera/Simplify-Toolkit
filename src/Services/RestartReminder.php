<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Enums\ContainerStatus;
use App\Models\Server;
use App\Repositories\Daemon\DaemonServerRepository;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * "Changes are waiting for a restart": set when a page here changes a file the game reads at start, until the server
 * starts again. A restart can be planned some minutes ahead. Pelican's scheduler checks every minute (tick()): it
 * restarts what is due and forgets reminders of servers that have started since, so the cards stay right without
 * asking Wings while they draw.
 */
final class RestartReminder
{
    /** uuid => ['since' => when the change was made, 'at' => planned restart or null] */
    private const KEY = 'simplify-toolkit.restarts';

    public const MINUTES = [5, 15, 30, 60];

    private static bool $quiet = false;

    /** @return array{since: int, at: int|null}|null */
    public static function get(Server $server): ?array
    {
        return self::all()[$server->uuid] ?? null;
    }

    public static function mark(Server $server): void
    {
        self::change(fn (array $all) => [...$all, $server->uuid => ['since' => time(), 'at' => $all[$server->uuid]['at'] ?? null]]);
    }

    /**
     * A file saved in Pelican's file manager while a Minecraft server runs: the game reads it on the next start, so the
     * same reminder, and the notice 0.7.0 gave ("File changes saved, restart to load them").
     */
    public static function fileSaved(Server $server): void
    {
        if (self::$quiet) {
            return;
        }
        try {
            if (! ServerKind::forServer($server)['minecraft'] || $server->retrieveStatus() !== ContainerStatus::Running) {
                return;
            }
            self::mark($server);
            Notification::make()->title(__('simplify-toolkit::restart.file_saved'))->body(__('simplify-toolkit::restart.waiting_help'))->warning()->send();
        } catch (Throwable $error) {
            report($error);
        }
    }

    /** Runs a file write of a Toolkit page, which shows its own reminder instead. */
    public static function quietly(callable $write): mixed
    {
        self::$quiet = true;
        try {
            return $write();
        } finally {
            self::$quiet = false;
        }
    }

    public static function plan(Server $server, int $minutes): void
    {
        self::change(fn (array $all) => [...$all, $server->uuid => ['since' => $all[$server->uuid]['since'] ?? time(), 'at' => time() + $minutes * 60]]);
    }

    public static function forget(Server $server): void
    {
        self::change(function (array $all) use ($server) {
            unset($all[$server->uuid]);

            return $all;
        });
    }

    public static function restart(Server $server): void
    {
        app(DaemonServerRepository::class)->setServer($server)->power('restart')->throw();
        self::forget($server);
    }

    /** Every minute: planned restarts that are due, and reminders of servers that stopped or started since. */
    public static function tick(): void
    {
        foreach (self::all() as $uuid => $reminder) {
            $server = Server::query()->where('uuid', $uuid)->first();
            try {
                if ($server === null) {
                    self::change(fn (array $all) => array_diff_key($all, [$uuid => true]));
                } elseif ($reminder['at'] !== null && $reminder['at'] <= time()) {
                    self::restart($server);
                } elseif (! self::runningSince($server, $reminder['since'])) {
                    self::forget($server);
                }
            } catch (Throwable $error) {
                report($error);
            }
        }
    }

    /** True while the server runs since before the change; a stopped server loads the change when it starts. */
    private static function runningSince(Server $server, int $since): bool
    {
        $details = app(DaemonServerRepository::class)->setServer($server)->getDetails();
        $uptime = (int) ($details['utilization']['uptime'] ?? 0) / 1000;

        return ($details['state'] ?? 'offline') === 'running' && time() - $uptime <= $since;
    }

    /** @return array<string, array{since: int, at: int|null}> */
    private static function all(): array
    {
        $all = Cache::get(self::KEY);

        return is_array($all) ? $all : [];
    }

    private static function change(callable $change): void
    {
        Cache::lock(self::KEY.'.lock', 5)->block(3, fn () => Cache::forever(self::KEY, $change(self::all())));
    }
}
