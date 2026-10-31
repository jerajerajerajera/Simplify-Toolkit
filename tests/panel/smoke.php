<?php

// Loads Simplify Toolkit inside a real Pelican panel. Run from the panel folder after
// `php artisan p:plugin:install simplify-toolkit`: php plugins/simplify-toolkit/tests/panel/smoke.php
use App\Enums\ContainerStatus;
use App\Facades\Activity;
use App\Models\Egg;
use App\Models\Role;
use App\Models\Server;
use App\Models\User;
use App\Repositories\Daemon\DaemonFileRepository;
use Filament\Facades\Filament;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Http\Client\Response;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Simplify\Core\Addons\Addon;
use Simplify\Toolkit\Filament\Admin\Pages\ToolkitSettings;
use Simplify\Toolkit\Filament\Server\Pages\Backups;
use Simplify\Toolkit\Filament\Server\Pages\Minecraft;
use Simplify\Toolkit\Filament\Server\Pages\Packages;
use Simplify\Toolkit\Filament\Server\Pages\Players;
use Simplify\Toolkit\Models\ApiKey;
use Simplify\Toolkit\Models\Package;
use Simplify\Toolkit\Services\AutomaticBackups;
use Simplify\Toolkit\Services\Keys;
use Simplify\Toolkit\Services\RestartReminder;
use Simplify\Toolkit\Services\ServerKind;
use Simplify\Toolkit\Services\Software;
use Simplify\Toolkit\Services\Web;
use Simplify\Toolkit\ToolkitAddon;

define('LARAVEL_START', microtime(true));
require getcwd().'/vendor/autoload.php';

/**
 * A fresh panel. Filament keeps the current panel for the whole process, so a page of another panel opened after it
 * fails with "Plugin [global-search-modal] is not registered"; every page visit gets its own application.
 */
function boot(): Application
{
    $app = require getcwd().'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    // Laravel's console bootstrap turns an uncaught error into output with exit code 0, so every error fails here.
    set_exception_handler(function (Throwable $e): void {
        echo 'FAIL outside a step: '.get_class($e).': '.substr($e->getMessage(), 0, 400).' ('.basename($e->getFile()).':'.$e->getLine().")\n";
        exit(1);
    });

    return $app;
}
boot();

$failed = false;
function step(string $what, callable $run): void
{
    global $failed;
    try {
        $run();
        $GLOBALS['passed']++;
        echo "PASS $what\n";
    } catch (Throwable $e) {
        $failed = true;
        echo "FAIL $what: ".get_class($e).': '.substr($e->getMessage(), 0, 400).' ('.basename($e->getFile()).':'.$e->getLine().")\n";
    }
}

function visit(User $user, string $url): TestResponse
{
    $client = new class(boot())
    {
        use InteractsWithAuthentication, InteractsWithSession, MakesHttpRequests;

        public function __construct(public $app) {}
    };

    return $client->actingAs($user)->get($url);
}

/** Wings stand-in: files live in memory. */
final class FakeFiles extends DaemonFileRepository
{
    /** @var array<string, string> */
    public static array $files = [];

    public function getContent(string $path, ?int $notLargerThan = null): string
    {
        return self::$files[$path] ?? throw new FileNotFoundException($path);
    }

    public function putContent(string $path, string $content): Response
    {
        self::$files[$path] = $content;

        return new Response(new Psr7Response(204));
    }

    public function deleteFiles(?string $root, array $files): Response
    {
        foreach ($files as $file) {
            unset(self::$files[rtrim((string) $root, '/').'/'.$file]);
        }

        return new Response(new Psr7Response(204));
    }

    public function getDirectory(string $path): array
    {
        $path = '/'.trim($path, '/');
        $entries = [];
        foreach (self::$files as $name => $content) {
            if (dirname($name) === $path) {
                $entries[] = ['name' => basename($name), 'file' => true, 'size' => strlen($content), 'modified' => now()->addMinute()->toIso8601String()];
            }
        }

        return $entries;
    }

