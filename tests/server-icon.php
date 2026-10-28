<?php

// Server icons without a panel: any picture becomes a 64 × 64 PNG, and modpack icons are found.
declare(strict_types=1);

require __DIR__.'/../src/Services/Web.php';
require __DIR__.'/../src/Services/ServerIcon.php';

use Simplify\Mods\Services\ServerIcon;
use Simplify\Mods\Services\Web;

$failures = [];
function check(bool $ok, string $what): void
{
    global $failures;
    if (! $ok) {
        $failures[] = $what;
    }
}

$wide = imagecreatetruecolor(300, 100);
imagefill($wide, 0, 0, (int) imagecolorallocate($wide, 255, 0, 0));
imagefilledrectangle($wide, 100, 0, 199, 99, (int) imagecolorallocate($wide, 0, 0, 255));
ob_start();
imagejpeg($wide);
$jpeg = (string) ob_get_clean();

$png = ServerIcon::fromImage($jpeg);
$size = $png ? getimagesizefromstring($png) : false;
check($size !== false && $size[0] === 64 && $size[1] === 64 && $size[2] === IMAGETYPE_PNG, 'a JPEG becomes a 64 × 64 PNG');
check(strlen((string) $png) <= 102400, 'the icon stays under Minecraft\'s limit');
$centre = $png ? imagecolorat(imagecreatefromstring($png), 32, 32) & 0xFF : 0;
check($centre > 200, 'the centre square is used (blue in the middle)');
check(ServerIcon::fromImage('not a picture') === null && ServerIcon::fromImage('') === null, 'other files are refused');
check(str_starts_with(ServerIcon::dataUrl((string) $png), 'data:image/png;base64,') && ServerIcon::dataUrl('<svg>') === '', 'only PNG bytes are shown');

$asked = [];
Web::$transport = function (string $url) use (&$asked): ?string {
    $asked[] = $url;

    return match ($url) {
        'https://api.modrinth.com/v2/project/fabulously-optimized' => json_encode(['icon_url' => 'https://cdn.modrinth.com/data/1KVo5zza/icon.png']),
        'https://api.feed-the-beast.com/v1/modpacks/public/modpack/126' => json_encode(['art' => [['type' => 'splash', 'url' => 'https://x/s.png'], ['type' => 'square', 'url' => 'https://apps.modpacks.ch/sq.png']]]),
        default => null,
    };
};
check(ServerIcon::modpackIconUrl('modrinth', ['PROJECT_ID' => 'fabulously-optimized']) === 'https://cdn.modrinth.com/data/1KVo5zza/icon.png', 'the Modrinth icon is found');
check(ServerIcon::modpackIconUrl('ftb', ['FTB_MODPACK_ID' => '126']) === 'https://apps.modpacks.ch/sq.png', 'the square FTB art is used');
$before = count($asked);
check(ServerIcon::modpackIconUrl('modrinth', ['PROJECT_ID' => '../admin']) === null && ServerIcon::modpackIconUrl('ftb', ['FTB_MODPACK_ID' => 'x']) === null, 'strange ids are refused');
check(count($asked) === $before, 'strange ids are never sent');
check(ServerIcon::modpackIconUrl('modrinth', ['PROJECT_ID' => 'gone']) === null, 'a missing project gives no icon');
check(Web::https('http://example.com/a.png') === null && Web::https('https://user:pw@example.com/') === null, 'only plain https links are followed');

if ($failures) {
    echo "FAIL server icon:\n  ".implode("\n  ", $failures)."\n";
    exit(1);
}
echo "PASS server icon\n";
