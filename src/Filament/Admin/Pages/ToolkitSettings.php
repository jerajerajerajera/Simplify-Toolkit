<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Simplify\Toolkit\Services\Keys;

/**
 * Settings of Simplify Toolkit for the whole panel: the CurseForge API key.
 *
 * @property Schema $form
 */
final class ToolkitSettings extends Page implements HasSchemas
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-cube';

    protected static ?int $navigationSort = 60;

    protected static ?string $slug = 'simplify-toolkit';

    protected string $view = 'simplify-toolkit::pages.form';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $hasKey = false;

    public static function canAccess(): bool
    {
        return (bool) user()?->can('view settings');
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

    public function canChange(): bool
    {
        return (bool) user()?->can('update settings');
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
        $this->hasKey = Keys::has(Keys::CURSEFORGE, null);
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->disabled(fn () => ! $this->canChange())->components([
            Section::make('CurseForge')
                ->description(new HtmlString(e(__('simplify-toolkit::settings.curseforge_help')).' <a href="https://console.curseforge.com/" target="_blank" rel="noopener" class="underline">console.curseforge.com</a>'))
                ->schema([
                    Text::make(fn () => __('simplify-toolkit::settings.'.($this->hasKey ? 'key_saved' : 'no_key')))
                        ->color(fn () => $this->hasKey ? 'success' : 'gray'),
                    TextInput::make('curseforge')->label(__('simplify-toolkit::settings.'.($this->hasKey ? 'new_key' : 'key')))
                        ->password()->autocomplete('off')->maxLength(200),
                    Actions::make([
                        Action::make('saveKey')->label(__('simplify-toolkit::settings.save'))->icon('tabler-device-floppy')->button()
                            ->action(fn () => $this->saveKey()),
                        Action::make('testKey')->label(__('simplify-toolkit::settings.test'))->icon('tabler-plug-connected')->color('gray')->button()
                            ->visible(fn () => $this->hasKey)->action(fn () => $this->testKey()),
                        Action::make('removeKey')->label(__('simplify-toolkit::settings.remove'))->icon('tabler-trash')->color('danger')->button()
                            ->requiresConfirmation()->visible(fn () => $this->hasKey)->action(fn () => $this->removeKey()),
                    ])->visible(fn () => $this->canChange()),
                ]),
        ]);
    }

    public function save(): void
    {
        $this->saveKey();
    }

    /** A key is only saved when CurseForge accepts it, so a typo never breaks the search. */
    public function saveKey(): void
    {
        abort_unless($this->canChange(), 403);
        $key = trim((string) ($this->form->getState()['curseforge'] ?? ''));
        if ($key === '') {
            return;
        }
        if (! Keys::curseForgeWorks($key)) {
            Notification::make()->title(__('simplify-toolkit::settings.key_refused'))->danger()->send();

            return;
        }
        Keys::save(Keys::CURSEFORGE, null, $key);
        $this->hasKey = true;
        $this->form->fill();
        Notification::make()->title(__('simplify-toolkit::settings.key_works'))->success()->send();
    }

    public function testKey(): void
    {
        abort_unless($this->canChange(), 403);
        $works = Keys::curseForgeWorks((string) Keys::get(Keys::CURSEFORGE, null));
        Notification::make()->title(__('simplify-toolkit::settings.'.($works ? 'key_works' : 'test_failed')))
            ->status($works ? 'success' : 'danger')->send();
    }

    public function removeKey(): void
    {
        abort_unless($this->canChange(), 403);
        Keys::forget(Keys::CURSEFORGE, null);
        $this->hasKey = false;
        Notification::make()->title(__('simplify-toolkit::settings.removed'))->success()->send();
    }
}
