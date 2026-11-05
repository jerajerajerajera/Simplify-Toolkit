<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Filament\Server\Pages;

use App\Enums\ServerUserSettingKey;
use App\Enums\SubuserPermission;
use App\Facades\Activity;
use App\Filament\Components\Actions\DeleteIcon;
use App\Filament\Components\Actions\UploadIcon;
use App\Filament\Components\Forms\Fields\StartupVariable;
use App\Filament\Server\Pages\Console;
use App\Models\Server;
use App\Models\ServerVariable;
use App\Services\Servers\ReinstallServerService;
use App\Services\Servers\StartupCommandService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Simplify\Toolkit\Filament\Components\MotdEditor;
use Simplify\Toolkit\Filament\Server\Actions\RestartActions;
use Simplify\Toolkit\Services\GameSettings;
use Simplify\Toolkit\Services\MinecraftFiles;
use Simplify\Toolkit\Services\PropertiesFile;
use Simplify\Toolkit\Services\RestartReminder;
use Simplify\Toolkit\Services\ServerIcon;
use Simplify\Toolkit\Services\ServerKind;
use Simplify\Toolkit\Services\SettingsSections;
use Simplify\Toolkit\Services\Software;
use Simplify\Toolkit\Services\Web;
use Throwable;

/**
 * One Settings page per server: Pelican's Settings and Startup, the Minecraft settings and the sections other add-ons
 * add, on one card with one Save. Pelican's own Settings and Startup pages send their visitors here.
 *
 * The form's state has one part per section: data.server, data.game, data.startup, data.sections.<add-on> and
 * data.notifications. Save only writes the parts that changed.
 *
 * @property Schema $form
 */
