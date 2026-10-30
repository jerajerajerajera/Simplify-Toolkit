<?php

return [
    'title' => 'Automatic backups',
    'help' => 'A failed backup never replaces a successful recovery point.',
    'enabled' => 'Automatic backups enabled',
    'interval' => 'Interval · minutes',
    'points' => 'Recovery points',
    'only_used' => 'Skip while the server has not run',
    'only_used_help' => 'An offline server that has not started since its last backup is not backed up again.',
    'no_storage' => 'Backup storage is not configured yet. Ask an administrator to configure a backup host in Pelican.',
    'paused' => 'Automatic backups are paused in Simplify settings.',
    'uses' => 'Uses automatic settings: :interval, with :points recovery points:unused.',
    'every_hours' => 'every hour|every :count hours',
    'every_minutes' => 'every minute|every :count minutes',
    'skipped_unused' => 'skipped while the server has not run',
    'name' => 'Automatic',
    'panel' => 'Automatic backups',
    'panel_help' => 'Run configured backup policies through the existing Pelican scheduler. Off pauses policies without deleting them.',
    'default_interval' => 'Backup interval for new servers (minutes)',
    'default_interval_help' => 'Starting interval for automatic backups of newly created servers.',
    'default_points' => 'Recovery points for new servers',
    'default_points_help' => 'How many successful automatic backups are kept.',
];
