<?php

return [
    'title' => 'Automatyczne kopie zapasowe',
    'help' => 'Nieudana kopia nigdy nie zastępuje udanego punktu przywracania.',
    'enabled' => 'Automatyczne kopie włączone',
    'interval' => 'Interwał · minuty',
    'points' => 'Punkty przywracania',
    'only_used' => 'Pomijaj, dopóki serwer nie działał',
    'only_used_help' => 'Serwer offline, który nie był uruchamiany od ostatniej kopii, nie dostaje kolejnej kopii.',
    'no_storage' => 'Magazyn kopii zapasowych nie jest jeszcze skonfigurowany. Poproś administratora o skonfigurowanie hosta kopii w Pelican.',
    'paused' => 'Automatyczne kopie zapasowe są wstrzymane w Ustawieniach Simplify.',
    'uses' => 'Używa ustawień automatycznych: :interval, punkty przywracania: :points:unused.',
    'every_hours' => 'co godzinę|co :count godziny|co :count godzin',
    'every_minutes' => 'co minutę|co :count minuty|co :count minut',
    'skipped_unused' => 'pomijane, dopóki serwer nie działał',
    'name' => 'Automatyczna',
    'panel' => 'Automatyczne kopie zapasowe',
    'panel_help' => 'Uruchamiaj skonfigurowane zasady kopii przez istniejący scheduler Pelican. Wył. wstrzymuje zasady bez ich usuwania.',
    'default_interval' => 'Interwał kopii dla nowych serwerów (minuty)',
    'default_interval_help' => 'Początkowy interwał automatycznych kopii nowo utworzonych serwerów.',
    'default_points' => 'Punkty przywracania dla nowych serwerów',
    'default_points_help' => 'Ile udanych automatycznych kopii jest przechowywanych.',
];
