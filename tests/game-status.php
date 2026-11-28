<?php

// Players of other games without a panel: Steam's server query (A2S_INFO), Bedrock's unconnected ping, FiveM's and
// Palworld's answers, the ports to ask and the choice per egg, against small servers on this machine.
declare(strict_types=1);

require __DIR__.'/../src/Services/SteamQuery.php';
require __DIR__.'/../src/Services/SourceRcon.php';
require __DIR__.'/../src/Services/StatusPing.php';
require __DIR__.'/../src/Services/GameStatus.php';
require __DIR__.'/../src/Services/BedrockQuery.php';
require __DIR__.'/../src/Services/PlayerCounts.php';

use Simplify\Toolkit\Services\BedrockQuery;
use Simplify\Toolkit\Services\GameStatus;
use Simplify\Toolkit\Services\PlayerCounts;
use Simplify\Toolkit\Services\SourceRcon;
use Simplify\Toolkit\Services\SteamQuery;

$failures = [];
function check(bool $ok, string $what): void
{
    global $failures;
    if (! $ok) {
        $failures[] = $what;
    }
}

/** An A2S_INFO answer as a Valheim server sends it: 3 players of whom 1 bot, 10 at most, version 0.219.16. */
function info(int $players = 3, int $max = 10, int $bots = 1, string $version = '0.219.16'): string
{
    return "\xFF\xFF\xFF\xFFI\x11"."My server\x00Dedicated\x00valheim\x00Valheim\x00".pack('v', 0)
        .chr($players).chr($max).chr($bots).'dl'."\x00\x00".$version."\x00";
}

check(SteamQuery::read(info()) === ['players' => 2, 'max_players' => 10, 'version' => '0.219.16'], 'players without bots, max and version: '.json_encode(SteamQuery::read(info())));
check(SteamQuery::read(info(0, 24, 0, "1.0\x01")) === ['players' => 0, 'max_players' => 24, 'version' => '1.0'], 'an empty server, control characters dropped from the version');
check(SteamQuery::read("\xFF\xFF\xFF\xFFA\x01\x02\x03\x04") === [] && SteamQuery::read('') === [] && SteamQuery::read("\xFF\xFF\xFF\xFFI\x11name only") === [], 'a challenge, nothing or half an answer is no answer');

check(GameStatus::queryPorts(2456, ['SRCDS_APPID' => '896660']) === [2457, 2456], 'Valheim: the port after the game port first');
check(GameStatus::queryPorts(9876, ['VR_QUERY_PORT' => '9877', 'VR_RCON_PORT' => '25575']) === [9877, 9876], 'V Rising: its query port, once');
check(GameStatus::queryPorts(15636, ['QUERY_PORT' => '15640', 'SERVER_PORT' => '15636']) === [15640, 15637, 15636], 'Enshrouded: the query port it names first');
check(GameStatus::queryPorts(27015, ['QUERY_PORT' => '', 'STEAM_QUERY_PORT' => 'x']) === [27016, 27015], 'empty or wrong query ports are left out');
check(GameStatus::isSteamGame([], ['SRCDS_APPID' => '2278520']) && GameStatus::isSteamGame(['steam_disk_space'], []), 'a Steam game by its app id or its disk space check');
check(! GameStatus::isSteamGame(['eula'], ['SERVER_JARFILE' => 'server.jar', 'SRCDS_APPID' => ' ']), 'Minecraft is not a Steam game');
check(GameStatus::palworldPlayers("name,playeruid,steamid\nAlice,123,7656\nBob,456,7657\n") === 2 && GameStatus::palworldPlayers("name,playeruid,steamid\n") === 0 && GameStatus::palworldPlayers('') === 0, 'players in Palworld\'s ShowPlayers');

