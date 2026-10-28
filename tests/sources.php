<?php

// Plugin and mod sites without a network: results of every site become one list with one card per plugin, and
// "Install" picks the newest release made for the server's version, or the newest at all marked as not made for it.
declare(strict_types=1);

require __DIR__.'/../src/Services/Web.php';
foreach (['Source', 'Versions', 'Release', 'Modrinth', 'Hangar', 'Spiget', 'CurseForge', 'Catalog'] as $class) {
    require __DIR__."/../src/Services/Sources/$class.php";
}

use Simplify\Mods\Services\Sources\Catalog;
use Simplify\Mods\Services\Sources\CurseForge;
use Simplify\Mods\Services\Sources\Hangar;
use Simplify\Mods\Services\Sources\Modrinth;
use Simplify\Mods\Services\Sources\Spiget;
use Simplify\Mods\Services\Sources\Versions;
use Simplify\Mods\Services\Web;

$failures = [];
function check(bool $ok, string $what): void
{
    global $failures;
    if (! $ok) {
        $failures[] = $what;
    }
}

check(Versions::fits(['1.21.1'], '1.21.4', false) && ! Versions::fits(['1.21.1'], '1.21.4', true) && Versions::fits(['26.1'], '26.1.2', false)
    && ! Versions::fits(['1.20.6'], '1.21.4', false) && ! Versions::fits(['1.21'], '', false), 'version lines');
check(Versions::fromFilename('LuckPerms-Bukkit-5.4.141.jar') === ['LuckPerms Bukkit', '5.4.141'] && Versions::fromFilename('Chunky.jar.disabled') === ['Chunky', ''], 'names from files');
check(Versions::downloadable('https://cdn.modrinth.com/a.jar', 'a.jar') && ! Versions::downloadable('http://cdn.modrinth.com/a.jar', 'a.jar')
    && ! Versions::downloadable('https://evil.test/a.jar', 'a.jar') && ! Versions::downloadable('https://cdn.modrinth.com/a.jar', '../a.jar')
    && ! Versions::downloadable('https://cdn.modrinth.com/a.zip', 'a.zip') && ! Versions::downloadable('https://github.com/a/b/releases/download/1/a.jar', 'a.jar'), 'download hosts and names');
check(Versions::range(['1.21.4', '1.20.6', 'snapshot', '1.21']) === '1.20.6 – 1.21.4', 'version ranges');

$catalog = new Catalog;
$ids = fn (string $kind, string $loader, Catalog $c) => array_map(fn ($s) => $s->id(), $c->sourcesFor($kind, $loader));
check($ids('plugins', 'paper', $catalog) === ['modrinth', 'hangar', 'spigot'], 'Paper searches Modrinth, Hangar and SpigotMC');
check($ids('plugins', 'spigot', $catalog) === ['modrinth', 'spigot'] && $ids('plugins', 'velocity', $catalog) === ['modrinth', 'hangar'], 'Spigot skips Hangar, Velocity skips SpigotMC');
check($ids('mods', 'fabric', $catalog) === ['modrinth'], 'mods search Modrinth');
$withKey = new Catalog('key');
check($ids('mods', 'fabric', $withKey) === ['modrinth', 'curseforge'] && $ids('plugins', 'paper', $withKey) === ['modrinth', 'hangar', 'spigot', 'curseforge'], 'CurseForge only with a key');
check(! in_array('curseforge', $ids('plugins', 'velocity', $withKey), true), 'CurseForge has no proxy plugins');
check(str_contains((new Spiget)->searchUrl('world edit', 'plugins', 'paper'), '/search/resources/world%20edit'), 'SpigotMC search URL');
check(str_contains(urldecode((new Modrinth)->searchUrl('', 'mods', 'fabric')), '"categories:fabric"'), 'Modrinth facets per loader');
check(str_contains((new CurseForge('k'))->searchUrl('x', 'mods', 'fabric'), 'modLoaderType=4') && (new CurseForge('k'))->headers() === ['x-api-key' => 'k'], 'CurseForge mods per loader, with the key');
check(! str_contains((new Hangar)->searchUrl('x', 'plugins', 'paper'), 'sort') && str_contains((new Hangar)->searchUrl('', 'plugins', 'velocity'), 'platform=VELOCITY'), 'Hangar sorts only without a search term');

