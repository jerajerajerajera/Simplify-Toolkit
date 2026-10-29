<?php

declare(strict_types=1);

namespace Simplify\Toolkit;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Simplify\Toolkit\Filament\Admin\Pages\ToolkitSettings;
use Simplify\Toolkit\Filament\Server\Pages\Minecraft;
use Simplify\Toolkit\Filament\Server\Pages\Packages;

final class SimplifyToolkitPlugin implements Plugin
{
    public const ID = 'simplify-toolkit';

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
            $panel->pages([ToolkitSettings::class]);
        }
    }

    public function boot(Panel $panel): void {}
}