final class ServerSettings extends Page implements HasSchemas
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-settings';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'server-settings';

    protected string $view = 'simplify-toolkit::pages.server-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed> the state as last loaded or saved, to tell which parts changed */
    public array $saved = [];

    /** @var array<string, string> the values read from server.properties, so old files keep their own format */
    public array $current = [];

    public bool $eulaAccepted = false;

    /** The current server-icon.png as a data URL, '' without one. */
    public string $icon = '';

    /** Wings could not be reached: the Minecraft part is never saved, so the file is never overwritten with defaults. */
    public bool $unreachable = false;

    public static function canAccess(): bool
    {
        return Filament::getTenant() instanceof Server && user() !== null;
    }

    /** With Simplify the core leaves Pelican's Settings and Startup out of the menu; without it they lead here. */
    public static function shouldRegisterNavigation(): bool
    {
        return class_exists('Simplify\\Core\\Addons\\Addon') && method_exists('Simplify\\Core\\Addons\\Addon', 'replacedServerPages');
    }

    public static function getNavigationLabel(): string
    {
        return trans('server/setting.title');
    }

    public function getTitle(): string
    {
        return trans('server/setting.title');
    }

    public function getSubheading(): string
    {
        $server = $this->server();
        $software = ServerKind::forServer($server)['minecraft'] ? Software::label($server) : '';

        return $software !== '' ? $software : (string) $server->egg->name;
    }

    public function server(): Server
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return $server;
    }

    /** The Minecraft section: Java servers whose files the person may read. */
    public function hasGame(): bool
    {
        $kind = ServerKind::forServer($this->server());

        return $kind['minecraft'] && ! $kind['proxy'] && $this->can(SubuserPermission::FileReadContent);
    }

    public function canChangeGame(): bool
    {
        return $this->hasGame() && ! $this->unreachable && $this->can(SubuserPermission::FileUpdate);
    }

    public function hasStartup(): bool
    {
        return $this->can(SubuserPermission::StartupRead);
    }

    /** Pelican's own switches for backup notifications. With Simplify they are in the notifications sheet already. */
    public function hasNotifications(): bool
    {
        return ! class_exists('Simplify\\Core\\Addons\\Addon');
    }

    private function can(SubuserPermission $permission): bool
    {
        return (bool) user()?->can($permission, $this->server());
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
        $server = $this->server();
        if ($this->hasGame()) {
            $files = new MinecraftFiles($server);
            try {
                $this->current = PropertiesFile::parse($files->read(MinecraftFiles::PROPERTIES));
                $this->eulaAccepted = GameSettings::eulaAccepted($files->read(MinecraftFiles::EULA));
                $this->icon = ServerIcon::dataUrl($files->read(ServerIcon::FILE));
            } catch (Throwable $error) {
                report($error);
                $this->unreachable = true;
            }
        }
        $this->form->fill([
            'server' => ['name' => $server->name, 'description' => $server->description],
            'game' => $this->hasGame() ? GameSettings::fromProperties($this->current) : [],
            'startup' => $this->hasStartup() ? $this->startupState() : [],
            'sections' => SettingsSections::fill($server),
            'notifications' => $this->notificationState(),
        ]);
        $this->saved = $this->data ?? [];
    }

    /** @return array<string, mixed> */
    private function startupState(): array
    {
        $server = $this->server();
        $server->ensureVariablesExist();
        $handled = SettingsSections::variables($server);
        $variables = [];
        $records = ServerVariable::query()->where('server_id', $server->id)->whereHas('variable')->with('variable')->get()
            ->filter(fn (ServerVariable $row) => $row->variable->user_viewable && ! in_array($row->variable->env_variable, $handled, true))
            ->sortBy(fn (ServerVariable $row) => (int) $row->variable->sort);
        foreach ($records as $row) {
            $variables['v'.$row->id] = [
                'id' => $row->id,
                'name' => $row->variable->name,
                'description' => $row->variable->description,
                'env_variable' => $row->variable->env_variable,
                'default_value' => $row->variable->default_value,
                'rules' => $row->variable->rules,
                'user_editable' => (bool) $row->variable->user_editable,
                'variable_value' => (string) $row->variable_value,
            ];
        }

        return ['command' => $server->startup, 'image' => $server->image, 'variables' => $variables];
    }

    /** @return array<string, bool> */
    private function notificationState(): array
    {
        if (! $this->hasNotifications()) {
            return [];
        }
        $state = [];
        foreach (ServerUserSettingKey::cases() as $key) {
            $state[$key->value] = (bool) user()?->getServerSetting($this->server(), $key);
        }

        return $state;
    }

    /** Simplify is installed: its card frame, with the title in the first section (ADDONS.md, "Page head"). */
    private static function withCore(): bool
    {
        return class_exists('Simplify\\Core\\Addons\\Addon');
    }

    public function form(Schema $schema): Schema
    {
        $server = $this->server();
        $sections = [
            $this->serverSection(),
            ...($this->hasGame() ? $this->gameSections() : []),
            ...($this->hasStartup() ? [$this->startupSection()] : []),
            ...SettingsSections::components($server),
            ...($this->hasNotifications() ? [$this->notificationsSection()] : []),
            $this->limitsSection(),
        ];
        $contents = View::make('simplify-toolkit::components.settings-contents')->viewData(['contents' => $this->contents()])
            ->columnSpan(['default' => 'full', 'lg' => 1]);
        $body = Group::make($sections)->columnSpan(['default' => 'full', 'lg' => 4])->extraAttributes(['class' => 'sm-settings-body']);
        $columns = ['default' => 1, 'lg' => 5];

        return $schema->statePath('data')->model($server)->components(self::withCore()
            // One card: the title and subtitle in its head, the sections inside without a box of their own.
            ? [Section::make($this->getTitle())->description($this->getSubheading())->columns($columns)->schema([$contents, $body])]
            // Without Simplify: Pelican's own heading above, and every section its own Filament section.
            : [Grid::make($columns)->schema([$contents, $body])]);
    }

    private function serverSection(): Section
    {
        $server = $this->server();
        $sftp = $this->can(SubuserPermission::FileSftp);

        return Section::make(trans('server/setting.server_info.title'))->icon('tabler-server')
            ->extraAttributes(['id' => 'settings-server'])->contained(! self::withCore())
            ->statePath('server')->columns(['default' => 1, 'md' => 2])
            ->schema([
                TextInput::make('name')->label(trans('server/setting.server_info.name'))
                    ->required()->maxLength(191)
                    ->disabled(fn () => ! $this->can(SubuserPermission::SettingsRename)),
                TextInput::make('sftp')->label(trans('server/setting.server_info.sftp.connection'))
                    ->visible($sftp)->dehydrated(false)->disabled()->copyable()
                    ->formatStateUsing(fn () => $server->getSftpUrl()),
                Textarea::make('description')->label(trans('server/setting.server_info.description'))
                    ->columnSpanFull()->autosize()
                    ->visible(fn () => (bool) config('panel.editable_server_descriptions'))
                    ->disabled(fn () => ! $this->can(SubuserPermission::SettingsDescription)),
                TextInput::make('sftp_username')->label(trans('server/setting.server_info.sftp.username'))
                    ->visible($sftp)->dehydrated(false)->disabled()->copyable()
                    ->formatStateUsing(fn () => user()?->username.'.'.$server->uuid_short),
                // The icon the panel shows for this server, with its buttons under a label like the fields beside it.
                Group::make([
                    Text::make(new HtmlString('<span class="sm-settings-icon-label">'.e(__('simplify-toolkit::toolkit.settings_page.panel_icon')).'</span>')),
                    Actions::make([
                        UploadIcon::make()->authorize(fn () => $this->can(SubuserPermission::SettingsChangeIcon)),
                        DeleteIcon::make()->iconStoragePath(Server::getIconStoragePath())->authorize(fn () => $this->can(SubuserPermission::SettingsChangeIcon)),
                    ]),
                ])->extraAttributes(['class' => 'sm-settings-icon-field'])->visible(fn () => $this->can(SubuserPermission::SettingsChangeIcon)),
            ]);
    }

    /** @return list<Section|Text> */
    private function gameSections(): array
    {
        $change = fn () => ! $this->canChangeGame();

        return [
            Section::make(__('simplify-toolkit::minecraft.title'))->icon('tabler-cube')
                ->description(__('simplify-toolkit::minecraft.restart_help'))
                ->extraAttributes(['id' => 'settings-minecraft'])->contained(! self::withCore())
                ->statePath('game')->disabled($change)
                ->schema([
                    Text::make(__('simplify-toolkit::minecraft.unreachable'))->color('danger')->visible(fn () => $this->unreachable),
                    Section::make(__('simplify-toolkit::minecraft.motd'))->description(__('simplify-toolkit::minecraft.motd_help'))->contained(false)
                        ->schema([
                            MotdEditor::make('motd')->hiddenLabel()->serverName(fn () => (string) $this->server()->name)->iconUrl(fn () => $this->icon),
                        ]),
                    Section::make(__('simplify-toolkit::minecraft.icon'))->description(__('simplify-toolkit::minecraft.icon_help'))->contained(false)
                        ->schema([
                            Text::make(fn () => $this->icon !== ''
                                ? new HtmlString('<img src="'.e($this->icon).'" alt="" width="64" height="64" style="image-rendering: pixelated; border-radius: .25rem">')
                                : __('simplify-toolkit::minecraft.no_icon')),
                            Actions::make([
                                Action::make('uploadIcon')->label(__('simplify-toolkit::minecraft.icon_upload'))->icon('tabler-upload')->button()
                                    ->schema([
                                        FileUpload::make('picture')->hiddenLabel()->image()->maxSize(8192)->storeFiles(false)->required(),
                                    ])
                                    ->action(fn (array $data) => $this->saveIcon($data['picture'] ?? null)),
                                Action::make('modpackIcon')->label(__('simplify-toolkit::minecraft.icon_modpack'))->icon('tabler-package')->color('gray')->button()
                                    ->visible(fn () => in_array(ServerKind::forServer($this->server())['modpack'], ['modrinth', 'ftb'], true))
                                    ->action(fn () => $this->useModpackIcon()),
                                Action::make('removeIcon')->label(__('simplify-toolkit::minecraft.icon_remove'))->icon('tabler-trash')->color('gray')->button()
                                    ->requiresConfirmation()->visible(fn () => $this->icon !== '')
                                    ->action(fn () => $this->removeIcon()),
                            ])->visible(fn () => $this->canChangeGame()),
                        ]),
                    Section::make(__('simplify-toolkit::minecraft.game'))->contained(false)
                        ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->schema([
                            Select::make('gamemode')->label(__('simplify-toolkit::minecraft.gamemode'))
                                ->options(self::choices('gamemodes', GameSettings::GAMEMODES))->selectablePlaceholder(false)->required(),
                            Select::make('difficulty')->label(__('simplify-toolkit::minecraft.difficulty'))
                                ->options(self::choices('difficulties', GameSettings::DIFFICULTIES))->selectablePlaceholder(false)->required(),
                            ...array_map(
                                fn (string $field) => TextInput::make($field)->label(__("simplify-toolkit::minecraft.$field"))
                                    ->integer()->required()
                                    ->minValue(GameSettings::NUMBERS[$field][2])->maxValue(GameSettings::NUMBERS[$field][3]),
                                array_keys(GameSettings::NUMBERS),
                            ),
                            ...array_map(
                                fn (string $field) => Toggle::make($field)->label(__("simplify-toolkit::minecraft.$field"))
                                    ->helperText(__("simplify-toolkit::minecraft.{$field}_help")),
                                array_keys(GameSettings::SWITCHES),
                            ),
                        ]),
                    Section::make(__('simplify-toolkit::minecraft.eula'))->contained(false)
                        ->schema([
                            Text::make(fn () => $this->eulaAccepted
                                ? __('simplify-toolkit::minecraft.eula_accepted')
                                : new HtmlString(e(__('simplify-toolkit::minecraft.eula_help')).' <a href="https://aka.ms/MinecraftEULA" target="_blank" rel="noopener" class="underline">aka.ms/MinecraftEULA</a>')),
                            Actions::make([
                                Action::make('acceptEula')->label(__('simplify-toolkit::minecraft.eula_accept'))->icon('tabler-check')->button()
                                    ->requiresConfirmation()->modalDescription(__('simplify-toolkit::minecraft.eula_confirm'))
                                    ->action(fn () => $this->acceptEula()),
                            ])->visible(fn () => ! $this->eulaAccepted && $this->canChangeGame()),
                        ]),
                ]),
        ];
    }

    private function startupSection(): Section
    {
        $server = $this->server();
        $commands = (array) $server->egg->startup_commands;
        $images = (array) $server->egg->docker_images;
        $update = $this->can(SubuserPermission::StartupUpdate);

        return Section::make(trans('server/startup.title'))->icon('tabler-player-play')
            ->extraAttributes(['id' => 'settings-startup'])->contained(! self::withCore())
            ->statePath('startup')->columns(['default' => 1, 'md' => 2])
            ->schema([
                // A command or image the egg does not list was set by an admin: shown, not changed here.
                in_array($server->startup, $commands, true)
                    ? Select::make('command')->label(trans('server/startup.command'))->options(array_flip($commands))
                        ->selectablePlaceholder(false)->disabled(! $update)
                    : TextInput::make('command')->label(trans('server/startup.command'))->disabled()->dehydrated(false)
                        ->formatStateUsing(fn () => 'Custom Startup'),
                in_array($server->image, $images, true)
                    ? Select::make('image')->label(trans('server/startup.docker_image'))->options(array_flip($images))
                        ->selectablePlaceholder(false)->disabled(! $this->can(SubuserPermission::StartupDockerImage))
                    : TextInput::make('image')->label(trans('server/startup.docker_image'))->disabled()->dehydrated(false),
                Text::make(fn () => new HtmlString('<code class="sm-command">'.e($this->startupPreview()).'</code>'))->columnSpanFull(),
                Repeater::make('variables')->label(trans('server/startup.variables'))->columnSpanFull()
                    ->grid(['default' => 1, 'md' => 2, 'xl' => 3])
                    ->addable(false)->deletable(false)->reorderable(false)->disabled(! $update)
                    ->visible(fn () => ($this->data['startup']['variables'] ?? []) !== [])
                    ->schema([StartupVariable::make('variable_value')->fromForm()]),
            ]);
    }

    /** The start command with its variables filled in, as Pelican's preview shows it. */
    private function startupPreview(): string
    {
        try {
            return app(StartupCommandService::class)->handle($this->server(), $this->server()->startup);
        } catch (Throwable $error) {
            report($error);

            return (string) $this->server()->startup;
        }
    }

    private function notificationsSection(): Section
    {
        return Section::make(trans('server/setting.notifications.title'))->icon('tabler-bell')
            ->extraAttributes(['id' => 'settings-notifications'])->contained(! self::withCore())
            ->statePath('notifications')
            ->schema([
                Toggle::make(ServerUserSettingKey::ManualBackupNotifications->value)
                    ->label(trans('server/setting.notifications.backup_manual.label'))
                    ->helperText(trans('server/setting.notifications.backup_manual.helper')),
                Toggle::make(ServerUserSettingKey::ScheduledBackupNotifications->value)
                    ->label(trans('server/setting.notifications.backup_scheduled.label'))
                    ->helperText(trans('server/setting.notifications.backup_scheduled.helper')),
            ]);
    }

    private function limitsSection(): Section
    {
        return Section::make(trans('server/setting.server_info.limits.title'))->icon('tabler-chart-bar')
            ->extraAttributes(['id' => 'settings-limits'])->contained(! self::withCore())
            ->schema([Text::make(fn () => new HtmlString('<span class="sm-limits">'.implode('', array_map(
                fn (array $limit) => '<span>'.e($limit[0]).' <b>'.e($limit[1]).'</b></span>',
                $this->limits(),
            )).'</span>'))]);
    }

    /** @return list<array{string, string}> */
    public function limits(): array
    {
        $server = $this->server();
        $unlimited = trans('server/setting.server_info.limits.unlimited');
        $of = fn (int $used, ?int $max) => $max ? $used.' '.trans('server/setting.server_info.limits.of', ['max' => $max]) : '0';

        return [
            [trans('server/setting.server_info.limits.cpu'), $server->cpu ? $server->cpu.'%' : $unlimited],
            [trans('server/setting.server_info.limits.memory'), $server->memory ? convert_bytes_to_readable($server->memory * 2 ** 20) : $unlimited],
            [trans('server/setting.server_info.limits.disk'), $server->disk ? convert_bytes_to_readable($server->disk * 2 ** 20) : $unlimited],
            [trans('server/setting.server_info.limits.backups'), $of($server->backups()->count(), $server->backup_limit)],
            [trans('server/setting.server_info.limits.databases'), $of($server->databases()->count(), $server->database_limit)],
            [trans('server/setting.server_info.limits.allocations'), $of($server->allocations()->count(), $server->allocation_limit)],
        ];
    }

    /**
     * The sections in the order of the page, for the list beside the card (desktop) or above it (phone).
     *
     * @return list<array{id: string, label: string, icon: string}>
     */
    public function contents(): array
    {
        $list = [['id' => 'settings-server', 'label' => trans('server/setting.server_info.title'), 'icon' => 'tabler-server']];
        if ($this->hasGame()) {
            $list[] = ['id' => 'settings-minecraft', 'label' => __('simplify-toolkit::minecraft.title'), 'icon' => 'tabler-cube'];
        }
        if ($this->hasStartup()) {
            $list[] = ['id' => 'settings-startup', 'label' => trans('server/startup.title'), 'icon' => 'tabler-player-play'];
        }
        array_push($list, ...SettingsSections::contents($this->server()));
        if ($this->hasNotifications()) {
            $list[] = ['id' => 'settings-notifications', 'label' => trans('server/setting.notifications.title'), 'icon' => 'tabler-bell'];
        }
        $list[] = ['id' => 'settings-limits', 'label' => trans('server/setting.server_info.limits.title'), 'icon' => 'tabler-chart-bar'];

        return $list;
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    private static function choices(string $group, array $keys): array
    {
        return array_combine($keys, array_map(fn (string $key) => __("simplify-toolkit::minecraft.$group.$key"), $keys));
    }

    protected function getHeaderActions(): array
    {
        $server = $this->server();

        return [
            ...($this->hasGame() ? [RestartActions::group()] : []),
            ActionGroup::make([
                Action::make('copyId')->label(__('simplify-toolkit::toolkit.settings_page.copy_id'))->icon('tabler-copy')
                    ->alpineClickHandler('window.navigator.clipboard.writeText('.Js::from($server->uuid).'); new FilamentNotification().title('.Js::from(__('simplify-toolkit::toolkit.settings_page.id_copied')).').success().send()'),
                Action::make('reinstall')->label(trans('server/setting.reinstall.action'))->icon('tabler-refresh')->color('danger')
                    ->visible(fn () => $this->can(SubuserPermission::SettingsReinstall))
                    ->requiresConfirmation()
                    ->modalHeading(trans('server/setting.reinstall.modal'))
                    ->modalDescription(trans('server/setting.reinstall.modal_description'))
                    ->modalSubmitActionLabel(trans('server/setting.reinstall.yes'))
                    ->action(fn () => $this->reinstall()),
            ])->icon('tabler-dots')->color('gray')->tooltip(__('simplify-toolkit::toolkit.settings_page.more')),
            Action::make('save')->label(__('simplify-toolkit::minecraft.save'))->icon('tabler-device-floppy')
                ->action('save')->keyBindings(['mod+s']),
        ];
    }

    public function save(): void
    {
        abort_unless(self::canAccess(), 403);
        $state = $this->form->getState();
        $raw = $this->data ?? [];
        $changed = fn (string $part) => ($raw[$part] ?? null) != ($this->saved[$part] ?? null);
        $failed = false;
        $restart = false;

        if ($changed('server') && ! $this->saveServer((array) ($state['server'] ?? []))) {
            $failed = true;
        }
        if ($changed('game') && $this->hasGame()) {
            if ($this->saveGame((array) ($state['game'] ?? []))) {
                $restart = true;
            } else {
                $failed = true;
            }
        }
        if ($changed('startup') && $this->hasStartup() && ! $this->saveStartup((array) ($raw['startup'] ?? []))) {
            $failed = true;
        }
        foreach (array_keys((array) ($raw['sections'] ?? [])) as $key) {
            if (($raw['sections'][$key] ?? null) != ($this->saved['sections'][$key] ?? null)
                && ! SettingsSections::save($this->server(), (string) $key, (array) ($state['sections'][$key] ?? []))) {
                $failed = true;
            }
        }
        if ($changed('notifications') && $this->hasNotifications()) {
            foreach (ServerUserSettingKey::cases() as $key) {
                user()?->updateServerSetting($this->server(), $key, (bool) ($state['notifications'][$key->value] ?? false));
            }
        }

        if ($failed) {
            // An error in the bag tells Simplify the changes were not saved, so its "Changes made" bar stays.
            $this->addError('data', __('simplify-toolkit::minecraft.not_saved'));

            return;
        }
        $this->saved = $this->data ?? [];
        $restart ? $this->savedNotification(__('simplify-toolkit::minecraft.saved'))
            : Notification::make()->title(__('simplify-toolkit::minecraft.saved'))->success()->send();
    }

    /** @param array<string, mixed> $state */
    private function saveServer(array $state): bool
    {
        $server = $this->server();
        $changes = [];
        $name = trim((string) ($state['name'] ?? $server->name));
        if ($name !== '' && $name !== $server->name && $this->can(SubuserPermission::SettingsRename)) {
            $changes['name'] = $name;
        }
        $description = (string) ($state['description'] ?? $server->description);
        if (config('panel.editable_server_descriptions') && $description !== (string) $server->description && $this->can(SubuserPermission::SettingsDescription)) {
            $changes['description'] = $description;
        }
        if ($changes === []) {
            return true;
        }
        $before = $server->only(array_keys($changes));
        try {
            $server->forceFill($changes)->saveOrFail();
        } catch (Throwable $error) {
            report($error);
            Notification::make()->title(trans('server/setting.server_info.failed'))->body($error->getMessage())->danger()->send();

            return false;
        }
        foreach ($changes as $field => $value) {
            Activity::event($field === 'name' ? 'server:settings.rename' : 'server:settings.description')
                ->property(['old' => $before[$field], 'new' => $value])->log();
        }

        return true;
    }

    /** @param array<string, mixed> $state */
    private function saveGame(array $state): bool
    {
        if (! $this->canChangeGame()) {
            return false;
        }
        $values = GameSettings::toProperties($state, $this->current);
        try {
            (new MinecraftFiles($this->server()))->saveProperties($values);
        } catch (Throwable $error) {
            report($error);
            $this->failedNotification();

            return false;
        }
        $this->current = [...$this->current, ...$values];

        return true;
    }

    /**
     * The command, image and variables, with Pelican's own checks: rights, the egg's lists and each variable's rules.
     *
     * @param  array<string, mixed>  $state
     */
    private function saveStartup(array $state): bool
    {
        $server = $this->server();
        $ok = true;
        $command = (string) ($state['command'] ?? $server->startup);
        if ($command !== $server->startup && in_array($command, (array) $server->egg->startup_commands, true) && $this->can(SubuserPermission::StartupUpdate)) {
            $names = array_flip((array) $server->egg->startup_commands);
            Activity::event('server:startup.command')->property(['old' => $names[$server->startup] ?? '', 'new' => $names[$command] ?? ''])->log();
            $server->forceFill(['startup' => $command])->save();
        }
        $image = (string) ($state['image'] ?? $server->image);
        if ($image !== $server->image && in_array($image, (array) $server->egg->docker_images, true) && $this->can(SubuserPermission::StartupDockerImage)) {
            Activity::event('server:startup.image')->property(['old' => $server->image, 'new' => $image])->log();
            $server->forceFill(['image' => $image])->save();
        }
        if (! $this->can(SubuserPermission::StartupUpdate)) {
            return $ok;
        }
        foreach ((array) ($state['variables'] ?? []) as $item) {
            $row = ServerVariable::query()->where('server_id', $server->id)->whereHas('variable')->find((int) ($item['id'] ?? 0));
            if (! $row instanceof ServerVariable || ! $row->variable->user_viewable || ! $row->variable->user_editable) {
                continue;
            }
            $value = $item['variable_value'] ?? '';
            $value = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
            if ($value === (string) $row->variable_value) {
                continue;
            }
            $validator = Validator::make(['variable_value' => $value], ['variable_value' => $row->variable->rules]);
            if ($validator->fails()) {
                Notification::make()->title(trans('server/startup.validation_fail', ['variable' => $row->variable->name]))
                    ->body(implode(', ', $validator->errors()->all()))->danger()->send();
                $ok = false;

                continue;
            }
            Activity::event('server:startup.edit')
                ->property(['variable' => $row->variable->env_variable, 'old' => $row->variable_value, 'new' => $value])->log();
            $row->forceFill(['variable_value' => $value])->save();
        }

        return $ok;
    }

    public function reinstall(): void
    {
        abort_unless($this->can(SubuserPermission::SettingsReinstall), 403);
        try {
            app(ReinstallServerService::class)->handle($this->server());
        } catch (Throwable $error) {
            report($error);
            Notification::make()->title(trans('server/setting.reinstall.notification_fail'))->body($error->getMessage())->danger()->send();

            return;
        }
        Activity::event('server:settings.reinstall')->log();
        Notification::make()->title(trans('server/setting.reinstall.notification_start'))->success()->send();
        $this->redirect(Console::getUrl());
    }

    public function acceptEula(): void
    {
        abort_unless($this->canChangeGame(), 403);
        try {
            (new MinecraftFiles($this->server()))->acceptEula();
        } catch (Throwable $error) {
            report($error);
            $this->failedNotification();

            return;
        }
        $this->eulaAccepted = true;
        $this->savedNotification(__('simplify-toolkit::minecraft.eula_saved'));
    }

    public function saveIcon(mixed $picture): void
    {
        abort_unless($this->canChangeGame(), 403);
        $file = is_array($picture) ? reset($picture) : $picture;
        $png = $file instanceof TemporaryUploadedFile ? ServerIcon::fromImage((string) $file->get()) : null;
        if ($png === null) {
            Notification::make()->title(__('simplify-toolkit::minecraft.icon_not_a_picture'))->danger()->send();

            return;
        }
        $this->writeIcon($png);
    }

    public function useModpackIcon(): void
    {
        abort_unless($this->canChangeGame(), 403);
        $server = $this->server();
        $variables = [];
        foreach ($server->variables as $variable) {
            $variables[$variable->env_variable] = (string) ($variable->server_value ?? $variable->default_value);
        }
        $url = ServerIcon::modpackIconUrl(ServerKind::forServer($server)['modpack'], $variables);
        $png = $url !== null ? ServerIcon::fromImage((string) Web::get($url)) : null;
        if ($png === null) {
            Notification::make()->title(__('simplify-toolkit::minecraft.icon_no_modpack'))->warning()->send();

            return;
        }
        $this->writeIcon($png);
    }

    public function removeIcon(): void
    {
        abort_unless($this->canChangeGame(), 403);
        try {
            (new MinecraftFiles($this->server()))->removeIcon();
        } catch (Throwable $error) {
            report($error);
            $this->failedNotification();

            return;
        }
        $this->icon = '';
        $this->savedNotification(__('simplify-toolkit::minecraft.icon_removed'));
    }

    private function writeIcon(string $png): void
    {
        try {
            (new MinecraftFiles($this->server()))->saveIcon($png);
        } catch (Throwable $error) {
            report($error);
            $this->failedNotification();

            return;
        }
        $this->icon = ServerIcon::dataUrl($png);
        $this->savedNotification(__('simplify-toolkit::minecraft.icon_saved'));
    }

    private function failedNotification(): void
    {
        Notification::make()->title(__('simplify-toolkit::minecraft.not_saved'))->body(__('simplify-toolkit::minecraft.unreachable'))->danger()->send();
    }

    /** Minecraft settings take effect on the next start, so the notification offers a restart to whoever may restart. */
    private function savedNotification(string $title): void
    {
        RestartReminder::mark($this->server());
        $restart = $this->can(SubuserPermission::ControlRestart);
        Notification::make()->title($title)->body(__('simplify-toolkit::minecraft.restart_help'))->success()
            ->actions($restart ? [Action::make('restart')->label(__('simplify-toolkit::minecraft.restart'))->button()->dispatch('simplify-toolkit-restart')] : [])
            ->send();
    }

    #[On('simplify-toolkit-restart')]
    public function restart(): void
    {
        abort_unless($this->can(SubuserPermission::ControlRestart), 403);
        RestartReminder::restart($this->server());
        Notification::make()->title(__('simplify-toolkit::minecraft.restarting'))->success()->send();
    }
}