// The protocol and query port chosen per egg (Simplify › Toolkit › Player counts).
check(PlayerCounts::clean(['protocol' => 'nope', 'port' => 'x', 'port_offset' => 0, 'port_variable' => 'A B']) === PlayerCounts::default(), 'unknown choices are automatic on the game port');
check(PlayerCounts::clean(['protocol' => 'steam', 'port' => 'offset', 'port_offset' => '19378']) === ['protocol' => 'steam', 'port' => 'offset', 'port_offset' => 19378, 'port_variable' => ''], 'a known choice is kept');
check(PlayerCounts::port(['port' => 'game', 'port_offset' => 1, 'port_variable' => ''], 2456, []) === 2456
    && PlayerCounts::port(['port' => 'offset', 'port_offset' => 1, 'port_variable' => ''], 2456, []) === 2457
    && PlayerCounts::port(['port' => 'offset', 'port_offset' => 9000, 'port_variable' => ''], 60000, []) === 60000
    && PlayerCounts::port(['port' => 'variable', 'port_offset' => 1, 'port_variable' => 'QUERY_PORT'], 15636, ['QUERY_PORT' => '15637']) === 15637
    && PlayerCounts::port(['port' => 'variable', 'port_offset' => 1, 'port_variable' => 'QUERY_PORT'], 15636, ['QUERY_PORT' => '']) === 15636, 'the query port: game port, plus a distance, from a variable, and the game port when that holds none');
check(GameStatus::fivem(['clients' => 4, 'sv_maxclients' => '48', 'hostname' => 'x']) === ['players' => 4, 'max_players' => 48, 'version' => ''] && GameStatus::fivem([]) === [], 'FiveM\'s dynamic.json');
check(GameStatus::palworldRest(['currentplayernum' => 2, 'maxplayernum' => 32]) === ['players' => 2, 'max_players' => 32, 'version' => ''] && GameStatus::palworldRest(['error' => 'x']) === [], 'Palworld\'s REST API metrics');

/** A Bedrock server's unconnected pong. */
function pong(string $text): string
{
    return "\x1c".pack('J', 1).pack('J', 99)."\x00\xff\xff\x00\xfe\xfe\xfe\xfe\xfd\xfd\xfd\xfd\x12\x34\x56\x78".pack('n', strlen($text)).$text;
}
check(BedrockQuery::read(pong('MCPE;§aMy world;748;1.21.40;3;10;123;Sub;Survival;1;19132;19133;')) === ['players' => 3, 'max_players' => 10, 'version' => '1.21.40', 'motd' => 'My world'], 'a Bedrock pong: players, max, version, MOTD without colours');
check(BedrockQuery::read(pong('MCPE;x;1;1;a;b')) === [] && BedrockQuery::read('') === [] && BedrockQuery::read("\x1c".str_repeat("\x00", 40)) === [], 'no Bedrock answer from anything else');
check(strlen(BedrockQuery::ping()) === 33 && BedrockQuery::ping()[0] === "\x01", 'a Bedrock unconnected ping');

$packet = SourceRcon::packet(7, 2, 'ShowPlayers');
check(strlen($packet) === 4 + 8 + 11 + 2 && unpack('V', $packet)[1] === 8 + 11 + 2, 'an RCON packet: size, id, type, body, two zero bytes');
check(SourceRcon::parse(substr($packet, 4)) === ['id' => 7, 'type' => 2, 'body' => 'ShowPlayers'], 'an RCON packet read back');
check(SourceRcon::parse(pack('l', -1).pack('V', 2)."\x00\x00")['id'] === -1, 'a refused login (id -1)');

