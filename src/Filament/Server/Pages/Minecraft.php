<?php

declare(strict_types=1);

namespace Simplify\Mods\Filament\Server\Pages;

use App\Enums\SubuserPermission;
use App\Models\Server;
use App\Repositories\Daemon\DaemonServerRepository;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Simplify\Mods\Filament\Components\MotdEditor;
use Simplify\Mods\Services\GameSettings;
use Simplify\Mods\Services\MinecraftFiles;
use Simplify\Mods\Services\PropertiesFile;
use Simplify\Mods\Services\ServerKind;
use Throwable;

/**
 * The main server.properties settings and the EULA of a Minecraft server, without editing files.
 *
 * @property Schema $form
 */
final class Minecraft extends Page implements HasSchemas
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-cube';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'minecraft';

    protected string $view = 'simplify-mods::pages.form';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, string> the values read from server.properties, so old files keep their own format */
    public array $current = [];

    public bool $eulaAccepted = false;

    /** Wings could not be reached: nothing is saved, so the file is never overwritten with defaults. */
    public bool $unreachable = false;

    public static function canAccess(): bool
    {
        $server = Filament::getTenant();
        if (! $server instanceof Server || ! user()?->can(SubuserPermission::FileReadContent, $server)) {
            return false;
        }
        $kind = ServerKind::forServer($server);

        return $kind['minecraft'] && ! $kind['proxy'];
    }

    public static function getNavigationLabel(): string
    {
        return __('simplify-mods::minecraft.title');
    }

    public function getTitle(): string
    {
        return __('simplify-mods::minecraft.title');
    }

    public function server(): Server
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return $server;
    }

    public function canChange(): bool
    {
        return ! $this->unreachable && (bool) user()?->can(SubuserPermission::FileUpdate, $this->server());
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
        $files = new MinecraftFiles($this->server());
        try {
            $this->current = PropertiesFile::parse($files->read(MinecraftFiles::PROPERTIES));
            $this->eulaAccepted = GameSettings::eulaAccepted($files->read(MinecraftFiles::EULA));
        } catch (Throwable $error) {
            report($error);
            $this->unreachable = true;
        }
        $this->form->fill(GameSettings::fromProperties($this->current));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->disabled(fn () => ! $this->canChange())->components([
            Text::make(__('simplify-mods::minecraft.unreachable'))->color('danger')->visible(fn () => $this->unreachable),
            Section::make(__('simplify-mods::minecraft.motd'))
                ->description(__('simplify-mods::minecraft.motd_help'))
                ->schema([
                    MotdEditor::make('motd')->hiddenLabel()->serverName(fn () => (string) $this->server()->name),
                ]),
            Section::make(__('simplify-mods::minecraft.game'))
                ->description(__('simplify-mods::minecraft.restart_help'))
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                ->schema([
                    Select::make('gamemode')->label(__('simplify-mods::minecraft.gamemode'))
                        ->options(self::choices('gamemodes', GameSettings::GAMEMODES))->selectablePlaceholder(false)->required(),
                    Select::make('difficulty')->label(__('simplify-mods::minecraft.difficulty'))
                        ->options(self::choices('difficulties', GameSettings::DIFFICULTIES))->selectablePlaceholder(false)->required(),
                    ...array_map(
                        fn (string $field) => TextInput::make($field)->label(__("simplify-mods::minecraft.$field"))
                            ->integer()->required()
                            ->minValue(GameSettings::NUMBERS[$field][2])->maxValue(GameSettings::NUMBERS[$field][3]),
                        array_keys(GameSettings::NUMBERS),
                    ),
                    ...array_map(
                        fn (string $field) => Toggle::make($field)->label(__("simplify-mods::minecraft.$field"))
                            ->helperText(__("simplify-mods::minecraft.{$field}_help")),
                        array_keys(GameSettings::SWITCHES),
                    ),
                ]),
            Section::make(__('simplify-mods::minecraft.eula'))
                ->schema([
                    Text::make(fn () => $this->eulaAccepted
                        ? __('simplify-mods::minecraft.eula_accepted')
                        : new HtmlString(e(__('simplify-mods::minecraft.eula_help')).' <a href="https://aka.ms/MinecraftEULA" target="_blank" rel="noopener" class="underline">aka.ms/MinecraftEULA</a>')),
                    Actions::make([
                        Action::make('acceptEula')->label(__('simplify-mods::minecraft.eula_accept'))->icon('tabler-check')
                            ->requiresConfirmation()->modalDescription(__('simplify-mods::minecraft.eula_confirm'))
                            ->action(fn () => $this->acceptEula()),
                    ])->visible(fn () => ! $this->eulaAccepted && $this->canChange()),
                ]),
        ]);
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    private static function choices(string $group, array $keys): array
    {
        return array_combine($keys, array_map(fn (string $key) => __("simplify-mods::minecraft.$group.$key"), $keys));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->hiddenLabel()->icon('tabler-device-floppy')->tooltip(__('simplify-mods::minecraft.save'))
                ->action('save')->keyBindings(['mod+s'])->visible(fn () => $this->canChange()),
        ];
    }

    public function save(): void
    {
        abort_unless($this->canChange(), 403);
        $values = GameSettings::toProperties($this->form->getState(), $this->current);
        try {
            (new MinecraftFiles($this->server()))->saveProperties($values);
        } catch (Throwable $error) {
            report($error);
            $this->failedNotification();

            return;
        }
        $this->current = [...$this->current, ...$values];
        $this->savedNotification(__('simplify-mods::minecraft.saved'));
    }

    public function acceptEula(): void
    {
        abort_unless($this->canChange(), 403);
        try {
            (new MinecraftFiles($this->server()))->acceptEula();
        } catch (Throwable $error) {
            report($error);
            $this->failedNotification();

            return;
        }
        $this->eulaAccepted = true;
        $this->savedNotification(__('simplify-mods::minecraft.eula_saved'));
    }

    private function failedNotification(): void
    {
        Notification::make()->title(__('simplify-mods::minecraft.not_saved'))->body(__('simplify-mods::minecraft.unreachable'))->danger()->send();
    }

    /** Settings take effect on the next start, so the notification offers a restart to whoever may restart. */
    private function savedNotification(string $title): void
    {
        $restart = (bool) user()?->can(SubuserPermission::ControlRestart, $this->server());
        Notification::make()->title($title)->body(__('simplify-mods::minecraft.restart_help'))->success()
            ->actions($restart ? [Action::make('restart')->label(__('simplify-mods::minecraft.restart'))->button()->dispatch('simplify-mods-restart')] : [])
            ->send();
    }

    #[On('simplify-mods-restart')]
    public function restart(): void
    {
        abort_unless(user()?->can(SubuserPermission::ControlRestart, $this->server()), 403);
        app(DaemonServerRepository::class)->setServer($this->server())->power('restart')->throw();
        Notification::make()->title(__('simplify-mods::minecraft.restarting'))->success()->send();
    }
}
