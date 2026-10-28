<?php

declare(strict_types=1);

namespace Simplify\Mods\Services\Sources;

/**
 * A site with plugins or mods. Sites only build URLs and read answers; Catalog does the requests, so every site
 * can be tested without a network.
 *
 * A card is ['key', 'source', 'label', 'title', 'description', 'icon', 'author', 'downloads', 'page', 'fits', 'also'].
 * A release is ['installable', 'source', 'label', 'version', 'version_id', 'filename', 'url', 'fits', 'for', 'needs',
 * 'need_names', 'page', 'reason'], where reason is a key in lang/<locale>/sources.php ('' when installable).
 */
interface Source
{
    public function id(): string;

    public function label(): string;

    /** Whether the site has anything for this kind ("plugins" or "mods") and loader. */
    public function supports(string $kind, string $loader): bool;

    /** @return array<string, string> request headers, such as an API key */
    public function headers(): array;

    public function searchUrl(string $query, string $kind, string $loader): string;

    /** @return list<array<string, mixed>> */
    public function cards(mixed $answer, string $minecraft, string $kind): array;

    /**
     * The requests needed to choose a release, by name.
     *
     * @return array<string, string>
     */
    public function releaseUrls(string $id, string $kind, string $loader): array;

    /**
     * The newest release made for this Minecraft version, or else the newest release with fits = false.
     *
     * @param  array<string, mixed>  $answers  the answers to releaseUrls(), by name; null where a request failed
     * @return array<string, mixed>
     */
    public function choose(string $id, array $answers, string $minecraft, string $kind): array;
}
