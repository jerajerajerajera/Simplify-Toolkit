<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services\Sources;

/** modrinth.com: plugins and mods for every loader. */
final class Modrinth implements Source
{
    private const API = 'https://api.modrinth.com/v2/';

    public function id(): string
    {
        return 'modrinth';
    }

    public function label(): string
    {
        return 'Modrinth';
    }

    public function supports(string $kind, string $loader): bool
    {
        return $loader !== '';
    }

    public function headers(): array
    {
        return [];
    }

    public function searchUrl(string $query, string $kind, string $loader): string
    {
        $types = $kind === 'plugins' ? ['project_type:plugin', 'project_type:mod'] : ['project_type:mod'];
        $loaders = array_map(fn (string $name) => 'categories:'.$name, Versions::modrinthLoaders($loader));

        return self::API.'search?'.http_build_query([
            'query' => $query,
            'limit' => 15,
            'index' => $query === '' ? 'downloads' : 'relevance',
            'facets' => json_encode([$types, $loaders, ['server_side:required', 'server_side:optional']]),
        ]);
    }

    public function cards(mixed $answer, string $minecraft, string $kind): array
    {
        $cards = [];
        foreach (is_array($answer) ? ($answer['hits'] ?? []) : [] as $hit) {
            if (! is_array($hit) || ! is_string($hit['project_id'] ?? null) || preg_match('/^\w{1,64}$/', $hit['project_id']) !== 1) {
                continue;
            }
            $type = ($hit['project_type'] ?? '') === 'mod' ? 'mod' : 'plugin';
            $cards[] = Release::card($this, $hit['project_id'], $hit['title'] ?? '', $hit['description'] ?? '', $hit['icon_url'] ?? '', $hit['author'] ?? '',
                (int) ($hit['downloads'] ?? 0), 'https://modrinth.com/'.$type.'/'.rawurlencode((string) ($hit['slug'] ?? $hit['project_id'])),
                Versions::fits((array) ($hit['versions'] ?? []), $minecraft, $kind !== 'plugins'));
        }

        return $cards;
    }

    public function releaseUrls(string $id, string $kind, string $loader): array
    {
        return ['versions' => self::API.'project/'.rawurlencode($id).'/version?'.http_build_query(['loaders' => json_encode(Versions::modrinthLoaders($loader))])];
    }

    public function choose(string $id, array $answers, string $minecraft, string $kind): array
    {
        $page = 'https://modrinth.com/project/'.rawurlencode($id);
        $data = $answers['versions'] ?? null;
        if (! is_array($data)) {
            return Release::none($this, 'site_down', $page);
        }
        $all = array_values(array_filter($data, 'is_array'));
        $versions = array_values(array_filter($all, fn (array $version) => ($version['version_type'] ?? '') === 'release')) ?: $all;
        $pick = null;
        foreach ($versions as $version) {
            if (Versions::fits((array) ($version['game_versions'] ?? []), $minecraft, $kind !== 'plugins')) {
                $pick = $version;
                break;
            }
        }
        $fits = $pick !== null;
        $pick ??= $versions[0] ?? null;
        if ($pick === null) {
            return Release::none($this, 'no_release', $page);
        }
        $file = null;
        foreach ((array) ($pick['files'] ?? []) as $candidate) {
            if (is_array($candidate) && str_ends_with(strtolower((string) ($candidate['filename'] ?? '')), '.jar') && ($file === null || ! empty($candidate['primary']))) {
                $file = $candidate;
            }
        }
        $needs = [];
        foreach ((array) ($pick['dependencies'] ?? []) as $dependency) {
            if (is_array($dependency) && ($dependency['dependency_type'] ?? '') === 'required' && is_string($dependency['project_id'] ?? null)) {
                $needs[] = $dependency['project_id'];
            }
        }

        return Release::found($this, (string) ($pick['version_number'] ?? ''), (string) ($pick['id'] ?? ''), (string) ($file['filename'] ?? ''),
            (string) ($file['url'] ?? ''), (array) ($pick['game_versions'] ?? []), $fits, $page, $needs);
    }
}
