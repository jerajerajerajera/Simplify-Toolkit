<?php

declare(strict_types=1);

namespace Simplify\Mods\Services\Sources;

/**
 * Minecraft versions and file names, shared by every site.
 */
final class Versions
{
    /** Hosts Wings may download from: the sites' own file servers. */
    public const HOSTS = ['cdn.modrinth.com', 'hangarcdn.papermc.io', 'api.spiget.org', 'edge.forgecdn.net', 'mediafilez.forgecdn.net'];

    /** "1.21.4" -> "1.21", "26.1.2" -> "26.1": plugins usually work across a version line, mods do not. */
    public static function line(string $version): string
    {
        return preg_match('/^(\d+\.\d+)/', $version, $match) ? $match[1] : $version;
    }

    /**
     * Whether a release made for $versions runs on $minecraft. Mods need the exact version, plugins the same line.
     *
     * @param  array<mixed>  $versions
     */
    public static function fits(array $versions, string $minecraft, bool $exact): bool
    {
        if ($minecraft === '') {
            return false;
        }
        if (in_array($minecraft, $versions, true)) {
            return true;
        }
        if ($exact) {
            return false;
        }
        foreach ($versions as $version) {
            if (is_string($version) && self::line($version) === self::line($minecraft)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ["1.20.6", "1.21", "1.21.4"] -> "1.20.6 – 1.21.4".
     *
     * @param  array<mixed>  $versions
     */
    public static function range(array $versions): string
    {
        $versions = array_values(array_filter($versions, fn ($version) => is_string($version) && preg_match('/^\d+\.\d+(\.\d+)?$/', $version) === 1));
        if (! $versions) {
            return '';
        }
        usort($versions, 'version_compare');
        $first = $versions[0];
        $last = end($versions);

        return $first === $last ? $first : $first.' – '.$last;
    }

    /** Whether Wings may download this file: a .jar with a plain name, over https, from a site's own file server. */
    public static function downloadable(string $url, string $filename): bool
    {
        return preg_match('/^[a-zA-Z0-9._+ -]{1,120}\.jar$/', $filename) === 1
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && in_array(parse_url($url, PHP_URL_HOST), self::HOSTS, true);
    }

    /**
     * "LuckPerms-Bukkit-5.4.141.jar" -> ['LuckPerms Bukkit', '5.4.141'], for files added by hand.
     *
     * @return array{0: string, 1: string}
     */
    public static function fromFilename(string $file): array
    {
        $base = preg_replace('/\.jar(\.disabled)?$/i', '', $file) ?? $file;
        if (preg_match('/^(.+?)[-_ ]v?(\d+(?:\.\d+)+[\w.+-]*)$/', $base, $match)) {
            return [trim(str_replace(['-', '_'], ' ', $match[1])), $match[2]];
        }

        return [trim(str_replace(['-', '_'], ' ', $base)), ''];
    }

    /** A plugin's name reduced to letters and digits, to find the same plugin on several sites. */
    public static function same(string $title): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($title)) ?? '';
    }

    public static function slug(string $text): string
    {
        return trim(preg_replace('/[^a-zA-Z0-9._+-]+/', '-', $text) ?? '', '-.') ?: 'plugin';
    }

    /** The loaders Modrinth lists a server's plugins or mods under. */
    public static function modrinthLoaders(string $loader): array
    {
        return match ($loader) {
            'paper', 'purpur', 'folia' => ['paper', 'spigot', 'bukkit', ...($loader === 'paper' ? [] : [$loader])],
            'spigot' => ['spigot', 'bukkit'],
            default => [$loader],
        };
    }
}
