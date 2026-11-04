<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Filament\Server\Pages;

use Filament\Pages\Page;

/**
 * The former Minecraft page. Its settings are a section of the Settings page now; this route only stays so old links
 * and bookmarks land there (RedirectReplacedPages).
 */
final class Minecraft extends Page
{
    protected static ?string $slug = 'minecraft';

    protected string $view = 'simplify-toolkit::pages.redirect';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return ServerSettings::canAccess();
    }

    public function mount(): void
    {
        $this->redirect(ServerSettings::getUrl().'#settings-minecraft');
    }
}
