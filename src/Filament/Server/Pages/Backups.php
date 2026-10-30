<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Filament\Server\Pages;

use App\Enums\SubuserPermission;
use App\Models\Server;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Simplify\Toolkit\Services\AutomaticBackups;

/**
 * Which automatic backups a server gets. Everyone who sees the server's backups sees the plan; only root admins
 * change it, as the backups use the panel's storage.
 *
 * @property Schema $form
 */
final class Backups extends Page implements HasSchemas
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-history';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'automatic-backups';

    protected string $view = 'simplify-toolkit::pages.form';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $server = Filament::getTenant();

        return $server instanceof Server && $server->backup_limit > 0 && (bool) user()?->can(SubuserPermission::BackupRead, $server);
    }

    public static function getNavigationLabel(): string
    {
        return __('simplify-toolkit::backups.title');
    }

    public function getTitle(): string
    {
        return __('simplify-toolkit::backups.title');
    }

    public function server(): Server
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return $server;
    }

    public function canChange(): bool
    {
        return (bool) user()?->isRootAdmin();
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);
        $this->form->fill(AutomaticBackups::for($this->server())->only(['enabled', 'every_minutes', 'keep', 'only_when_used']));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->disabled(fn () => ! $this->canChange())->components([
            Section::make(__('simplify-toolkit::backups.title'))->description(__('simplify-toolkit::backups.help'))->columns(2)->schema([
                Text::make(__('simplify-toolkit::backups.paused'))->color('warning')->columnSpanFull()
                    ->visible(fn () => ! AutomaticBackups::panel()->enabled),
                Text::make(__('simplify-toolkit::backups.no_storage'))->color('warning')->columnSpanFull()
                    ->visible(fn () => ! AutomaticBackups::storage($this->server())),
                Text::make(fn () => self::summary($this->data ?? []))->columnSpanFull(),
                Toggle::make('enabled')->label(__('simplify-toolkit::backups.enabled'))->live()->columnSpanFull(),
                TextInput::make('every_minutes')->label(__('simplify-toolkit::backups.interval'))->live(onBlur: true)
                    ->integer()->minValue(30)->maxValue(10080)->required(),
                TextInput::make('keep')->label(__('simplify-toolkit::backups.points'))->live(onBlur: true)
                    ->integer()->minValue(1)->maxValue(100)->required(),
                Toggle::make('only_when_used')->label(__('simplify-toolkit::backups.only_used'))->live()
                    ->helperText(__('simplify-toolkit::backups.only_used_help'))->columnSpanFull(),
            ]),
        ]);
    }

    /** "Every 6 hours, with 2 recovery points, skipped while the server has not run." */
    public static function summary(array $plan): string
    {
        if (! ($plan['enabled'] ?? false)) {
            return '';
        }
        $minutes = max(1, (int) ($plan['every_minutes'] ?? 360));
        $every = $minutes % 60 === 0
            ? trans_choice('simplify-toolkit::backups.every_hours', intdiv($minutes, 60), ['count' => intdiv($minutes, 60)])
            : trans_choice('simplify-toolkit::backups.every_minutes', $minutes, ['count' => $minutes]);

        return __('simplify-toolkit::backups.uses', [
            'interval' => $every, 'points' => (int) ($plan['keep'] ?? 2),
            'unused' => ($plan['only_when_used'] ?? false) ? ', '.__('simplify-toolkit::backups.skipped_unused') : '',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')->hiddenLabel()->icon('tabler-device-floppy')->tooltip(__('simplify-toolkit::minecraft.save'))
                ->action('save')->keyBindings(['mod+s'])->visible(fn () => $this->canChange()),
        ];
    }

    public function save(): void
    {
        abort_unless($this->canChange(), 403);
        AutomaticBackups::save($this->server(), $this->form->getState());
        Notification::make()->title(__('simplify-toolkit::minecraft.saved'))->success()->send();
    }
}
