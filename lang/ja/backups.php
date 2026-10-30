<?php

return [
    'title' => '自動バックアップ',
    'help' => '失敗したバックアップが成功した復元ポイントを置き換えることはありません。',
    'enabled' => '自動バックアップ有効',
    'interval' => '間隔 · 分',
    'points' => '復元ポイント',
    'only_used' => 'サーバーが動いていない間はスキップ',
    'only_used_help' => '最後のバックアップ以降に起動していないオフラインのサーバーは、再度バックアップされません。',
    'no_storage' => 'バックアップの保存先がまだ設定されていません。Pelican でバックアップホストの設定を管理者に依頼してください。',
    'paused' => '自動バックアップは Simplify 設定で一時停止中です。',
    'uses' => '自動設定を使用: :interval、復元ポイント :points 個:unused。',
    'every_hours' => ':count 時間ごと',
    'every_minutes' => ':count 分ごと',
    'skipped_unused' => 'サーバーが動いていない間はスキップ',
    'name' => '自動',
    'panel' => '自動バックアップ',
    'panel_help' => '設定したバックアップポリシーを既存の Pelican スケジューラーで実行します。オフにすると、ポリシーを削除せずに一時停止します。',
    'default_interval' => '新しいサーバーのバックアップ間隔(分)',
    'default_interval_help' => '新しく作成したサーバーの自動バックアップの初期間隔です。',
    'default_points' => '新しいサーバーの復元ポイント数',
    'default_points_help' => '保持する成功した自動バックアップの数です。',
];
