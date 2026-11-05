<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Simplify\Toolkit\Services\Keys;

/**
 * The CurseForge row on Simplify's Connections page (add-on API 6): a person's own CurseForge key, which goes before
 * the panel's key. It saves only from its own popups, only a key CurseForge accepts, and never shows the key again.
 */
final class CurseForgeConnection extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public function connectAction(): Action
    {
        return Action::make('connect')
            ->label(__('simplify-toolkit::settings.own_key'))
            ->button()
            ->color('gray')
            ->icon('tabler-key')
            ->modalDescription(fn () => __('simplify-toolkit::settings.'.(Keys::has(Keys::CURSEFORGE, user()) ? 'own_key_saved' : 'own_key_help')))
            ->schema([
                TextInput::make('key')->label(__('simplify-toolkit::settings.key'))->password()->autocomplete('new-password')->required()->maxLength(200),
            ])
            ->modalSubmitActionLabel(__('simplify-toolkit::settings.save'))
            ->action(function (array $data): void {
                $user = user();
                abort_if($user === null, 403);
                $key = trim((string) $data['key']);
                if (! Keys::curseForgeWorks($key)) {
                    Notification::make()->title(__('simplify-toolkit::settings.key_refused'))->danger()->send();

                    return;
                }
                Keys::save(Keys::CURSEFORGE, $user, $key);
                Notification::make()->title(__('simplify-toolkit::settings.key_works'))->success()->send();
            });
    }

    public function disconnectAction(): Action
    {
        return Action::make('disconnect')
            ->label(__('simplify-toolkit::settings.remove'))
            ->icon('tabler-unlink')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn () => Keys::has(Keys::CURSEFORGE, user()))
            ->action(function (): void {
                abort_if(user() === null, 403);
                Keys::forget(Keys::CURSEFORGE, user());
                Notification::make()->title(__('simplify-toolkit::settings.removed'))->success()->send();
            });
    }

    public function render(): View
    {
        $saved = Keys::has(Keys::CURSEFORGE, user());

        return view('simplify-toolkit::livewire.curseforge-connection', [
            'saved' => $saved,
            'line' => __('simplify-toolkit::settings.'.($saved ? 'own_key_saved' : 'own_key_help')),
        ]);
    }
}
