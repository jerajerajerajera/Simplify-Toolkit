<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plugins and mods installed through Simplify Toolkit, so the list shows their site, version and page.
        // Kept on removal (down() keeps the data), so installing again finds the table.
        if (! Schema::hasTable('simplify_toolkit_packages')) {
            Schema::create('simplify_toolkit_packages', function (Blueprint $table) {
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
        }

        // What could not be read from the egg, chosen by the server's owner: the mod loader and Minecraft version.
        // Kept on removal (down() keeps the data), so installing again finds the table.
        if (! Schema::hasTable('simplify_toolkit_servers')) {
            Schema::create('simplify_toolkit_servers', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('server_id')->unique();
                $table->string('loader', 20)->default('');
                $table->string('minecraft', 40)->default('');
                $table->timestamps();

                $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            });
        }
    }

    /** Removing Toolkit keeps its data (Pelican rolls back a plugin's migrations on removal), so a reinstall finds every setting again. */
    public function down(): void {}
};
