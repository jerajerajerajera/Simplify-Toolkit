<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A plugin is watched from its install until the next start; one that fails then is switched off with its log lines.
        if (Schema::hasColumn('simplify_toolkit_packages', 'watch_since')) {
            return; // Kept on removal; installing again finds the columns.
        }
        Schema::table('simplify_toolkit_packages', function (Blueprint $table) {
            $table->timestamp('watch_since')->nullable();
            $table->string('off_reason', 240)->default('');
            $table->json('off_lines')->nullable();
        });
    }

    /** Removing Toolkit keeps its data (Pelican rolls back a plugin's migrations on removal), so a reinstall finds every setting again. */
    public function down(): void {}
};
