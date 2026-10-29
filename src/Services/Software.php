<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\Server;
use Simplify\Toolkit\Models\ServerChoice;

/**
 * What a Minecraft server runs, from its egg or what its owner chose. Cheap: the database only, never Wings.
 */
final class Software
{
    private const NAMES = ['neoforge' => 'NeoForge', 'bungeecord' => 'BungeeCord', 'spigot' => 'Spigot', 'bukkit' => 'Bukkit'];

    /** @return array{name: string, version: string} name '' when it is no Minecraft server or not known */
    public static function of(Server $server): array
    {
        $kind = ServerKind::forServer($server);
        if (! $kind['minecraft']) {
            return ['name' => '', 'version' => ''];
        }
        $loader = $kind['loader'];
        $version = $kind['version'];
        if ($loader === '' || $version === '') {
            $choice = ServerChoice::query()->where('server_id', $server->id)->first();
            $loader = $loader ?: (string) $choice?->loader;
            $version = $version ?: (string) $choice?->minecraft;
        }

        return ['name' => $loader === '' ? '' : (self::NAMES[$loader] ?? ucfirst($loader)), 'version' => $version];
    }

    /** "Paper 1.21.4", "Fabric" or "". */
    public static function label(Server $server): string
    {
        $software = self::of($server);

        return trim($software['name'].' '.($software['name'] !== '' ? $software['version'] : ''));
    }
}
