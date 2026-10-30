<?php

return [
    'title' => 'Automatiske backups',
    'help' => 'En mislykket backup erstatter aldrig et vellykket gendannelsespunkt.',
    'enabled' => 'Automatiske backups slået til',
    'interval' => 'Interval · minutter',
    'points' => 'Gendannelsespunkter',
    'only_used' => 'Spring over, så længe serveren ikke har kørt',
    'only_used_help' => 'En offline server, der ikke er startet siden sin sidste backup, tages der ikke backup af igen.',
    'no_storage' => 'Backuplager er ikke konfigureret endnu. Bed en administrator om at konfigurere en backupvært i Pelican.',
    'paused' => 'Automatiske backups er sat på pause i Simplify-indstillinger.',
    'uses' => 'Bruger automatiske indstillinger: :interval, med :points gendannelsespunkter:unused.',
    'every_hours' => 'hver time|hver :count. time',
    'every_minutes' => 'hvert minut|hvert :count. minut',
    'skipped_unused' => 'springes over, så længe serveren ikke har kørt',
    'name' => 'Automatisk',
    'panel' => 'Automatiske backups',
    'panel_help' => 'Kør konfigurerede backuppolitikker via Pelicans eksisterende scheduler. Fra sætter politikker på pause uden at slette dem.',
    'default_interval' => 'Backupinterval for nye servere (minutter)',
    'default_interval_help' => 'Startinterval for automatiske backups af nyoprettede servere.',
    'default_points' => 'Gendannelsespunkter for nye servere',
    'default_points_help' => 'Hvor mange vellykkede automatiske backups der gemmes.',
];
