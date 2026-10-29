<?php

declare(strict_types=1);

namespace Simplify\Toolkit;

use App\Models\Server;
use Simplify\Core\Addons\Addon;
use Simplify\Toolkit\Filament\Admin\Pages\ToolkitSettings;
use Simplify\Toolkit\Livewire\ServerSummary;
use Simplify\Toolkit\Services\ServerInfo;
use Simplify\Toolkit\Services\ServerKind;
use Simplify\Toolkit\Services\Software;

/**
 * How Simplify Toolkit shows up inside Simplify, when Simplify is installed. Simplify only loads this class itself.
 */
final class ToolkitAddon extends Addon
{
    public function id(): string
    {
        return 'simplify-toolkit';
    }

    public function name(): string
    {
        return 'Toolkit';
    }

    public function description(): string
    {
        return __('simplify-toolkit::toolkit.addon.description');
    }

    public function icon(): string
    {
        return 'tabler-cube';
    }

    public function settingsUrl(): ?string
    {
        return ToolkitSettings::canAccess() ? ToolkitSettings::getUrl(panel: 'admin') : null;
    }

    /** "Paper 1.21.4" on Minecraft tiles, from the database only. */
    public function tileBadges(Server $server): array
    {
        $label = Software::label($server);

        return $label !== '' ? [$label] : [];
    }

    /** Players, MOTD, version and icon of a running Minecraft server, for Simplify 1.1 and later (API 2). */
    public function serverInfo(Server $server): array
    {
        return ServerInfo::for($server);
    }

    public function serverSections(Server $server): array
    {
        return ServerKind::forServer($server)['minecraft'] ? [ServerSummary::class] : [];
    }
}
