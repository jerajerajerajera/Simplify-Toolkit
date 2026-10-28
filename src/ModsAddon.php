<?php

declare(strict_types=1);

namespace Simplify\Mods;

use Simplify\Core\Addons\Addon;

/**
 * How Simplify Mods shows up inside Simplify, when Simplify is installed. Simplify only loads this class itself.
 */
final class ModsAddon extends Addon
{
    public function id(): string
    {
        return 'simplify-mods';
    }

    public function name(): string
    {
        return 'Simplify Mods';
    }

    public function description(): string
    {
        return __('simplify-mods::mods.addon.description');
    }

    public function icon(): string
    {
        return 'tabler-cube';
    }
}