// The real exchanges, against servers in a child process. Steam's query: a challenge first, as servers have sent since
// 2020.
if (function_exists('pcntl_fork')) {
    $server = stream_socket_server('udp://127.0.0.1:0', $code, $error, STREAM_SERVER_BIND);
    $port = (int) substr((string) stream_socket_get_name($server, false), strrpos((string) stream_socket_get_name($server, false), ':') + 1);
    $child = pcntl_fork();
    if ($child === 0) {
        for ($i = 0; $i < 2; $i++) {
            $request = stream_socket_recvfrom($server, 1400, 0, $peer);
            $answer = str_ends_with($request, "Query\x00") ? "\xFF\xFF\xFF\xFFA\x01\x02\x03\x04" : (str_ends_with($request, "\x01\x02\x03\x04") ? info(5, 16, 0) : '');
            stream_socket_sendto($server, $answer, 0, $peer);
        }
        exit(0);
    }
    $answer = SteamQuery::ask(['127.0.0.1'], [$port + 1 === 65536 ? 1 : $port + 1, $port], 2.0);
    pcntl_waitpid($child, $status);
    check($answer === ['players' => 5, 'max_players' => 16, 'version' => '0.219.16'], 'a real exchange with a challenge, after a port without a server: '.json_encode($answer));
    // Bedrock's unconnected ping.
    $server = stream_socket_server('udp://127.0.0.1:0', $code, $error, STREAM_SERVER_BIND);
    $port = (int) substr((string) stream_socket_get_name($server, false), strrpos((string) stream_socket_get_name($server, false), ':') + 1);
    $child = pcntl_fork();
    if ($child === 0) {
        $request = stream_socket_recvfrom($server, 1400, 0, $peer);
        stream_socket_sendto($server, $request[0] === "\x01" ? pong('MCPE;Hi;748;1.21.40;1;5;1;;Survival;') : '', 0, $peer);
        exit(0);
    }
    $answer = BedrockQuery::ask(['127.0.0.1'], $port, 2.0);
    pcntl_waitpid($child, $status);
    check(($answer['players'] ?? null) === 1 && ($answer['max_players'] ?? null) === 5, 'a real Bedrock exchange: '.json_encode($answer));
    // Palworld's remote console: an empty answer before the login's result, then the players.
    $server = stream_socket_server('tcp://127.0.0.1:0', $code, $error);
    $name = (string) stream_socket_get_name($server, false);
    $port = (int) substr($name, strrpos($name, ':') + 1);
    $child = pcntl_fork();
    if ($child === 0) {
        $client = stream_socket_accept($server, 5);
        $login = SourceRcon::parse(substr((string) fread($client, 4096), 4));
        $ok = $login['body'] === 'secret';
        stream_socket_sendto($client, SourceRcon::packet($login['id'], 0, '').SourceRcon::packet($ok ? $login['id'] : -1, 2, ''));
        if ($ok) {
            $command = SourceRcon::parse(substr((string) fread($client, 4096), 4));
            stream_socket_sendto($client, SourceRcon::packet($command['id'], 0, $command['body'] === 'ShowPlayers' ? "name,playeruid,steamid\nAlice,1,2\n" : 'Unknown command'));
        }
        exit(0);
    }
    $answer = SourceRcon::run('127.0.0.1', $port, 'secret', 'ShowPlayers');
    pcntl_waitpid($child, $status);
    check($answer !== null && GameStatus::palworldPlayers($answer) === 1, 'Palworld\'s remote console: '.json_encode($answer));
    $child = pcntl_fork();
    if ($child === 0) {
        $client = stream_socket_accept($server, 5);
        $login = SourceRcon::parse(substr((string) fread($client, 4096), 4));
        stream_socket_sendto($client, SourceRcon::packet(-1, 2, ''));
        exit(0);
    }
    $answer = SourceRcon::run('127.0.0.1', $port, 'wrong', 'ShowPlayers');
    pcntl_waitpid($child, $status);
    check($answer === null, 'a wrong password gives nothing');
} else {
    echo "SKIP the real exchanges: no pcntl here\n";
}
check(SourceRcon::run('127.0.0.1', 9, 'secret', 'ShowPlayers', 0.3) === null, 'no remote console on a port without a server');
check(SteamQuery::ask(['127.0.0.1'], [9], 0.3) === [], 'no answer from a port without a server');
check(BedrockQuery::ask(['127.0.0.1'], 9, 0.3) === [], 'no Bedrock answer from a port without a server');

if ($failures !== []) {
    echo 'FAIL Steam query: '.implode('; ', $failures)."\n";
    exit(1);
}
echo "PASS other games: Steam's server query with its challenge, Bedrock, FiveM, Palworld, the ports to ask and the egg's choice\n";
