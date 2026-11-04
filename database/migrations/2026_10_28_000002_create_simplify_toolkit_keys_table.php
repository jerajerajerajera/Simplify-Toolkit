<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // API keys of outside services, encrypted with the panel's APP_KEY: one for the whole panel (user_id null)
        // and one per user who sets their own.
        // Kept on removal (down() keeps the data), so installing again finds the table.
        if (! Schema::hasTable('simplify_toolkit_keys')) {
            Schema::create('simplify_toolkit_keys', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->nullable();
                $table->string('service', 20);
                $table->text('secret');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['service', 'user_id']);
            });
        }
    }

    /** Removing Toolkit keeps its data (Pelican rolls back a plugin's migrations on removal), so a reinstall finds every setting again. */
    public function down(): void {}
};
