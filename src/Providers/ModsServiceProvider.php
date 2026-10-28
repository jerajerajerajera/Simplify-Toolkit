<?php

declare(strict_types=1);

namespace Simplify\Mods\Providers;

use App\Models\Server;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Simplify\Mods\ModsAddon;
use Simplify\Mods\Services\EmbedDetails;

final class ModsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Only the class name is stored: without Simplify, ModsAddon is never loaded. See Simplify's ADDONS.md.
        $this->app->tag([ModsAddon::class], 'simplify.addons');
    }

    public function boot(): void
    {
        // Players, version and modpack in Simplify Embed's public server list. A plain event name: without Simplify
        // Embed nobody fires it.
        Event::listen('simplify.embed.server', fn (Server $server, bool $running) => EmbedDetails::for($server, $running));
    }
}
