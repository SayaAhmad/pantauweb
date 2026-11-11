# DEPLOYMENT - Panduan Produksi Pantau

Panduan ini menjelaskan cara menjalankan Pantau di server produksi: web server, PHP-FPM, cron scheduler, queue worker, backup, dan prosedur rilis. Semua contoh memakai Debian/Ubuntu dan path `/var/www/pantau`; sesuaikan bila memakai distro atau path lain.

## 1. Arsitektur runtime

Ada tiga proses yang harus hidup bersamaan:

| Proses | Fungsi | Dijalankan oleh |
|---|---|---|
| nginx + PHP-FPM | Melayani halaman Blade dan ekspor CSV | systemd (bawaan paket) |
| `schedule:run` | Menjadwalkan pemeriksaan tiap menit, rollup 00:15, prune 02:00 | cron |
| `queue:work` | Mengeksekusi pemeriksaan HTTP/TCP dan notifikasi | supervisor atau systemd |

Tanpa queue worker, scheduler tetap berjalan tetapi tidak ada pemeriksaan yang dieksekusi. Dashboard akan menampilkan "cek terakhir" yang makin lama makin usang.

## 2. Prasyarat

- PHP 8.3 atau lebih baru dengan ekstensi `curl`, `openssl`, `pdo`, `mbstring`, `xml`, `ctype`, `json`, `tokenizer`, `fileinfo`, `bcmath`.
- Composer 2.
- Node.js 20+ dan npm, hanya untuk membangun aset saat rilis.
- nginx.
- Basis data: SQLite (default), MySQL 8, atau PostgreSQL 15+.
- supervisor (`apt install supervisor`) atau systemd.
- SQLite CLI bila memakai SQLite (`apt install sqlite3`), untuk backup dan WAL.

## 3. Checklist environment produksi

Salin `.env.example` menjadi `.env`, lalu sesuaikan minimal nilai berikut.

```dotenv
APP_NAME=Pantau
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pantau.example.com

APP_LOCALE=id
APP_TIMEZONE=UTC

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pantau
DB_USERNAME=pantau
DB_PASSWORD=ganti-dengan-sandi-kuat

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

LOG_CHANNEL=stack
LOG_LEVEL=warning

ADMIN_NAME="Admin Pantau"
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=ganti-sebelum-seed

MONITOR_RETENTION_DAYS=30
MONITOR_DISPLAY_TIMEZONE=Asia/Jakarta
MONITOR_SSL_WARNING_DAYS=30
MONITOR_ALERT_EMAIL=ops@example.com
```

Catatan:

- `APP_TIMEZONE` harus tetap `UTC`. Aplikasi menyimpan waktu dalam UTC dan mengubahnya ke `Asia/Jakarta` hanya saat ditampilkan.
- `APP_DEBUG=false` wajib. Dengan `true`, stack trace dan isi `.env` bisa bocor ke browser.
- `QUEUE_CONNECTION` dan `CACHE_STORE` memakai `database` agar tidak perlu Redis pada instalasi kecil.
- `MONITOR_ALERT_EMAIL` boleh dikosongkan bila notifikasi email tidak dipakai. Notifikasi in-app tetap berjalan.
- Isi variabel `MAIL_*` bila memakai email. Default `MAIL_MAILER=log` hanya menulis ke `storage/logs/laravel.log`.

## 4. Langkah rilis pertama

```bash
sudo mkdir -p /var/www/pantau
# unggah atau clone kode ke /var/www/pantau

cd /var/www/pantau
composer install --no-dev --optimize-autoloader --no-interaction
cp .env.example .env
php artisan key:generate
```

Isi `.env` seperti bagian 3, lalu:

```bash
php artisan migrate --force
php artisan db:seed --class=DatabaseSeeder --force
npm ci
npm run build
php artisan optimize
```

`DatabaseSeeder` membuat satu akun admin dari `ADMIN_EMAIL` dan `ADMIN_PASSWORD`, plus dua server dan lima monitor contoh. Hapus monitor contoh dari UI sebelum dipakai sungguhan.

Untuk mengganti kata sandi admin di kemudian hari, ubah `ADMIN_PASSWORD` di `.env`, jalankan `php artisan config:clear`, lalu ulangi `php artisan db:seed --class=DatabaseSeeder --force`. Jangan menjalankan seeder dengan `ADMIN_PASSWORD` kosong karena nilai default `password` akan dipakai.

Atur izin tulis:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache database
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Bila memakai SQLite, pastikan folder `database` dan file `database/database.sqlite` dapat ditulis `www-data`, lalu aktifkan WAL agar web dan queue worker tidak saling mengunci:

