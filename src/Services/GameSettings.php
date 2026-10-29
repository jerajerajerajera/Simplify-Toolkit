<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

/**
 * The server.properties settings the Minecraft page shows, converted between the file and the form.
 */
final class GameSettings
{
    public const DIFFICULTIES = ['peaceful', 'easy', 'normal', 'hard'];

    public const GAMEMODES = ['survival', 'creative', 'adventure', 'spectator'];

    /** Numbers: form field => [property, default, minimum, maximum]. */
    public const NUMBERS = [
        'max_players' => ['max-players', 20, 1, 1000],
        'view_distance' => ['view-distance', 10, 2, 32],
        'simulation_distance' => ['simulation-distance', 10, 2, 32],
        'spawn_protection' => ['spawn-protection', 16, 0, 1000],
    ];

    /** Switches: form field => [property, default]. */
    public const SWITCHES = [
        'pvp' => ['pvp', true],
        'hardcore' => ['hardcore', false],
        'allow_flight' => ['allow-flight', false],
        'white_list' => ['white-list', false],
    ];

    public const MOTD_MAX = 512;

    /**
     * Form state from the file's values; Minecraft's own defaults where a key is missing.
     *
     * @param  array<string, string>  $properties
     * @return array<string, mixed>
     */
    public static function fromProperties(array $properties): array
    {
        $state = [
            'motd' => $properties['motd'] ?? 'A Minecraft Server',
            'difficulty' => self::choice($properties['difficulty'] ?? null, self::DIFFICULTIES, 'easy'),
            'gamemode' => self::choice($properties['gamemode'] ?? null, self::GAMEMODES, 'survival'),
        ];
        foreach (self::NUMBERS as $field => [$key, $default]) {
            $number = filter_var($properties[$key] ?? null, FILTER_VALIDATE_INT);
            $state[$field] = $number === false ? $default : $number;
        }
        foreach (self::SWITCHES as $field => [$key, $default]) {
            $state[$field] = isset($properties[$key]) ? strtolower(trim($properties[$key])) === 'true' : $default;
        }

        return $state;
    }

    /**
     * The file's new values from the form. Difficulty and game mode stay numbers when the file used numbers, as
     * Minecraft before 1.13 does.
     *
     * @param  array<string, mixed>  $state
     * @param  array<string, string>  $current
     * @return array<string, string>
     */
    public static function toProperties(array $state, array $current): array
    {
        $values = ['motd' => mb_substr(str_replace(["\r\n", "\r"], "\n", (string) ($state['motd'] ?? '')), 0, self::MOTD_MAX)];
        foreach (['difficulty' => self::DIFFICULTIES, 'gamemode' => self::GAMEMODES] as $key => $allowed) {
            $value = self::choice($state[$key] ?? null, $allowed, $allowed[0]);
            $numeric = preg_match('/^\d$/', trim($current[$key] ?? '')) === 1;
            $values[$key] = $numeric ? (string) array_search($value, $allowed, true) : $value;
        }
        foreach (self::NUMBERS as $field => [$key, $default, $min, $max]) {
            $number = filter_var($state[$field] ?? null, FILTER_VALIDATE_INT);
            $values[$key] = (string) ($number === false ? $default : max($min, min($max, $number)));
        }
        foreach (self::SWITCHES as $field => [$key]) {
            $values[$key] = ! empty($state[$field]) ? 'true' : 'false';
        }

        return $values;
    }

    /**
     * A choice by name, or by its number as in old server.properties files.
     *
     * @param  list<string>  $allowed
     */
    private static function choice(mixed $value, array $allowed, string $default): string
    {
        $value = strtolower(trim((string) $value));
        if (in_array($value, $allowed, true)) {
            return $value;
        }

        return preg_match('/^\d$/', $value) === 1 && isset($allowed[(int) $value]) ? $allowed[(int) $value] : $default;
    }

    /** Whether eula.txt says the EULA is accepted. */
    public static function eulaAccepted(string $eula): bool
    {
        return strtolower(trim(PropertiesFile::parse($eula)['eula'] ?? '')) === 'true';
    }
}
