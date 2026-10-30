<?php

return [
    'title' => 'Automatische Backups',
    'help' => 'Ein fehlgeschlagenes Backup ersetzt nie einen erfolgreichen Wiederherstellungspunkt.',
    'enabled' => 'Automatische Backups aktiviert',
    'interval' => 'Intervall · Minuten',
    'points' => 'Wiederherstellungspunkte',
    'only_used' => 'Überspringen, solange der Server nicht gelaufen ist',
    'only_used_help' => 'Ein Offline-Server, der seit seinem letzten Backup nicht gestartet wurde, wird nicht erneut gesichert.',
    'no_storage' => 'Backup-Speicher ist noch nicht eingerichtet. Bitte einen Administrator, in Pelican einen Backup-Host einzurichten.',
    'paused' => 'Automatische Backups sind in den Simplify-Einstellungen pausiert.',
    'uses' => 'Nutzt automatische Einstellungen: :interval, mit :points Wiederherstellungspunkten:unused.',
    'every_hours' => 'jede Stunde|alle :count Stunden',
    'every_minutes' => 'jede Minute|alle :count Minuten',
    'skipped_unused' => 'übersprungen, solange der Server nicht gelaufen ist',
    'name' => 'Automatisch',
    'panel' => 'Automatische Backups',
    'panel_help' => 'Eingerichtete Backup-Richtlinien über den vorhandenen Scheduler von Pelican ausführen. Aus pausiert die Richtlinien, ohne sie zu löschen.',
    'default_interval' => 'Backup-Intervall für neue Server (Minuten)',
    'default_interval_help' => 'Startintervall für automatische Backups neu erstellter Server.',
    'default_points' => 'Wiederherstellungspunkte für neue Server',
    'default_points_help' => 'Wie viele erfolgreiche automatische Backups aufbewahrt werden.',
];
