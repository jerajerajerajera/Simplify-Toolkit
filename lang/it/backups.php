<?php

return [
    'title' => 'Backup automatici',
    'help' => 'Un backup non riuscito non sostituisce mai un punto di ripristino riuscito.',
    'enabled' => 'Backup automatici attivi',
    'interval' => 'Intervallo · minuti',
    'points' => 'Punti di ripristino',
    'only_used' => 'Salta finché il server non ha girato',
    'only_used_help' => 'Un server offline che non è partito dall\'ultimo backup non viene salvato di nuovo.',
    'no_storage' => 'Lo spazio per i backup non è ancora configurato. Chiedi a un amministratore di configurare un host di backup in Pelican.',
    'paused' => 'I backup automatici sono in pausa in Impostazioni di Simplify.',
    'uses' => 'Usa le impostazioni automatiche: :interval, con :points punti di ripristino:unused.',
    'every_hours' => 'ogni ora|ogni :count ore',
    'every_minutes' => 'ogni minuto|ogni :count minuti',
    'skipped_unused' => 'saltato finché il server non ha girato',
    'name' => 'Automatico',
    'panel' => 'Backup automatici',
    'panel_help' => 'Esegui le regole di backup configurate tramite lo scheduler di Pelican esistente. Spento mette in pausa le regole senza eliminarle.',
    'default_interval' => 'Intervallo di backup per nuovi server (minuti)',
    'default_interval_help' => 'Intervallo iniziale dei backup automatici dei server appena creati.',
    'default_points' => 'Punti di ripristino per nuovi server',
    'default_points_help' => 'Quanti backup automatici riusciti vengono conservati.',
];
