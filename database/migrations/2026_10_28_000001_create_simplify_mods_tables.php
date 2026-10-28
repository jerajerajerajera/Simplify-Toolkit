<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plugins and mods installed through Simplify Mods, so the list shows their site, version and page.
        Schema::create('simplify_mods_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id');
            $table->string('key', 160);
            $table->string('source', 20);
            $table->string('directory', 20);
            $table->string('filename', 160);
            $table->string('title', 100);
            $table->string('version', 80)->default('');
            $table->string('version_id', 140)->default('');
            $table->string('icon', 500)->default('');
            $table->string('page', 500)->default('');
            $table->boolean('fits')->default(true);
            $table->timestamps();

            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->unique(['server_id', 'key']);
        });

        // What could not be read from the egg, chosen by the server's owner: the mod loader and Minecraft version.
        Schema::create('simplify_mods_servers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id')->unique();
            $table->string('loader', 20)->default('');
            $table->string('minecraft', 40)->default('');
            $table->timestamps();

            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simplify_mods_servers');
        Schema::dropIfExists('simplify_mods_packages');
    }
};
