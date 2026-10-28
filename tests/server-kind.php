<?php

// ServerKind without a panel: which eggs are Minecraft, plugins or mods, and which version they run.
declare(strict_types=1);

require __DIR__.'/../src/Services/ServerKind.php';

use Simplify\Mods\Services\ServerKind;

$failures = [];
function check(bool $ok, string $what): void
{
    global $failures;
    if (! $ok) {
        $failures[] = $what;
    }
}
function kind(string $egg, array $features = [], array $variables = []): array
{
    return ServerKind::of($egg, $features, $variables);
}

$paper = kind('Paper', ['eula', 'java_version'], ['MINECRAFT_VERSION' => '1.21.4', 'BUILD_NUMBER' => 'latest']);
check($paper === ['minecraft' => true, 'proxy' => false, 'kind' => 'plugins', 'loader' => 'paper', 'version' => '1.21.4', 'modpack' => ''], 'Paper has plugins: '.json_encode($paper));
check(kind('Purpur', [], ['MINECRAFT_VERSION' => 'latest'])['version'] === '', '"latest" is no version: the real one is read from the server later');
check(kind('NeoForge', [], ['MC_VERSION' => '1.21.1'])['loader'] === 'neoforge', 'NeoForge is not mistaken for Forge');
check(kind('Forge Enhanced', [], ['MC_VERSION' => '1.20.1'])['kind'] === 'mods', 'Forge has mods');
check(kind('Fabric')['loader'] === 'fabric' && kind('Quilt')['kind'] === 'mods', 'Fabric and Quilt have mods');
check(kind('Vanilla Minecraft', ['eula'])['kind'] === '' && kind('Vanilla Minecraft', ['eula'])['minecraft'], 'Vanilla is Minecraft without plugins or mods');
check(kind('Modrinth Modpack')['modpack'] === 'modrinth' && kind('Modrinth Modpack')['kind'] === 'mods', 'a Modrinth modpack has mods');
check(kind('Feed The Beast')['modpack'] === 'ftb' && kind('CurseForge Generic')['modpack'] === 'curseforge', 'FTB and CurseForge modpacks are recognised');
check(kind('Velocity')['proxy'] && kind('Velocity')['kind'] === 'plugins', 'Velocity is a proxy with plugins');
check(kind('BungeeCord')['loader'] === 'bungeecord', 'BungeeCord is recognised by "bungee"');
check(! kind('Minecraft Bedrock', ['eula'])['minecraft'] && ! kind('PocketMine-MP')['minecraft'], 'Bedrock servers are left out');
check(! kind('Rust')['minecraft'] && ! kind('Terraria', ['pid_limit'])['minecraft'], 'other games are left out');
check(kind('My own server', ['eula'])['minecraft'], 'any egg with the "eula" feature is Minecraft');
check(kind('Paper', [], ['MINECRAFT_VERSION' => '1.21; rm -rf'])['version'] === '', 'a strange version is ignored');

if ($failures) {
    echo "FAIL server kind:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS server kind\n";
