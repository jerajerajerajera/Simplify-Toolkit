<?php

declare(strict_types=1);

namespace Simplify\Mods;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Simplify\Mods\Filament\Admin\Pages\ModsSettings;
use Simplify\Mods\Filament\Server\Pages\Minecraft;
use Simplify\Mods\Filament\Server\Pages\Packages;

final class SimplifyModsPlugin implements Plugin
{
    public const ID = 'simplify-mods';

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void
    {
        if ($panel->getId() === 'server') {
            $panel->pages([Minecraft::class, Packages::class]);
        }
        if ($panel->getId() === 'admin') {
            $panel->pages([ModsSettings::class]);
        }
    }

    public function boot(Panel $panel): void {}
}
