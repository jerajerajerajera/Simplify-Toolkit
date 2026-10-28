<?php

declare(strict_types=1);

namespace Simplify\Mods\Providers;

use Illuminate\Support\ServiceProvider;
use Simplify\Mods\ModsAddon;

final class ModsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Only the class name is stored: without Simplify, ModsAddon is never loaded. See Simplify's ADDONS.md.
        $this->app->tag([ModsAddon::class], 'simplify.addons');
    }
}
