<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A plugin is watched from its install until the next start; one that fails then is switched off with its log lines.
        Schema::table('simplify_toolkit_packages', function (Blueprint $table) {
            $table->timestamp('watch_since')->nullable();
            $table->string('off_reason', 240)->default('');
            $table->json('off_lines')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('simplify_toolkit_packages', function (Blueprint $table) {
            $table->dropColumn(['watch_since', 'off_reason', 'off_lines']);
        });
    }
};
