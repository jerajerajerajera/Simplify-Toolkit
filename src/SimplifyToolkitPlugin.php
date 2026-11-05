<?php

declare(strict_types=1);

namespace Simplify\Toolkit;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Simplify\Toolkit\Filament\Admin\Pages\ToolkitSettings;
use Simplify\Toolkit\Filament\Server\Pages\Backups;
use Simplify\Toolkit\Filament\Server\Pages\Minecraft;
use Simplify\Toolkit\Filament\Server\Pages\Packages;
use Simplify\Toolkit\Filament\Server\Pages\Players;
use Simplify\Toolkit\Filament\Server\Pages\ServerSettings;
use Simplify\Toolkit\Http\Middleware\RedirectReplacedPages;

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
            $panel->pages([ServerSettings::class, Minecraft::class, Players::class, Packages::class, Backups::class]);
            // Pelican's Settings and Startup, and the former Minecraft page, lead to the Settings page (G2).
            $panel->tenantMiddleware([RedirectReplacedPages::class]);
        }
        if ($panel->getId() === 'admin') {
            $panel->pages([ToolkitSettings::class]);
        }
    }

    public function boot(Panel $panel): void {}
}
