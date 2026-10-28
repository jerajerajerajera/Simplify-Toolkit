<?php

declare(strict_types=1);

namespace Simplify\Mods\Filament\Server\Pages;

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
use Simplify\Mods\Services\Keys;
use Simplify\Mods\Services\Packages as ServerPackages;
use Simplify\Mods\Services\ServerKind;
use Simplify\Mods\Services\Sources\Catalog;
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

    protected string $view = 'simplify-mods::pages.packages';

    public string $query = '';

    /** @var list<array<string, mixed>> */
    public array $results = [];

    /** @var list<string> sites that did not answer the last search */
    public array $offline = [];

    public bool $searched = false;

    /** @var list<array<string, mixed>> */
    public array $installed = [];

    /** @var array{kind: string, loader: string, minecraft: string, directory: string, detected: bool} */
    public array $context = ['kind' => '', 'loader' => '', 'minecraft' => '', 'directory' => '', 'detected' => false];

    public bool $unreachable = false;

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

        return __('simplify-mods::packages.'.($kind === 'mods' ? 'mods' : 'plugins'));
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
        $this->refresh();
        if ($this->ready()) {
            $this->search();
        }
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
            $this->installed = $packages->installed();
            $this->unreachable = false;
        } catch (Throwable $error) {
            report($error);
            $this->unreachable = true;
        }
    }

    public function search(): void
    {
        if (! $this->ready()) {
            return;
        }
        $found = (new Catalog(Keys::curseForge()))
            ->search($this->query, $this->context['kind'], $this->context['loader'], $this->context['minecraft']);
        $this->results = $found['items'];
        $this->offline = $found['offline'];
        $this->searched = true;
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
            Notification::make()->title($done)->body(__('simplify-mods::packages.restart_help'))->success()->send();
        } catch (HttpException $refused) {
            Notification::make()->title(__('simplify-mods::packages.not_done'))->body($refused->getMessage())->danger()->send();
        } catch (Throwable $error) {
            report($error);
            Notification::make()->title(__('simplify-mods::packages.not_done'))->body(__('simplify-mods::packages.unreachable'))->danger()->send();
        }
        $this->refresh();
    }

    public function installAction(): Action
    {
        return Action::make('install')
            ->label(fn (array $arguments) => __('simplify-mods::packages.'.(($arguments['fits'] ?? true) ? 'install' : 'install_anyway')))
            ->icon('tabler-download')->size('sm')
            ->color(fn (array $arguments) => ($arguments['fits'] ?? true) ? 'primary' : 'gray')
            ->visible(fn () => $this->can(SubuserPermission::FileCreate) && $this->ready())
            // A release that is not made for this version may still work; installing it is the owner's choice.
            ->requiresConfirmation(fn (array $arguments) => ! ($arguments['fits'] ?? true))
            ->modalHeading(__('simplify-mods::packages.install_anyway'))
            ->modalDescription(fn () => __('simplify-mods::packages.not_for_version', ['version' => $this->context['minecraft']]))
            ->action(function (array $arguments): void {
                $card = collect($this->results)->firstWhere('key', $arguments['key'] ?? '') ?? [];
                $this->attempt(
                    fn () => $this->packages()->install((string) ($arguments['key'] ?? ''), ! ($arguments['fits'] ?? true), $card),
                    __('simplify-mods::packages.installed_title', ['name' => $card['title'] ?? '']),
                );
            });
    }

    public function toggleAction(): Action
    {
        return Action::make('toggle')
            ->iconButton()->color('gray')
            ->icon(fn (array $arguments) => ($arguments['enabled'] ?? true) ? 'tabler-player-pause' : 'tabler-player-play')
            ->tooltip(fn (array $arguments) => __('simplify-mods::packages.'.(($arguments['enabled'] ?? true) ? 'switch_off' : 'switch_on')))
            ->visible(fn () => $this->can(SubuserPermission::FileUpdate))
            ->action(fn (array $arguments) => $this->attempt(
                fn () => $this->packages()->toggle((string) ($arguments['file'] ?? ''), ! ($arguments['enabled'] ?? true)),
                __('simplify-mods::packages.'.(($arguments['enabled'] ?? true) ? 'switched_off' : 'switched_on')),
            ));
    }

    public function removeAction(): Action
    {
        return Action::make('remove')
            ->iconButton()->color('danger')->icon('tabler-trash')
            ->tooltip(__('simplify-mods::packages.remove'))
            ->visible(fn () => $this->can(SubuserPermission::FileDelete))
            ->requiresConfirmation()
            ->modalHeading(__('simplify-mods::packages.remove'))
            ->modalDescription(__('simplify-mods::packages.remove_help'))
            ->action(fn (array $arguments) => $this->attempt(
                fn () => $this->packages()->remove((string) ($arguments['file'] ?? '')),
                __('simplify-mods::packages.removed'),
            ));
    }

    /** Asks for the loader and Minecraft version when the egg does not tell them. */
    public function chooseAction(): Action
    {
        $loaders = $this->context['kind'] === 'plugins' ? ServerKind::PLUGIN_LOADERS : ServerKind::MOD_LOADERS;

        return Action::make('choose')
            ->label(__('simplify-mods::packages.choose'))->icon('tabler-adjustments')
            ->visible(fn () => $this->can(SubuserPermission::StartupUpdate) || $this->can(SubuserPermission::FileUpdate))
            ->fillForm(fn () => ['loader' => $this->context['loader'], 'minecraft' => $this->context['minecraft']])
            ->schema([
                Select::make('loader')->label(__('simplify-mods::packages.loader'))->required()
                    ->options(array_combine($loaders, array_map('ucfirst', $loaders))),
                TextInput::make('minecraft')->label(__('simplify-mods::packages.minecraft'))->required()
                    ->placeholder('1.21.4')->regex('/^\d+\.\d+(\.\d+)?$/'),
            ])
            ->action(function (array $data): void {
                $this->packages()->choose((string) $data['loader'], (string) $data['minecraft']);
                $this->refresh();
                $this->search();
            });
    }
}
