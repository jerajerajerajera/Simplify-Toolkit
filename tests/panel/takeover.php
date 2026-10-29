<?php

// Simplify Toolkit was called Simplify Mods before 1.1.0. Run from the panel folder:
// `php plugins/simplify-toolkit/tests/panel/takeover.php before` makes a Simplify Mods table with a saved key, then
// `php artisan p:plugin:install simplify-toolkit`, then `... takeover.php after` checks the key carried over and
// cleans up, so the smoke test starts without a key.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (($argv[1] ?? '') === 'before') {
    Schema::create('simplify_mods_keys', function (Blueprint $table) {
        $table->id();
        $table->unsignedInteger('user_id')->nullable();
        $table->string('service', 20);
        $table->text('secret');
        $table->timestamps();
    });
    DB::table('simplify_mods_keys')->insert(['service' => 'curseforge', 'secret' => Crypt::encryptString('carried'), 'created_at' => now(), 'updated_at' => now()]);
    echo "Simplify Mods key saved\n";
    exit(0);
}

$row = DB::table('simplify_toolkit_keys')->where('service', 'curseforge')->first();
$ok = $row !== null && Crypt::decryptString($row->secret) === 'carried';
DB::table('simplify_toolkit_keys')->delete();
Schema::drop('simplify_mods_keys');
echo $ok ? "The Simplify Mods key carried over\n" : "FAIL: the Simplify Mods key did not carry over\n";
exit($ok ? 0 : 1);
