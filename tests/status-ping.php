<?php

// The Minecraft status ping without a server: lengths, hosts to try and the answer turned into plain values.
declare(strict_types=1);

require __DIR__.'/../src/Services/StatusPing.php';
require __DIR__.'/../src/Services/ServerInfo.php';
require __DIR__.'/../src/Services/Players.php';

use Simplify\Toolkit\Services\ServerInfo;
use Simplify\Toolkit\Services\StatusPing;

$failures = [];
function check(bool $ok, string $what): void
{
    global $failures;
    if (! $ok) {
        $failures[] = $what;
    }
}

check(StatusPing::varint(0) === "\x00" && StatusPing::varint(300) === "\xac\x02" && StatusPing::varint(25565) === "\xdd\xc7\x01", 'lengths as Minecraft writes them');
check(StatusPing::targets('10.0.0.5', 'play.example.com', 'node.example.com') === ['10.0.0.5'], 'an allocation with its own address');
check(StatusPing::targets('0.0.0.0', 'play.example.com', 'node.example.com') === ['127.0.0.1', 'play.example.com', 'node.example.com'], 'a wildcard: loopback, alias, node');
check(StatusPing::targets('::', 'not a host', '') === ['::1'], 'IPv6 wildcard, invalid alias skipped');

$answer = StatusPing::read(json_encode([
    'version' => ['name' => 'Paper 1.21.4', 'protocol' => 769],
    'players' => ['online' => 3, 'max' => 20, 'sample' => [['name' => 'Steve', 'id' => 'x']]],
    'description' => ['text' => 'Hello ', 'color' => 'gold', 'extra' => [['text' => 'world', 'bold' => true], ['text' => '!', 'color' => '#ff0000']]],
    'favicon' => 'data:image/png;base64,AAAA',
]));
check($answer['players'] === 3 && $answer['max_players'] === 20 && $answer['version'] === 'Paper 1.21.4', 'players and version: '.json_encode($answer));
check($answer['motd'] === '§r§6Hello §r§6§lworld§r§x§f§f§0§0§0§0!', 'MOTD as legacy text: '.$answer['motd']);
check($answer['favicon'] === 'data:image/png;base64,AAAA', 'the icon the server sends');
check(StatusPing::read('{"description":"Plain","favicon":"javascript:x"}')['motd'] === 'Plain' && StatusPing::read('{"favicon":"https://x"}')['favicon'] === '', 'plain MOTD, other icons dropped');
check(StatusPing::read('not json') === [], 'not a Minecraft answer');
check($answer['names'] === ['Steve'], 'online names: '.json_encode($answer['names']));
check(StatusPing::read('{"players":{"sample":[{"name":"§6Join us!"},{"name":".BedrockKid"},"x",{"name":"Alex"}]}}')['names'] === ['.BedrockKid', 'Alex'], 'text lines in the sample are not names');

$info = ServerInfo::from($answer, 'https://cdn.modrinth.com/icon.png');
check($info === ['players' => 3, 'max_players' => 20, 'motd' => $answer['motd'], 'version' => 'Paper 1.21.4', 'icon' => 'data:image/png;base64,AAAA'], 'Simplify card info: '.json_encode($info));
check(ServerInfo::from([...$answer, 'favicon' => ''], 'https://cdn.modrinth.com/icon.png')['icon'] === 'https://cdn.modrinth.com/icon.png', 'the modpack icon when the server sends none');
check(! isset(ServerInfo::from([...$answer, 'favicon' => ''], 'http://x/icon.png')['icon']), 'only https icons');
check(ServerInfo::from([], 'https://cdn.modrinth.com/icon.png') === [], 'nothing while the server does not answer');

if ($failures) {
    echo "FAIL status ping:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS status ping\n";
