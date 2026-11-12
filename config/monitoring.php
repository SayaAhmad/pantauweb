<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Retensi hasil pemeriksaan mentah
    |--------------------------------------------------------------------------
    */
    'retention_days' => (int) env('MONITOR_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Alamat email penerima notifikasi insiden (opsional)
    |--------------------------------------------------------------------------
    */
    'alert_email' => env('MONITOR_ALERT_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Zona waktu tampilan
    |--------------------------------------------------------------------------
    */
    'display_timezone' => env('MONITOR_DISPLAY_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Ambang peringatan sertifikat SSL (hari)
    |--------------------------------------------------------------------------
    */
    'ssl_warning_days' => (int) env('MONITOR_SSL_WARNING_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Sinkronisasi otomatis ke git
    |--------------------------------------------------------------------------
    |
    | Saat aktif, scheduler menjalankan `pantau:git-sync` untuk commit dan
    | push perubahan ke remote origin. Biarkan nonaktif di server produksi.
    |
    */
    'git_sync' => [
        'enabled' => (bool) env('GIT_SYNC_ENABLED', false),
        'interval_minutes' => (int) env('GIT_SYNC_INTERVAL_MINUTES', 30),
    ],
];
