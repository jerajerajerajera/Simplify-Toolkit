<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services\Sources;

/** hangar.papermc.io: plugins for Paper, Velocity and Waterfall. */
final class Hangar implements Source
{
    private const API = 'https://hangar.papermc.io/api/v1/';

    public function id(): string
    {
        return 'hangar';
    }

    public function label(): string
    {
        return 'Hangar';
    }

    public function supports(string $kind, string $loader): bool
    {
        return $kind === 'plugins' && self::platform($loader) !== '';
    }

    public function headers(): array
    {
        return [];
    }

    private static function platform(string $loader): string
    {
        return match ($loader) {
            'paper', 'purpur', 'folia' => 'PAPER',
            'velocity' => 'VELOCITY',
            'waterfall', 'bungeecord' => 'WATERFALL',
            default => '',
        };
    }

    public function searchUrl(string $query, string $kind, string $loader): string
    {
        // Sorting by downloads with a search term puts unrelated popular plugins first, so only without one.
        return self::API.'projects?'.http_build_query(['q' => $query, 'limit' => 15, 'platform' => self::platform($loader), ...($query === '' ? ['sort' => '-downloads'] : [])]);
    }

    public function cards(mixed $answer, string $minecraft, string $kind): array
    {
        $cards = [];
        foreach (is_array($answer) ? ($answer['result'] ?? []) : [] as $project) {
            $owner = (string) ($project['namespace']['owner'] ?? '');
            $slug = (string) ($project['namespace']['slug'] ?? '');
            if (preg_match('/^[\w.-]{1,64}$/', $owner) !== 1 || preg_match('/^[\w.-]{1,64}$/', $slug) !== 1) {
                continue;
            }
            $versions = [];
            foreach ((array) ($project['supportedPlatforms'] ?? []) as $platformVersions) {
                $versions = [...$versions, ...(array) $platformVersions];
            }
            $cards[] = Release::card($this, $slug, $project['name'] ?? $slug, $project['description'] ?? '', $project['avatarUrl'] ?? '', $owner,
                (int) ($project['stats']['downloads'] ?? 0), 'https://hangar.papermc.io/'.rawurlencode($owner).'/'.rawurlencode($slug), Versions::fits($versions, $minecraft, false));
        }

        return $cards;
    }

    public function releaseUrls(string $id, string $kind, string $loader): array
    {
        return ['versions' => self::API.'projects/'.rawurlencode($id).'/versions?'.http_build_query(['limit' => 25, 'platform' => self::platform($loader) ?: 'PAPER'])];
    }

    public function choose(string $id, array $answers, string $minecraft, string $kind): array
    {
        // The project page needs the owner, which the card has; releases link to nothing.
        $page = '';
        $data = $answers['versions'] ?? null;
        if (! is_array($data)) {
            return Release::none($this, 'site_down', $page);
        }
        $versions = array_values(array_filter((array) ($data['result'] ?? []), 'is_array'));
        $platform = '';
        $pick = null;
        foreach ([true, false] as $releaseOnly) {
            foreach ($versions as $version) {
                $platform = (string) array_key_first((array) ($version['downloads'] ?? [])) ?: 'PAPER';
                $isRelease = strcasecmp((string) ($version['channel']['name'] ?? ''), 'Release') === 0;
                if ((! $releaseOnly || $isRelease) && Versions::fits((array) ($version['platformDependencies'][$platform] ?? []), $minecraft, false)) {
                    $pick = $version;
                    break 2;
                }
            }
        }
        $fits = $pick !== null;
        $pick ??= $versions[0] ?? null;
        if ($pick === null) {
            return Release::none($this, 'no_release', $page);
        }
        $platform = (string) array_key_first((array) ($pick['downloads'] ?? [])) ?: 'PAPER';
        $download = (array) ($pick['downloads'][$platform] ?? []);
        $names = [];
        foreach ((array) ($pick['pluginDependencies'][$platform] ?? []) as $dependency) {
            if (is_array($dependency) && ! empty($dependency['required']) && is_string($dependency['name'] ?? null)) {
                $names[] = $dependency['name'];
            }
        }
        $name = (string) ($pick['name'] ?? '');

        return Release::found($this, $name, $name, (string) ($download['fileInfo']['name'] ?? ''), (string) ($download['downloadUrl'] ?? ''),
            (array) ($pick['platformDependencies'][$platform] ?? []), $fits, $page, [], $names);
    }
}
