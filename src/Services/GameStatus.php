<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\Server;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Players of a running game, for Simplify's server cards. Automatic: Steam's server query for a Steam egg that is not
 * Minecraft (Valheim, Enshrouded, V Rising, Counter-Strike, …), Palworld's remote console. Chosen per egg (PlayerCounts):
 * Minecraft Java or Bedrock, Steam, FiveM/RedM, Palworld's REST API or remote console, on the query port chosen; or
 * Player Counter's game query when that plugin has one for the egg. An answer is kept 30 seconds; no answer is kept two
 * minutes, so a game that never answers does not slow down every page that lists it.
 */
final class GameStatus
{
    private const PALWORLD = '2394010';

    /**
     * Toolkit's own automatic count for a Steam egg that is not Minecraft: Steam's server query, or Palworld's remote
     * console. [] for any other egg.
     *
     * @return array{players: int, max_players: int, version: string}|array{}
     */
    public static function get(Server $server): array
    {
        $allocation = $server->allocation;
        if ($allocation === null || $allocation->port < 1) {
            return [];
        }
        $variables = self::variables($server);
        if (! self::isSteamGame((array) ($server->egg->features ?? []), $variables)) {
            return [];
        }

        return self::kept($server, function () use ($server, $allocation, $variables): array {
            $hosts = self::hosts($server);

            return ($variables['SRCDS_APPID'] ?? '') === self::PALWORLD
                ? self::palworld($hosts, $variables)
                : SteamQuery::ask($hosts, self::queryPorts($allocation->port, $variables));
        });
    }

    /**
     * The count the admin chose for the server's egg: from Player Counter when it has a game query for the egg, else
     * the protocol and query port set under Simplify › Toolkit › Player counts ([] while it does not answer, and for
     * "Off"). Null when the egg is automatic, so the caller counts as before.
     *
     * @return array{players: int, max_players: int, version: string, motd?: string, favicon?: string}|array{}|null
     */
    public static function chosen(Server $server): ?array
    {
        if (isset(PlayerCounter::eggs()[(int) $server->egg_id])) {
            return self::kept($server, fn (): array => PlayerCounter::get($server) ?? []);
        }
        $choice = PlayerCounts::forEgg((int) $server->egg_id);
        if ($choice['protocol'] === PlayerCounts::AUTOMATIC) {
            return null;
        }
        $allocation = $server->allocation;
        if ($choice['protocol'] === PlayerCounts::OFF || $allocation === null || $allocation->port < 1) {
            return [];
        }

        return self::kept($server, function () use ($server, $allocation, $choice): array {
            $variables = self::variables($server);

            return self::ask($choice['protocol'], self::hosts($server), PlayerCounts::port($choice, $allocation->port, $variables), $variables);
        });
    }

    /**
     * One protocol on one port, for each host in turn.
     *
     * @param  list<string>  $hosts
     * @param  array<string, string>  $variables
     * @return array{players: int, max_players: int, version: string, motd?: string, favicon?: string}|array{}
     */
    public static function ask(string $protocol, array $hosts, int $port, array $variables): array
    {
        switch ($protocol) {
            case 'minecraft_java':
                $status = StatusPing::at($hosts, $port);

                return $status === [] ? [] : array_intersect_key($status, array_flip(['players', 'max_players', 'version', 'motd', 'favicon']));
            case 'minecraft_bedrock':
                return BedrockQuery::ask($hosts, $port);
            case 'steam':
                return SteamQuery::ask($hosts, [$port]);
            case 'palworld_rcon':
                return self::palworld($hosts, ['RCON_PORT' => (string) $port] + $variables);
        }
        foreach (array_slice($hosts, 0, 2) as $host) {
            $base = 'http://'.(str_contains($host, ':') ? '['.$host.']' : $host).':'.$port;
            try {
                $answer = match ($protocol) {
                    'fivem' => self::fivem(self::json(Http::timeout(2)->connectTimeout(1)->acceptJson()->get($base.'/dynamic.json'))),
                    'palworld_rest' => ($variables['ADMIN_PASSWORD'] ?? '') === '' ? [] : self::palworldRest(self::json(Http::timeout(2)->connectTimeout(1)->acceptJson()
                        ->withBasicAuth('admin', $variables['ADMIN_PASSWORD'])->get($base.'/v1/api/metrics'))),
                    default => [],
                };
            } catch (Throwable) {
                $answer = [];
            }
            if ($answer !== []) {
                return $answer;
            }
        }

        return [];
    }

    /**
     * FiveM's and RedM's /dynamic.json: "clients" and "sv_maxclients".
     *
     * @param  array<mixed>  $answer
     * @return array{players: int, max_players: int, version: string}|array{}
     */
    public static function fivem(array $answer): array
    {
        if (! isset($answer['clients']) || ! is_numeric($answer['clients'])) {
            return [];
        }

        return ['players' => max(0, (int) $answer['clients']), 'max_players' => max(0, (int) ($answer['sv_maxclients'] ?? 0)), 'version' => ''];
    }

    /**
     * Palworld's REST API, /v1/api/metrics: "currentplayernum" and "maxplayernum".
     *
     * @param  array<mixed>  $answer
     * @return array{players: int, max_players: int, version: string}|array{}
     */
    public static function palworldRest(array $answer): array
    {
        if (! isset($answer['currentplayernum']) || ! is_numeric($answer['currentplayernum'])) {
            return [];
        }

        return ['players' => max(0, (int) $answer['currentplayernum']), 'max_players' => max(0, (int) ($answer['maxplayernum'] ?? 0)), 'version' => ''];
    }

    /** @return array<string, string> the egg variables of the server, with its own values */
    public static function variables(Server $server): array
    {
        $variables = [];
        foreach ($server->variables as $variable) {
            $variables[$variable->env_variable] = (string) ($variable->server_value ?? $variable->default_value);
        }

        return $variables;
    }

    /** @return list<string> */
    private static function hosts(Server $server): array
    {
        $allocation = $server->allocation;
        $node = (string) parse_url($server->node->getConnectionAddress(), PHP_URL_HOST);

        return StatusPing::targets($allocation?->ip, $allocation?->ip_alias, $node, PlayerCounts::useAlias());
    }

    /**
     * An answer is kept 30 seconds; no answer is kept two minutes, so a game that never answers does not slow down
     * every page that lists it.
     *
     * @param  callable(): array  $ask
     */
    private static function kept(Server $server, callable $ask): array
    {
        $key = 'simplify-toolkit.game-status.'.$server->uuid;
        $kept = Cache::get($key);
        if (is_array($kept)) {
            return $kept;
        }
        try {
            $status = $ask();
        } catch (Throwable) {
            $status = [];
        }
        Cache::put($key, $status, $status === [] ? 120 : 30);

        return $status;
    }

    /** @return array<mixed> */
    private static function json(Response $response): array
    {
        $json = $response->successful() ? $response->json() : null;

        return is_array($json) ? $json : [];
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
