<?php

declare(strict_types=1);

namespace Simplify\Mods\Services;

/**
 * API keys of outside services. CurseForge's key arrives with the settings page; until then there is none.
 */
final class Keys
{
    public static function curseForge(): string
    {
        return '';
    }
}
