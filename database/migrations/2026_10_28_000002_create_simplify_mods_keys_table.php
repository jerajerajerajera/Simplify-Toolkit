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
        Schema::create('simplify_mods_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('service', 20);
            $table->text('secret');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['service', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simplify_mods_keys');
    }
};
