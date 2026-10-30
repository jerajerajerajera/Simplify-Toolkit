<?php

return [
    'title' => 'Automatiske sikkerhetskopier',
    'help' => 'En mislykket sikkerhetskopi erstatter aldri et vellykket gjenopprettingspunkt.',
    'enabled' => 'Automatiske sikkerhetskopier på',
    'interval' => 'Intervall · minutter',
    'points' => 'Gjenopprettingspunkter',
    'only_used' => 'Hopp over så lenge serveren ikke har kjørt',
    'only_used_help' => 'En frakoblet server som ikke har startet siden forrige sikkerhetskopi, sikkerhetskopieres ikke på nytt.',
    'no_storage' => 'Lagring for sikkerhetskopier er ikke konfigurert ennå. Be en administrator om å konfigurere en backupvert i Pelican.',
    'paused' => 'Automatiske sikkerhetskopier er satt på pause i Simplify-innstillinger.',
    'uses' => 'Bruker automatiske innstillinger: :interval, med :points gjenopprettingspunkter:unused.',
    'every_hours' => 'hver time|hver :count. time',
    'every_minutes' => 'hvert minutt|hvert :count. minutt',
    'skipped_unused' => 'hoppes over så lenge serveren ikke har kjørt',
    'name' => 'Automatisk',
    'panel' => 'Automatiske sikkerhetskopier',
    'panel_help' => 'Kjør konfigurerte policyer for sikkerhetskopier via Pelicans eksisterende scheduler. Av setter policyene på pause uten å slette dem.',
    'default_interval' => 'Intervall for sikkerhetskopier av nye servere (minutter)',
    'default_interval_help' => 'Startintervall for automatiske sikkerhetskopier av nyopprettede servere.',
    'default_points' => 'Gjenopprettingspunkter for nye servere',
    'default_points_help' => 'Hvor mange vellykkede automatiske sikkerhetskopier som beholdes.',
];
