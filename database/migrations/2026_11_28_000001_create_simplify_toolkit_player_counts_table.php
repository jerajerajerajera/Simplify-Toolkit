<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How the server cards count players, per egg: the protocol and the query port. The row with egg_id null holds
        // the panel's own choice: ask the allocation alias when the IP is 0.0.0.0 or a local address.
        if (Schema::hasTable('simplify_toolkit_player_counts')) {
            return;
        }
        Schema::create('simplify_toolkit_player_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('egg_id')->nullable()->unique();
            $table->string('protocol', 32)->default('automatic');
            $table->string('port', 16)->default('game');
            $table->unsignedInteger('port_offset')->default(1);
            $table->string('port_variable', 191)->nullable();
            $table->boolean('use_alias')->default(true);
            $table->timestamps();

            $table->foreign('egg_id')->references('id')->on('eggs')->cascadeOnDelete();
        });
    }

    /** Removing Toolkit keeps its data (Pelican rolls back a plugin's migrations on removal), so a reinstall finds every setting again. */
    public function down(): void {}
};
