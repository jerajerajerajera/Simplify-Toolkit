<?php

return [
    'title' => 'Automatiska säkerhetskopior',
    'help' => 'En misslyckad säkerhetskopia ersätter aldrig en lyckad återställningspunkt.',
    'enabled' => 'Automatiska säkerhetskopior på',
    'interval' => 'Intervall · minuter',
    'points' => 'Återställningspunkter',
    'only_used' => 'Hoppa över så länge servern inte har körts',
    'only_used_help' => 'En server som är offline och inte har startats sedan sin senaste säkerhetskopia säkerhetskopieras inte igen.',
    'no_storage' => 'Lagring för säkerhetskopior är inte konfigurerad ännu. Be en administratör att konfigurera en backupvärd i Pelican.',
    'paused' => 'Automatiska säkerhetskopior är pausade i Simplify-inställningar.',
    'uses' => 'Använder automatiska inställningar: :interval, med :points återställningspunkter:unused.',
    'every_hours' => 'varje timme|med :count timmars intervall',
    'every_minutes' => 'varje minut|med :count minuters intervall',
    'skipped_unused' => 'hoppas över så länge servern inte har körts',
    'name' => 'Automatisk',
    'panel' => 'Automatiska säkerhetskopior',
    'panel_help' => 'Kör konfigurerade policyer för säkerhetskopior via Pelicans befintliga scheduler. Av pausar policyerna utan att ta bort dem.',
    'default_interval' => 'Intervall för säkerhetskopior av nya servrar (minuter)',
    'default_interval_help' => 'Startintervall för automatiska säkerhetskopior av nyskapade servrar.',
    'default_points' => 'Återställningspunkter för nya servrar',
    'default_points_help' => 'Hur många lyckade automatiska säkerhetskopior som sparas.',
];
