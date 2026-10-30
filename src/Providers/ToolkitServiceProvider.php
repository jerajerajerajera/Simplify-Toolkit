<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Providers;

use App\Models\Server;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Simplify\Toolkit\Services\EmbedDetails;
use Simplify\Toolkit\Services\RestartReminder;
use Simplify\Toolkit\ToolkitAddon;

final class ToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Only the class name is stored: without Simplify, ToolkitAddon is never loaded. See Simplify's ADDONS.md.
        $this->app->tag([ToolkitAddon::class], 'simplify.addons');
    }

    public function boot(): void
    {
        // Players, version and modpack in Simplify Embed's public server list. A plain event name: without Simplify
        // Embed nobody fires it.
        Event::listen('simplify.embed.server', fn (Server $server, bool $running) => EmbedDetails::for($server, $running));
        // Planned restarts, and reminders of servers that started since; Pelican's own scheduler runs it.
        $this->callAfterResolving(Schedule::class, fn (Schedule $schedule) => $schedule->call(fn () => RestartReminder::tick())
            ->everyMinute()->name('simplify-toolkit-restarts')->withoutOverlapping());
    }
}
