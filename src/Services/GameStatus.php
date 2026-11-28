<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Cache;

/**
 * Players of a running Steam game that is not Minecraft, for Simplify's server cards: Steam's server query for most
 * games (Valheim, Enshrouded, V Rising, Counter-Strike, …) and the remote console for Palworld, which does not answer
 * that query. An answer is kept 30 seconds; no answer is kept two minutes, so a game that never answers does not slow
 * down every page that lists it.
 */
final class GameStatus
{
    private const PALWORLD = '2394010';

    /** @return array{players: int, max_players: int, version: string}|array{} */
    public static function get(Server $server): array
    {
        $allocation = $server->allocation;
        if ($allocation === null || $allocation->port < 1) {
            return [];
        }
        $variables = [];
        foreach ($server->variables as $variable) {
            $variables[$variable->env_variable] = (string) ($variable->server_value ?? $variable->default_value);
        }
        if (! self::isSteamGame((array) ($server->egg->features ?? []), $variables)) {
            return [];
        }
        $key = 'simplify-toolkit.game-status.'.$server->uuid;
        $kept = Cache::get($key);
        if (is_array($kept)) {
            return $kept;
        }
        $node = (string) parse_url($server->node->getConnectionAddress(), PHP_URL_HOST);
        $hosts = StatusPing::targets($allocation->ip, $allocation->ip_alias, $node);
        $status = ($variables['SRCDS_APPID'] ?? '') === self::PALWORLD
            ? self::palworld($hosts, $variables)
            : SteamQuery::ask($hosts, self::queryPorts($allocation->port, $variables));
        Cache::put($key, $status, $status === [] ? 120 : 30);

        return $status;
    }

    /**
     * A game installed with SteamCMD: its egg names a Steam app id (SRCDS_APPID) or asks for Steam's disk space check.
     *
     * @param  array<int|string, mixed>  $features
     * @param  array<string, string>  $variables
     */
    public static function isSteamGame(array $features, array $variables): bool
    {
        return trim($variables['SRCDS_APPID'] ?? '') !== '' || in_array('steam_disk_space', $features, true);
    }

    /**
     * Ports to ask, in order: a query port the egg names (QUERY_PORT, VR_QUERY_PORT, …), the port after the game's port
     * (Valheim, Enshrouded) and the game's port itself (Source games such as Counter-Strike).
     *
     * @param  array<string, string>  $variables
     * @return list<int>
     */
    public static function queryPorts(int $port, array $variables): array
    {
        $ports = [];
        foreach ($variables as $name => $value) {
            if (preg_match('/(^|_)QUERY(_PORT)?$/', (string) $name) === 1 && ctype_digit(trim($value))) {
                $ports[] = (int) trim($value);
            }
        }
        $ports[] = $port + 1;
        $ports[] = $port;

        return array_values(array_unique(array_filter($ports, fn (int $number) => $number > 0 && $number < 65536)));
    }

    /**
     * Palworld: who is online from its remote console (ShowPlayers), and the most players from the egg's MAX_PLAYERS.
     * Only while the remote console is on and its port is given to the server.
     *
     * @param  list<string>  $hosts
     * @param  array<string, string>  $variables
     * @return array{players: int, max_players: int, version: string}|array{}
     */
    private static function palworld(array $hosts, array $variables): array
    {
        $port = (int) ($variables['RCON_PORT'] ?? 0);
        $password = $variables['ADMIN_PASSWORD'] ?? '';
        if (strtolower(trim($variables['RCON_ENABLE'] ?? 'true')) === 'false' || $port < 1 || $password === '') {
            return [];
        }
        foreach ($hosts as $host) {
            $answer = SourceRcon::run($host, $port, $password, 'ShowPlayers');
            if ($answer !== null) {
                return ['players' => self::palworldPlayers($answer), 'max_players' => max(0, (int) ($variables['MAX_PLAYERS'] ?? 0)), 'version' => ''];
            }
        }

        return [];
    }

    /** The players in Palworld's answer to ShowPlayers: a line "name,playeruid,steamid", then one line per player. */
    public static function palworldPlayers(string $answer): int
    {
        $lines = array_filter(array_map('trim', explode("\n", $answer)), fn (string $line) => $line !== '');

        return max(0, count($lines) - 1);
    }
}
