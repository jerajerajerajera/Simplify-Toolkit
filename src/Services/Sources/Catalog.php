<?php

declare(strict_types=1);

namespace Simplify\Mods\Services\Sources;

use Simplify\Mods\Services\Web;

/**
 * Searches every site at once and works out which file "Install" downloads for a server.
 */
final class Catalog
{
    /** The preferred site for the same plugin: the first one that has a release for this version. */
    private const ORDER = ['modrinth' => 0, 'hangar' => 1, 'spigot' => 2, 'curseforge' => 3];

    /** @var array<string, Source> */
    private array $sources = [];

    public function __construct(string $curseForgeKey = '')
    {
        foreach ([new Modrinth, new Hangar, new Spiget, new CurseForge($curseForgeKey)] as $source) {
            $this->sources[$source->id()] = $source;
        }
    }

    /** @return list<Source> the sites that have plugins or mods for this server */
    public function sourcesFor(string $kind, string $loader): array
    {
        return array_values(array_filter($this->sources, fn (Source $source) => $source->supports($kind, $loader)));
    }

    /**
     * One list of cards: one per plugin, from the preferred site, with the other sites under "also".
     *
     * @return array{items: list<array<string, mixed>>, offline: list<string>}
     */
    public function search(string $query, string $kind, string $loader, string $minecraft): array
    {
        $query = trim(mb_substr($query, 0, 80));
        $requests = [];
        foreach ($this->sourcesFor($kind, $loader) as $source) {
            $requests[$source->id()] = [$source->searchUrl($query, $kind, $loader), $source->headers()];
        }
        $answers = Web::many($requests);
        $cards = [];
        $offline = [];
        foreach ($answers as $id => $answer) {
            if ($answer === null) {
                $offline[] = $this->sources[$id]->label();

                continue;
            }
            $cards = [...$cards, ...$this->sources[$id]->cards($answer, $minecraft, $kind)];
        }

        return ['items' => self::merge($cards, $query), 'offline' => $offline];
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     * @return list<array<string, mixed>>
     */
    public static function merge(array $cards, string $query): array
    {
        // Each site sorts by relevance already: take their results in turns, made-for-this-version and exact names first.
        $wanted = Versions::same($query);
        $rank = [];
        foreach ($cards as $index => $card) {
            $cards[$index]['rank'] = $rank[$card['source']] = ($rank[$card['source']] ?? -1) + 1;
        }
        $order = fn (array $card) => [$card['fits'], $wanted !== '' && Versions::same($card['title']) === $wanted, $wanted === '' ? $card['downloads'] : -$card['rank'], $card['downloads']];
        usort($cards, fn (array $a, array $b) => $order($b) <=> $order($a));

        $groups = [];
        foreach ($cards as $card) {
            if (($same = Versions::same($card['title'])) !== '') {
                $groups[$same][] = $card;
            }
        }
        $merged = [];
        foreach ($groups as $group) {
            usort($group, fn (array $a, array $b) => [$b['fits'], -self::ORDER[$b['source']]] <=> [$a['fits'], -self::ORDER[$a['source']]]);
            $card = array_shift($group);
            foreach ($group as $other) {
                if ($other['source'] === $card['source'] || in_array($other['source'], array_column($card['also'], 'source'), true)) {
                    continue;
                }
                $card['also'][] = ['source' => $other['source'], 'label' => $other['label'], 'key' => $other['key'], 'fits' => $other['fits'], 'page' => $other['page']];
                $card['downloads'] = max($card['downloads'], $other['downloads']);
                $card['icon'] = $card['icon'] ?: $other['icon'];
                $card['description'] = $card['description'] ?: $other['description'];
            }
            unset($card['rank']);
            $merged[] = $card;
        }

        return array_slice($merged, 0, 24);
    }

    /**
     * "modrinth:AANobbMI" -> ['modrinth', 'AANobbMI'], or null when the key is not one of ours.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function parseKey(string $key): ?array
    {
        if (preg_match('/^(modrinth|hangar|spigot|curseforge):([\w.-]{1,128})$/', $key, $match) !== 1) {
            return null;
        }
        if (in_array($match[1], ['spigot', 'curseforge'], true) && ! ctype_digit($match[2])) {
            return null;
        }

        return [$match[1], $match[2]];
    }

    /**
     * The release "Install" downloads for each key, looked up for all keys at once.
     *
     * @param  list<string>  $keys
     * @return array<string, array<string, mixed>>
     */
    public function releases(array $keys, string $kind, string $loader, string $minecraft): array
    {
        $parsed = [];
        $requests = [];
        foreach (array_slice(array_unique($keys), 0, 24) as $key) {
            $parts = is_string($key) ? self::parseKey($key) : null;
            $source = $parts ? $this->sources[$parts[0]] : null;
            if ($source === null || ! $source->supports($kind, $loader)) {
                continue;
            }
            $parsed[$key] = $parts;
            foreach ($source->releaseUrls($parts[1], $kind, $loader) as $name => $url) {
                $requests[$key.'#'.$name] = [$url, $source->headers()];
            }
        }
        $answers = Web::many($requests);
        $out = [];
        foreach ($parsed as $key => [$sourceId, $id]) {
            $mine = [];
            foreach ($answers as $request => $answer) {
                if (str_starts_with($request, $key.'#')) {
                    $mine[substr($request, strlen($key) + 1)] = $answer;
                }
            }
            $out[$key] = $this->sources[$sourceId]->choose($id, $mine, $minecraft, $kind);
        }

        return $out;
    }
}
