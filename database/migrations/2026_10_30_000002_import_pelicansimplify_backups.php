<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * PelicanSimplify 0.7.0 kept a backup plan per server (simplify/backups/<uuid>.json) and the values for new servers in
 * performance.json. Servers keep their plan; a plan already made here stays. The old files are only read.
 */
return new class extends Migration
{
    public function up(): void
    {
        $disk = Storage::disk('local');
        $now = now();
        $performance = json_decode((string) $disk->get('simplify/performance.json'), true);
        if (is_array($performance) && ! DB::table('simplify_toolkit_backups')->whereNull('server_id')->exists()) {
            DB::table('simplify_toolkit_backups')->insert([
                'server_id' => null, 'enabled' => true, 'every_minutes' => $this->number($performance['backup_interval_minutes'] ?? 360, 30, 10080),
                'keep' => $this->number($performance['backup_retention'] ?? 2, 1, 100), 'only_when_used' => (bool) ($performance['backup_only_when_used'] ?? true),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        foreach ($disk->files('simplify/backups') as $file) {
            $uuid = basename($file, '.json');
            $plan = json_decode((string) $disk->get($file), true);
            $server = DB::table('servers')->where('uuid', $uuid)->value('id');
            if (! is_array($plan) || $server === null || DB::table('simplify_toolkit_backups')->where('server_id', $server)->exists()) {
                continue;
            }
            DB::table('simplify_toolkit_backups')->insert([
                'server_id' => $server, 'enabled' => (bool) ($plan['enabled'] ?? true),
                'every_minutes' => $this->number($plan['interval_minutes'] ?? 360, 30, 10080), 'keep' => $this->number($plan['retention'] ?? 2, 1, 100),
                'only_when_used' => (bool) ($plan['only_when_used'] ?? true), 'last_attempt' => max(0, (int) ($plan['last_attempt'] ?? 0)),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function number(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }

    public function down(): void {}
};
