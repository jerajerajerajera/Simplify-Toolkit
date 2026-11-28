<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\Server;
use Throwable;

/**
 * What Simplify Toolkit adds to a server in Simplify Embed's public server list (the event "simplify.embed.server"):
 * for Minecraft software, version and modpack always, and players and MOTD while it runs; for another Steam game its
 * players while it runs.
 */
final class EmbedDetails
{
    /** @return array<string, mixed> */
    public static function for(Server $server, bool $running): array
    {
        try {
            if (! ServerKind::forServer($server)['minecraft']) {
                $status = $running ? (GameStatus::chosen($server) ?? GameStatus::get($server)) : [];

                return $status === [] ? [] : ['players' => $status['players'], 'max_players' => $status['max_players']];
            }
            $software = Software::of($server);
            $details = array_filter([
                'game' => 'Minecraft',
                'software' => $software['name'],
                'version' => $software['version'],
            ]);
            $pack = Modpack::of($server);
            if ($pack !== null) {
                $details['modpack'] = array_filter($pack);
                if ($pack['icon'] !== '') {
                    $details['icon'] = $pack['icon'];
                }
            }
            $status = $running ? (GameStatus::chosen($server) ?? StatusPing::get($server)) : [];
            if ($status !== []) {
                $details += ['players' => $status['players'], 'max_players' => $status['max_players'], 'motd' => $status['motd'] ?? ''];
                // The running server knows its version best, also when the egg says "latest".
                if (($status['version'] ?? '') !== '' && preg_match('/\d+\.\d+(\.\d+)?/', $status['version'], $match) === 1) {
                    $details['version'] = $match[0];
                }
            }

            return $details;
        } catch (Throwable $error) {
            report($error);

            return [];
        }
    }
}
