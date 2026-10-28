<?php

// Loads Simplify Mods inside a real Pelican panel. Run from the panel folder after
// `php artisan p:plugin:install simplify-mods`: php plugins/simplify-mods/tests/panel/smoke.php
use App\Models\Egg;
use App\Models\Role;
use App\Models\Server;
use App\Models\User;
use App\Repositories\Daemon\DaemonFileRepository;
use Filament\Facades\Filament;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Http\Client\Response;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Simplify\Mods\Filament\Server\Pages\Minecraft;
use Simplify\Mods\ModsAddon;
use Simplify\Mods\Services\ServerKind;

define('LARAVEL_START', microtime(true));
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

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
    $client = new class(app())
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
}

// Laravel's console bootstrap turns an uncaught error into output with exit code 0, so every error fails here.
set_exception_handler(function (Throwable $e): void {
    echo 'FAIL outside a step: '.get_class($e).': '.substr($e->getMessage(), 0, 400).' ('.basename($e->getFile()).':'.$e->getLine().")\n";
    exit(1);
});
$passed = 0;

$admin = User::where('email', 'smoke-mods@example.com')->first()
    ?? User::factory()->create(['email' => 'smoke-mods@example.com']);
$admin->syncRoles(Role::getRootAdmin());

step('the add-on is tagged for Simplify without loading its class', function () use ($app) {
    $tags = (fn () => $this->tags['simplify.addons'] ?? [])->call($app);
    if (! in_array(ModsAddon::class, $tags, true)) {
        throw new RuntimeException('ModsAddon is not tagged: '.json_encode($tags));
    }
});
step('the translations load', function () {
    if (__('simplify-mods::mods.addon.description') === 'simplify-mods::mods.addon.description') {
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
    if (! str_contains($response->getContent(), __('simplify-mods::minecraft.unreachable'))) {
        throw new RuntimeException('the page does not say the files cannot be reached');
    }
});
step('saving changes only the chosen lines of server.properties', function () use ($admin, $paper, $app) {
    $app->bind(DaemonFileRepository::class, FakeFiles::class);
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
step('other games have no Minecraft page', function () use ($admin) {
    $rust = Server::factory()->create(['owner_id' => $admin->id, 'egg_id' => Egg::factory()->create(['name' => 'Rust', 'features' => []])->id]);
    Filament::setTenant($rust);
    if (Minecraft::canAccess()) {
        throw new RuntimeException('Rust got a Minecraft page');
    }
});
// Every step must have run: a step that never ran is a failure too.
$expected = 7;
if ($passed !== $expected) {
    echo "FAIL $passed of $expected steps passed\n";
    exit(1);
}
echo "All steps passed\n";
exit(0);
