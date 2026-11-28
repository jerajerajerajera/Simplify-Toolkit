<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use RuntimeException;
use Throwable;

/**
 * One command over Source RCON (TCP), the remote console that Palworld and many Steam games offer. Used where a game
 * does not answer Steam's server query, such as Palworld, to ask who is online.
 *
 * @see https://developer.valvesoftware.com/wiki/Source_RCON_Protocol
 */
final class SourceRcon
{
    private const AUTH = 3;

    private const COMMAND = 2;

    /** The answer to the command, or null when the server cannot be reached or refuses the password. */
    public static function run(string $host, int $port, string $password, string $command, float $seconds = 1.0): ?string
    {
        $socket = @stream_socket_client('tcp://'.(str_contains($host, ':') ? '['.$host.']' : $host).':'.$port, $code, $error, $seconds);
        if ($socket === false) {
            return null;
        }
        try {
            stream_set_timeout($socket, (int) $seconds, (int) (fmod($seconds, 1) * 1000000));
            stream_socket_sendto($socket, self::packet(1, self::AUTH, $password));
            // Some servers send an empty answer before the result of the login; a refused login comes back with id -1.
            for ($tries = 0, $reply = self::readPacket($socket); $reply['type'] !== 2 && $tries < 3; $tries++) {
                $reply = self::readPacket($socket);
            }
            if ($reply['type'] !== 2 || $reply['id'] !== 1) {
                return null;
            }
            stream_socket_sendto($socket, self::packet(2, self::COMMAND, $command));

            return self::readPacket($socket)['body'];
        } catch (Throwable) {
            return null;
        } finally {
            fclose($socket);
        }
    }

    /** A packet as the protocol writes it: size, id, type, body and two zero bytes, all little-endian. */
    public static function packet(int $id, int $type, string $body): string
    {
        $payload = pack('V2', $id, $type).$body."\x00\x00";

        return pack('V', strlen($payload)).$payload;
    }

    /**
     * @param  resource  $socket
     * @return array{id: int, type: int, body: string}
     */
    private static function readPacket($socket): array
    {
        $size = unpack('Vsize', self::bytes($socket, 4))['size'];
        if ($size < 10 || $size > 65536) {
            throw new RuntimeException('not an RCON packet');
        }

        return self::parse(self::bytes($socket, $size));
    }

    /** @return array{id: int, type: int, body: string} the packet after its size */
    public static function parse(string $payload): array
    {
        $head = unpack('lid/Vtype', substr($payload, 0, 8));

        return ['id' => (int) $head['id'], 'type' => (int) $head['type'], 'body' => rtrim(substr($payload, 8), "\x00")];
    }

    /** @param  resource  $socket */
    private static function bytes($socket, int $count): string
    {
        $bytes = '';
        $missing = $count;
        while ($missing > 0) {
            $chunk = fread($socket, $missing);
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('incomplete answer');
            }
            $bytes .= $chunk;
            $missing -= strlen($chunk);
        }

        return $bytes;
    }
}
