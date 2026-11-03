<?php

// Modpacks without a panel: name, version, icon and where players get the client of the installed version.
declare(strict_types=1);

require __DIR__.'/../src/Services/Web.php';
require __DIR__.'/../src/Services/Modpack.php';

use Simplify\Toolkit\Services\Modpack;
use Simplify\Toolkit\Services\Web;

$failures = [];
function check(bool $ok, string $what): void
{
    global $failures;
    if (! $ok) {
        $failures[] = $what;
    }
}

$keys = [];
Web::$transport = function (string $url, array $headers) use (&$keys): ?string {
    $keys[] = $headers['x-api-key'] ?? null;

    return match ($url) {
        'https://api.modrinth.com/v2/project/fabulously-optimized' => json_encode(['title' => 'Fabulously Optimized', 'slug' => 'fabulously-optimized', 'icon_url' => 'https://cdn.modrinth.com/fo.png']),
        'https://api.modrinth.com/v2/version/AbC123' => json_encode(['id' => 'AbC123', 'version_number' => '6.4.0']),
        'https://api.feed-the-beast.com/v1/modpacks/public/modpack/126' => json_encode(['name' => 'FTB Skies', 'versions' => [['id' => 9, 'name' => '1.2.0']]]),
        'https://api.curseforge.com/v1/mods/925200' => json_encode(['data' => ['name' => 'All the Mods 10', 'slug' => 'all-the-mods-10', 'logo' => ['thumbnailUrl' => 'https://media.forgecdn.net/atm.png']]]),
        'https://api.curseforge.com/v1/mods/925200/files/6113000' => json_encode(['data' => ['displayName' => 'ATM10-2.40']]),
        default => null,
    };
};

$pack = Modpack::fromVariables('modrinth', ['PROJECT_ID' => 'fabulously-optimized', 'VERSION_ID' => 'AbC123']);
check($pack['name'] === 'Fabulously Optimized' && $pack['version'] === '6.4.0', 'Modrinth: name and version');
check($pack['download'] === 'https://modrinth.com/modpack/fabulously-optimized/version/AbC123', 'Modrinth: the page of the installed version');
$latest = Modpack::fromVariables('modrinth', ['PROJECT_ID' => 'fabulously-optimized', 'VERSION_ID' => '']);
check($latest['download'] === 'https://modrinth.com/modpack/fabulously-optimized', 'Modrinth without a version: the modpack\'s page');

$ftb = Modpack::fromVariables('ftb', ['FTB_MODPACK_ID' => '126', 'FTB_MODPACK_VERSION_ID' => '9']);
check($ftb['version'] === '1.2.0' && $ftb['download'] === 'https://www.feed-the-beast.com/modpacks/126-ftb-skies', 'FTB: the modpack\'s page');

$cf = Modpack::fromVariables('curseforge', ['PROJECT_ID' => '925200', 'VERSION_ID' => '6113000'], 'panel-key');
check($cf['name'] === 'All the Mods 10' && $cf['version'] === 'ATM10-2.40' && $cf['icon'] === 'https://media.forgecdn.net/atm.png', 'CurseForge: name, version and icon');
check($cf['download'] === 'https://www.curseforge.com/minecraft/modpacks/all-the-mods-10/files/6113000', 'CurseForge: the page of the installed file');
check(array_values(array_unique($keys)) === [null, 'panel-key'], 'only CurseForge gets the key');
$keys = [];
check(Modpack::fromVariables('curseforge', ['PROJECT_ID' => '925200', 'VERSION_ID' => '6113000'])['name'] === '' && $keys === [], 'CurseForge without a key asks nothing');
check(Modpack::fromVariables('curseforge', ['PROJECT_ID' => '../x'], 'panel-key')['download'] === '' && Modpack::fromVariables('modrinth', ['PROJECT_ID' => 'gone'])['download'] === '', 'strange ids and missing projects give no link');

if ($failures) {
    echo "FAIL modpacks:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS modpacks: the client download of Modrinth, FTB and CurseForge packs\n";
