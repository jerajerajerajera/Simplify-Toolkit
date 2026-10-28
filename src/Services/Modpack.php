<?php

declare(strict_types=1);

namespace Simplify\Mods\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Cache;

/**
 * The modpack a server runs, from the Modrinth or FTB egg's variables and the modpack's site. Cached for six hours.
 */
final class Modpack
{
    /** @return array{name: string, version: string, url: string, icon: string}|null */
    public static function of(Server $server): ?array
    {
        $modpack = ServerKind::forServer($server)['modpack'];
        if (! in_array($modpack, ['modrinth', 'ftb'], true)) {
            return null;
        }
        $variables = [];
        foreach ($server->variables as $variable) {
            $variables[$variable->env_variable] = (string) ($variable->server_value ?? $variable->default_value);
        }
        $pack = Cache::remember('simplify-mods.modpack-info.'.$server->uuid.'.'.md5(json_encode($variables) ?: ''), 21600,
            fn () => $modpack === 'modrinth' ? self::modrinth($variables) : self::ftb($variables));

        return $pack['name'] === '' ? null : $pack;
    }

    /**
     * @param  array<string, string>  $variables
     * @return array{name: string, version: string, url: string, icon: string}
     */
    private static function modrinth(array $variables): array
    {
        $id = $variables['PROJECT_ID'] ?? '';
        if (preg_match('/^[\w-]{1,64}$/', $id) !== 1) {
            return self::none();
        }
        $project = Web::json('https://api.modrinth.com/v2/project/'.rawurlencode($id)) ?? [];
        $version = preg_match('/^\w{1,64}$/', $variables['VERSION_ID'] ?? '') === 1
            ? (Web::json('https://api.modrinth.com/v2/version/'.$variables['VERSION_ID']) ?? []) : [];
        $slug = (string) ($project['slug'] ?? '');

        return [
            'name' => mb_substr((string) ($project['title'] ?? ''), 0, 80),
            'version' => mb_substr((string) ($version['version_number'] ?? ''), 0, 40),
            'url' => preg_match('/^[\w-]{1,64}$/', $slug) === 1 ? 'https://modrinth.com/modpack/'.$slug : '',
            'icon' => Web::https($project['icon_url'] ?? null) ?? '',
        ];
    }

    /**
     * @param  array<string, string>  $variables
     * @return array{name: string, version: string, url: string, icon: string}
     */
    private static function ftb(array $variables): array
    {
        $id = $variables['FTB_MODPACK_ID'] ?? '';
        if (preg_match('/^\d{1,10}$/', $id) !== 1) {
            return self::none();
        }
        $pack = Web::json('https://api.feed-the-beast.com/v1/modpacks/public/modpack/'.$id) ?? [];
        $icon = '';
        foreach ((array) ($pack['art'] ?? []) as $art) {
            if (is_array($art) && ($art['type'] ?? '') === 'square' && ($url = Web::https($art['url'] ?? null))) {
                $icon = $url;
                break;
            }
        }
        $versionId = $variables['FTB_MODPACK_VERSION_ID'] ?? '';
        $version = '';
        foreach ((array) ($pack['versions'] ?? []) as $candidate) {
            if (is_array($candidate) && (string) ($candidate['id'] ?? '') === $versionId) {
                $version = (string) ($candidate['name'] ?? '');
            }
        }
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', (string) ($pack['name'] ?? '')), '-'));

        return [
            'name' => mb_substr((string) ($pack['name'] ?? ''), 0, 80),
            'version' => mb_substr($version, 0, 40),
            'url' => $slug !== '' ? 'https://www.feed-the-beast.com/modpacks/'.$id.'-'.$slug : '',
            'icon' => $icon,
        ];
    }

    /** @return array{name: string, version: string, url: string, icon: string} */
    private static function none(): array
    {
        return ['name' => '', 'version' => '', 'url' => '', 'icon' => ''];
    }
}
