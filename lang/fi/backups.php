<?php

return [
    'title' => 'Automaattiset varmuuskopiot',
    'help' => 'Epäonnistunut varmuuskopio ei koskaan korvaa onnistunutta palautuspistettä.',
    'enabled' => 'Automaattiset varmuuskopiot käytössä',
    'interval' => 'Väli · minuuttia',
    'points' => 'Palautuspisteet',
    'only_used' => 'Ohita, kun palvelin ei ole ollut käynnissä',
    'only_used_help' => 'Pysäytettyä palvelinta, jota ei ole käynnistetty edellisen varmuuskopion jälkeen, ei varmuuskopioida uudelleen.',
    'no_storage' => 'Varmuuskopiotallennusta ei ole vielä määritetty. Pyydä ylläpitäjää määrittämään varmuuskopioisäntä Pelicanissa.',
    'paused' => 'Automaattiset varmuuskopiot on keskeytetty Simplifyn asetuksissa.',
    'uses' => 'Käyttää automaattisia asetuksia: :interval, :points palautuspistettä:unused.',
    'every_hours' => 'tunnin välein|:count tunnin välein',
    'every_minutes' => 'minuutin välein|:count minuutin välein',
    'skipped_unused' => 'ohitetaan, kun palvelin ei ole ollut käynnissä',
    'name' => 'Automaattinen',
    'panel' => 'Automaattiset varmuuskopiot',
    'panel_help' => 'Aja määritetyt varmuuskopiointikäytännöt Pelicanin olemassa olevan ajastimen kautta. Pois keskeyttää käytännöt poistamatta niitä.',
    'default_interval' => 'Varmuuskopioinnin väli uusille palvelimille (minuutteina)',
    'default_interval_help' => 'Automaattisten varmuuskopioiden aloitusväli uusille palvelimille.',
    'default_points' => 'Palautuspisteet uusille palvelimille',
    'default_points_help' => 'Kuinka monta onnistunutta automaattista varmuuskopiota säilytetään.',
];
