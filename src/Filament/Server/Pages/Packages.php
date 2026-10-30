<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Filament\Server\Pages;

use App\Enums\SubuserPermission;
use App\Models\Server;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Simplify\Toolkit\Services\Keys;
use Simplify\Toolkit\Services\Packages as ServerPackages;
use Simplify\Toolkit\Services\ServerKind;
use Simplify\Toolkit\Services\Sources\Catalog;
use Simplify\Toolkit\Services\StartCheck;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Plugins (Paper, Spigot, Velocity …) or mods (Fabric, Forge …) of a Minecraft server: search the sites on the left,
 * the installed files on the right.
 */
final class Packages extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-puzzle';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'plugins';

    protected string $view = 'simplify-toolkit::pages.packages';

    public string $query = '';

    /** @var list<array<string, mixed>> */
    public array $results = [];

    /** @var list<string> sites that did not answer the last search */
    public array $offline = [];

    public bool $searched = false;

    /** How many results are shown; "Show more" adds a page. */
    public int $shown = 10;

    /** @var list<array<string, mixed>> */
    public array $installed = [];

    /** @var array{kind: string, loader: string, minecraft: string, directory: string, detected: bool} */
    public array $context = ['kind' => '', 'loader' => '', 'minecraft' => '', 'directory' => '', 'detected' => false];

    public bool $unreachable = false;

    /** A plugin installed here waits for its first start; the page then checks the log now and then. */
    public bool $watching = false;

    /** False until load() ran: the page opens at once with its boxes, Wings and the plugin sites are asked right after. */
    public bool $loaded = false;

    public static function canAccess(): bool
    {
        $server = Filament::getTenant();

        return $server instanceof Server
            && (bool) user()?->can(SubuserPermission::FileRead, $server)
            && ServerKind::forServer($server)['kind'] !== '';
    }

    public static function getNavigationLabel(): string
    {
        $server = Filament::getTenant();
        $kind = $server instanceof Server ? ServerKind::forServer($server)['kind'] : 'plugins';

        return __('simplify-toolkit::packages.'.($kind === 'mods' ? 'mods' : 'plugins'));
    }

    public function getTitle(): string
    {
        return self::getNavigationLabel();
    }

    public function server(): Server
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return $server;
    }

    private function packages(): ServerPackages
    {
        return ServerPackages::for($this->server());
    }

    private function can(SubuserPermission $permission): bool
    {
        return (bool) user()?->can($permission, $this->server());
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
    }

    /** Called by the page itself (wire:init) once it is on screen. */
    public function load(): void
    {
        $this->checkStart();
        if ($this->ready()) {
            $this->search();
        }
        $this->loaded = true;
    }

    /** Whether the loader and Minecraft version are known, so searching and installing can work. */
    public function ready(): bool
    {
        return $this->context['loader'] !== '' && $this->context['minecraft'] !== '';
    }

    public function refresh(): void
    {
        try {
            $packages = $this->packages();
            $this->context = $packages->context();
            $this->installed = $packages->installed($this->context);
            $this->watching = StartCheck::watching($this->server());
            $this->unreachable = false;
        } catch (Throwable $error) {
            report($error);
            $this->unreachable = true;
        }
    }

    /** Switches off plugins that failed on the last start, and says so. Polled while a plugin is watched. */
    public function checkStart(): void
    {
        foreach (StartCheck::run($this->server()) as $off) {
            Notification::make()->title(__('simplify-toolkit::packages.switched_off_after_start', ['name' => $off['title']]))
                ->body($off['reason'])->warning()->persistent()->send();
        }
        $this->refresh();
    }

    public function search(): void
    {
        if (! $this->ready()) {
            return;
        }
        $found = (new Catalog(Keys::curseForge(user())))
            ->search($this->query, $this->context['kind'], $this->context['loader'], $this->context['minecraft']);
        $this->results = $found['items'];
        $this->shown = 10;
        $this->offline = $found['offline'];
        $this->searched = true;
    }

    public function showMore(): void
    {
        $this->shown += 10;
    }

    public function updatedQuery(): void
    {
        $this->search();
    }

    /** True when the plugin on this card is installed already, by its key or one of the other sites' keys. */
    public function isInstalled(array $card): bool
    {
        $keys = array_filter(array_column($this->installed, 'key'));

        return in_array($card['key'], $keys, true) || array_intersect(array_column($card['also'], 'key'), $keys) !== [];
    }

    /** Runs a change and reports a refusal as a notification instead of an error page. */
    private function attempt(callable $change, string $done): void
    {
        try {
            $change();
            Notification::make()->title($done)->body(__('simplify-toolkit::packages.restart_help'))->success()->send();
        } catch (HttpException $refused) {
            Notification::make()->title(__('simplify-toolkit::packages.not_done'))->body($refused->getMessage())->danger()->send();
        } catch (Throwable $error) {
            report($error);
            Notification::make()->title(__('simplify-toolkit::packages.not_done'))->body(__('simplify-toolkit::packages.unreachable'))->danger()->send();
        }
        $this->refresh();
    }

    public function installAction(): Action
    {
        return Action::make('install')
            ->label(fn (array $arguments) => __('simplify-toolkit::packages.'.(($arguments['fits'] ?? true) ? 'install' : 'install_anyway')))
            ->icon('tabler-download')->size('sm')->button()
            ->color(fn (array $arguments) => ($arguments['fits'] ?? true) ? 'primary' : 'gray')
            ->visible(fn () => $this->can(SubuserPermission::FileCreate) && $this->ready())
            // A release that is not made for this version may still work; installing it is the owner's choice.
            ->requiresConfirmation(fn (array $arguments) => ! ($arguments['fits'] ?? true))
            ->modalHeading(__('simplify-toolkit::packages.install_anyway'))
            ->modalDescription(fn () => __('simplify-toolkit::packages.not_for_version', ['version' => $this->context['minecraft']]))
            ->action(function (array $arguments): void {
                $card = collect($this->results)->firstWhere('key', $arguments['key'] ?? '') ?? [];
                $this->attempt(
                    fn () => $this->packages()->install((string) ($arguments['key'] ?? ''), ! ($arguments['fits'] ?? true), $card),
                    __('simplify-toolkit::packages.installed_title', ['name' => $card['title'] ?? '']),
                );
            });
    }

    public function toggleAction(): Action
    {
        return Action::make('toggle')
            ->iconButton()->color('gray')
            ->icon(fn (array $arguments) => ($arguments['enabled'] ?? true) ? 'tabler-player-pause' : 'tabler-player-play')
            ->tooltip(fn (array $arguments) => __('simplify-toolkit::packages.'.(($arguments['enabled'] ?? true) ? 'switch_off' : 'switch_on')))
            ->visible(fn () => $this->can(SubuserPermission::FileUpdate))
            ->action(fn (array $arguments) => $this->attempt(
                fn () => $this->packages()->toggle((string) ($arguments['file'] ?? ''), ! ($arguments['enabled'] ?? true)),
                __('simplify-toolkit::packages.'.(($arguments['enabled'] ?? true) ? 'switched_off' : 'switched_on')),
            ));
    }

    public function removeAction(): Action
    {
        return Action::make('remove')
            ->iconButton()->color('danger')->icon('tabler-trash')
            ->tooltip(__('simplify-toolkit::packages.remove'))
            ->visible(fn () => $this->can(SubuserPermission::FileDelete))
            ->requiresConfirmation()
            ->modalHeading(__('simplify-toolkit::packages.remove'))
            ->modalDescription(__('simplify-toolkit::packages.remove_help'))
            ->action(fn (array $arguments) => $this->attempt(
                fn () => $this->packages()->remove((string) ($arguments['file'] ?? '')),
                __('simplify-toolkit::packages.removed'),
            ));
    }

    /** A user's own CurseForge key, in its own card: CurseForge results for whoever has one, also when the panel has none. */
    public function ownKeyAction(): Action
    {
        return Action::make('ownKey')
            ->label(__('simplify-toolkit::settings.own_key'))->icon('tabler-key')->color('gray')->button()
            ->modalDescription(fn () => __('simplify-toolkit::settings.'.(Keys::has(Keys::CURSEFORGE, user()) ? 'own_key_saved' : 'own_key_help')))
            ->schema([
                TextInput::make('key')->label(__('simplify-toolkit::settings.key'))->password()->autocomplete('off')->required()->maxLength(200),
            ])
            ->modalSubmitActionLabel(__('simplify-toolkit::settings.save'))
            ->extraModalFooterActions(fn () => Keys::has(Keys::CURSEFORGE, user()) ? [
                Action::make('forgetOwnKey')->label(__('simplify-toolkit::settings.remove'))->color('danger')->cancelParentActions()
                    ->action(function (): void {
                        Keys::forget(Keys::CURSEFORGE, user());
                        Notification::make()->title(__('simplify-toolkit::settings.removed'))->success()->send();
                        $this->search();
                    }),
            ] : [])
            ->action(function (array $data): void {
                $key = trim((string) $data['key']);
                if (! Keys::curseForgeWorks($key)) {
                    Notification::make()->title(__('simplify-toolkit::settings.key_refused'))->danger()->send();

                    return;
                }
                Keys::save(Keys::CURSEFORGE, user(), $key);
                Notification::make()->title(__('simplify-toolkit::settings.key_works'))->success()->send();
                $this->search();
            });
    }

    /** The log lines of a plugin that was switched off after a start. */
    public function offLogAction(): Action
    {
        return Action::make('offLog')
            ->iconButton()->color('warning')->icon('tabler-alert-triangle')
            ->tooltip(__('simplify-toolkit::packages.show_log'))
            ->modalHeading(__('simplify-toolkit::packages.show_log'))
            ->modalContent(fn (array $arguments) => view('simplify-toolkit::components.log-lines', [
                'lines' => $this->packages()->offLines((string) ($arguments['file'] ?? '')),
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel(__('simplify-toolkit::packages.close'));
    }

    /** Asks for the loader and Minecraft version when the egg does not tell them. */
    public function chooseAction(): Action
    {
        $loaders = $this->context['kind'] === 'plugins' ? ServerKind::PLUGIN_LOADERS : ServerKind::MOD_LOADERS;

        return Action::make('choose')
            ->label(__('simplify-toolkit::packages.choose'))->icon('tabler-adjustments')->button()
            ->visible(fn () => $this->can(SubuserPermission::StartupUpdate) || $this->can(SubuserPermission::FileUpdate))
            ->fillForm(fn () => ['loader' => $this->context['loader'], 'minecraft' => $this->context['minecraft']])
            ->schema([
                Select::make('loader')->label(__('simplify-toolkit::packages.loader'))->required()
                    ->options(array_combine($loaders, array_map('ucfirst', $loaders))),
                TextInput::make('minecraft')->label(__('simplify-toolkit::packages.minecraft'))->required()
                    ->placeholder('1.21.4')->regex('/^\d+\.\d+(\.\d+)?$/'),
            ])
            ->action(function (array $data): void {
                $this->packages()->choose((string) $data['loader'], (string) $data['minecraft']);
                $this->refresh();
                $this->search();
            });
    }
}
