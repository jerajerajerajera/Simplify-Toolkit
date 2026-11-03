<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Cache;

/**
 * The modpack a server runs, from the Modrinth, CurseForge or FTB egg's variables and the modpack's site. Cached for
 * six hours. "download" is where players get the client of the installed version (Simplify Embed's server list).
 *
 * @phpstan-type Pack array{name: string, version: string, url: string, icon: string, download: string}
 */
final class Modpack
{
    /** @return Pack|null */
    public static function of(Server $server): ?array
    {
        $modpack = ServerKind::forServer($server)['modpack'];
        if ($modpack === '') {
            return null;
        }
        $variables = [];
        foreach ($server->variables as $variable) {
            $variables[$variable->env_variable] = (string) ($variable->server_value ?? $variable->default_value);
        }
        // CurseForge only answers with a key; the panel's own, never a user's, since the server list is public.
        $key = $modpack === 'curseforge' ? (Keys::get(Keys::CURSEFORGE, null) ?? '') : '';
        $pack = Cache::remember('simplify-toolkit.modpack-info.'.$server->uuid.'.'.md5((json_encode($variables) ?: '').$modpack.$key), 21600,
            fn () => self::fromVariables($modpack, $variables, $key));

        return $pack['name'] === '' ? null : $pack;
    }

    /**
     * @param  array<string, string>  $variables  the egg's variables
     * @return Pack
     */
    public static function fromVariables(string $modpack, array $variables, string $key = ''): array
    {
        return match ($modpack) {
            'modrinth' => self::modrinth($variables),
            'ftb' => self::ftb($variables),
            'curseforge' => $key !== '' ? self::curseForge($variables, $key) : self::none(),
            default => self::none(),
        };
    }

    /**
     * @param  array<string, string>  $variables
     * @return Pack
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
        $url = preg_match('/^[\w-]{1,64}$/', $slug) === 1 ? 'https://modrinth.com/modpack/'.$slug : '';
        $versionId = (string) ($version['id'] ?? '');

        return [
            'name' => mb_substr((string) ($project['title'] ?? ''), 0, 80),
            'version' => mb_substr((string) ($version['version_number'] ?? ''), 0, 40),
            'url' => $url,
            'icon' => Web::https($project['icon_url'] ?? null) ?? '',
            'download' => $url !== '' && preg_match('/^\w{1,64}$/', $versionId) === 1 ? $url.'/version/'.$versionId : $url,
        ];
    }

    /**
     * @param  array<string, string>  $variables
     * @return Pack
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
        $url = $slug !== '' ? 'https://www.feed-the-beast.com/modpacks/'.$id.'-'.$slug : '';

        // FTB has no page per version: players install the client with the FTB app from the modpack's page.
        return [
            'name' => mb_substr((string) ($pack['name'] ?? ''), 0, 80),
            'version' => mb_substr($version, 0, 40),
            'url' => $url,
            'icon' => $icon,
            'download' => $url,
        ];
    }

    /**
     * PROJECT_ID is the modpack's id, VERSION_ID the id of the installed file (the CurseForge egg).
     *
     * @param  array<string, string>  $variables
     * @return Pack
     */
    private static function curseForge(array $variables, string $key): array
    {
        $id = $variables['PROJECT_ID'] ?? '';
        if (preg_match('/^\d{1,10}$/', $id) !== 1) {
            return self::none();
        }
        $headers = ['x-api-key' => $key];
        $project = (array) (Web::json('https://api.curseforge.com/v1/mods/'.$id, $headers)['data'] ?? []);
        $fileId = $variables['VERSION_ID'] ?? '';
        $file = preg_match('/^\d{1,12}$/', $fileId) === 1
            ? (array) (Web::json('https://api.curseforge.com/v1/mods/'.$id.'/files/'.$fileId, $headers)['data'] ?? []) : [];
        $slug = (string) ($project['slug'] ?? '');
        $url = preg_match('/^[\w-]{1,100}$/', $slug) === 1 ? 'https://www.curseforge.com/minecraft/modpacks/'.$slug : '';

        return [
            'name' => mb_substr((string) ($project['name'] ?? ''), 0, 80),
            'version' => mb_substr((string) ($file['displayName'] ?? ''), 0, 40),
            'url' => $url,
            'icon' => Web::https($project['logo']['thumbnailUrl'] ?? null) ?? '',
            'download' => $url !== '' && $file !== [] ? $url.'/files/'.$fileId : $url,
        ];
    }

    /** @return Pack */
    private static function none(): array
    {
        return ['name' => '', 'version' => '', 'url' => '', 'icon' => '', 'download' => ''];
    }
}
