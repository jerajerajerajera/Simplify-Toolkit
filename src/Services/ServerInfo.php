<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\Server;
use Throwable;

/**
 * What Simplify 1.1 and later show on a Minecraft server's card and page (Addon::serverInfo): players, MOTD,
 * version and icon from the status ping, with the modpack's icon when the server sends none.
 */
final class ServerInfo
{
    /** @return array{players?: int, max_players?: int, motd?: string, version?: string, icon?: string} */
    public static function for(Server $server): array
    {
        try {
            if (! ServerKind::forServer($server)['minecraft']) {
                return [];
            }
            $status = StatusPing::get($server);

            return self::from($status, ($status['favicon'] ?? '') === '' ? (Modpack::of($server)['icon'] ?? null) : null);
        } catch (Throwable $error) {
            report($error);

            return [];
        }
    }

    /**
     * @param  array{players?: int, max_players?: int, version?: string, motd?: string, favicon?: string}  $status
     * @return array{players?: int, max_players?: int, motd?: string, version?: string, icon?: string}
     */
    public static function from(array $status, ?string $modpackIcon): array
    {
        if ($status === []) {
            return [];
        }
        $icon = ($status['favicon'] ?? '') !== '' ? $status['favicon'] : (string) $modpackIcon;

        return array_filter([
            'players' => $status['players'] ?? null,
            'max_players' => $status['max_players'] ?? null,
            'motd' => $status['motd'] ?? '',
            'version' => $status['version'] ?? '',
            'icon' => str_starts_with($icon, 'data:image/png;base64,') || str_starts_with($icon, 'https://') ? $icon : '',
        ], fn ($value) => $value !== null && $value !== '');
    }
}