```bash
sudo -u www-data sqlite3 database/database.sqlite "PRAGMA journal_mode=WAL;"
sudo -u www-data sqlite3 database/database.sqlite "PRAGMA busy_timeout=5000;"
```

Untuk produksi dengan trafik tulis rutin, MySQL atau PostgreSQL lebih disarankan daripada SQLite.

## 5. nginx dan PHP-FPM

Contoh `/etc/nginx/sites-available/pantau`:

```nginx
server {
    listen 80;
    server_name pantau.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name pantau.example.com;

    root /var/www/pantau/public;
    index index.php;
    charset utf-8;
    client_max_body_size 2m;

    ssl_certificate     /etc/letsencrypt/live/pantau.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/pantau.example.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(?:css|js|woff2?|png|svg|ico)$ {
        expires 30d;
        access_log off;
        try_files $uri =404;
    }
}
```

Aktifkan dan muat ulang:

```bash
sudo ln -s /etc/nginx/sites-available/pantau /etc/nginx/sites-enabled/pantau
sudo nginx -t
sudo systemctl reload nginx
```

Setelan PHP-FPM yang disarankan pada `/etc/php/8.5/fpm/php.ini`:

```ini
memory_limit = 256M
max_execution_time = 120
expose_php = Off
```

Kuota 256 MB memberi ruang aman. Uji beban pada 50 monitor x 30 hari menunjukkan dashboard, daftar monitor, dan laporan tetap berjalan dengan `memory_limit=64M`, jadi 256 MB cukup lega untuk pertumbuhan data.

## 6. Scheduler (cron)

Cukup satu entri cron. Jangan menjalankan `schedule:work` dan cron bersamaan.

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * cd /var/www/pantau && /usr/bin/php artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

Jadwal yang terdaftar:

```bash
php artisan schedule:list
```

| Waktu | Perintah | Fungsi |
|---|---|---|
| tiap menit | `monitors:dispatch` | Enqueue monitor yang jatuh tempo |
| 00:15 | `monitors:rollup` | Bangun rollup harian dari data mentah |
| 02:00 | `monitors:prune` | Hapus data mentah melewati retensi |

Semua tugas memakai `withoutOverlapping`, sehingga aman bila satu putaran lebih lambat dari jadwal.

## 7. Queue worker

### Opsi A: supervisor

`/etc/supervisor/conf.d/pantau.conf`:

```ini
[program:pantau-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/pantau/artisan queue:work --sleep=3 --tries=1 --timeout=130
directory=/var/www/pantau
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/pantau/storage/logs/worker.log
stopwaitsecs=135
```

`numprocs=1` cukup untuk MVP. Tambah hanya bila antrean menumpuk, dan hindari banyak worker pada SQLite. `--timeout=130` berada di atas timeout job pemeriksaan (120 detik).

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status pantau-queue:*
```

### Opsi B: systemd

`/etc/systemd/system/pantau-queue.service`:

```ini
[Unit]
Description=Pantau queue worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/pantau
ExecStart=/usr/bin/php /var/www/pantau/artisan queue:work --sleep=3 --tries=1 --timeout=130
Restart=always
RestartSec=5
KillSignal=SIGTERM
TimeoutStopSec=135

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now pantau-queue
sudo systemctl status pantau-queue
```

Setelah setiap rilis, jalankan `php artisan queue:restart` agar worker memuat kode baru.

## 8. Backup dan restore

Yang perlu dibackup:

- Basis data.
- File `.env` (berisi `APP_KEY` dan kredensial). Simpan terpisah dengan izin `600`.
- Lampiran unggahan bila ada. MVP belum menyimpan berkas pengguna, jadi `storage/app` cukup untuk bukti QA.

Jangan menyalin file SQLite saat aplikasi sedang menulis. Gunakan `.backup`.

### SQLite

```bash
mkdir -p /var/backups/pantau
sudo -u www-data sqlite3 /var/www/pantau/database/database.sqlite \
  ".backup '/var/backups/pantau/pantau-$(date +%F-%H%M).sqlite'"
sudo find /var/backups/pantau -name 'pantau-*.sqlite' -mtime +14 -delete
```

### MySQL

`/etc/pantau/backup.env` (izin `600`) berisi kredensial, lalu cron:

```bash
mysqldump --single-transaction --quick --defaults-extra-file=/etc/pantau/backup.env pantau \
  | gzip > /var/backups/pantau/pantau-$(date +%F-%H%M).sql.gz
