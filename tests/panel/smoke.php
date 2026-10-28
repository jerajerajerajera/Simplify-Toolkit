<?php

// Loads Simplify Mods inside a real Pelican panel. Run from the panel folder after
// `php artisan p:plugin:install simplify-mods`: php plugins/simplify-mods/tests/panel/smoke.php
use App\Models\Server;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
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
        echo "PASS $what\n";
    } catch (Throwable $e) {
        $failed = true;
        echo "FAIL $what: ".get_class($e).': '.substr($e->getMessage(), 0, 400).' ('.basename($e->getFile()).':'.$e->getLine().")\n";
    }
}

$admin = User::where('email', 'smoke-mods@example.com')->first()
    ?? User::factory()->create(['email' => 'smoke-mods@example.com', 'root_admin' => true]);

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
exit($failed ? 1 : 0);
