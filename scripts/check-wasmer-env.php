<?php

/*
 * Pemeriksa file rahasia Wasmer.
 *
 * Jalankan sebelum `wasmer app secret import`:
 *   php scripts/check-wasmer-env.php .env.wasmer
 *
 * Yang diperiksa:
 * - hanya berisi variabel yang memang boleh jadi secret;
 * - tidak membawa DB_* / APP_ENV / APP_DEBUG / QUEUE_CONNECTION dari .env lokal;
 * - APP_KEY valid untuk cipher Laravel;
 * - ADMIN_EMAIL dan ADMIN_PASSWORD terisi wajar.
 */

$path = $argv[1] ?? '.env.wasmer';

if (! is_file($path)) {
    fwrite(STDERR, "File tidak ditemukan: {$path}\n");
    fwrite(STDERR, "Salin dulu: cp .env.wasmer.example .env.wasmer\n");
    exit(1);
}

/**
 * Variabel yang sudah diatur di app.yaml atau disuntikkan Wasmer.
 * Kalau ikut diunggah sebagai secret, nilainya menimpa konfigurasi platform
 * dan aplikasi akan mencoba konek ke database lokal/Supabase.
 */
$forbidden = [
    'DB_CONNECTION' => 'sudah diatur di app.yaml (mysql)',
    'DB_HOST' => 'disuntikkan Wasmer untuk MySQL terkelola',
    'DB_PORT' => 'disuntikkan Wasmer untuk MySQL terkelola',
    'DB_DATABASE' => 'disuntikkan Wasmer sebagai DB_NAME',
    'DB_NAME' => 'disuntikkan Wasmer untuk MySQL terkelola',
    'DB_USERNAME' => 'disuntikkan Wasmer untuk MySQL terkelola',
    'DB_PASSWORD' => 'disuntikkan Wasmer untuk MySQL terkelola',
    'DB_URL' => 'akan menimpa kredensial MySQL dari Wasmer',
    'DB_SSLMODE' => 'hanya dipakai driver PostgreSQL, tidak relevan di Edge',
    'APP_ENV' => 'sudah diatur di app.yaml (production)',
    'APP_DEBUG' => 'sudah diatur di app.yaml (false)',
    'APP_URL' => 'sesuaikan di app.yaml, bukan di secret',
    'APP_NAME' => 'sudah diatur di app.yaml',
    'APP_MAINTENANCE_DRIVER' => 'sudah diatur di app.yaml',
    'APP_MAINTENANCE_STORE' => 'sudah diatur di app.yaml',
    'LOG_CHANNEL' => 'sudah diatur di app.yaml (stderr)',
    'LOG_STACK' => 'tidak dipakai saat LOG_CHANNEL=stderr',
    'SESSION_DRIVER' => 'sudah diatur di app.yaml (database)',
    'CACHE_STORE' => 'sudah diatur di app.yaml (database)',
    'QUEUE_CONNECTION' => 'sudah diatur di app.yaml (sync)',
    'FILESYSTEM_DISK' => 'sudah diatur di app.yaml (local)',
    'BROADCAST_CONNECTION' => 'tidak dipakai di Edge',
    'REDIS_HOST' => 'tidak ada Redis di Wasmer Edge',
    'REDIS_PORT' => 'tidak ada Redis di Wasmer Edge',
    'REDIS_PASSWORD' => 'tidak ada Redis di Wasmer Edge',
    'REDIS_CLIENT' => 'tidak ada Redis di Wasmer Edge',
    'MEMCACHED_HOST' => 'tidak ada Memcached di Wasmer Edge',
    'AWS_ACCESS_KEY_ID' => 'hanya perlu kalau memakai S3, bukan untuk deploy',
    'AWS_SECRET_ACCESS_KEY' => 'hanya perlu kalau memakai S3, bukan untuk deploy',
    'AWS_DEFAULT_REGION' => 'hanya perlu kalau memakai S3, bukan untuk deploy',
    'AWS_BUCKET' => 'hanya perlu kalau memakai S3, bukan untuk deploy',
    'AWS_USE_PATH_STYLE_ENDPOINT' => 'hanya perlu kalau memakai S3, bukan untuk deploy',
    'GIT_SYNC_ENABLED' => 'tidak dipakai di Edge',
    'GIT_SYNC_INTERVAL_MINUTES' => 'tidak dipakai di Edge',
];

$parsed = [];
$errors = [];
$warnings = [];
$ignored = 0;

$lines = file($path, FILE_IGNORE_NEW_LINES);

foreach ($lines as $number => $line) {
    $lineNumber = $number + 1;

    // Buang BOM dan spasi di awal/akhir.
    $line = trim(preg_replace('/^\xEF\xBB\xBF/', '', $line));

    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    if (! preg_match('/^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $m)) {
        $warnings[] = "baris {$lineNumber}: bukan format KEY=VALUE, diabaikan -> {$line}";
        $ignored++;

        continue;
    }

    $key = $m[1];
    $value = $m[2];

    // Nilai berkutip: buang kutip pembungkus, jangan ubah isinya.
    if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
        $value = substr($value, 1, -1);
    } elseif (strlen($value) >= 2 && $value[0] === "'" && str_ends_with($value, "'")) {
        $value = substr($value, 1, -1);
    } else {
        // Komentar di belakang nilai tanpa kutip.
        $value = trim(preg_replace('/\s+#.*$/', '', $value));
    }

    if ($value !== trim($value)) {
        $warnings[] = "baris {$lineNumber}: nilai {$key} punya spasi di awal/akhir";
    }

    if (str_contains($value, "\r")) {
        $warnings[] = "baris {$lineNumber}: nilai {$key} mengandung karakter CR (line ending Windows)";
    }

    if (isset($parsed[$key])) {
        $warnings[] = "baris {$lineNumber}: {$key} didefinisikan lebih dari sekali, nilai terakhir yang dipakai";
    }

    $parsed[$key] = trim($value);
}

