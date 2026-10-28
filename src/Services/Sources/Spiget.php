<?php

declare(strict_types=1);

namespace Simplify\Mods\Services\Sources;

/** spigotmc.org through the Spiget API: free plugins for Spigot and Paper. */
final class Spiget implements Source
{
    private const API = 'https://api.spiget.org/v2/';

    public function id(): string
    {
        return 'spigot';
    }

    public function label(): string
    {
        return 'SpigotMC';
    }

    public function supports(string $kind, string $loader): bool
    {
        return $kind === 'plugins' && in_array($loader, ['paper', 'purpur', 'folia', 'spigot', 'bukkit'], true);
    }

    public function headers(): array
    {
        return [];
    }

    public function searchUrl(string $query, string $kind, string $loader): string
    {
        return $query === ''
            ? self::API.'resources/free?'.http_build_query(['size' => 15, 'sort' => '-downloads'])
            : self::API.'search/resources/'.rawurlencode($query).'?'.http_build_query(['field' => 'name', 'size' => 15, 'sort' => '-downloads']);
    }

    public function cards(mixed $answer, string $minecraft, string $kind): array
    {
        $cards = [];
        foreach (is_array($answer) ? $answer : [] as $resource) {
            if (! is_array($resource) || ! empty($resource['premium']) || empty($resource['id'])) {
                continue;
            }
            $icon = (string) ($resource['icon']['url'] ?? '');
            $id = (int) $resource['id'];
            $cards[] = Release::card($this, (string) $id, $resource['name'] ?? '', $resource['tag'] ?? '', $icon !== '' ? 'https://www.spigotmc.org/'.ltrim($icon, '/') : '', '',
                (int) ($resource['downloads'] ?? 0), 'https://www.spigotmc.org/resources/'.$id.'/', Versions::fits((array) ($resource['testedVersions'] ?? []), $minecraft, false));
        }

        return $cards;
    }

    public function releaseUrls(string $id, string $kind, string $loader): array
    {
        return ['resource' => self::API.'resources/'.$id, 'latest' => self::API.'resources/'.$id.'/versions/latest'];
    }

    public function choose(string $id, array $answers, string $minecraft, string $kind): array
    {
        $page = 'https://www.spigotmc.org/resources/'.$id.'/';
        $data = $answers['resource'] ?? null;
        if (! is_array($data)) {
            return Release::none($this, 'site_down', $page);
        }
        // Spiget only offers the newest version, and lists the versions it was tested on.
        $tested = (array) ($data['testedVersions'] ?? []);
        $latest = $answers['latest'] ?? null;
        $name = is_array($latest) ? (string) ($latest['name'] ?? '') : '';
        $isJar = strtolower((string) ($data['file']['type'] ?? '')) === '.jar';
        $filename = Versions::slug((string) ($data['name'] ?? 'plugin')).($name !== '' ? '-'.Versions::slug($name) : '').'.jar';

        return Release::found($this, $name, (string) ($data['version']['id'] ?? ''), $isJar ? $filename : '',
            $isJar ? self::API.'resources/'.$id.'/download' : '', $tested, Versions::fits($tested, $minecraft, false), $page);
    }
}
