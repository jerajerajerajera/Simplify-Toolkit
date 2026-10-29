<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\Server;

/**
 * What kind of Minecraft server a server is, read from its egg and variables only (no Wings call), so it is cheap
 * enough for menus and lists.
 */
final class ServerKind
{
    /** Server software that loads plugins from /plugins. */
    public const PLUGIN_LOADERS = ['paper', 'purpur', 'folia', 'spigot', 'bukkit', 'velocity', 'waterfall', 'bungeecord'];

    /** Mod loaders that load mods from /mods. */
    public const MOD_LOADERS = ['neoforge', 'forge', 'fabric', 'quilt'];

    /** Proxies have plugins, but no world: no server.properties, EULA or server icon of their own. */
    public const PROXIES = ['velocity', 'waterfall', 'bungeecord'];

    private const VERSION_VARIABLES = ['MINECRAFT_VERSION', 'MC_VERSION', 'SERVER_VERSION'];

    /**
     * @param  list<string>  $features  the egg's features, such as "eula"
     * @param  array<string, string>  $variables  the server's variables by env name
     * @return array{minecraft: bool, proxy: bool, kind: string, loader: string, version: string, modpack: string}
     *                                                                                                             kind is "plugins", "mods" or ""; modpack is "modrinth", "curseforge", "ftb" or ""
     */
    public static function of(string $eggName, array $features, array $variables): array
    {
        $egg = strtolower($eggName);
        $none = ['minecraft' => false, 'proxy' => false, 'kind' => '', 'loader' => '', 'version' => '', 'modpack' => ''];

        // Bedrock has no Java plugins, mods, server.properties layout or EULA file.
        if (preg_match('/bedrock|pocketmine|nukkit|geyser/', $egg) === 1) {
            return $none;
        }
        $named = preg_match('/minecraft|vanilla|paper|purpur|folia|spigot|bukkit|velocity|waterfall|bungee|forge|fabric|quilt|modrinth|curseforge|ftb|feed the beast|technic|tekkit|sponge/', $egg) === 1;
        if (! $named && ! in_array('eula', $features, true)) {
            return $none;
        }

        $loader = '';
        foreach ([...self::MOD_LOADERS, ...self::PLUGIN_LOADERS] as $candidate) {
            $pattern = $candidate === 'bungeecord' ? 'bungee' : $candidate;
            // "neoforge" contains "forge", so it is tried first.
            if (str_contains($egg, $pattern)) {
                $loader = $candidate;
                break;
            }
        }
        $modpack = match (true) {
            str_contains($egg, 'modrinth') => 'modrinth',
            str_contains($egg, 'curseforge') => 'curseforge',
            str_contains($egg, 'ftb') || str_contains($egg, 'feed the beast') => 'ftb',
            default => '',
        };
        $kind = match (true) {
            in_array($loader, self::PLUGIN_LOADERS, true) => 'plugins',
            in_array($loader, self::MOD_LOADERS, true), $modpack !== '' => 'mods',
            default => '',
        };

        $version = '';
        foreach (self::VERSION_VARIABLES as $name) {
            $value = trim((string) ($variables[$name] ?? ''));
            if ($value !== '') {
                $version = $value;
                break;
            }
        }
        if (strtolower($version) === 'latest' || preg_match('/^[0-9a-zA-Z._-]{1,40}$/', $version) !== 1) {
            $version = '';
        }

        return [
            'minecraft' => true,
            'proxy' => in_array($loader, self::PROXIES, true),
            'kind' => $kind,
            'loader' => $loader,
            'version' => $version,
            'modpack' => $modpack,
        ];
    }

    /** @return array{minecraft: bool, proxy: bool, kind: string, loader: string, version: string, modpack: string} */
    public static function forServer(Server $server): array
    {
        $variables = [];
        foreach ($server->variables as $variable) {
            $variables[$variable->env_variable] = (string) ($variable->server_value ?? $variable->default_value);
        }

        return self::of((string) $server->egg?->name, (array) ($server->egg?->features ?? []), $variables);
    }
}
