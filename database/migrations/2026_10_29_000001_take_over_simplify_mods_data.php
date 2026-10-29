<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Simplify Toolkit was called Simplify Mods before 1.1.0: these tables have the same columns under the new name. */
    private const TABLES = [
        'simplify_mods_packages' => 'simplify_toolkit_packages',
        'simplify_mods_servers' => 'simplify_toolkit_servers',
        'simplify_mods_keys' => 'simplify_toolkit_keys',
    ];

    public function up(): void
    {
        // Installed plugins, chosen loaders and saved keys of Simplify Mods carry over once. Its own tables stay
        // until Simplify Mods is uninstalled.
        foreach (self::TABLES as $old => $new) {
            if (! Schema::hasTable($old) || DB::table($new)->exists()) {
                continue;
            }
            $columns = array_values(array_intersect(Schema::getColumnListing($old), Schema::getColumnListing($new)));
            DB::table($new)->insertUsing($columns, DB::table($old)->select($columns));
        }
    }

    public function down(): void {}
};
