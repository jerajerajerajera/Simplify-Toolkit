<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Filament\Server\Actions;

use App\Enums\SubuserPermission;
use App\Models\Server;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Simplify\Toolkit\Services\RestartReminder;
use Throwable;

/** The page-head button while saved changes wait for a restart: restart now, in a few minutes, or forget it. */
final class RestartActions
{
    public static function group(): ActionGroup
    {
        return ActionGroup::make([
            Action::make('restartNow')->label(__('simplify-toolkit::restart.now'))->icon('tabler-refresh')
                ->action(function (): void {
                    try {
                        RestartReminder::restart(self::server());
                        Notification::make()->title(__('simplify-toolkit::minecraft.restarting'))->success()->send();
                    } catch (Throwable $error) {
                        report($error);
                        Notification::make()->title(__('simplify-toolkit::minecraft.unreachable'))->danger()->send();
                    }
                }),
            ...array_map(fn (int $minutes) => Action::make('restartIn'.$minutes)->icon('tabler-clock')
                ->label(__('simplify-toolkit::restart.in_minutes', ['minutes' => $minutes]))
                ->action(function () use ($minutes): void {
                    RestartReminder::plan(self::server(), $minutes);
                    Notification::make()->title(__('simplify-toolkit::restart.scheduled'))->success()->send();
                }), RestartReminder::MINUTES),
            Action::make('restartDismiss')->label(__('simplify-toolkit::restart.dismiss'))->icon('tabler-bell-off')
                ->action(function (): void {
                    RestartReminder::forget(self::server());
                    Notification::make()->title(__('simplify-toolkit::restart.dismissed'))->success()->send();
                }),
        ])->button()->color('warning')->icon('tabler-refresh-alert')
            ->label(fn () => __('simplify-toolkit::restart.'.(RestartReminder::get(self::server())['at'] ?? null ? 'scheduled' : 'needed')))
            ->tooltip(fn () => __('simplify-toolkit::restart.'.(RestartReminder::get(self::server())['at'] ?? null ? 'scheduled_help' : 'waiting_help')))
            ->visible(fn () => RestartReminder::get(self::server()) !== null && user()?->can(SubuserPermission::ControlRestart, self::server()));
    }

    private static function server(): Server
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return $server;
    }
}
