<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Livewire;

use App\Models\Egg;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View as ViewComponent;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Livewire\Component;
use Simplify\Toolkit\Services\AutomaticBackups;
use Simplify\Toolkit\Services\Keys;
use Simplify\Toolkit\Services\PlayerCounter;
use Simplify\Toolkit\Services\PlayerCounts;

/**
 * Settings of Simplify Toolkit for the whole panel: the CurseForge API key, automatic backups, and how the server cards
 * count players per egg. Shown in Simplify
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

    /**
     * How each egg counts players, by "egg<id>", as the Edit windows left it; stored with Save.
     *
     * @var array<string, array{protocol: string, port: string, port_offset: int, port_variable: string}>
     */
    public array $eggCounts = [];

    /** The egg whose Edit window is open. */
    public int $editingEgg = 0;

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
        $this->form->fill([
            'backups' => AutomaticBackups::panel()->only(['enabled', 'every_minutes', 'keep', 'only_when_used']),
            'players' => ['use_alias' => PlayerCounts::useAlias()],
        ]);
        // The eggs' choices are no fields of the form: the Edit window of each row changes them, Save stores them.
        $this->eggCounts = [];
        foreach (Egg::query()->orderBy('id')->pluck('id') as $egg) {
            $this->eggCounts['egg'.$egg] = PlayerCounts::forEgg((int) $egg);
        }
    }

    /**
     * One row per egg for the Player counts card: its name, the protocol, the query port and its servers.
     *
     * @return list<array{id: int, name: string, protocol: string, note: string, port: string, servers: int, from_player_counter: bool, tone: string}>
     */
    public function playerCountRows(): array
    {
        $fromPlayerCounter = PlayerCounter::eggs();
        $rows = [];
        foreach (Egg::query()->withCount('servers')->orderBy('name')->get(['id', 'name']) as $egg) {
            $id = (int) $egg->id;
            if (isset($fromPlayerCounter[$id])) {
                $query = $fromPlayerCounter[$id];
                $port = $query['variable'] !== null && $query['variable'] !== ''
                    ? ['port' => 'variable', 'port_offset' => 1, 'port_variable' => $query['variable']]
                    : ['port' => ($query['offset'] ?? 0) > 0 ? 'offset' : 'game', 'port_offset' => max(1, (int) $query['offset']), 'port_variable' => ''];
                $rows[] = ['id' => $id, 'name' => (string) $egg->name, 'protocol' => ucfirst(str_replace('_', ' ', $query['type'])),
                    'note' => __('simplify-toolkit::counts.from_player_counter'), 'port' => self::portLabel($port),
                    'servers' => (int) $egg->getAttribute('servers_count'), 'from_player_counter' => true, 'tone' => 'info'];

                continue;
            }
            $choice = PlayerCounts::clean((array) ($this->eggCounts['egg'.$id] ?? []));
            $protocol = $choice['protocol'];
            $rows[] = [
                'id' => $id,
                'name' => (string) $egg->name,
                'protocol' => PlayerCounts::PROTOCOLS[$protocol] ?? __('simplify-toolkit::counts.'.$protocol),
                'note' => match ($protocol) {
                    PlayerCounts::AUTOMATIC => __('simplify-toolkit::counts.automatic_note'),
                    PlayerCounts::OFF => __('simplify-toolkit::counts.off_note'),
                    'palworld_rest', 'palworld_rcon' => __('simplify-toolkit::counts.palworld_note'),
                    default => '',
                },
                'port' => in_array($protocol, [PlayerCounts::AUTOMATIC, PlayerCounts::OFF], true) ? '–' : self::portLabel($choice),
                'servers' => (int) $egg->getAttribute('servers_count'),
                'from_player_counter' => false,
                'tone' => match ($protocol) {
                    PlayerCounts::AUTOMATIC => 'gray',
                    PlayerCounts::OFF => 'off',
                    default => 'success',
                },
            ];
        }

        return $rows;
    }

    /** @param  array{port: string, port_offset: int, port_variable: string}  $choice */
    private static function portLabel(array $choice): string
    {
        return match ($choice['port']) {
            'offset' => __('simplify-toolkit::counts.game_port_plus', ['offset' => $choice['port_offset']]),
            'variable' => __('simplify-toolkit::counts.variable_port', ['name' => $choice['port_variable']]),
            default => __('simplify-toolkit::counts.game_port'),
        };
    }

    /** The Edit window of an egg's row: protocol and query port, applied to the form; Save stores them. */
    public function editEggAction(): Action
    {
        return Action::make('editEgg')
            ->label(__('simplify-toolkit::counts.edit'))->icon('tabler-pencil')->color('gray')->size('sm')
            ->visible(fn () => self::canChange())
            ->modalHeading(fn (array $arguments) => __('simplify-toolkit::counts.edit_title', ['egg' => (string) Egg::query()->whereKey((int) ($arguments['egg'] ?? 0))->value('name')]))
            ->modalDescription(__('simplify-toolkit::counts.edit_help'))
            ->modalSubmitActionLabel(__('simplify-toolkit::counts.apply'))
            ->modalWidth('lg')
            ->fillForm(function (array $arguments): array {
                $this->editingEgg = (int) ($arguments['egg'] ?? 0);

                return PlayerCounts::clean((array) ($this->eggCounts['egg'.$this->editingEgg] ?? []));
            })
            ->schema(fn (array $arguments) => [
                Select::make('protocol')->label(__('simplify-toolkit::counts.protocol'))->required()->selectablePlaceholder(false)->live()
                    ->options([PlayerCounts::AUTOMATIC => __('simplify-toolkit::counts.automatic').' ('.__('simplify-toolkit::counts.automatic_note').')']
                        + PlayerCounts::PROTOCOLS + [PlayerCounts::OFF => __('simplify-toolkit::counts.off')])
                    ->helperText(__('simplify-toolkit::counts.protocol_help')),
                Radio::make('port')->label(__('simplify-toolkit::counts.query_port'))->required()->live()
                    ->options(['game' => __('simplify-toolkit::counts.port_game'), 'offset' => __('simplify-toolkit::counts.port_offset'), 'variable' => __('simplify-toolkit::counts.port_variable')])
                    ->helperText(__('simplify-toolkit::counts.port_help'))
                    ->visible(fn (Get $get) => ! in_array($get('protocol'), [PlayerCounts::AUTOMATIC, PlayerCounts::OFF], true)),
                TextInput::make('port_offset')->label(__('simplify-toolkit::counts.offset'))->integer()->minValue(1)->maxValue(65534)->required()
                    ->visible(fn (Get $get) => $get('port') === 'offset' && ! in_array($get('protocol'), [PlayerCounts::AUTOMATIC, PlayerCounts::OFF], true)),
                Select::make('port_variable')->label(__('simplify-toolkit::counts.variable'))->required()
                    ->placeholder(__('simplify-toolkit::counts.choose_variable'))
                    ->options(fn () => self::variablesOf((int) ($arguments['egg'] ?? 0)))
                    ->visible(fn (Get $get) => $get('port') === 'variable' && ! in_array($get('protocol'), [PlayerCounts::AUTOMATIC, PlayerCounts::OFF], true)),
            ])
            ->action(function (array $data, array $arguments): void {
                abort_unless(self::canChange(), 403);
                $egg = 'egg'.(int) ($arguments['egg'] ?? $this->editingEgg);
                if (! isset($this->eggCounts[$egg])) {
                    return;
                }
                $this->eggCounts[$egg] = PlayerCounts::clean($data + PlayerCounts::default());
            });
    }

    /** @return array<string, string> the egg's variables, by their name in the start command */
    private static function variablesOf(int $egg): array
    {
        $variables = [];
        foreach (Egg::query()->find($egg)?->variables ?? [] as $variable) {
            $variables[(string) $variable->env_variable] = (string) $variable->env_variable;
        }
        ksort($variables);

        return $variables;
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
                        ->password()->autocomplete('new-password')->maxLength(200),
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
            Section::make(__('simplify-toolkit::counts.title'))->description(__('simplify-toolkit::counts.help'))
                ->statePath('players')->schema([
                    Text::make(__('simplify-toolkit::counts.player_counter').'. '.__('simplify-toolkit::counts.player_counter_help'))
                        ->color('info')->visible(fn () => PlayerCounter::installed()),
                    Toggle::make('use_alias')->label(__('simplify-toolkit::counts.use_alias'))
                        ->helperText(__('simplify-toolkit::counts.use_alias_help')),
                    ViewComponent::make('simplify-toolkit::components.player-counts'),
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
        $eggs = [];
        foreach ($this->eggCounts as $key => $choice) {
            $eggs[(int) substr((string) $key, 3)] = (array) $choice;
        }
        PlayerCounts::save($eggs, (bool) ($state['players']['use_alias'] ?? true));
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
