<?php

declare(strict_types=1);

namespace Simplify\Mods;

use Filament\Contracts\Plugin;
use Filament\Panel;

final class SimplifyModsPlugin implements Plugin
{
    public const ID = 'simplify-mods';

    public function getId(): string
    {
        return self::ID;
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
