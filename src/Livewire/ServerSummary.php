<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Livewire;

use App\Enums\SubuserPermission;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Simplify\Toolkit\Filament\Server\Pages\Minecraft;
use Simplify\Toolkit\Filament\Server\Pages\Packages;
use Simplify\Toolkit\Models\Package;
use Simplify\Toolkit\Services\Modpack;
use Simplify\Toolkit\Services\ServerKind;
use Simplify\Toolkit\Services\Software;
use Simplify\Toolkit\Services\StatusPing;

/**
 * The Minecraft part of a server on Simplify's server page: players online, software, modpack and plugins.
 * The player count is loaded after the page, so a server that does not answer never slows the page down.
 */
final class ServerSummary extends Component
{
    public Server $server;

    /** @var array<string, mixed>|null null until loaded */
    public ?array $status = null;

    public function loadStatus(): void
    {
        $this->status = StatusPing::get($this->server);
    }

    public function render(): View
    {
        $user = user();
        $kind = ServerKind::forServer($this->server);
        $packages = Package::query()->where('server_id', $this->server->id);

        return view('simplify-toolkit::livewire.server-summary', [
            'software' => Software::label($this->server),
            'modpack' => Modpack::of($this->server),
            'mods' => $kind['kind'] === 'mods',
            'count' => $kind['kind'] !== '' ? (clone $packages)->count() : 0,
            'switchedOff' => (clone $packages)->where('off_reason', '!=', '')->pluck('title')->all(),
            'minecraftUrl' => ! $kind['proxy'] && $user?->can(SubuserPermission::FileReadContent, $this->server)
                ? Minecraft::getUrl(panel: 'server', tenant: $this->server) : null,
            'packagesUrl' => $kind['kind'] !== '' && $user?->can(SubuserPermission::FileRead, $this->server)
                ? Packages::getUrl(panel: 'server', tenant: $this->server) : null,
        ]);
    }
}