    public function createDirectory(string $name, string $path): Response
    {
        return new Response(new Psr7Response(204));
    }

    public function renameFiles(?string $root, array $files): Response
    {
        $root = rtrim('/'.trim((string) $root, '/'), '/');
        foreach ($files as $move) {
            self::$files[$root.'/'.$move['to']] = self::$files[$root.'/'.$move['from']] ?? throw new RuntimeException('no '.$move['from']);
            unset(self::$files[$root.'/'.$move['from']]);
        }

        return new Response(new Psr7Response(204));
    }

    public function pull(string $url, ?string $directory, array $params = []): Response
    {
        self::$files[rtrim((string) $directory, '/').'/'.$params['filename']] = 'downloaded from '.$url;

        return new Response(new Psr7Response(204));
    }
}

$passed = 0;

$admin = User::where('email', 'smoke-mods@example.com')->first()
    ?? User::factory()->create(['email' => 'smoke-mods@example.com']);
$admin->syncRoles(Role::getRootAdmin());

step('the add-on is tagged for Simplify without loading its class', function () {
    $tags = (fn () => $this->tags['simplify.addons'] ?? [])->call(app());
    if (! in_array(ToolkitAddon::class, $tags, true)) {
        throw new RuntimeException('ToolkitAddon is not tagged: '.json_encode($tags));
    }
});
step('the translations load', function () {
    if (__('simplify-toolkit::toolkit.addon.description') === 'simplify-toolkit::toolkit.addon.description') {
        throw new RuntimeException('the text was not found');
    }
});
step('a server is classified', function () use ($admin) {
    $server = Server::query()->first() ?? Server::factory()->create(['owner_id' => $admin->id]);
    $kind = ServerKind::forServer($server);
    if (! is_bool($kind['minecraft'])) {
        throw new RuntimeException('unexpected result: '.json_encode($kind));
    }
});
step('the admin settings page renders', function () use ($admin) {
    $url = ToolkitSettings::getUrl(panel: 'admin');
    $response = visit($admin, $url);
    if ($response->getStatusCode() !== 200 || ! str_contains($response->getContent(), e(__('simplify-toolkit::settings.no_key')))) {
        throw new RuntimeException('HTTP '.$response->getStatusCode().' at '.$url.($response->exception ? ': '.$response->exception->getMessage() : ''));
    }
    // With Simplify the admin page never scrolls: its form gets the window's height and scrolls inside (style audit laag1366).
    if (! str_contains($response->getContent(), '<form wire:submit="save" class="s-fit">')) {
        throw new RuntimeException('the settings form is not marked s-fit');
    }
});
$paper = Server::factory()->create([
    'owner_id' => $admin->id,
    'egg_id' => Egg::factory()->create(['name' => 'Paper', 'features' => ['eula', 'java_version']])->id,
]);
step('the Minecraft page renders while Wings is offline', function () use ($admin, $paper) {
    $url = Minecraft::getUrl(panel: 'server', tenant: $paper);
    $response = visit($admin, $url);
    if ($response->getStatusCode() !== 200) {
        throw new RuntimeException('HTTP '.$response->getStatusCode().' at '.$url.($response->exception ? ': '.$response->exception->getMessage() : ''));
    }
    if (! str_contains($response->getContent(), e(__('simplify-toolkit::minecraft.unreachable')))) {
        throw new RuntimeException('the page does not say the files cannot be reached');
    }
});
step('saving changes only the chosen lines of server.properties', function () use ($admin, $paper) {
    app()->bind(DaemonFileRepository::class, FakeFiles::class);
    FakeFiles::$files = ['/server.properties' => "#Minecraft server properties\nmax-players=20\ndifficulty=easy\nlevel-seed=oak\n"];
    Filament::setCurrentPanel(Filament::getPanel('server'));
    Filament::setTenant($paper);
    Livewire::actingAs($admin)->test(Minecraft::class)
        ->assertSet('data.max_players', 20)
        ->set('data.max_players', 42)->set('data.difficulty', 'hard')->set('data.motd', '§aHello')
        ->call('save')->assertHasNoErrors()
        ->call('acceptEula');
    $file = FakeFiles::$files['/server.properties'];
    foreach (['max-players=42', 'difficulty=hard', 'motd=\\u00a7aHello', 'level-seed=oak', '#Minecraft server properties'] as $line) {
        if (! str_contains($file, $line)) {
            throw new RuntimeException("missing $line in: $file");
        }
    }
    if (trim(FakeFiles::$files['/eula.txt'] ?? '') !== 'eula=true') {
        throw new RuntimeException('the EULA was not accepted');
    }
});
step('the server icon shows and can be removed', function () use ($admin) {
    $picture = imagecreatetruecolor(64, 64);
    ob_start();
    imagepng($picture);
    FakeFiles::$files['/server-icon.png'] = (string) ob_get_clean();
    $page = Livewire::actingAs($admin)->test(Minecraft::class);
    if (! str_starts_with((string) $page->get('icon'), 'data:image/png;base64,')) {
        throw new RuntimeException('the icon is not shown');
    }
    $page->call('removeIcon')->assertSet('icon', '');
    if (isset(FakeFiles::$files['/server-icon.png'])) {
        throw new RuntimeException('the icon was not removed');
    }
});
step('the Players page lists whitelist, operators and bans from their files', function () use ($admin) {
    FakeFiles::$files['/whitelist.json'] = json_encode([['uuid' => 'a1', 'name' => 'Steve'], ['name' => 'bad name!']]);
    FakeFiles::$files['/ops.json'] = json_encode([['uuid' => 'b2', 'name' => 'Alex', 'level' => 4]]);
    $page = Livewire::actingAs($admin)->test(Players::class)->call('load')->assertSet('unreachable', false)->assertSet('running', false);
    $lists = $page->get('lists');
    if (array_column($lists['whitelist'], 'name') !== ['Steve'] || array_column($lists['operators'], 'name') !== ['Alex'] || $lists['banned'] !== []) {
        throw new RuntimeException('lists: '.json_encode($lists));
    }
    // A head that does not load gives way to the user icon underneath (style audit: no broken images).
    $page->assertSee(__('simplify-toolkit::players.offline'))->assertSeeHtml('sm-face')->assertSeeHtml('x-on:error="$el.remove()"');
});
step('a saved change leaves a restart reminder on the card until it is dismissed', function () use ($admin, $paper) {
    Livewire::actingAs($admin)->test(Minecraft::class)->call('save')->assertHasNoErrors();
    // The card's badge reads the same reminder (ToolkitAddon::tileBadges, checked with Simplify further on).
    if (RestartReminder::get($paper) === null) {
        throw new RuntimeException('no reminder after saving: '.json_encode(RestartReminder::get($paper)));
    }
    RestartReminder::plan($paper, 15);
    if ((RestartReminder::get($paper)['at'] ?? 0) < time() + 14 * 60) {
        throw new RuntimeException('the restart was not planned');
    }
    RestartReminder::forget($paper);
    if (RestartReminder::get($paper) !== null) {
        throw new RuntimeException('the reminder stayed');
    }
});
step('a file saved in the file manager of a running Minecraft server leaves the restart reminder, as in 0.7.0', function () use ($admin, $paper) {
    Filament::setTenant($paper);
    $status = "servers.$paper->uuid.status";
    cache()->put($status, ContainerStatus::Running, 60);
    try {
        Activity::event('server:file.write')->property('file', '/plugins/Essentials/config.yml')->log();
        if (RestartReminder::get($paper) === null) {
            throw new RuntimeException('no reminder after saving a file');
        }
        RestartReminder::forget($paper);
        // The Minecraft page writes files too, and says so itself: no second notice. The notice above is still in
        // the session, so that one goes first.
        session()->forget('filament.notifications');
        Livewire::actingAs($admin)->test(Minecraft::class)->call('save')->assertNotNotified(__('simplify-toolkit::restart.file_saved'));
        RestartReminder::forget($paper);
        cache()->put($status, ContainerStatus::Offline, 60);
        Activity::event('server:file.write')->property('file', '/plugins/Essentials/config.yml')->log();
        if (RestartReminder::get($paper) !== null) {
            throw new RuntimeException('a reminder for a server that is not running');
        }
    } finally {
        cache()->forget($status);
        RestartReminder::forget($paper);
    }
});
step('automatic backups: new servers get the panel plan, root admins change it', function () use ($admin, $paper) {
    if (! AutomaticBackups::for($paper)->exists || ! AutomaticBackups::for($paper)->enabled) {
        throw new RuntimeException('the new server has no plan');
    }
    $paper->forceFill(['backup_limit' => 3])->save();
    Livewire::actingAs($admin)->test(Backups::class)
        ->set('data.every_minutes', 720)->set('data.keep', 3)->call('save')->assertHasNoErrors()
        ->assertSee(Backups::summary(['enabled' => true, 'every_minutes' => 720, 'keep' => 3, 'only_when_used' => true]));
    if (AutomaticBackups::for($paper)->every_minutes !== 720) {
        throw new RuntimeException('the plan was not saved');
    }
});
step('other games have no Minecraft page', function () use ($admin) {
    $rust = Server::factory()->create(['owner_id' => $admin->id, 'egg_id' => Egg::factory()->create(['name' => 'Rust', 'features' => []])->id]);
    Filament::setTenant($rust);
    if (Minecraft::canAccess() || Players::canAccess()) {
        throw new RuntimeException('Rust got a Minecraft page');
    }
});
// The plugin sites answer from here, so the steps below need no network.
Web::$transport = fn (string $url, array $headers = []) => json_encode(match (true) {
    str_contains($url, 'api.curseforge.com/v1/games/432') => ($headers['x-api-key'] ?? '') === 'good-key' ? ['data' => ['id' => 432]] : null,
    str_contains($url, 'api.modrinth.com/v2/search') => ['hits' => [
        ['project_id' => 'AAAA1111', 'title' => 'Smoke Edit', 'description' => 'Edits', 'author' => 'Smoke', 'downloads' => 5, 'slug' => 'smoke-edit', 'project_type' => 'plugin', 'versions' => ['1.21.4']],
    ]],
    str_contains($url, 'api.modrinth.com/v2/project/AAAA1111/version') => [
        ['id' => 'ver1', 'version_number' => '1.0', 'version_type' => 'release', 'game_versions' => ['1.21.4'], 'loaders' => ['paper'],
            'files' => [['filename' => 'SmokeEdit-1.0.jar', 'url' => 'https://cdn.modrinth.com/data/AAAA1111/SmokeEdit-1.0.jar', 'primary' => true]]],
    ],
    default => null,
});
step('the Plugins page finds plugins for the version the server runs', function () use ($admin, $paper) {
    FakeFiles::$files = [
        '/version_history.json' => '{"currentVersion":"git-Paper-100 (MC: 1.21.4)"}',
        '/plugins/Chunky-1.4.jar' => 'jar',
    ];
    Filament::setTenant($paper);
    // The page opens with its boxes only; load() (wire:init) fills them.
    $page = Livewire::actingAs($admin)->test(Packages::class)->assertSet('loaded', false)->assertSee('sm-skeleton')
        ->call('load')->assertSet('loaded', true)
        ->assertSet('context.loader', 'paper')->assertSet('context.minecraft', '1.21.4')->assertSet('unreachable', false)
        ->assertSee('Smoke Edit')->assertSee('Chunky');
    if (count($page->get('installed')) !== 1) {
        throw new RuntimeException('installed: '.json_encode($page->get('installed')));
    }
    // On a phone the button in a box's head goes under the title (style audit phone390).
    if (! str_contains(view('simplify-toolkit::assets.pages-css')->render(), '.sm-columns :is(.fi-section-header, .fi-section-header-content):has(> .fi-section-header-after-ctn) { flex-wrap: wrap; }')) {
        throw new RuntimeException('the phone rule for buttons in a box\'s head is missing');
    }
});
step('the search can be limited to one site, as in 0.7.0', function () use ($admin) {
    $sites = Web::$transport;
    $asked = [];
    Web::$transport = function (string $url, array $headers = []) use (&$asked, $sites) {
        $asked[] = $url;

        return $sites($url, $headers);
    };
    try {
        $page = Livewire::actingAs($admin)->test(Packages::class)->call('load')->assertSee('All sites')->assertSee('Hangar')->assertSee('Smoke Edit');
        $asked = [];
        $page->set('site', 'hangar')->assertDontSee('Smoke Edit');
        if ($asked === [] || array_filter($asked, fn (string $url) => ! str_contains($url, 'hangar')) !== []) {
            throw new RuntimeException('not only Hangar was asked: '.json_encode($asked));
        }
        $page->set('site', 'nowhere')->assertSet('site', '')->assertSee('Smoke Edit');
    } finally {
        Web::$transport = $sites;
    }
});
step('installing, switching off and removing a plugin goes through Wings', function () use ($admin, $paper) {
    $page = Livewire::actingAs($admin)->test(Packages::class)->call('load')
        ->callAction('install', arguments: ['key' => 'modrinth:AAAA1111', 'fits' => true]);
    if (! isset(FakeFiles::$files['/plugins/SmokeEdit-1.0.jar']) || ! Package::query()->where('server_id', $paper->id)->where('key', 'modrinth:AAAA1111')->exists()) {
        throw new RuntimeException('not installed: '.json_encode(array_keys(FakeFiles::$files)));
    }
    $page->callAction('toggle', arguments: ['file' => 'SmokeEdit-1.0.jar', 'enabled' => true]);
    if (! isset(FakeFiles::$files['/plugins/SmokeEdit-1.0.jar.disabled'])) {
        throw new RuntimeException('not switched off: '.json_encode(array_keys(FakeFiles::$files)));
    }
    $page->callAction('remove', arguments: ['file' => 'SmokeEdit-1.0.jar.disabled']);
    $backups = array_filter(array_keys(FakeFiles::$files), fn (string $name) => str_starts_with($name, '/.simplify-toolkit-backups/'));
    if (isset(FakeFiles::$files['/plugins/SmokeEdit-1.0.jar.disabled']) || count($backups) !== 1 || Package::query()->where('server_id', $paper->id)->exists()) {
        throw new RuntimeException('not removed to the backup folder: '.json_encode(array_keys(FakeFiles::$files)));
    }
});
step('updating a plugin installs its newest release, and asks first when that is for another version', function () use ($admin, $paper) {
    $sites = Web::$transport;
    $release = fn (string $version, string $minecraft) => ['id' => 'ver'.$version, 'version_number' => $version, 'version_type' => 'release', 'game_versions' => [$minecraft], 'loaders' => ['paper'],
        'files' => [['filename' => "SmokeEdit-$version.jar", 'url' => "https://cdn.modrinth.com/data/AAAA1111/SmokeEdit-$version.jar", 'primary' => true]]];
    $newest = null;
    Web::$transport = function (string $url, array $headers = []) use (&$newest, $sites) {
        return $newest !== null && str_contains($url, 'AAAA1111/version') ? json_encode([$newest]) : $sites($url, $headers);
    };
    try {
        $page = Livewire::actingAs($admin)->test(Packages::class)->call('load')
            ->callAction('install', arguments: ['key' => 'modrinth:AAAA1111', 'fits' => true])
            ->callAction('update', arguments: ['file' => 'SmokeEdit-1.0.jar', 'title' => 'Smoke Edit']);
        if (! isset(FakeFiles::$files['/plugins/SmokeEdit-1.0.jar'])) {
            throw new RuntimeException('the newest release was installed again');
        }
        $newest = $release('2.0', '1.21.4');
        $page->callAction('update', arguments: ['file' => 'SmokeEdit-1.0.jar', 'title' => 'Smoke Edit']);
        if (! isset(FakeFiles::$files['/plugins/SmokeEdit-2.0.jar']) || isset(FakeFiles::$files['/plugins/SmokeEdit-1.0.jar'])
            || Package::query()->where('server_id', $paper->id)->value('version') !== '2.0') {
            throw new RuntimeException('not updated: '.json_encode(array_keys(FakeFiles::$files)));
        }
        $newest = $release('3.0', '1.20.6');
        $page->callAction('update', arguments: ['file' => 'SmokeEdit-2.0.jar', 'title' => 'Smoke Edit'])->assertActionMounted('updateAnyway');
        if (isset(FakeFiles::$files['/plugins/SmokeEdit-3.0.jar'])) {
            throw new RuntimeException('a release for another version was installed without asking');
        }
        $page->callMountedAction();
        if (! isset(FakeFiles::$files['/plugins/SmokeEdit-3.0.jar'])) {
            throw new RuntimeException('Update anyway did not install: '.json_encode(array_keys(FakeFiles::$files)));
        }
        $page->callAction('remove', arguments: ['file' => 'SmokeEdit-3.0.jar']);
    } finally {
        Web::$transport = $sites;
    }
});
step('a plugin that fails on the next start is switched off with its log line', function () use ($admin, $paper) {
    $page = Livewire::actingAs($admin)->test(Packages::class)->call('load')
        ->callAction('install', arguments: ['key' => 'modrinth:AAAA1111', 'fits' => true])
        ->assertSet('watching', true);
    FakeFiles::$files['/logs/latest.log'] = "[12:00:01 INFO]: Starting\n[12:00:02 ERROR]: Could not load 'plugins/SmokeEdit-1.0.jar' in folder 'plugins'\n[12:00:03 INFO]: Done (2.0s)!\n";
    $page->call('checkStart')->assertSet('watching', false);
    $package = Package::query()->where('server_id', $paper->id)->where('key', 'modrinth:AAAA1111')->firstOrFail();
    if (! isset(FakeFiles::$files['/plugins/SmokeEdit-1.0.jar.disabled']) || ! str_starts_with($package->off_reason, 'Could not load') || count((array) $package->off_lines) < 2) {
        throw new RuntimeException('not switched off: '.json_encode([array_keys(FakeFiles::$files), $package->off_reason]));
    }
    $page->callAction('toggle', arguments: ['file' => 'SmokeEdit-1.0.jar.disabled', 'enabled' => false]);
    if ($package->refresh()->off_reason !== '' || $package->watch_since === null) {
        throw new RuntimeException('switching it on again does not watch it again');
    }
});
step('a modpack is one row in the installed list, and its mods only show when asked for', function () use ($admin) {
    $forge = Server::factory()->create(['owner_id' => $admin->id, 'egg_id' => Egg::factory()->create(['name' => 'Forge', 'features' => ['eula']])->id]);
    FakeFiles::$files = [
        '/modrinth.index.json' => json_encode(['formatVersion' => 1, 'game' => 'minecraft', 'name' => 'Smoke Pack', 'versionId' => '2.0']),
        '/mods/create-1.21.1-6.0.4.jar' => 'jar',
        '/mods/jei-1.21.1-19.0.jar' => 'jar',
    ];
    Filament::setTenant($forge);
    Livewire::actingAs($admin)->test(Packages::class)->call('load')
        ->assertSet('modpack.name', 'Smoke Pack')->assertSet('modpack.version', '2.0')->assertSee('Smoke Pack')
        ->assertDontSee('file-create-1.21.1-6.0.4.jar', false)
        ->set('showPack', true)->assertSee('file-create-1.21.1-6.0.4.jar', false)->assertSee('file-jei-1.21.1-19.0.jar', false);
});
step('Simplify Embed gets the software of Minecraft servers, and nothing for other games', function () use ($admin, $paper) {
    $answers = event('simplify.embed.server', [$paper, false]);
    if (! in_array(['game' => 'Minecraft', 'software' => 'Paper'], $answers, true)) {
        throw new RuntimeException('unexpected: '.json_encode($answers));
    }
    $rust = Server::factory()->create(['owner_id' => $admin->id, 'egg_id' => Egg::factory()->create(['name' => 'Rust', 'features' => []])->id]);
    if (array_filter(event('simplify.embed.server', [$rust, true])) !== [] || Software::label($rust) !== '' || Software::label($paper) !== 'Paper') {
        throw new RuntimeException('other games got Minecraft details');
    }
});
step('CurseForge keys are tested, stored encrypted, and a user\'s own key goes first', function () use ($admin) {
    if (Keys::curseForgeWorks('bad-key') || ! Keys::curseForgeWorks('good-key')) {
        throw new RuntimeException('the key test does not tell good from bad');
    }
    Keys::save(Keys::CURSEFORGE, null, 'good-key');
    $raw = (string) ApiKey::query()->toBase()->whereNull('user_id')->value('secret');
    if ($raw === '' || str_contains($raw, 'good-key') || Keys::curseForge($admin) !== 'good-key') {
        throw new RuntimeException('the panel key is not stored encrypted or not used');
    }
    Keys::save(Keys::CURSEFORGE, $admin, 'own-key');
    if (Keys::curseForge($admin) !== 'own-key' || Keys::curseForge(null) !== 'good-key') {
        throw new RuntimeException('the user\'s own key does not go first');
    }
    Keys::forget(Keys::CURSEFORGE, $admin);
    Keys::forget(Keys::CURSEFORGE, null);
    if (Keys::curseForge($admin) !== '' || ApiKey::query()->exists()) {
        throw new RuntimeException('keys were not removed');
    }
});
step('Simplify\'s setup asks for the CurseForge key and saves only a working one', function () use ($admin) {
    if (! class_exists(Addon::class)) {
        require dirname(__DIR__).'/fixtures/Addon.php';
        require dirname(__DIR__).'/fixtures/Button.php';
    }
    auth()->setUser($admin);
    $addon = new ToolkitAddon;
    if (count($addon->setupFields()) !== 1 || $addon->setupState() !== []) {
        throw new RuntimeException('no setup field, or the key is sent back');
    }
    $addon->saveSetup(['curseforge' => 'bad-key']);
    if (Keys::curseForge(null) !== '') {
        throw new RuntimeException('a refused key was saved');
    }
    $addon->saveSetup(['curseforge' => ' good-key ']);
    if (Keys::curseForge(null) !== 'good-key') {
        throw new RuntimeException('the key was not saved');
    }
    Keys::forget(Keys::CURSEFORGE, null);
});
step('servers without plugins or mods have no Plugins page', function () use ($admin) {
    $rust = Server::factory()->create(['owner_id' => $admin->id, 'egg_id' => Egg::factory()->create(['name' => 'Rust', 'features' => []])->id]);
    Filament::setTenant($rust);
    if (Packages::canAccess()) {
        throw new RuntimeException('Rust got a Plugins page');
    }
});
// Every step must have run: a step that never ran is a failure too.
$expected = 22;
if ($passed !== $expected) {
    echo "FAIL $passed of $expected steps passed\n";
    exit(1);
}
echo "All steps passed\n";
exit(0);
