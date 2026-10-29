<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services\Sources;

/**
 * Builds cards and releases in the shape Source describes.
 */
final class Release
{
    /** @return array<string, mixed> */
    public static function card(Source $source, string $id, mixed $title, mixed $description, mixed $icon, mixed $author, int $downloads, string $page, bool $fits): array
    {
        $icon = (string) $icon;

        return [
            'key' => $source->id().':'.$id,
            'source' => $source->id(),
            'label' => $source->label(),
            'title' => mb_substr(trim((string) $title), 0, 80),
            'description' => mb_substr(trim((string) $description), 0, 240),
            'icon' => str_starts_with($icon, 'https://') ? $icon : '',
            'author' => mb_substr(trim((string) $author), 0, 60),
            'downloads' => $downloads,
            'page' => str_starts_with($page, 'https://') ? $page : '',
            'fits' => $fits,
            'also' => [],
        ];
    }

    /**
     * @param  array<mixed>  $versions  the Minecraft versions the release is made for
     * @param  list<string>  $needs  Modrinth project ids it requires
     * @param  list<string>  $needNames  names of plugins it requires
     * @return array<string, mixed>
     */
    public static function found(Source $source, string $version, string $versionId, string $filename, string $url, array $versions, bool $fits, string $page, array $needs = [], array $needNames = [], string $unavailable = 'external_only'): array
    {
        $installable = Versions::downloadable($url, $filename);

        return [
            'installable' => $installable,
            'source' => $source->id(),
            'label' => $source->label(),
            'version' => mb_substr($version, 0, 60),
            'version_id' => mb_substr($versionId, 0, 128),
            'filename' => $filename,
            'url' => $installable ? $url : '',
            'fits' => $fits,
            'for' => Versions::range($versions),
            'needs' => $needs,
            'need_names' => $needNames,
            'page' => str_starts_with($page, 'https://') ? $page : '',
            'reason' => $installable ? '' : $unavailable,
        ];
    }

    /** @return array<string, mixed> */
    public static function none(Source $source, string $reason, string $page = ''): array
    {
        return [
            'installable' => false,
            'source' => $source->id(),
            'label' => $source->label(),
            'version' => '',
            'version_id' => '',
            'filename' => '',
            'url' => '',
            'fits' => false,
            'for' => '',
            'needs' => [],
            'need_names' => [],
            'page' => $page,
            'reason' => $reason,
        ];
    }
}
