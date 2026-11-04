<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Livewire\Component;
use Simplify\Toolkit\Services\AutomaticBackups;
use Simplify\Toolkit\Services\Keys;

/**
 * Settings of Simplify Toolkit for the whole panel: the CurseForge API key, and automatic backups. Shown in Simplify
 * settings › Add-ons (add-on API 8, ADDONS.md "Settings") and on Toolkit's own settings page without Simplify.
 *
 * @property Schema $form
 */
final class ToolkitSettingsForm extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /** The name its requests post back to (ToolkitServiceProvider). */
    public const NAME = 'simplify-toolkit-settings-form';

    /** The event Toolkit's own settings page fires from its Save button. */
    public const SAVE = 'simplify-toolkit-settings-save';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $hasKey = false;

    public static function canView(): bool
    {
        return (bool) user()?->can('view settings');
    }

    public static function canChange(): bool
    {
        return (bool) user()?->can('update settings');
    }

    public function mount(): void
    {
        abort_unless(self::canView(), 403);
        $this->hasKey = Keys::has(Keys::CURSEFORGE, null);
        $this->fillForm();
    }

    private function fillForm(): void
    {
        $this->form->fill(['backups' => AutomaticBackups::panel()->only(['enabled', 'every_minutes', 'keep', 'only_when_used'])]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->disabled(fn () => ! self::canChange())->components([
            Section::make('CurseForge')
                ->description(new HtmlString(e(__('simplify-toolkit::settings.curseforge_help')).' <a href="https://console.curseforge.com/" target="_blank" rel="noopener" class="underline">console.curseforge.com</a>'))
                ->schema([
                    Text::make(fn () => __('simplify-toolkit::settings.'.($this->hasKey ? 'key_saved' : 'no_key')))
                        ->color(fn () => $this->hasKey ? 'success' : 'gray'),
                    TextInput::make('curseforge')->label(fn () => __('simplify-toolkit::settings.'.($this->hasKey ? 'new_key' : 'key')))
                        ->password()->autocomplete('off')->maxLength(200),
                    Actions::make([
                        Action::make('testKey')->label(__('simplify-toolkit::settings.test'))->icon('tabler-plug-connected')->color('gray')
                            ->action(fn () => $this->testKey()),
                        Action::make('removeKey')->label(__('simplify-toolkit::settings.remove'))->icon('tabler-trash')->color('gray')
                            ->requiresConfirmation()->action(fn () => $this->removeKey()),
                    ])->visible(fn () => self::canChange() && $this->hasKey),
                ]),
            Section::make(__('simplify-toolkit::backups.title'))->description(__('simplify-toolkit::backups.help'))
                ->statePath('backups')->columns(2)->schema([
                    Toggle::make('enabled')->label(__('simplify-toolkit::backups.panel'))
                        ->helperText(__('simplify-toolkit::backups.panel_help'))->columnSpanFull(),
                    TextInput::make('every_minutes')->label(__('simplify-toolkit::backups.default_interval'))
                        ->helperText(__('simplify-toolkit::backups.default_interval_help'))->integer()->minValue(30)->maxValue(10080)->required(),
                    TextInput::make('keep')->label(__('simplify-toolkit::backups.default_points'))
                        ->helperText(__('simplify-toolkit::backups.default_points_help'))->integer()->minValue(1)->maxValue(100)->required(),
                    Toggle::make('only_when_used')->label(__('simplify-toolkit::backups.only_used'))
                        ->helperText(__('simplify-toolkit::backups.only_used_help'))->columnSpanFull(),
                ]),
        ]);
    }

    /** Saved by Simplify's Save (data-simplify-save), or by the Save button of Toolkit's own settings page. */
    #[On(self::SAVE)]
    public function save(): void
    {
        abort_unless(self::canChange(), 403);
        $state = $this->form->getState();
        AutomaticBackups::save(null, $state['backups'] ?? []);
        if (trim((string) ($state['curseforge'] ?? '')) === '') {
            Notification::make()->title(__('simplify-toolkit::minecraft.saved'))->success()->send();

            return;
        }
        $this->saveKey();
    }

    /** A key is only saved when CurseForge accepts it, so a typo never breaks the search. */
    public function saveKey(): void
    {
        abort_unless(self::canChange(), 403);
        $key = trim((string) ($this->form->getState()['curseforge'] ?? ''));
        if ($key === '') {
            return;
        }
        if (! Keys::curseForgeWorks($key)) {
            Notification::make()->title(__('simplify-toolkit::settings.key_refused'))->danger()->send();
            // An error in the bag tells Simplify the changes were not saved, so its "Changes made" bar stays.
            $this->addError('data', __('simplify-toolkit::settings.key_refused'));

            return;
        }
        Keys::save(Keys::CURSEFORGE, null, $key);
        $this->hasKey = true;
        $this->fillForm();
        Notification::make()->title(__('simplify-toolkit::settings.key_works'))->success()->send();
    }

    public function testKey(): void
    {
        abort_unless(self::canChange(), 403);
        $works = Keys::curseForgeWorks((string) Keys::get(Keys::CURSEFORGE, null));
        Notification::make()->title(__('simplify-toolkit::settings.'.($works ? 'key_works' : 'test_failed')))
            ->status($works ? 'success' : 'danger')->send();
    }

    public function removeKey(): void
    {
        abort_unless(self::canChange(), 403);
        Keys::forget(Keys::CURSEFORGE, null);
        $this->hasKey = false;
        Notification::make()->title(__('simplify-toolkit::settings.removed'))->success()->send();
    }

    public function render(): View
    {
        return view('simplify-toolkit::livewire.settings-form');
    }
}
