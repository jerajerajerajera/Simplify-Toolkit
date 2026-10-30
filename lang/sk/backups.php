<?php

return [
    'title' => 'Automatické zálohy',
    'help' => 'Neúspešná záloha nikdy nenahradí úspešný bod obnovy.',
    'enabled' => 'Automatické zálohy zapnuté',
    'interval' => 'Interval · minúty',
    'points' => 'Body obnovy',
    'only_used' => 'Preskočiť, kým server nebežal',
    'only_used_help' => 'Offline server, ktorý od poslednej zálohy nebežal, sa znova nezálohuje.',
    'no_storage' => 'Úložisko záloh ešte nie je nastavené. Požiadajte správcu, aby v Pelican nastavil hostiteľa záloh.',
    'paused' => 'Automatické zálohy sú pozastavené v Nastaveniach Simplify.',
    'uses' => 'Používa automatické nastavenia: :interval, bodov obnovy: :points:unused.',
    'every_hours' => 'každú hodinu|každé :count hodiny|každých :count hodín',
    'every_minutes' => 'každú minútu|každé :count minúty|každých :count minút',
    'skipped_unused' => 'preskočené, kým server nebežal',
    'name' => 'Automatická',
    'panel' => 'Automatické zálohy',
    'panel_help' => 'Spúšťať nastavené zásady záloh cez existujúci scheduler Pelican. Vyp. zásady pozastaví bez ich odstránenia.',
    'default_interval' => 'Interval záloh pre nové servery (minúty)',
    'default_interval_help' => 'Počiatočný interval automatických záloh nových serverov.',
    'default_points' => 'Body obnovy pre nové servery',
    'default_points_help' => 'Koľko úspešných automatických záloh sa uchováva.',
];
