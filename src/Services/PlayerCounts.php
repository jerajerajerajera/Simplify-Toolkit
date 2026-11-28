<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use Simplify\Toolkit\Models\PlayerCount;
use Throwable;

/**
 * The admin's choice, per egg, of how the server cards count players: the protocol to ask and the query port. An egg
 * without a choice is "automatic": the Minecraft status ping, or Steam's server query for a Steam egg.
 */
final class PlayerCounts
{
    public const AUTOMATIC = 'automatic';

    public const OFF = 'off';

    /** The protocols, by id. Their names are the games' own, so they are not translated. */
    public const PROTOCOLS = [
        'minecraft_java' => 'Minecraft Java',
        'minecraft_bedrock' => 'Minecraft Bedrock',
        'steam' => 'Steam (A2S)',
        'fivem' => 'FiveM / RedM',
        'palworld_rest' => 'Palworld (REST API)',
        'palworld_rcon' => 'Palworld (RCON)',
    ];

    public const PORTS = ['game', 'offset', 'variable'];

    /** @var array<int, array{protocol: string, port: string, port_offset: int, port_variable: string}>|null */
    private static ?array $eggs = null;

    private static ?bool $alias = null;

    /** @return array{protocol: string, port: string, port_offset: int, port_variable: string} */
    public static function default(): array
    {
        return ['protocol' => self::AUTOMATIC, 'port' => 'game', 'port_offset' => 1, 'port_variable' => ''];
    }

    /** @return array{protocol: string, port: string, port_offset: int, port_variable: string} */
    public static function forEgg(int $egg): array
    {
        return self::eggs()[$egg] ?? self::default();
    }

    /** @return array<int, array{protocol: string, port: string, port_offset: int, port_variable: string}> */
    public static function eggs(): array
    {
        if (self::$eggs === null) {
            self::$eggs = [];
            try {
                foreach (PlayerCount::query()->whereNotNull('egg_id')->get() as $row) {
                    self::$eggs[(int) $row->egg_id] = self::clean($row->only(['protocol', 'port', 'port_offset', 'port_variable']));
                }
            } catch (Throwable) {
                // Before the migration ran there is no choice yet: every egg is automatic.
            }
        }

        return self::$eggs;
    }

    /** Ask the allocation alias when the allocation's IP is 0.0.0.0 or a local address (on unless switched off). */
    public static function useAlias(): bool
    {
        if (self::$alias === null) {
            try {
                self::$alias = (bool) (PlayerCount::query()->whereNull('egg_id')->value('use_alias') ?? true);
            } catch (Throwable) {
                self::$alias = true;
            }
        }

        return self::$alias;
    }

    /**
     * Saves the choices of the eggs given and the panel's alias choice. An automatic egg keeps no row.
     *
     * @param  array<int|string, mixed>  $eggs
     */
    public static function save(array $eggs, bool $useAlias): void
    {
        PlayerCount::query()->updateOrCreate(['egg_id' => null], ['use_alias' => $useAlias]);
        foreach ($eggs as $egg => $choice) {
            if (! is_numeric($egg) || ! is_array($choice)) {
                continue;
            }
            $choice = self::clean($choice);
            if ($choice === self::default()) {
                PlayerCount::query()->where('egg_id', (int) $egg)->delete();

                continue;
            }
            PlayerCount::query()->updateOrCreate(['egg_id' => (int) $egg], $choice);
        }
        self::$eggs = null;
        self::$alias = null;
    }

    /**
     * A choice with only known values: an unknown protocol is automatic, an unknown port the game port.
     *
     * @param  array<string, mixed>  $choice
     * @return array{protocol: string, port: string, port_offset: int, port_variable: string}
     */
    public static function clean(array $choice): array
    {
        $protocol = (string) ($choice['protocol'] ?? '');
        $port = (string) ($choice['port'] ?? '');
        $variable = (string) ($choice['port_variable'] ?? '');

        return [
            'protocol' => isset(self::PROTOCOLS[$protocol]) || $protocol === self::OFF ? $protocol : self::AUTOMATIC,
            'port' => in_array($port, self::PORTS, true) ? $port : 'game',
            'port_offset' => min(65534, max(1, (int) ($choice['port_offset'] ?? 1))),
            'port_variable' => preg_match('/^[A-Za-z0-9_]{1,191}$/', $variable) === 1 ? $variable : '',
        ];
    }

    /**
     * The port to ask: the game port, the game port plus the distance chosen, or the value of the egg variable chosen
     * (the game port when that variable holds no port).
     *
     * @param  array{port: string, port_offset: int, port_variable: string}  $choice
     * @param  array<string, string>  $variables
     */
    public static function port(array $choice, int $gamePort, array $variables): int
    {
        $port = match ($choice['port']) {
            'offset' => $gamePort + $choice['port_offset'],
            'variable' => ctype_digit(trim($variables[$choice['port_variable']] ?? '')) ? (int) trim($variables[$choice['port_variable']]) : $gamePort,
            default => $gamePort,
        };

        return $port > 0 && $port < 65536 ? $port : $gamePort;
    }

    /** For tests: forget what was read from the database. */
    public static function forget(): void
    {
        self::$eggs = null;
        self::$alias = null;
    }
}
