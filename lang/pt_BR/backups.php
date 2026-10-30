<?php

return [
    'title' => 'Backups automáticos',
    'help' => 'Um backup com falha nunca substitui um ponto de recuperação bem-sucedido.',
    'enabled' => 'Backups automáticos ativados',
    'interval' => 'Intervalo · minutos',
    'points' => 'Pontos de recuperação',
    'only_used' => 'Pular enquanto o servidor não tiver rodado',
    'only_used_help' => 'Um servidor offline que não foi iniciado desde o último backup não recebe outro backup.',
    'no_storage' => 'O armazenamento de backup ainda não está configurado. Peça a um administrador para configurar um host de backup no Pelican.',
    'paused' => 'Os backups automáticos estão pausados nas configurações do Simplify.',
    'uses' => 'Usa configurações automáticas: :interval, com :points pontos de recuperação:unused.',
    'every_hours' => 'a cada hora|a cada :count horas',
    'every_minutes' => 'a cada minuto|a cada :count minutos',
    'skipped_unused' => 'pulado enquanto o servidor não tiver rodado',
    'name' => 'Automático',
    'panel' => 'Backups automáticos',
    'panel_help' => 'Executar as políticas de backup configuradas pelo agendador existente do Pelican. Desligado pausa as políticas sem excluí-las.',
    'default_interval' => 'Intervalo de backup para servidores novos (minutos)',
    'default_interval_help' => 'Intervalo inicial dos backups automáticos de servidores recém-criados.',
    'default_points' => 'Pontos de recuperação para servidores novos',
    'default_points_help' => 'Quantos backups automáticos bem-sucedidos são mantidos.',
];
