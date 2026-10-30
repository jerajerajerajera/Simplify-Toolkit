<?php

return [
    'title' => 'Automatické zálohy',
    'help' => 'Neúspěšná záloha nikdy nenahradí úspěšný bod obnovy.',
    'enabled' => 'Automatické zálohy zapnuty',
    'interval' => 'Interval · minuty',
    'points' => 'Body obnovy',
    'only_used' => 'Přeskočit, dokud server neběžel',
    'only_used_help' => 'Offline server, který od poslední zálohy neběžel, se znovu nezálohuje.',
    'no_storage' => 'Úložiště záloh ještě není nastaveno. Požádejte správce, ať v Pelican nastaví hostitele záloh.',
    'paused' => 'Automatické zálohy jsou pozastaveny v Nastavení Simplify.',
    'uses' => 'Používá automatická nastavení: :interval, bodů obnovy: :points:unused.',
    'every_hours' => 'každou hodinu|každé :count hodiny|každých :count hodin',
    'every_minutes' => 'každou minutu|každé :count minuty|každých :count minut',
    'skipped_unused' => 'přeskočeno, dokud server neběžel',
    'name' => 'Automatická',
    'panel' => 'Automatické zálohy',
    'panel_help' => 'Spouštět nastavené zásady záloh přes stávající scheduler Pelican. Vyp. zásady pozastaví, aniž by je smazal.',
    'default_interval' => 'Interval záloh pro nové servery (minuty)',
    'default_interval_help' => 'Počáteční interval automatických záloh nových serverů.',
    'default_points' => 'Body obnovy pro nové servery',
    'default_points_help' => 'Kolik úspěšných automatických záloh se uchovává.',
];
