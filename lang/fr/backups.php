<?php

return [
    'title' => 'Sauvegardes automatiques',
    'help' => 'Une sauvegarde échouée ne remplace jamais un point de restauration réussi.',
    'enabled' => 'Sauvegardes automatiques activées',
    'interval' => 'Intervalle · minutes',
    'points' => 'Points de restauration',
    'only_used' => 'Ignorer tant que le serveur n’a pas tourné',
    'only_used_help' => 'Un serveur hors ligne qui n’a pas démarré depuis sa dernière sauvegarde n’est pas sauvegardé à nouveau.',
    'no_storage' => 'Le stockage des sauvegardes n’est pas encore configuré. Demandez à un administrateur de configurer un hôte de sauvegarde dans Pelican.',
    'paused' => 'Les sauvegardes automatiques sont en pause dans les paramètres de Simplify.',
    'uses' => 'Utilise les paramètres automatiques : :interval, avec :points points de restauration:unused.',
    'every_hours' => 'toutes les heures|toutes les :count heures',
    'every_minutes' => 'toutes les minutes|toutes les :count minutes',
    'skipped_unused' => 'ignorées tant que le serveur n’a pas tourné',
    'name' => 'Automatique',
    'panel' => 'Sauvegardes automatiques',
    'panel_help' => 'Exécuter les règles de sauvegarde configurées via le planificateur de Pelican existant. Désactivé met les règles en pause sans les supprimer.',
    'default_interval' => 'Intervalle de sauvegarde des nouveaux serveurs (minutes)',
    'default_interval_help' => 'Intervalle de départ des sauvegardes automatiques des serveurs nouvellement créés.',
    'default_points' => 'Points de restauration des nouveaux serveurs',
    'default_points_help' => 'Nombre de sauvegardes automatiques réussies conservées.',
];
