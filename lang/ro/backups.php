<?php

return [
    'title' => 'Copii de rezervă automate',
    'help' => 'O copie de rezervă eșuată nu înlocuiește niciodată un punct de restaurare reușit.',
    'enabled' => 'Copii de rezervă automate activate',
    'interval' => 'Interval · minute',
    'points' => 'Puncte de restaurare',
    'only_used' => 'Omiteți cât timp serverul nu a rulat',
    'only_used_help' => 'Un server offline care nu a pornit de la ultima copie de rezervă nu este salvat din nou.',
    'no_storage' => 'Stocarea copiilor de rezervă nu este încă configurată. Cereți unui administrator să configureze o gazdă pentru copii de rezervă în Pelican.',
    'paused' => 'Copiile de rezervă automate sunt întrerupte în setările Simplify.',
    'uses' => 'Folosește setările automate: :interval, cu :points puncte de restaurare:unused.',
    'every_hours' => 'la fiecare oră|la fiecare :count ore|la fiecare :count de ore',
    'every_minutes' => 'la fiecare minut|la fiecare :count minute|la fiecare :count de minute',
    'skipped_unused' => 'omis cât timp serverul nu a rulat',
    'name' => 'Automat',
    'panel' => 'Copii de rezervă automate',
    'panel_help' => 'Rulați politicile de copii de rezervă configurate prin scheduler-ul Pelican existent. Dezactivat întrerupe politicile fără a le șterge.',
    'default_interval' => 'Intervalul copiilor de rezervă pentru serverele noi (minute)',
    'default_interval_help' => 'Intervalul inițial pentru copiile de rezervă automate ale serverelor nou create.',
    'default_points' => 'Puncte de restaurare pentru serverele noi',
    'default_points_help' => 'Câte copii de rezervă automate reușite sunt păstrate.',
];
