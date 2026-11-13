# Pantau

Aplikasi pemantauan ketersediaan **website**, **port**, dan **layanan server** yang di-host sendiri. Dibangun dengan Laravel 13, Blade, Tailwind CSS v4, Alpine.js, dan Chart.js.

- Cek website secara berkala: kode status HTTP, waktu respons, dan masa berlaku sertifikat SSL.
- Cek port TCP: aktif, tidak aktif, atau pemeriksaan gagal.
- Cek layanan (endpoint kesehatan) dan pengelompokan per server.
- Status terkonfirmasi (ambang 2 kegagalan/keberhasilan berturut-turut) untuk menghindari false alarm.
- Riwayat insiden lengkap: mulai, pulih, durasi, penyebab.
- Dashboard, grafik waktu respons & uptime, laporan yang dapat difilter, ekspor CSV, dan tampilan cetak.
- Retensi data mentah 30 hari + rollup harian permanen.
- Notifikasi in-app dan email opsional.
- Login dengan registrasi publik; akun baru menjadi pengguna biasa, dan admin dapat mengelola akun lewat menu Pengguna.

Dokumen produk: [`docs/PRD.md`](docs/PRD.md) · Glosarium: [`GLOSSARY.md`](GLOSSARY.md) · Keputusan arsitektur: [`docs/adr/`](docs/adr/) · Tracker tugas: [`docs/TASKS.md`](docs/TASKS.md) · Panduan produksi: [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

---

## Kebutuhan

- PHP 8.3+ dengan ekstensi `curl` dan `openssl`
- Composer
- Node.js 20+ dan npm
- SQLite (default), MySQL, atau PostgreSQL

## Instalasi

```bash
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate

# Atur kredensial admin di .env (ADMIN_EMAIL / ADMIN_PASSWORD)
php artisan migrate --seed
npm install
npm run build
```

Jalankan aplikasi:

```bash
php artisan serve
```

Buka `http://localhost:8000`. Akun default dari seeder: `admin@pantau.test` / `password` (ubah sebelum dipakai sungguhan).

## Deploy ke Wasmer Edge

Konfigurasi deploy sudah disiapkan di [`app.yaml`](app.yaml) dan [`wasmer.toml`](wasmer.toml): runtime `php/php-eh` 8.3.404-beta.8, MySQL terkelola, volume `/app/storage`, dan scheduler lewat Wasmer Jobs.

Runtime `php/php` sengaja tidak dipakai: build itu tidak memuat `pdo_mysql` maupun `curl`, sehingga tidak bisa terhubung ke MySQL dan pemeriksaan HTTP kehilangan cURL.

Panduan langkah demi langkah (termasuk secrets, migrasi otomatis, dan troubleshooting): [`docs/DEPLOY_WASMER.md`](docs/DEPLOY_WASMER.md).

```bash
composer install
npm ci && npm run build
cp .env.wasmer.example .env.wasmer   # isi APP_KEY, ADMIN_EMAIL, ADMIN_PASSWORD
# sesuaikan owner, name, dan APP_URL di app.yaml
wasmer deploy
php scripts/check-wasmer-env.php .env.wasmer
wasmer app secret import --from-file=.env.wasmer
wasmer deploy
```

Jangan mengunggah `.env` lokal. File itu berisi `DB_*` Supabase, `APP_ENV=local`, dan `QUEUE_CONNECTION=database` yang akan menimpa konfigurasi Wasmer. `.env.wasmer` hanya boleh berisi `APP_KEY`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, dan opsional `MAIL_*`.

Catatan: `QUEUE_CONNECTION` di Wasmer memakai `sync` karena Edge tidak menyediakan queue worker persisten. Pemeriksaan dijalankan oleh job `artisan monitors:dispatch` setiap menit, langsung dari Wasmer Jobs.

## Menjalankan pemeriksaan otomatis

Pemeriksaan dijalankan oleh scheduler + queue worker. Untuk pengembangan, buka tiga terminal:

```bash
# 1. Scheduler: meng-enqueue pemeriksaan yang jatuh tempo setiap menit
php artisan schedule:work

# 2. Queue worker: mengeksekusi pemeriksaan
php artisan queue:work --tries=1

# 3. Web server
php artisan serve
```

Di produksi, daftarkan cron:

```
* * * * * cd /path/pantau && php artisan schedule:run >> /dev/null 2>&1
```

dan jalankan queue worker lewat supervisor/systemd.

### Perintah yang tersedia

| Perintah | Fungsi |
|---|---|
| `monitors:dispatch` | Enqueue pemeriksaan untuk monitor yang jatuh tempo (dijadwalkan tiap menit). |
| `monitors:rollup --days=2` | Bangun rollup harian dari data mentah (dijadwalkan 00:15). |
| `monitors:prune` | Hapus data mentah melewati retensi (dijadwalkan 02:00). |
| `servers:agent-token {server}` | Terbitkan token agent untuk sebuah server. |
| `pantau:git-sync` | Commit dan push perubahan ke `origin` (dijadwalkan bila `GIT_SYNC_ENABLED=true`). |

### Sinkronisasi otomatis ke git

Aktifkan di `.env` untuk membuat scheduler melakukan commit dan push berkala:

```dotenv
GIT_SYNC_ENABLED=true
GIT_SYNC_INTERVAL_MINUTES=30
```

Jalankan scheduler (`php artisan schedule:work`) dan pastikan `git`, remote `origin`, serta kredensial push (SSH key atau token) sudah siap di mesin tersebut.

Perintahnya juga bisa dipanggil manual:

```bash
php artisan pantau:git-sync                          # commit semua perubahan lalu push
php artisan pantau:git-sync --message="fix: ..."     # pesan commit kustom
php artisan pantau:git-sync --dry-run                # lihat perubahan saja
php artisan pantau:git-sync --no-push                # commit tanpa push
```

Perintah ini tidak berjalan saat merge/rebase sedang berlangsung dan tidak menyentuh file yang diabaikan `.gitignore`. Jangan aktifkan di server produksi: riwayat commit sebaiknya tetap berasal dari mesin pengembang.

Pemeriksaan bisa juga dijalankan manual dari UI ("Periksa sekarang") atau dari Tinker:

```bash
php artisan monitors:dispatch
php artisan queue:work --once
```

## Data demo

Untuk mengisi dashboard dengan riwayat 72 jam (berguna saat evaluasi tampilan):

```bash
php artisan db:seed --class=DemoDataSeeder
```

Perintah ini **menghapus** seluruh hasil pemeriksaan dan insiden yang ada, lalu membuat data contoh untuk monitor hasil `DatabaseSeeder`. Jangan jalankan pada data sungguhan.

## Variabel lingkungan

| Variabel | Default | Keterangan |
|---|---|---|
| `ADMIN_NAME` | `Admin Pantau` | Nama akun admin hasil seeder. |
| `ADMIN_EMAIL` | `admin@pantau.test` | Email login admin. |
| `ADMIN_PASSWORD` | `password` | Kata sandi awal admin. |
| `MONITOR_RETENTION_DAYS` | `30` | Retensi hasil pemeriksaan mentah. |
| `MONITOR_DISPLAY_TIMEZONE` | `Asia/Jakarta` | Zona waktu tampilan (penyimpanan tetap UTC). |
| `MONITOR_SSL_WARNING_DAYS` | `30` | Ambang peringatan sertifikat SSL. |
| `MONITOR_ALERT_EMAIL` | kosong | Penerima email insiden. Kosong = notifikasi email nonaktif. |

Email memakai konfigurasi `MAIL_*` Laravel. Secara default `MAIL_MAILER=log`, sehingga email ditulis ke `storage/logs/laravel.log`.

## Agent metrik (fase 2, endpoint sudah tersedia)

```bash
php artisan servers:agent-token "Server Produksi"
```

```bash
curl -X POST http://localhost:8000/api/agent/metrics \
  -H "X-Agent-Token: <token>" \
  -H "Content-Type: application/json" \
  -d '{"cpu_percent":12.5,"ram_percent":43.1,"disk_percent":61,"load_average":0.42}'
```

Metrik tersimpan di `server_metrics`; antarmuka grafiknya belum termasuk MVP.

## Pengujian

```bash
php artisan test
```

Test mencakup mesin status dan insiden, perhitungan uptime/series, autentikasi, serta filter laporan dan ekspor CSV.

## Struktur singkat

```
app/
├── Console/Commands/        # dispatch, rollup, prune, token agent
├── Enums/                   # MonitorType, MonitorStatus, CheckStatus
├── Http/Controllers/        # dashboard, monitor, insiden, laporan, server, agent
├── Jobs/CheckMonitorJob.php # eksekusi satu pemeriksaan
├── Monitoring/              # checker HTTP/TCP, mesin status, metrik
├── Models/                  # Monitor, CheckResult, Incident, Server, ...
└── Support/Format.php       # format tanggal/durasi/angka Indonesia
docs/                        # PRD dan ADR
resources/views/             # Blade (layout, dashboard, monitor, laporan, ...)
```

## Catatan operasional

- Tanpa queue worker, pemeriksaan tidak akan berjalan meski scheduler aktif.
- Monitor baru dijadwalkan pada `next_check_at` terdekat; interval minimum efektif 60 detik.
- Status monitor hanya berubah setelah ambang konfirmasi/pemulihan terpenuhi, sehingga dashboard dapat menampilkan "Menunggu konfirmasi".
- Insiden dibuka dengan waktu mulai = kegagalan pertama, dan ditutup pada keberhasilan pertama saat pemulihan.
- Uptime = pemeriksaan berhasil ÷ total pemeriksaan pada rentang; waktu respons hanya dihitung dari pemeriksaan berhasil.
