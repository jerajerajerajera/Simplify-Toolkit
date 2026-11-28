<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use Throwable;

/**
 * Asks a running Steam game server what Steam's server browser asks it (A2S_INFO, over UDP): players, the most it
 * takes and its version. Valheim, Enshrouded, V Rising, Counter-Strike and most other Steam games answer it, on the
 * game's own port or on a separate query port.
 *
 * @see https://developer.valvesoftware.com/wiki/Server_queries#A2S_INFO
 */
final class SteamQuery
{
    private const REQUEST = "\xFF\xFF\xFF\xFFTSource Engine Query\x00";

    /**
     * The first host and port that answers, or [] when none does within the time given.
     *
     * @param  list<string>  $hosts
     * @param  list<int>  $ports
     * @return array{players: int, max_players: int, version: string}|array{}
     */
    public static function ask(array $hosts, array $ports, float $seconds = 1.5): array
    {
        $until = microtime(true) + $seconds;
        foreach ($ports as $port) {
            foreach ($hosts as $host) {
                $left = $until - microtime(true);
                if ($left <= 0) {
                    return [];
                }
                $answer = self::once($host, $port, min(0.5, $left));
                if ($answer !== []) {
                    return $answer;
                }
            }
        }

        return [];
    }

    /** @return array{players: int, max_players: int, version: string}|array{} */
    private static function once(string $host, int $port, float $seconds): array
    {
        $socket = @stream_socket_client('udp://'.(str_contains($host, ':') ? '['.$host.']' : $host).':'.$port, $code, $error, $seconds);
        if ($socket === false) {
            return [];
        }
        try {
            stream_set_timeout($socket, (int) $seconds, (int) (fmod($seconds, 1) * 1000000));
            $reply = self::exchange($socket, self::REQUEST);
            // Since 2020 a server may first send a challenge, to be sent back with the request.
            if (strlen($reply) === 9 && $reply[4] === 'A') {
                $reply = self::exchange($socket, self::REQUEST.substr($reply, 5, 4));
            }

            return self::read($reply);
        } catch (Throwable) {
            return [];
        } finally {
            fclose($socket);
        }
    }

    /** @param  resource  $socket */
    private static function exchange($socket, string $request): string
    {
        if (stream_socket_sendto($socket, $request) === -1) {
            return '';
        }

        return (string) @fread($socket, 1400);
    }

    /**
     * The players, the most the server takes and its version from an A2S_INFO answer; [] for anything else.
     *
     * @return array{players: int, max_players: int, version: string}|array{}
     */
    public static function read(string $reply): array
    {
        if (strlen($reply) < 6 || substr($reply, 0, 5) !== "\xFF\xFF\xFF\xFFI") {
            return [];
        }
        $at = 6; // after the header, the type and the protocol
        foreach (['name', 'map', 'folder', 'game'] as $field) {
            $end = strpos($reply, "\x00", $at);
            if ($end === false) {
                return [];
            }
            $at = $end + 1;
        }
        // App id (2 bytes), then players, max players, bots, server type, environment, visibility and VAC (1 byte each).
        if (strlen($reply) < $at + 9) {
            return [];
        }
        $players = ord($reply[$at + 2]);
        $max = ord($reply[$at + 3]);
        $bots = ord($reply[$at + 4]);
        $end = strpos($reply, "\x00", $at + 9);
        $version = $end === false ? '' : substr($reply, $at + 9, $end - $at - 9);

        return [
            'players' => max(0, $players - $bots),
            'max_players' => $max,
            'version' => mb_substr(preg_replace('/[^\x20-\x7E]/', '', $version) ?? '', 0, 40),
        ];
    }
}
