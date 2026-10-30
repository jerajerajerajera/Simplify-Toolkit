<?php

return [
    'title' => 'Otomatik yedekler',
    'help' => 'Başarısız bir yedek, başarılı bir kurtarma noktasının yerini asla almaz.',
    'enabled' => 'Otomatik yedekler etkin',
    'interval' => 'Aralık · dakika',
    'points' => 'Kurtarma noktaları',
    'only_used' => 'Sunucu çalışmadığı sürece atla',
    'only_used_help' => 'Son yedeğinden bu yana başlamamış çevrimdışı bir sunucu yeniden yedeklenmez.',
    'no_storage' => 'Yedek depolaması henüz yapılandırılmadı. Bir yöneticiden Pelican\'da bir yedekleme sunucusu yapılandırmasını iste.',
    'paused' => 'Otomatik yedekler Simplify ayarlarında duraklatıldı.',
    'uses' => 'Otomatik ayarlar kullanılır: :interval, :points kurtarma noktası:unused.',
    'every_hours' => 'her :count saatte bir',
    'every_minutes' => 'her :count dakikada bir',
    'skipped_unused' => 'sunucu çalışmadığı sürece atlanır',
    'name' => 'Otomatik',
    'panel' => 'Otomatik yedekler',
    'panel_help' => 'Yapılandırılmış yedekleme politikalarını mevcut Pelican zamanlayıcısıyla çalıştır. Kapalı, politikaları silmeden duraklatır.',
    'default_interval' => 'Yeni sunucular için yedekleme aralığı (dakika)',
    'default_interval_help' => 'Yeni oluşturulan sunucuların otomatik yedekleri için başlangıç aralığı.',
    'default_points' => 'Yeni sunucular için kurtarma noktaları',
    'default_points_help' => 'Kaç başarılı otomatik yedeğin saklanacağı.',
];
