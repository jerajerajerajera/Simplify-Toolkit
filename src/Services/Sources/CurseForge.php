<?php

declare(strict_types=1);

namespace Simplify\Mods\Services\Sources;

/**
 * curseforge.com: Bukkit plugins and mods. Its API needs a key, which an admin saves in the settings.
 */
final class CurseForge implements Source
{
    public const API = 'https://api.curseforge.com/v1/';

    /** Minecraft, its classes for Bukkit plugins and for mods, and its numbers for mod loaders. */
    private const GAME = 432;

    private const CLASSES = ['plugins' => 5, 'mods' => 6];

    private const LOADERS = ['forge' => 1, 'fabric' => 4, 'quilt' => 5, 'neoforge' => 6];

    public function __construct(private readonly string $key) {}

    public function id(): string
    {
        return 'curseforge';
    }

    public function label(): string
    {
        return 'CurseForge';
    }

    public function supports(string $kind, string $loader): bool
    {
        return $this->key !== '' && ($kind === 'plugins'
            ? in_array($loader, ['paper', 'purpur', 'folia', 'spigot', 'bukkit'], true)
            : isset(self::LOADERS[$loader]));
    }

    public function headers(): array
    {
        return ['x-api-key' => $this->key];
    }

    public function searchUrl(string $query, string $kind, string $loader): string
    {
        return self::API.'mods/search?'.http_build_query([
            'gameId' => self::GAME,
            'classId' => self::CLASSES[$kind === 'plugins' ? 'plugins' : 'mods'],
            'searchFilter' => $query,
            'sortField' => 2,
            'sortOrder' => 'desc',
            'pageSize' => 15,
            ...($kind === 'plugins' ? [] : ['modLoaderType' => self::LOADERS[$loader] ?? 0]),
        ]);
    }

    public function cards(mixed $answer, string $minecraft, string $kind): array
    {
        $cards = [];
        foreach (is_array($answer) ? ($answer['data'] ?? []) : [] as $project) {
            if (! is_array($project) || empty($project['id'])) {
                continue;
            }
            $versions = array_column(array_filter((array) ($project['latestFilesIndexes'] ?? []), 'is_array'), 'gameVersion');
            $page = (string) ($project['links']['websiteUrl'] ?? '');
            $cards[] = Release::card($this, (string) (int) $project['id'], $project['name'] ?? '', $project['summary'] ?? '', $project['logo']['thumbnailUrl'] ?? '',
                $project['authors'][0]['name'] ?? '', (int) ($project['downloadCount'] ?? 0),
                str_starts_with($page, 'https://www.curseforge.com/') ? $page : 'https://www.curseforge.com/minecraft', Versions::fits($versions, $minecraft, $kind !== 'plugins'));
        }

        return $cards;
    }

    public function releaseUrls(string $id, string $kind, string $loader): array
    {
        return [
            'project' => self::API.'mods/'.$id,
            'files' => self::API.'mods/'.$id.'/files?'.http_build_query(['pageSize' => 30, ...($kind === 'plugins' ? [] : ['modLoaderType' => self::LOADERS[$loader] ?? 0])]),
        ];
    }

    public function choose(string $id, array $answers, string $minecraft, string $kind): array
    {
        $page = (string) ($answers['project']['data']['links']['websiteUrl'] ?? '');
        $data = $answers['files'] ?? null;
        if (! is_array($data)) {
            return Release::none($this, 'site_down', $page);
        }
        // Release files first, newest first.
        $files = array_values(array_filter((array) ($data['data'] ?? []), fn ($file) => is_array($file) && ($file['isAvailable'] ?? true) !== false));
        usort($files, fn (array $a, array $b) => [(int) (($b['releaseType'] ?? 1) === 1), (string) ($b['fileDate'] ?? '')] <=> [(int) (($a['releaseType'] ?? 1) === 1), (string) ($a['fileDate'] ?? '')]);
        $pick = null;
        foreach ($files as $file) {
            if (Versions::fits((array) ($file['gameVersions'] ?? []), $minecraft, $kind !== 'plugins')) {
                $pick = $file;
                break;
            }
        }
        $fits = $pick !== null;
        $pick ??= $files[0] ?? null;
        if ($pick === null) {
            return Release::none($this, 'no_release', $page);
        }
        $filename = (string) ($pick['fileName'] ?? '');
        $version = Versions::fromFilename($filename)[1] ?: (string) ($pick['displayName'] ?? '');

        // No downloadUrl: the author only allows downloads on CurseForge itself.
        return Release::found($this, $version, (string) ($pick['id'] ?? ''), $filename, (string) ($pick['downloadUrl'] ?? ''),
            (array) ($pick['gameVersions'] ?? []), $fits, $page, [], [], 'curseforge_only');
    }
}
