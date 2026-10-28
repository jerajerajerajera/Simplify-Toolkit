<?php

declare(strict_types=1);

namespace Simplify\Mods\Services;

use App\Facades\Activity;
use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Illuminate\Support\Carbon;
use Simplify\Mods\Models\Package;
use Simplify\Mods\Services\Sources\Versions;
use Throwable;

/**
 * After a start: plugins installed through Simplify Mods that failed to load are switched off (.jar.disabled), so the
 * next start is clean, and keep the log line that says why. Plugins that loaded fine are no longer watched.
 */
final class StartCheck
{
    public const LOG = '/logs/latest.log';

    /** Whether a plugin installed here waits for its first start. Cheap: one query, no Wings. */
    public static function watching(Server $server): bool
    {
        return Package::query()->where('server_id', $server->id)->whereNotNull('watch_since')->exists();
    }

    /**
     * Reads the log of the last start when it is newer than the oldest install that is watched.
     *
     * @return list<array{title: string, version: string, reason: string}> the plugins that were switched off now
     */
    public static function run(Server $server): array
    {
        $watched = Package::query()->where('server_id', $server->id)->whereNotNull('watch_since')->get();
        if ($watched->isEmpty()) {
            return [];
        }
        $files = app(DaemonFileRepository::class)->setServer($server);
        try {
            $written = null;
            foreach ($files->getDirectory('/logs') as $file) {
                if (($file['name'] ?? '') === 'latest.log') {
                    $written = Carbon::parse((string) ($file['modified'] ?? $file['modified_at'] ?? ''));
                }
            }
            // Installing needs no stopped server, so only a log written after the install belongs to a start with it.
            if ($written === null || $written->lt($watched->min('watch_since'))) {
                return [];
            }
            $log = $files->getContent(self::LOG, 4194304);
        } catch (Throwable) {
            return [];
        }
        $failed = self::failures($log, $watched->map(fn (Package $p) => ['filename' => $p->filename, 'title' => $p->title])->all());
        $started = preg_match('/\bDone \(\d/', $log) === 1;
        $off = [];
        foreach ($watched as $index => $package) {
            if (isset($failed[$index])) {
                $directory = ltrim($package->directory, '/');
                try {
                    $files->renameFiles('/', [['from' => $directory.'/'.$package->filename, 'to' => $directory.'/'.$package->filename.'.disabled']])->throw();
                } catch (Throwable) {
                    continue;
                }
                Activity::event('server:file.rename')->subject($server)->property('directory', '/'.$directory)
                    ->property('files', [['from' => $package->filename, 'to' => $package->filename.'.disabled']])->log();
                $package->update(['watch_since' => null, 'off_reason' => $failed[$index]['reason'], 'off_lines' => $failed[$index]['lines']]);
                $off[] = ['title' => $package->title, 'version' => $package->version, 'reason' => $failed[$index]['reason']];
            } elseif ($started && $written->gte($package->watch_since)) {
                $package->update(['watch_since' => null]);
            }
        }

        return $off;
    }

    /**
     * Plugins named in error lines of a start log: "Could not load 'plugins/x.jar'", "Error occurred while enabling
     * Name v1 (Is it up to date?)", or any ERROR, SEVERE or FATAL line with the file name.
     *
     * @param  array<int|string, array{filename: string, title: string}>  $watched
     * @return array<int|string, array{reason: string, lines: list<string>}> per key of $watched that failed
     */
    public static function failures(string $log, array $watched): array
    {
        $found = [];
        $lines = preg_split('/\R/', $log) ?: [];
        foreach ($lines as $at => $line) {
            if (preg_match('/\b(ERROR|SEVERE|FATAL)\b|Error occurred while enabling|Could not load/i', $line) !== 1) {
                continue;
            }
            $named = preg_match('/(?:Error occurred while enabling|Could not load plugin) \'?([^\'\s]+)/i', $line, $match) === 1
                ? Versions::same($match[1]) : '';
            foreach ($watched as $key => $plugin) {
                if (isset($found[$key])) {
                    continue;
                }
                $names = array_filter(
                    [Versions::same($plugin['title']), Versions::same(Versions::fromFilename($plugin['filename'])[0])],
                    fn (string $name) => strlen($name) >= 3,
                );
                $byName = strlen($named) >= 3 && array_filter($names, fn (string $name) => str_starts_with($name, $named) || str_starts_with($named, $name)) !== [];
                if (($plugin['filename'] !== '' && str_contains($line, $plugin['filename'])) || $byName) {
                    $found[$key] = $at;
                }
            }
        }

        // The line without its time stamp, and the cause and first stack frames around it.
        return array_map(fn (int $at) => [
            'reason' => mb_substr(trim((string) preg_replace('/^\[[^\]]*\]:?\s*/', '', $lines[$at])), 0, 240),
            'lines' => array_map(fn (string $line) => mb_substr($line, 0, 400), array_slice($lines, max(0, $at - 2), 40)),
        ], $found);
    }
}