find /var/backups/pantau -name 'pantau-*.sql.gz' -mtime +14 -delete
```

### PostgreSQL

```bash
pg_dump -Fc -h 127.0.0.1 -U pantau pantau > /var/backups/pantau/pantau-$(date +%F-%H%M).dump
find /var/backups/pantau -name 'pantau-*.dump' -mtime +14 -delete
```

Jadwalkan backup setelah prune (misalnya 02:30) dan salin hasilnya ke mesin lain atau object storage. Backup yang hanya ada di server yang sama tidak melindungi dari kehilangan disk.

### Restore

```bash
# SQLite
sudo systemctl stop pantau-queue
sudo -u www-data cp /var/backups/pantau/pantau-2026-11-11-0230.sqlite database/database.sqlite
sudo systemctl start pantau-queue

# MySQL
gunzip < /var/backups/pantau/pantau-2026-11-11-0230.sql.gz | mysql -u pantau -p pantau

# PostgreSQL
pg_restore -d pantau --clean --if-exists /var/backups/pantau/pantau-2026-11-11-0230.dump
```

Setelah restore, jalankan `php artisan migrate --force` bila skema backup lebih lama, lalu `php artisan queue:restart`.

## 9. Pemantauan operasional

- `storage/logs/laravel.log` untuk error aplikasi.
- `storage/logs/worker.log` untuk kegagalan job.
- `php artisan queue:failed` dan `php artisan queue:retry all`.
- `php artisan schedule:list` untuk memastikan jadwal terdaftar.
- Dashboard: bila "cek terakhir" semua monitor lebih tua dari dua kali interval, scheduler atau worker berhenti.
- `df -h` untuk ruang disk. Perkiraan: 50 monitor dengan interval 5 menit menghasilkan sekitar 432.000 baris per 30 hari, sekitar 66 MB pada SQLite.
- Rotasi log: gunakan `logrotate` untuk `storage/logs/*.log` dengan `copytruncate` atau sinyal reload, maksimal 14 hari.

## 10. Prosedur upgrade

```bash
cd /var/www/pantau
php artisan down --retry=60
# unggah rilis baru
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
```

Bila `.env` berubah, jalankan `php artisan optimize` ulang agar konfigurasi ter-cache memakai nilai baru. Untuk kembali ke rilis sebelumnya, pulihkan kode dan backup basis data, lalu jalankan `php artisan optimize:clear`, `php artisan migrate --force`, dan `php artisan queue:restart`.

## 11. Keamanan

- Wajib HTTPS. Cookie sesi Laravel otomatis memakai `Secure` bila `APP_URL` memakai `https`.
- Satu akun admin, tanpa registrasi dan tanpa peran tambahan. Ganti kata sandi default sebelum aplikasi dapat diakses publik.
- `APP_DEBUG=false`, `APP_ENV=production`.
- Batasi akses panel dengan firewall bila hanya dipakai dari jaringan internal, misalnya `allow 10.0.0.0/8; deny all;` pada blok `location /`.
- Rate limit login sudah aktif (5 percobaan per menit per email dan IP).
- Jangan menaruh `.env`, `database/`, atau `storage/` di dalam document root. Document root harus `public/`.
- Periksa pembaruan dependensi secara berkala dengan `composer audit` dan `npm audit`.

## 12. Verifikasi pasca-deploy

```bash
curl -I https://pantau.example.com/login          # harus 200
php artisan schedule:list                          # tiga tugas terjadwal
php artisan queue:failed                           # kosong
sudo supervisorctl status pantau-queue:*           # RUNNING
```

Lalu masuk ke aplikasi dan pastikan:

1. Dashboard menampilkan monitor dan "cek terakhir" bertambah dalam beberapa menit.
2. Tombol "Periksa sekarang" mengubah status monitor.
3. Halaman Laporan dapat dibuka untuk rentang 7 hari dan 90 hari.
4. Ekspor CSV terunduh dan berisi baris sesuai filter.
5. Setelah satu siklus pemeriksaan, tidak ada error baru di `storage/logs/laravel.log`.

## 13. Catatan kompatibilitas basis data

Rilis ini diuji penuh pada SQLite, MySQL 8.4.6, dan PostgreSQL 16.9. Query laporan memakai agregasi SQL dan ekspresi tanggal khusus per driver (`sqlite`, `mysql`, `pgsql`). Meski begitu, tetap jalankan migrasi, seed, dan seluruh halaman pada salinan staging sebelum produksi, terutama bila memakai versi atau konfigurasi server yang berbeda.
