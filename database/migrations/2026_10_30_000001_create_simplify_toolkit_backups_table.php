<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Automatic backups per server, and the panel's own row (server_id null): on or paused, and what new servers get.
        if (Schema::hasTable('simplify_toolkit_backups')) {
            return;
        }
        Schema::create('simplify_toolkit_backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id')->nullable()->unique();
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('every_minutes')->default(360);
            $table->unsignedSmallInteger('keep')->default(2);
            $table->boolean('only_when_used')->default(true);
            $table->unsignedInteger('last_attempt')->default(0);
            $table->unsignedInteger('last_active')->default(0);
            $table->unsignedInteger('last_probe')->default(0);
            $table->unsignedInteger('pruned_at')->default(0);
            $table->timestamps();

            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simplify_toolkit_backups');
    }
};
