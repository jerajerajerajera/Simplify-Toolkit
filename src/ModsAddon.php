<?php

declare(strict_types=1);

namespace Simplify\Mods;

use App\Models\Server;
use Simplify\Core\Addons\Addon;
use Simplify\Mods\Filament\Admin\Pages\ModsSettings;
use Simplify\Mods\Livewire\ServerSummary;
use Simplify\Mods\Services\ServerInfo;
use Simplify\Mods\Services\ServerKind;
use Simplify\Mods\Services\Software;

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
        return 'Mods';
    }

    public function description(): string
    {
        return __('simplify-mods::mods.addon.description');
    }

    public function icon(): string
    {
        return 'tabler-cube';
    }

    public function settingsUrl(): ?string
    {
        return ModsSettings::canAccess() ? ModsSettings::getUrl(panel: 'admin') : null;
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
