<?php

return [
    'title' => 'Automatinės atsarginės kopijos',
    'help' => 'Nepavykusi atsarginė kopija niekada nepakeičia sėkmingo atkūrimo taško.',
    'enabled' => 'Automatinės atsarginės kopijos įjungtos',
    'interval' => 'Intervalas · minutės',
    'points' => 'Atkūrimo taškai',
    'only_used' => 'Praleisti, kol serveris neveikė',
    'only_used_help' => 'Neveikiantis serveris, kuris nebuvo paleistas nuo paskutinės atsarginės kopijos, dar kartą nekopijuojamas.',
    'no_storage' => 'Atsarginių kopijų saugykla dar nenustatyta. Paprašykite administratoriaus Pelican nustatyti atsarginių kopijų hostą.',
    'paused' => 'Automatinės atsarginės kopijos pristabdytos Simplify nustatymuose.',
    'uses' => 'Naudojami automatiniai nustatymai: :interval, atkūrimo taškų: :points:unused.',
    'every_hours' => 'kas :count valandą|kas :count valandas|kas :count valandų',
    'every_minutes' => 'kas :count minutę|kas :count minutes|kas :count minučių',
    'skipped_unused' => 'praleista, kol serveris neveikė',
    'name' => 'Automatinė',
    'panel' => 'Automatinės atsarginės kopijos',
    'panel_help' => 'Vykdyti nustatytas atsarginių kopijų politikas per esamą Pelican scheduler. Išjungus politikos pristabdomos jų neištrinant.',
    'default_interval' => 'Atsarginių kopijų intervalas naujiems serveriams (minutės)',
    'default_interval_help' => 'Pradinis automatinių atsarginių kopijų intervalas naujai sukurtiems serveriams.',
    'default_points' => 'Atkūrimo taškai naujiems serveriams',
    'default_points_help' => 'Kiek sėkmingų automatinių atsarginių kopijų išsaugoma.',
];
