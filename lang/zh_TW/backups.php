<?php

return [
    'title' => '自動備份',
    'help' => '失敗的備份永遠不會替換成功的還原點。',
    'enabled' => '已啟用自動備份',
    'interval' => '間隔 · 分鐘',
    'points' => '還原點',
    'only_used' => '伺服器未執行期間跳過',
    'only_used_help' => '自上次備份後未啟動過的離線伺服器不會再次備份。',
    'no_storage' => '尚未設定備份儲存。請讓管理員在 Pelican 中設定備份主機。',
    'paused' => '自動備份已在 Simplify 設定中暫停。',
    'uses' => '使用自動設定：:interval，保留 :points 個還原點:unused。',
    'every_hours' => '每 :count 小時',
    'every_minutes' => '每 :count 分鐘',
    'skipped_unused' => '伺服器未執行期間跳過',
    'name' => '自動',
    'panel' => '自動備份',
    'panel_help' => '透過現有的 Pelican 排程器執行已設定的備份策略。關閉後會暫停策略，但不會刪除它們。',
    'default_interval' => '新伺服器的備份間隔（分鐘）',
    'default_interval_help' => '新建立伺服器的自動備份初始間隔。',
    'default_points' => '新伺服器的還原點',
    'default_points_help' => '保留多少個成功的自動備份。',
];