// Search: the same plugin on several sites is one card from the preferred site that has a release for this version.
$answers = [
    'api.modrinth.com' => ['hits' => [
        ['project_id' => 'AAA', 'title' => 'WorldEdit', 'description' => 'Edit', 'icon_url' => 'https://cdn.modrinth.com/i.png', 'author' => 'EngineHub', 'downloads' => 10, 'slug' => 'worldedit', 'project_type' => 'plugin', 'versions' => ['1.21.4']],
        ['project_id' => 'BBB', 'title' => 'OldThing', 'versions' => ['1.16.5'], 'downloads' => 999999],
        ['project_id' => '../x', 'title' => 'Bad'],
    ]],
    'hangar.papermc.io' => ['result' => [
        ['name' => 'WorldEdit', 'namespace' => ['owner' => 'EngineHub', 'slug' => 'WorldEdit'], 'stats' => ['downloads' => 500], 'supportedPlatforms' => ['PAPER' => ['1.21.4']], 'avatarUrl' => 'javascript:x'],
        ['name' => 'Bad', 'namespace' => ['owner' => 'a/b', 'slug' => 'x']],
    ]],
    'api.spiget.org' => [['id' => 13932, 'name' => 'World Edit', 'tag' => 't', 'downloads' => 9000, 'testedVersions' => ['1.21']], ['id' => 1, 'name' => 'Paid', 'premium' => true]],
];
Web::$transport = fn (string $url) => json_encode($answers[parse_url($url, PHP_URL_HOST)] ?? null);
$found = $catalog->search('worldedit', 'plugins', 'paper', '1.21.4');
$cards = $found['items'];
check(count($cards) === 2 && $found['offline'] === [], 'one card per plugin, premium and invalid ones skipped: '.json_encode(array_column($cards, 'key')));
check($cards[0]['key'] === 'modrinth:AAA' && array_column($cards[0]['also'], 'key') === ['hangar:WorldEdit', 'spigot:13932'] && $cards[0]['downloads'] === 9000, 'Modrinth card with Hangar and SpigotMC under "also"');
check($cards[1]['key'] === 'modrinth:BBB' && $cards[1]['fits'] === false, 'not made for this version comes after');
check((new Hangar)->cards($answers['hangar.papermc.io'], '1.21.4', 'plugins')[0]['icon'] === '', 'icons that are not https are dropped');
Web::$transport = fn (string $url) => str_contains($url, 'spiget') ? null : json_encode($answers[parse_url($url, PHP_URL_HOST)] ?? null);
check($catalog->search('worldedit', 'plugins', 'paper', '1.21.4')['offline'] === ['SpigotMC'], 'a site that does not answer is named');

check(Catalog::parseKey('spigot:123') === ['spigot', '123'] && Catalog::parseKey('spigot:abc') === null && Catalog::parseKey('hangar:../x') === null && Catalog::parseKey('evil:x') === null, 'keys');

// Modrinth: newest release for this version, else the newest release with fits = false.
$modrinth = [
    ['id' => 'v3', 'version_number' => '3.0', 'version_type' => 'release', 'game_versions' => ['1.21.5'], 'files' => [['filename' => 'x-3.0.jar', 'url' => 'https://cdn.modrinth.com/x3.jar', 'primary' => true]]],
    ['id' => 'v2', 'version_number' => '2.0', 'version_type' => 'release', 'game_versions' => ['1.21.4'], 'files' => [['filename' => 'x-2.0-sources.jar', 'url' => 'https://cdn.modrinth.com/s.jar'], ['filename' => 'x-2.0.jar', 'url' => 'https://cdn.modrinth.com/x2.jar', 'primary' => true]], 'dependencies' => [['dependency_type' => 'required', 'project_id' => 'DEP']]],
];
$release = (new Modrinth)->choose('AAA', ['versions' => $modrinth], '1.21.4', 'mods');
check($release['installable'] && $release['version'] === '2.0' && $release['filename'] === 'x-2.0.jar' && $release['fits'] && $release['needs'] === ['DEP'], 'Modrinth: exact version for mods: '.json_encode($release));
check((new Modrinth)->choose('AAA', ['versions' => $modrinth], '1.21.4', 'plugins')['version'] === '3.0', 'plugins accept the same version line');
$release = (new Modrinth)->choose('AAA', ['versions' => $modrinth], '1.20.1', 'plugins');
check($release['version'] === '3.0' && ! $release['fits'] && $release['installable'] && $release['for'] === '1.21.5', 'no release for 1.20.1: the newest, marked as not made for it');
check((new Modrinth)->choose('AAA', ['versions' => null], '1.21.4', 'plugins')['reason'] === 'site_down', 'site down');

// Hangar: the Release channel first; a download that is only a link elsewhere cannot be installed.
$hangar = ['result' => [
    ['name' => '2.1-beta', 'channel' => ['name' => 'Beta'], 'platformDependencies' => ['PAPER' => ['1.21.4']], 'downloads' => ['PAPER' => ['downloadUrl' => 'https://hangarcdn.papermc.io/b.jar', 'fileInfo' => ['name' => 'B.jar']]]],
    ['name' => '2.0', 'channel' => ['name' => 'Release'], 'platformDependencies' => ['PAPER' => ['1.21.1']], 'downloads' => ['PAPER' => ['externalUrl' => 'https://github.com/Owner/Repo/releases/tag/2.0']], 'pluginDependencies' => ['PAPER' => [['name' => 'Vault', 'required' => true], ['name' => 'Opt', 'required' => false]]]],
]];
$release = (new Hangar)->choose('X', ['versions' => $hangar], '1.21.4', 'plugins');
check($release['version'] === '2.0' && ! $release['installable'] && $release['reason'] === 'external_only' && $release['need_names'] === ['Vault'], 'Hangar: Release channel, external download: '.json_encode($release));
$hangar['result'][1]['downloads'] = ['PAPER' => ['downloadUrl' => 'https://hangarcdn.papermc.io/w.jar', 'fileInfo' => ['name' => 'WorldEdit-2.0.jar']]];
check((new Hangar)->choose('X', ['versions' => $hangar], '1.21.4', 'plugins')['installable'], 'Hangar: own download');

