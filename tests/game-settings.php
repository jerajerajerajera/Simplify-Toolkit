<?php

// server.properties without a panel: reading, changing only chosen keys, and the Minecraft page's settings.
declare(strict_types=1);

require __DIR__.'/../src/Services/PropertiesFile.php';
require __DIR__.'/../src/Services/GameSettings.php';

use Simplify\Toolkit\Services\GameSettings;
use Simplify\Toolkit\Services\PropertiesFile;

$failures = [];
function check(bool $ok, string $what): void
{
    global $failures;
    if (! $ok) {
        $failures[] = $what;
    }
}

$file = "#Minecraft server properties\n#Tue Oct 28 12:00:00 UTC 2026\nallow-flight=false\ndifficulty=easy\ngamemode=survival\nmax-players=20\nmotd=\\u00a7aHello\\nworld \\u2764\nlevel-seed=\nserver-port=25565\nresource-pack=https\\://example.com/pack.zip\n";
$read = PropertiesFile::parse($file);
check($read['motd'] === "§aHello\nworld ❤", 'the MOTD is decoded: '.json_encode($read['motd']));
check($read['resource-pack'] === 'https://example.com/pack.zip', 'escaped colons are decoded');
check($read['level-seed'] === '' && ! isset($read['#Minecraft']), 'empty values are kept and comments skipped');
check(PropertiesFile::parse("a=one \\\n    two\n")['a'] === 'one two', 'continuation lines are joined');

$merged = PropertiesFile::merge($file, ['motd' => "§cRed\nline", 'max-players' => '50', 'hardcore' => 'true']);
check(str_contains($merged, "motd=\\u00a7cRed\\nline\n"), 'the new MOTD is encoded: '.$merged);
check(str_contains($merged, "max-players=50\n") && str_contains($merged, "hardcore=true\n"), 'changed keys are replaced and missing ones added');
check(str_starts_with($merged, "#Minecraft server properties\n#Tue Oct 28") && str_contains($merged, "server-port=25565\nresource-pack=https\\://example.com/pack.zip\n"), 'comments and other keys stay exactly as they were');
check(substr_count($merged, 'motd=') === 1, 'a key is written once');
check(PropertiesFile::parse($merged)['motd'] === "§cRed\nline", 'what is written reads back the same');
check(PropertiesFile::merge("motd=old\\\n  continued\npvp=true\n", ['motd' => 'new']) === "motd=new\npvp=true\n", 'a continued old value is replaced as a whole');
check(PropertiesFile::encode('  lead') === '\\ \\ lead', 'leading spaces are kept');

$state = GameSettings::fromProperties($read);
check($state['motd'] === "§aHello\nworld ❤" && $state['difficulty'] === 'easy' && $state['max_players'] === 20, 'the form is filled from the file');
check($state['view_distance'] === 10 && $state['pvp'] === true && $state['hardcore'] === false, 'missing keys get Minecraft\'s defaults');
check(GameSettings::fromProperties(['difficulty' => '3', 'gamemode' => '1'])['difficulty'] === 'hard', 'numbers from old files are understood');

$values = GameSettings::toProperties([...$state, 'difficulty' => 'hard', 'max_players' => 5000, 'pvp' => false], $read);
check($values['difficulty'] === 'hard' && $values['max-players'] === '1000' && $values['pvp'] === 'false', 'the file gets names, limits and true/false: '.json_encode($values));
check(GameSettings::toProperties([...$state, 'gamemode' => 'creative'], ['gamemode' => '0'])['gamemode'] === '1', 'an old file that used numbers keeps numbers');
check(GameSettings::toProperties([...$state, 'difficulty' => 'chaos'], [])['difficulty'] === 'peaceful', 'an unknown choice is never written');
check(mb_strlen(GameSettings::toProperties([...$state, 'motd' => str_repeat('a', 600)], [])['motd']) === 512, 'the MOTD is cut at 512 characters');

check(GameSettings::eulaAccepted("#By changing...\neula=true\n") && ! GameSettings::eulaAccepted("eula=false\n") && ! GameSettings::eulaAccepted(''), 'eula.txt is read');

if ($failures) {
    echo "FAIL game settings:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS game settings and server.properties\n";
