<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Simplify\Core\Addons\Addon;
use Simplify\Toolkit\Livewire\ToolkitSettingsForm;

/**
 * Toolkit's own settings page, for a panel without Simplify. With Simplify 2.0 and later (add-on API 8) the same form is
 * a section of Simplify settings › Add-ons, and this page leaves the menu.
 */
final class ToolkitSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'tabler-cube';

    protected static ?int $navigationSort = 95;

    protected static ?string $slug = 'simplify-toolkit';

    protected string $view = 'simplify-toolkit::pages.settings';

    public static function canAccess(): bool
    {
        return ToolkitSettingsForm::canView();
    }

    /** With Simplify's settings sections the form is there (ADDONS.md, "Settings"). */
    public static function shouldRegisterNavigation(): bool
    {
        return ! self::inSimplifySettings();
    }

    public static function inSimplifySettings(): bool
    {
        return method_exists(Addon::class, 'settingsForm');
    }

    /** In the Simplify group of Pelican's menu, with the other Simplify add-ons (Simplify's ADDONS.md, "Admin menu"). */
    public static function getNavigationGroup(): ?string
    {
        return 'Simplify';
    }

    public static function getNavigationLabel(): string
    {
        return 'Toolkit';
    }

    public function getTitle(): string
    {
        return 'Toolkit';
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
    }

    /** Save in the head of the page, as on Simplify's own pages; the form saves itself. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->label(__('simplify-toolkit::minecraft.save'))->icon('tabler-device-floppy')
                ->visible(fn () => ToolkitSettingsForm::canChange())
                ->action(fn () => $this->dispatch(ToolkitSettingsForm::SAVE)->to(ToolkitSettingsForm::class)),
        ];
    }
}
