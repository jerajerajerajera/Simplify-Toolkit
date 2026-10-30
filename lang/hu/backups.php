<?php

return [
    'title' => 'Automatikus biztonsági mentések',
    'help' => 'Egy sikertelen mentés soha nem cserél le egy sikeres visszaállítási pontot.',
    'enabled' => 'Automatikus biztonsági mentések bekapcsolva',
    'interval' => 'Időköz · perc',
    'points' => 'Visszaállítási pontok',
    'only_used' => 'Kihagyás, amíg a szerver nem futott',
    'only_used_help' => 'Egy offline szerverről, amely az utolsó mentés óta nem indult el, nem készül újabb mentés.',
    'no_storage' => 'A biztonsági mentések tárolója még nincs beállítva. Kérd meg egy adminisztrátort, hogy állítson be backup hostot a Pelicanben.',
    'paused' => 'Az automatikus biztonsági mentések szüneteltetve vannak a Simplify-beállításokban.',
    'uses' => 'Automatikus beállítások: :interval, :points helyreállítási ponttal:unused.',
    'every_hours' => 'óránként|:count óránként',
    'every_minutes' => 'percenként|:count percenként',
    'skipped_unused' => 'kihagyva, amíg a szerver nem futott',
    'name' => 'Automatikus',
    'panel' => 'Automatikus biztonsági mentések',
    'panel_help' => 'A beállított mentési szabályok futtatása a meglévő Pelican-ütemezőn keresztül. Kikapcsolva szünetelteti a szabályokat törlés nélkül.',
    'default_interval' => 'Mentési időköz új szerverekhez (perc)',
    'default_interval_help' => 'Az újonnan létrehozott szerverek automatikus mentéseinek kezdő időköze.',
    'default_points' => 'Helyreállítási pontok új szerverekhez',
    'default_points_help' => 'Hány sikeres automatikus biztonsági mentés marad meg.',
];
