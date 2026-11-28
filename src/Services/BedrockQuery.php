<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use Throwable;

/**
 * Asks a Minecraft Bedrock server what the game's server list asks it (RakNet's unconnected ping, over UDP): players,
 * the most it takes, its version and MOTD.
 *
 * @see https://wiki.bedrock.dev/servers/raknet-and-mcpe
 */
final class BedrockQuery
{
    private const MAGIC = "\x00\xff\xff\x00\xfe\xfe\xfe\xfe\xfd\xfd\xfd\xfd\x12\x34\x56\x78";

    /**
     * The first host that answers on the port, or [] when none does within the time given.
     *
     * @param  list<string>  $hosts
     * @return array{players: int, max_players: int, version: string, motd: string}|array{}
     */
    public static function ask(array $hosts, int $port, float $seconds = 1.5): array
    {
        $until = microtime(true) + $seconds;
        foreach ($hosts as $host) {
            $left = $until - microtime(true);
            if ($left <= 0) {
                break;
            }
            $answer = self::once($host, $port, min(0.7, $left));
            if ($answer !== []) {
                return $answer;
            }
        }

        return [];
    }

    /** @return array{players: int, max_players: int, version: string, motd: string}|array{} */
    private static function once(string $host, int $port, float $seconds): array
    {
        $socket = @stream_socket_client('udp://'.(str_contains($host, ':') ? '['.$host.']' : $host).':'.$port, $code, $error, $seconds);
        if ($socket === false) {
            return [];
        }
        try {
            stream_set_timeout($socket, (int) $seconds, (int) (fmod($seconds, 1) * 1000000));
            if (stream_socket_sendto($socket, self::ping()) === -1) {
                return [];
            }

            return self::read((string) @fread($socket, 2048));
        } catch (Throwable) {
            return [];
        } finally {
            fclose($socket);
        }
    }

    /** An unconnected ping: its id, the time, RakNet's magic bytes and a client id. */
    public static function ping(): string
    {
        return "\x01".pack('J', (int) (microtime(true) * 1000)).self::MAGIC.pack('J', 2);
    }

    /**
     * The players, the most the server takes, its version and MOTD from an unconnected pong; [] for anything else. The
     * pong carries "MCPE;motd;protocol;version;players;max;…".
     *
     * @return array{players: int, max_players: int, version: string, motd: string}|array{}
     */
    public static function read(string $reply): array
    {
        if (strlen($reply) < 35 || $reply[0] !== "\x1c" || substr($reply, 17, 16) !== self::MAGIC) {
            return [];
        }
        $length = unpack('n', substr($reply, 33, 2))[1];
        $parts = explode(';', substr($reply, 35, $length));
        if (count($parts) < 6 || ! in_array($parts[0], ['MCPE', 'MCEE'], true) || ! ctype_digit($parts[4]) || ! ctype_digit($parts[5])) {
            return [];
        }
        $text = fn (string $value): string => mb_substr(preg_replace('/§.|[\x00-\x1F\x7F]/u', '', $value) ?? '', 0, 120);

        return ['players' => (int) $parts[4], 'max_players' => (int) $parts[5], 'version' => $text($parts[3]), 'motd' => $text($parts[1])];
    }
}
