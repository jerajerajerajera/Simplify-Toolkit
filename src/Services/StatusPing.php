<?php

declare(strict_types=1);

namespace Simplify\Mods\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

/**
 * Asks a running Minecraft server what every Minecraft client asks it for its server list: players, version and
 * MOTD. One short TCP connection to the server's own port, cached for half a minute.
 */
final class StatusPing
{
    private const PROTOCOL = 47;

    /**
     * Empty when the server does not answer.
     *
     * @return array{players: int, max_players: int, version: string, motd: string, favicon: string}|array{}
     */
    public static function get(Server $server): array
    {
        return Cache::remember('simplify-mods.ping.'.$server->uuid, 30, function () use ($server): array {
            $port = (int) $server->allocation?->port;
            $node = (string) parse_url((string) $server->node?->getConnectionAddress(), PHP_URL_HOST);
            foreach ($port > 0 ? self::targets($server->allocation?->ip, $server->allocation?->ip_alias, $node) : [] as $host) {
                $answer = self::ask($host, $port);
                if ($answer !== null) {
                    return self::read($answer);
                }
            }

            return [];
        });
    }

    /**
     * Hosts to try, in order. A wildcard allocation (0.0.0.0 or ::) has no address of its own: loopback first (panel
     * and Wings on one machine), then its alias (the address players use), then the node's host name.
     *
     * @return list<string>
     */
    public static function targets(?string $ip, ?string $alias, string $nodeHost): array
    {
        if (! in_array($ip, ['', '0.0.0.0', '::', null], true)) {
            return [$ip];
        }
        $valid = fn (string $host): bool => $host !== '' && (filter_var($host, FILTER_VALIDATE_IP) !== false
            || (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false && str_contains($host, '.')));
        $alias = trim((string) $alias);

        return array_values(array_unique(array_filter([$ip === '::' ? '::1' : '127.0.0.1', $valid($alias) ? $alias : '', $valid($nodeHost) ? $nodeHost : ''])));
    }

    /** The JSON status the server sends, null when it does not answer like a Minecraft server. */
    private static function ask(string $host, int $port): ?string
    {
        $socket = @stream_socket_client('tcp://'.(str_contains($host, ':') ? '['.$host.']' : $host).':'.$port, $code, $error, 0.7);
        if ($socket === false) {
            return null;
        }
        try {
            stream_set_timeout($socket, 1);
            $handshake = self::varint(0).self::varint(self::PROTOCOL).self::varint(strlen($host)).$host.pack('n', $port).self::varint(1);
            stream_socket_sendto($socket, self::varint(strlen($handshake)).$handshake."\x01\x00");
            $length = self::readVarint($socket);
            if ($length < 1 || $length > 2097152 || self::readVarint($socket) !== 0) {
                return null;
            }
            $size = self::readVarint($socket);

            return $size < 1 || $size > 2097152 ? null : self::readBytes($socket, $size);
        } catch (Throwable) {
            return null;
        } finally {
            fclose($socket);
        }
    }

    /**
     * @return array{players: int, max_players: int, version: string, motd: string, favicon: string}|array{}
     */
    public static function read(string $json): array
    {
        $data = json_decode($json, true);
        if (! is_array($data)) {
            return [];
        }
        $favicon = (string) ($data['favicon'] ?? '');

        return [
            'players' => max(0, (int) ($data['players']['online'] ?? 0)),
            'max_players' => max(0, (int) ($data['players']['max'] ?? 0)),
            'version' => mb_substr((string) ($data['version']['name'] ?? ''), 0, 40),
            'motd' => mb_substr(self::legacy($data['description'] ?? ''), 0, 2048),
            'favicon' => str_starts_with($favicon, 'data:image/png;base64,') && strlen($favicon) <= 150000 ? $favicon : '',
        ];
    }

    /** The status text component as legacy § text, as in server.properties. Never HTML. */
    public static function legacy(mixed $component, array $inherited = [], int $depth = 0): string
    {
        if ($depth > 24) {
            return '';
        }
        if (is_string($component)) {
            return $component;
        }
        if (! is_array($component)) {
            return '';
        }
        if (array_is_list($component)) {
            return implode('', array_map(fn ($part) => self::legacy($part, $inherited, $depth + 1), array_slice($component, 0, 200)));
        }
        $style = array_replace($inherited, array_intersect_key($component, array_flip(['color', 'bold', 'italic', 'underlined', 'strikethrough', 'obfuscated'])));
        $colors = ['black' => '0', 'dark_blue' => '1', 'dark_green' => '2', 'dark_aqua' => '3', 'dark_red' => '4', 'dark_purple' => '5', 'gold' => '6', 'gray' => '7',
            'dark_gray' => '8', 'blue' => '9', 'green' => 'a', 'aqua' => 'b', 'red' => 'c', 'light_purple' => 'd', 'yellow' => 'e', 'white' => 'f'];
        $prefix = '§r';
        $color = $style['color'] ?? null;
        if (is_string($color) && isset($colors[$color])) {
            $prefix .= '§'.$colors[$color];
        } elseif (is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color) === 1) {
            $prefix .= '§x§'.implode('§', str_split(substr($color, 1)));
        }
        foreach (['bold' => 'l', 'italic' => 'o', 'underlined' => 'n', 'strikethrough' => 'm', 'obfuscated' => 'k'] as $field => $code) {
            if (($style[$field] ?? false) === true) {
                $prefix .= '§'.$code;
            }
        }
        $text = is_string($component['text'] ?? null) ? $component['text'] : '';
        $result = $text !== '' ? $prefix.$text : '';
        foreach (array_slice(is_array($component['extra'] ?? null) ? $component['extra'] : [], 0, 200) as $extra) {
            $result .= self::legacy($extra, $style, $depth + 1);
        }

        return $result;
    }

    public static function varint(int $number): string
    {
        $bytes = '';
        do {
            $byte = $number & 127;
            $number >>= 7;
            $bytes .= chr($byte | ($number !== 0 ? 128 : 0));
        } while ($number !== 0);

        return $bytes;
    }

    /** @param  resource  $socket */
    private static function readVarint($socket): int
    {
        $value = 0;
        for ($i = 0; $i < 5; $i++) {
            $byte = ord(self::readBytes($socket, 1));
            $value |= ($byte & 127) << ($i * 7);
            if (($byte & 128) === 0) {
                return $value;
            }
        }
        throw new RuntimeException('invalid length');
    }

    /** @param  resource  $socket */
    private static function readBytes($socket, int $count): string
    {
        $bytes = '';
        while (strlen($bytes) < $count) {
            $chunk = fread($socket, $count - strlen($bytes));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('incomplete answer');
            }
            $bytes .= $chunk;
        }

        return $bytes;
    }
}
