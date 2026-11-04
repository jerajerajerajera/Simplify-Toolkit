<?php

// Updating, removing and going back, in a real panel (checks.yml, job "upgrade"). Run from the panel folder:
//   fill   after installing an old release: a root admin, a Minecraft server and every Toolkit setting filled in
//   check  after installing this version over it: every setting is still there
//   open   the admin settings page and the server's Plugins page open (any version, also an older one put back)
//   gone   after removing Toolkit: Pelican's own pages still open, Toolkit's page is gone
//   kept   after installing again: every setting came back (removing keeps Toolkit's data)
use App\Models\Egg;
use App\Models\Role;
use App\Models\Server;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;

// Pelican's footer shows the page's render time.
define('LARAVEL_START', microtime(true));
require getcwd().'/vendor/autoload.php';

/** A fresh panel: Filament keeps the current panel for the whole process, so every page visit gets its own (as in smoke.php). */
function boot(): Application
{
    $app = require getcwd().'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    return $app;
}
boot();

const EMAIL = 'upgrade-toolkit@example.com';

function fail(string $why): never
{
    echo "FAIL $why\n";
    exit(1);
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

/** @return array{0: User, 1: Server} */
function admin(): array
{
    $user = User::query()->where('email', EMAIL)->first() ?? fail('no admin: run "fill" first');
    $server = Server::query()->where('owner_id', $user->id)->first() ?? fail('no server: run "fill" first');

    return [$user, $server];
}

/** @return array<string, mixed> the settings as they are in the database, the same way for every version */
function settings(User $user, Server $server): array
{
    $key = fn (?int $userId) => ($row = DB::table('simplify_toolkit_keys')->where('service', 'curseforge')->where('user_id', $userId)->first())
        ? Crypt::decryptString($row->secret) : null;
    $plan = fn (?int $serverId) => (array) (DB::table('simplify_toolkit_backups')->where('server_id', $serverId)
        ->first(['enabled', 'every_minutes', 'keep', 'only_when_used']) ?? []);

    return [
        'panel_key' => $key(null),
        'own_key' => $key($user->id),
        'panel_plan' => array_map('intval', $plan(null)),
        'server_plan' => array_map('intval', $plan($server->id)),
        'package' => DB::table('simplify_toolkit_packages')->where('server_id', $server->id)->value('title'),
        'server' => (array) (DB::table('simplify_toolkit_servers')->where('server_id', $server->id)->first(['loader', 'minecraft']) ?? []),
    ];
}

const FILLED = [
    'panel_key' => 'kept-panel-key',
    'own_key' => 'kept-own-key',
    'panel_plan' => ['enabled' => 0, 'every_minutes' => 720, 'keep' => 5, 'only_when_used' => 0],
    'server_plan' => ['enabled' => 1, 'every_minutes' => 180, 'keep' => 3, 'only_when_used' => 1],
    'package' => 'Kept',
    'server' => ['loader' => 'paper', 'minecraft' => '1.21.4'],
];

switch ($argv[1] ?? '') {
    case 'fill':
        $user = User::factory()->create(['email' => EMAIL]);
        $user->syncRoles(Role::getRootAdmin());
        $server = Server::factory()->create([
            'owner_id' => $user->id,
            'egg_id' => Egg::factory()->create(['name' => 'Paper', 'features' => ['eula', 'java_version']])->id,
        ]);
        $now = now();
        DB::table('simplify_toolkit_keys')->insert([
            ['user_id' => null, 'service' => 'curseforge', 'secret' => Crypt::encryptString(FILLED['panel_key']), 'created_at' => $now, 'updated_at' => $now],
            ['user_id' => $user->id, 'service' => 'curseforge', 'secret' => Crypt::encryptString(FILLED['own_key']), 'created_at' => $now, 'updated_at' => $now],
        ]);
        // A new server got the panel's plan already; the filled-in values replace both rows.
        DB::table('simplify_toolkit_backups')->whereNull('server_id')->orWhere('server_id', $server->id)->delete();
        DB::table('simplify_toolkit_backups')->insert([
            ['server_id' => null, ...FILLED['panel_plan'], 'created_at' => $now, 'updated_at' => $now],
            ['server_id' => $server->id, ...FILLED['server_plan'], 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('simplify_toolkit_packages')->insert(['server_id' => $server->id, 'key' => 'modrinth:AAAA1111', 'source' => 'modrinth',
            'directory' => '/plugins', 'filename' => 'Kept-1.0.jar', 'title' => FILLED['package'], 'version' => '1.0', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('simplify_toolkit_servers')->insert(['server_id' => $server->id, ...FILLED['server'], 'created_at' => $now, 'updated_at' => $now]);
        if (settings($user, $server) !== FILLED) {
            fail('the old version did not take the settings: '.json_encode(settings($user, $server)));
        }
        echo "PASS every Toolkit setting filled in\n";
        break;
    case 'check':
        [$user, $server] = admin();
        if (($now = settings($user, $server)) !== FILLED) {
            fail('settings changed by the update: '.json_encode($now));
        }
        echo "PASS every setting is still there after the update\n";
        break;
    case 'open':
        [$user, $server] = admin();
        // The server's settings of every version so far: the Settings page, before 2.0 the Minecraft page. Wings is not
        // there, so it shows its offline state.
        $settings = class_exists('Simplify\\Toolkit\\Filament\\Server\\Pages\\ServerSettings')
            ? 'Simplify\\Toolkit\\Filament\\Server\\Pages\\ServerSettings' : 'Simplify\\Toolkit\\Filament\\Server\\Pages\\Minecraft';
        foreach (['/admin/simplify-toolkit', $settings::getUrl(panel: 'server', tenant: $server)] as $url) {
            if (($status = visit($user, $url)->getStatusCode()) !== 200) {
                fail("$url gives HTTP $status");
            }
        }
        echo "PASS the settings page and the server's settings open\n";
        break;
    case 'gone':
        [$user] = admin();
        if (($status = visit($user, '/admin')->getStatusCode()) !== 200) {
            fail("Pelican's admin gives HTTP $status after removing Toolkit");
        }
        if (($status = visit($user, '/admin/simplify-toolkit')->getStatusCode()) !== 404) {
            fail("Toolkit's page still answers with HTTP $status after removing it");
        }
        if (! Schema::hasTable('simplify_toolkit_keys') || ! Schema::hasTable('simplify_toolkit_backups') || ! Schema::hasTable('simplify_toolkit_packages')) {
            fail('removing Toolkit dropped its tables');
        }
        echo "PASS Pelican opens without Toolkit, and Toolkit's data stays\n";
        break;
    case 'kept':
        [$user, $server] = admin();
        $now = settings($user, $server);
        if ($now !== FILLED) {
            fail('removing and installing again lost settings: '.json_encode($now));
        }
        echo "PASS installed again, with every setting\n";
        break;
    default:
        fail('usage: php upgrade.php fill|check|open|gone|kept');
}
exit(0);
