<?php

return [
    'title' => 'Automatische back-ups',
    'help' => 'Een mislukte back-up vervangt nooit een geslaagd herstelpunt.',
    'enabled' => 'Automatische back-ups aan',
    'interval' => 'Interval · minuten',
    'points' => 'Herstelpunten',
    'only_used' => 'Overslaan zolang de server niet heeft gedraaid',
    'only_used_help' => 'Een offline server die sinds de laatste back-up niet is gestart, krijgt geen nieuwe back-up.',
    'no_storage' => 'Back-upopslag is nog niet ingesteld. Vraag een beheerder om een back-uphost in te stellen in Pelican.',
    'paused' => 'Automatische back-ups zijn gepauzeerd in Simplify-instellingen.',
    'uses' => 'Gebruikt automatische instellingen: :interval, met :points herstelpunten:unused.',
    'every_hours' => 'elk uur|elke :count uur',
    'every_minutes' => 'elke minuut|elke :count minuten',
    'skipped_unused' => 'overgeslagen zolang de server niet heeft gedraaid',
    'name' => 'Automatisch',
    'panel' => 'Automatische back-ups',
    'panel_help' => 'Voer ingestelde back-upbeleidsregels uit via de bestaande scheduler van Pelican. Uit pauzeert ze zonder ze te verwijderen.',
    'default_interval' => 'Back-upinterval voor nieuwe servers (minuten)',
    'default_interval_help' => 'Begininterval voor automatische back-ups van nieuwe servers.',
    'default_points' => 'Herstelpunten voor nieuwe servers',
    'default_points_help' => 'Hoeveel geslaagde automatische back-ups worden bewaard.',
];
