<?php

return [
    'title' => 'Copias de seguridad automáticas',
    'help' => 'Una copia fallida nunca reemplaza un punto de recuperación correcto.',
    'enabled' => 'Copias automáticas activadas',
    'interval' => 'Intervalo · minutos',
    'points' => 'Puntos de recuperación',
    'only_used' => 'Saltar mientras el servidor no ha funcionado',
    'only_used_help' => 'Un servidor apagado que no ha arrancado desde su última copia no se vuelve a copiar.',
    'no_storage' => 'El almacenamiento de copias aún no está configurado. Pide a un administrador que configure un host de copias en Pelican.',
    'paused' => 'Las copias automáticas están en pausa en Ajustes de Simplify.',
    'uses' => 'Usa ajustes automáticos: :interval, con :points puntos de recuperación:unused.',
    'every_hours' => 'cada hora|cada :count horas',
    'every_minutes' => 'cada minuto|cada :count minutos',
    'skipped_unused' => 'se salta mientras el servidor no ha funcionado',
    'name' => 'Automática',
    'panel' => 'Copias de seguridad automáticas',
    'panel_help' => 'Ejecutar los planes de copias configurados con el scheduler de Pelican existente. Desactivado pausa los planes sin borrarlos.',
    'default_interval' => 'Intervalo de copias para servidores nuevos (minutos)',
    'default_interval_help' => 'Intervalo inicial de copias automáticas de los servidores recién creados.',
    'default_points' => 'Puntos de recuperación para servidores nuevos',
    'default_points_help' => 'Cuántas copias automáticas correctas se conservan.',
];
