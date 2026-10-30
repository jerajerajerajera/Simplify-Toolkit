<?php

return [
    'title' => '自动备份',
    'help' => '失败的备份永远不会替换成功的恢复点。',
    'enabled' => '已启用自动备份',
    'interval' => '间隔 · 分钟',
    'points' => '恢复点',
    'only_used' => '服务器未运行期间跳过',
    'only_used_help' => '自上次备份后未启动过的离线服务器不会再次备份。',
    'no_storage' => '尚未配置备份存储。请让管理员在 Pelican 中配置备份主机。',
    'paused' => '自动备份已在 Simplify 设置中暂停。',
    'uses' => '使用自动设置：:interval，保留 :points 个恢复点:unused。',
    'every_hours' => '每 :count 小时',
    'every_minutes' => '每 :count 分钟',
    'skipped_unused' => '服务器未运行期间跳过',
    'name' => '自动',
    'panel' => '自动备份',
    'panel_help' => '通过现有的 Pelican 调度器运行已配置的备份策略。关闭后会暂停策略，但不会删除它们。',
    'default_interval' => '新服务器的备份间隔（分钟）',
    'default_interval_help' => '新创建服务器的自动备份初始间隔。',
    'default_points' => '新服务器的恢复点',
    'default_points_help' => '保留多少个成功的自动备份。',
];