foreach ($parsed as $key => $value) {
    if (isset($forbidden[$key])) {
        $errors[] = "{$key} tidak boleh jadi secret: {$forbidden[$key]}";
    }
}

if ($ignored > 0) {
    $warnings[] = "{$ignored} baris tidak dikenali dan akan dilewati saat import";
}

// APP_KEY wajib: tanpa ini Laravel gagal mengenkripsi session/cookie.
if (! array_key_exists('APP_KEY', $parsed)) {
    $errors[] = 'APP_KEY wajib ada. Buat dengan: php artisan key:generate --show';
} elseif ($parsed['APP_KEY'] === '') {
    $errors[] = 'APP_KEY kosong. Buat dengan: php artisan key:generate --show';
} else {
    $appKey = $parsed['APP_KEY'];

    if (! str_starts_with($appKey, 'base64:')) {
        $errors[] = 'APP_KEY harus diawali "base64:". Buat ulang dengan: php artisan key:generate --show';
    } else {
        $decoded = base64_decode(substr($appKey, 7), true);

        if ($decoded === false) {
            $errors[] = 'APP_KEY bukan base64 yang valid. Buat ulang dengan: php artisan key:generate --show';
        } elseif (strlen($decoded) !== 32) {
            $errors[] = 'APP_KEY harus berisi 32 byte (cipher AES-256-CBC). Panjang sekarang: '.strlen($decoded).' byte.';
        }
    }

    if (isset($parsed['APP_KEY']) && preg_match('/\s/', $appKey)) {
        $errors[] = 'APP_KEY mengandung spasi/newline. Salin ulang tanpa spasi.';
    }
}

// ADMIN_EMAIL / ADMIN_PASSWORD dipakai job pantau-deploy untuk membuat akun pertama.
if (! isset($parsed['ADMIN_EMAIL']) || $parsed['ADMIN_EMAIL'] === '') {
    $errors[] = 'ADMIN_EMAIL wajib ada (email login admin pertama).';
} elseif (! filter_var($parsed['ADMIN_EMAIL'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'ADMIN_EMAIL bukan alamat email yang valid: '.$parsed['ADMIN_EMAIL'];
}

if (! isset($parsed['ADMIN_PASSWORD']) || $parsed['ADMIN_PASSWORD'] === '') {
    $errors[] = 'ADMIN_PASSWORD wajib ada (password login admin pertama).';
} else {
    $password = $parsed['ADMIN_PASSWORD'];

    if ($password === 'ganti-dengan-kata-sandi-kuat') {
        $errors[] = 'ADMIN_PASSWORD masih nilai contoh. Ganti dengan kata sandi sungguhan.';
    } elseif (in_array($password, ['password', 'admin', 'pantau', '12345678'], true)) {
        $errors[] = 'ADMIN_PASSWORD terlalu lemah. Pakai minimal 12 karakter acak.';
    } elseif (strlen($password) < 12) {
        $warnings[] = 'ADMIN_PASSWORD kurang dari 12 karakter. Sebaiknya lebih panjang.';
    }
}

// MAIL_* hanya dikirim kalau memang ingin email insiden aktif.
if (isset($parsed['MONITOR_ALERT_EMAIL']) && $parsed['MONITOR_ALERT_EMAIL'] !== ''
    && ! filter_var($parsed['MONITOR_ALERT_EMAIL'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'MONITOR_ALERT_EMAIL bukan alamat email yang valid.';
}

if (isset($parsed['MAIL_MAILER']) && $parsed['MAIL_MAILER'] === 'sendmail') {
    $warnings[] = 'MAIL_MAILER=sendmail butuh enable_email: true di app.yaml, lalu deploy ulang.';
}

echo "Memeriksa: {$path}\n";

foreach ($parsed as $key => $value) {
    echo isset($forbidden[$key]) ? "  [X] {$key}\n" : "  [v] {$key}\n";
}

if ($errors) {
    fwrite(STDERR, "\nAda masalah yang harus diperbaiki:\n");

    foreach ($errors as $error) {
        fwrite(STDERR, "  - {$error}\n");
    }
}

if ($warnings) {
    echo "\nPeringatan:\n";

    foreach ($warnings as $warning) {
        echo "  - {$warning}\n";
    }
}

if ($errors) {
    echo "\nJangan jalankan 'wasmer app secret import' sebelum semua error di atas hilang.\n";
    exit(1);
}

echo "\nAman diimpor. Lanjutkan dengan:\n";
echo "  wasmer app secret import --from-file={$path}\n";
echo "  wasmer deploy\n";
