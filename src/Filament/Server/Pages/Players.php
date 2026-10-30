<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Filament\Server\Pages;

use App\Enums\SubuserPermission;
use App\Models\Server;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Simplify\Toolkit\Services\Players as ServerPlayers;
use Simplify\Toolkit\Services\ServerKind;
use Throwable;

/** Whitelist, operators and bans of a Minecraft server, and who is online, as 0.7.0's player management had them. */
final class Players extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-users';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'players';

    protected string $view = 'simplify-toolkit::pages.players';

    /** The player the buttons act on; clicking a name in a list fills it in. */
    public string $name = '';

    /** @var array<string, list<mixed>> */
    public array $lists = [];

    public bool $loaded = false;

    public bool $running = false;

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
        return __('simplify-toolkit::players.title');
    }

    public function getTitle(): string
    {
        return __('simplify-toolkit::players.title');
    }

    public function server(): Server
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return $server;
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
    }

    /** Called by the page itself (wire:init) once it is on screen, and again a moment after a change. */
    public function load(): void
    {
        $players = new ServerPlayers($this->server());
        try {
            $this->running = $players->running();
            $this->lists = $players->lists();
            $this->unreachable = false;
        } catch (Throwable $error) {
            report($error);
            $this->unreachable = true;
        }
        $this->loaded = true;
    }

    public function canAct(): bool
    {
        return $this->running && (bool) user()?->can(SubuserPermission::ControlConsole, $this->server());
    }

    /** One button per command, told apart by its "do" argument; OP, kick and ban ask first. */
    public function playerAction(): Action
    {
        $label = fn (array $arguments) => __('simplify-toolkit::players.actions.'.self::command($arguments));
        $asks = fn (array $arguments) => in_array(self::command($arguments), ['op', 'kick', 'ban'], true);

        return Action::make('player')->label($label)->button()->size('sm')->outlined()
            ->color(fn (array $arguments) => in_array(self::command($arguments), ['kick', 'ban'], true) ? 'danger' : 'gray')
            ->disabled(fn () => ! $this->canAct() || ! ServerPlayers::valid($this->name))
            ->requiresConfirmation($asks)
            ->modalHeading(fn (array $arguments) => __('simplify-toolkit::players.confirm', ['action' => $label($arguments), 'player' => $this->name]))
            ->modalDescription(fn (array $arguments) => __('simplify-toolkit::players.'.(self::command($arguments) === 'op' ? 'op_message' : 'removed_message')))
            ->modalSubmitActionLabel($label)
            ->action(function (array $arguments) use ($label): void {
                abort_unless($this->canAct(), 403);
                try {
                    (new ServerPlayers($this->server()))->act(self::command($arguments), $this->name);
                } catch (Throwable $error) {
                    report($error);
                    Notification::make()->title(__('simplify-toolkit::minecraft.unreachable'))->danger()->send();

                    return;
                }
                Notification::make()->title(__('simplify-toolkit::players.sent', ['action' => $label($arguments)]))->success()->send();
                // The server writes its lists a moment after the command; the page reads them again then.
                $this->dispatch('simplify-toolkit-players-changed');
            });
    }

    /** @param  array<string, mixed>  $arguments */
    private static function command(array $arguments): string
    {
        $action = (string) ($arguments['do'] ?? '');

        return isset(ServerPlayers::COMMANDS[$action]) ? $action : 'whitelist';
    }
}
