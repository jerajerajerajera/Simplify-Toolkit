<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use GdImage;

/**
 * Minecraft's server icon: server-icon.png, a PNG of exactly 64 × 64 pixels. Any picture is made into one.
 */
final class ServerIcon
{
    public const FILE = '/server-icon.png';

    public const SIZE = 64;

    /** The centre square of the picture, scaled to 64 × 64, as PNG bytes; null when it is no picture GD can read. */
    public static function fromImage(string $bytes): ?string
    {
        if ($bytes === '' || strlen($bytes) > 8 * 1024 * 1024 || ! @getimagesizefromstring($bytes)) {
            return null;
        }
        $source = @imagecreatefromstring($bytes);
        if (! $source instanceof GdImage) {
            return null;
        }
        [$width, $height] = [imagesx($source), imagesy($source)];
        $side = min($width, $height);
        $icon = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagealphablending($icon, false);
        imagesavealpha($icon, true);
        imagefill($icon, 0, 0, (int) imagecolorallocatealpha($icon, 0, 0, 0, 127));
        imagecopyresampled($icon, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), self::SIZE, self::SIZE, $side, $side);
        ob_start();
        imagepng($icon, null, 9);
        $png = (string) ob_get_clean();

        return $png !== '' ? $png : null;
    }

    /** An image source for the browser, or '' without an icon. */
    public static function dataUrl(string $png): string
    {
        return $png !== '' && str_starts_with($png, "\x89PNG") ? 'data:image/png;base64,'.base64_encode($png) : '';
    }

    /**
     * Where the modpack's own icon is, from the server's modpack egg variables.
     *
     * @param  array<string, string>  $variables
     */
    public static function modpackIconUrl(string $modpack, array $variables): ?string
    {
        if ($modpack === 'modrinth') {
            $id = $variables['PROJECT_ID'] ?? '';

            return preg_match('/^[\w-]{1,64}$/', $id) === 1
                ? Web::https(Web::json('https://api.modrinth.com/v2/project/'.rawurlencode($id))['icon_url'] ?? null)
                : null;
        }
        if ($modpack === 'ftb') {
            $id = $variables['FTB_MODPACK_ID'] ?? '';
            if (preg_match('/^\d{1,10}$/', $id) !== 1) {
                return null;
            }
            foreach (Web::json('https://api.feed-the-beast.com/v1/modpacks/public/modpack/'.$id)['art'] ?? [] as $art) {
                if (($art['type'] ?? '') === 'square' && ($url = Web::https($art['url'] ?? null))) {
                    return $url;
                }
            }
        }

        return null;
    }
}