// SpigotMC: only the newest version, file name from name and version, external downloads stay on the website.
$release = (new Spiget)->choose('9089', ['resource' => ['name' => 'Essentials X!', 'file' => ['type' => '.jar'], 'version' => ['id' => 5], 'testedVersions' => ['1.20', '1.21']], 'latest' => ['name' => '2.21.0']], '1.21.4', 'plugins');
check($release['installable'] && $release['filename'] === 'Essentials-X-2.21.0.jar' && $release['fits'] && $release['for'] === '1.20 – 1.21', 'SpigotMC: '.json_encode($release));
$release = (new Spiget)->choose('1', ['resource' => ['name' => 'Ext', 'file' => ['type' => 'external', 'externalUrl' => 'https://example.com/dl'], 'testedVersions' => ['1.8']], 'latest' => null], '1.21.4', 'plugins');
check(! $release['installable'] && $release['reason'] === 'external_only' && ! $release['fits'] && $release['page'] === 'https://www.spigotmc.org/resources/1/', 'external download stays a website link');

// CurseForge: release files before betas, newest first; no downloadUrl means downloads only on CurseForge.
$files = ['data' => [
    ['id' => 3, 'fileName' => 'Holo-3.0.jar', 'displayName' => 'Holo 3.0', 'releaseType' => 1, 'fileDate' => '2026-09-01', 'gameVersions' => ['1.22'], 'downloadUrl' => 'https://edge.forgecdn.net/files/3/Holo-3.0.jar'],
    ['id' => 2, 'fileName' => 'Holo-2.0.jar', 'releaseType' => 1, 'fileDate' => '2026-01-01', 'gameVersions' => ['1.21'], 'downloadUrl' => 'https://edge.forgecdn.net/files/2/Holo-2.0.jar'],
    ['id' => 4, 'fileName' => 'Holo-4.0-beta.jar', 'releaseType' => 2, 'fileDate' => '2026-10-01', 'gameVersions' => ['1.21'], 'downloadUrl' => 'https://edge.forgecdn.net/files/4/Holo-4.0-beta.jar'],
]];
$release = (new CurseForge('k'))->choose('93', ['files' => $files, 'project' => ['data' => ['links' => ['websiteUrl' => 'https://www.curseforge.com/minecraft/bukkit-plugins/holo']]]], '1.21.4', 'plugins');
check($release['installable'] && $release['version'] === '2.0' && $release['fits'] && $release['page'] === 'https://www.curseforge.com/minecraft/bukkit-plugins/holo', 'CurseForge: newest release for the version: '.json_encode($release));
$release = (new CurseForge('k'))->choose('93', ['files' => ['data' => [['id' => 5, 'fileName' => 'X-1.jar', 'releaseType' => 1, 'gameVersions' => ['1.21'], 'downloadUrl' => null]]]], '1.21.4', 'plugins');
check(! $release['installable'] && $release['reason'] === 'curseforge_only', 'no downloads by other apps: website only');

// Releases for several keys at once, each with its own answers.
Web::$transport = fn (string $url) => json_encode(match (true) {
    str_contains($url, '/project/AAA/version') => $modrinth,
    str_ends_with($url, '/resources/9089') => ['name' => 'Ess', 'file' => ['type' => '.jar'], 'testedVersions' => ['1.21']],
    str_ends_with($url, '/resources/9089/versions/latest') => ['name' => '2.21'],
    default => null,
});
$releases = $catalog->releases(['modrinth:AAA', 'spigot:9089', 'spigot:x', 'hangar:Gone', 'curseforge:93'], 'plugins', 'paper', '1.21.4');
check(array_keys($releases) === ['modrinth:AAA', 'spigot:9089', 'hangar:Gone'], 'invalid keys and sites without a key are skipped: '.json_encode(array_keys($releases)));
check($releases['spigot:9089']['filename'] === 'Ess-2.21.jar' && $releases['modrinth:AAA']['version'] === '3.0' && $releases['hangar:Gone']['reason'] === 'site_down', 'each key gets its own answers');

if ($failures) {
    echo "FAIL sources:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS plugin and mod sites\n";
