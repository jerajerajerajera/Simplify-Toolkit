<?php

return [
    'title' => 'Cadangan otomatis',
    'help' => 'Cadangan yang gagal tidak pernah menggantikan titik pemulihan yang berhasil.',
    'enabled' => 'Cadangan otomatis aktif',
    'interval' => 'Interval · menit',
    'points' => 'Titik pemulihan',
    'only_used' => 'Lewati selama server belum berjalan',
    'only_used_help' => 'Server offline yang belum dijalankan sejak cadangan terakhirnya tidak dicadangkan lagi.',
    'no_storage' => 'Penyimpanan cadangan belum dikonfigurasi. Minta administrator mengonfigurasi host cadangan di Pelican.',
    'paused' => 'Cadangan otomatis dijeda di pengaturan Simplify.',
    'uses' => 'Memakai pengaturan otomatis: :interval, dengan :points titik pemulihan:unused.',
    'every_hours' => 'setiap :count jam',
    'every_minutes' => 'setiap :count menit',
    'skipped_unused' => 'dilewati selama server belum berjalan',
    'name' => 'Otomatis',
    'panel' => 'Cadangan otomatis',
    'panel_help' => 'Jalankan kebijakan cadangan yang dikonfigurasi melalui scheduler Pelican yang ada. Nonaktif menjeda kebijakan tanpa menghapusnya.',
    'default_interval' => 'Interval cadangan untuk server baru (menit)',
    'default_interval_help' => 'Interval awal untuk cadangan otomatis server yang baru dibuat.',
    'default_points' => 'Titik pemulihan untuk server baru',
    'default_points_help' => 'Berapa banyak cadangan otomatis yang berhasil disimpan.',
];
