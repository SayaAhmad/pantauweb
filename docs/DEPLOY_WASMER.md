# Deploy Pantau ke Wasmer Edge

Panduan ini menjalankan Pantau di [Wasmer Edge](https://wasmer.io/edge) memakai runtime PHP resmi `php/php-eh`, database MySQL terkelola, volume penyimpanan, dan Wasmer Jobs sebagai pengganti cron server.

## Ringkasan arsitektur

| Komponen | Di Wasmer Edge |
|---|---|
| Web server | PHP built-in server (`php -S`), `scaling.mode: single_concurrency` |
| Runtime | `php/php-eh` 8.3.404-beta.8 (WASI/WASIX) |
| Database | MySQL terkelola, kredensial disuntikkan sebagai `DB_*` |
| Sesi & cache | Database (`sessions`, `cache`) |
| Queue | `sync` — pemeriksaan dieksekusi langsung, tanpa worker terpisah |
| Scheduler | Wasmer Job `execute` menjalankan `artisan monitors:dispatch` tiap menit |
| File runtime | Volume `/app/storage` (Blade, log, file aplikasi) |
| Health check | `GET /up` |

Wasmer Edge bersifat stateless dan tidak menyediakan proses queue worker yang hidup terus. Karena itu Pantau di sini memakai `QUEUE_CONNECTION=sync`: perintah `monitors:dispatch` langsung menjalankan pemeriksaan yang jatuh tempo di dalam job scheduler.

## Kenapa `php/php-eh`?

Build resmi `php/php` 8.3.4 **tidak memuat** `pdo_mysql`, `mysqli`, dan `curl`. Tanpa itu Pantau tidak bisa memakai MySQL terkelola dan pemeriksaan HTTP hanya mengandalkan fallback stream Guzzle.

`php/php-eh` adalah build resmi Wasmer yang sama, dengan dukungan *wasm exception handling* dan ekstensi yang jauh lebih lengkap (`pdo_mysql`, `mysqlnd`, `mysqli`, `pdo_pgsql`, `curl`, `intl`, `gd`, `zip`). Build ini yang dipakai template WordPress Wasmer Edge, yang juga butuh MySQL. Daftar ekstensinya berasal dari `wasix-configure.sh` di [wasix-org/php](https://github.com/wasix-org/php).

Catatan saat mengembangkan di Windows: Wasmer CLI 7.5 di Windows gagal menjalankan `php/php-eh` karena syscall WASIX `clock_time_get(ProcessCputimeId)` belum diimplementasikan di host Windows. Ini masalah host, bukan aplikasi. Efeknya:

- `wasmer run .` di Windows akan berhenti dengan panic.
- `wasmer deploy` tetap normal: paket diunggah dan dijalankan di server Linux Wasmer.
- Untuk uji lokal di Windows, pakai `php artisan serve` atau `php -S localhost:8080 -t public` dengan PHP native.

## 1. Prasyarat

- PHP 8.3+ dan Composer di komputer Anda
- Node.js 20+ dan npm
- [Wasmer CLI](https://docs.wasmer.io/install) 7.5 atau lebih baru
- Akun Wasmer

Proyek ini mengunci platform Composer ke PHP 8.3.4 agar dependensi (Symfony 7.4) cocok dengan runtime resmi Wasmer. Jangan mengubah `config.platform.php` ke 8.4+ selama masih memakai `php/php-eh` 8.3.

## 2. Siapkan dependensi dan aset

```bash
composer install
npm ci
npm run build
```

`npm run build` penting: aset Vite ada di `public/build` dan ikut dipaketkan ke Wasmer.

Untuk paket yang lebih kecil, jalankan `composer install --no-dev --optimize-autoloader` tepat sebelum deploy. Setelah deploy, jalankan `composer install` lagi kalau Anda masih butuh PHPUnit/Pint di lokal.

## Struktur paket Wasmer

Wasmer CLI 7.x hanya memetakan **direktori** lewat `[fs]`, bukan file tunggal. `wasmer.toml` karena itu memetakan direktori `wasmer/` ke `/app`, lalu direktori Laravel di atasnya:

```
wasmer/console.php       -> /app/console.php      (pengganti artisan)
wasmer/composer.json     -> /app/composer.json    (deteksi namespace oleh Laravel)
app/, config/, public/   -> /app/app, /app/config, ...
```

`artisan` tidak bisa dipetakan langsung, jadi Wasmer Jobs memanggil `/app/console.php`. Laravel juga membaca `/app/composer.json` saat mendeteksi namespace aplikasi, jadi stub di `wasmer/composer.json` harus tetap ada.

> Jika blok `autoload.psr-4` di `composer.json` root berubah, samakan juga `wasmer/composer.json`. Kalau tidak, Laravel gagal mendeteksi namespace dan job berhenti dengan `Unable to detect application namespace`.

Direktori yang tidak dipetakan (`.env`, `tests`, `node_modules`, `docs`) memang tidak dibutuhkan saat runtime, dan `.env` tidak ikut terunggah.

## 3. Buat file rahasia

Salin contoh, lalu isi nilainya:

```bash
cp .env.wasmer.example .env.wasmer
```

Buat `APP_KEY`:

```bash
php artisan key:generate --show
```

Tempel hasilnya ke `APP_KEY=` di `.env.wasmer`. Isi juga `ADMIN_EMAIL` dan `ADMIN_PASSWORD` dengan kredensial yang kuat.

> `.env.wasmer` sudah masuk `.gitignore`. Jangan commit file ini.

## 4. Sesuaikan `app.yaml`

Buka `app.yaml` dan ganti:

- `owner`: username atau organisasi Wasmer Anda
- `name`: nama aplikasi (mis. `pantau`)
- `APP_URL`: sesuaikan dengan URL akhir, mis. `https://pantau-<owner>.wasmer.app`

Catatan:

- `locality.regions` dikunci ke `fr-roub1` karena region itu mendukung MySQL. Jika memakai PostgreSQL, ganti `DB_CONNECTION` dan `engine`, lalu tambahkan `DB_SSLMODE=require`.
- Jangan mengganti nama volume `storage`. Mengganti nama volume akan menghapus isinya.
- `APP_KEY`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` sengaja tidak ditulis di `app.yaml`; semuanya diberikan lewat secrets.

## 5. Deploy pertama

```bash
wasmer login
wasmer deploy
```

Perintah ini akan menanyakan owner, nama app, dan versi paket. Pada deploy pertama Wasmer akan:

1. menerbitkan paket lokal,
2. membuat app,
3. menyediakan database MySQL dan volume `storage`,
4. menjalankan job `pantau-deploy` setelah deploy.

Deploy pertama bisa menampilkan error 500 karena `APP_KEY` belum ada. Itu normal; lanjutkan ke langkah berikutnya.

## 6. Tambahkan secrets

```bash
wasmer app secrets create --from-file=.env.wasmer
```

Secret harus dibuat setelah app ada. Setelah secrets dibuat, deploy ulang agar job pasca-deploy berjalan dengan kredensial yang benar:

```bash
wasmer deploy
```

Pada deploy kedua, job `pantau-deploy` menjalankan migrasi dan membuat akun admin dari `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

Alternatif: buat app lebih dulu lewat dashboard Wasmer, tambahkan secrets di **Settings → Environment Vars**, lalu jalankan `wasmer deploy` sekali.

## 7. Verifikasi

1. Buka `https://<nama-app>-<owner>.wasmer.app`.
2. Login dengan `ADMIN_EMAIL` dan `ADMIN_PASSWORD`.
3. Buka tab **Jobs** di dashboard Wasmer dan pastikan:
   - `pantau-deploy` sukses (migrasi + admin),
   - `pantau-dispatch` berjalan tiap menit,
   - `pantau-rollup` dan `pantau-prune` terjadwal harian.
4. Tambahkan monitor, lalu klik **Periksa sekarang** atau tunggu scheduler.

Untuk melihat URL dan status app:

```bash
wasmer app info
```

## Cara kerja scheduler

`app.yaml` mendefinisikan tiga job terjadwal:

```yaml
jobs:
  - name: pantau-dispatch
    trigger: '* * * * *'
    action:
      execute:
        command: artisan
        cli_args: ["monitors:dispatch"]

  - name: pantau-rollup
    trigger: '15 0 * * *'
    action:
      execute:
        command: artisan
        cli_args: ["monitors:rollup"]

  - name: pantau-prune
    trigger: '0 2 * * *'
    action:
      execute:
        command: artisan
        cli_args: ["monitors:prune"]
```

Perintah dipanggil langsung oleh Wasmer Jobs, bukan lewat `php artisan schedule:run`. Ini disengaja: `schedule:run` menjalankan perintah terjadwal sebagai proses PHP baru, sedangkan runtime WASI tidak selalu mendukung pembuatan proses seperti itu.

Karena queue `sync`, pemeriksaan monitor berjalan di dalam job `pantau-dispatch`. Job itu diberi timeout 5 menit. Jika monitor sangat banyak atau timeout pemeriksaan besar, naikkan `timeout` job dan/atau bagi monitor ke beberapa app.

Jadwal di `routes/console.php` tetap berguna untuk pengembangan lokal (`php artisan schedule:work`), tetapi tidak dipakai di Wasmer.

Interval minimum Wasmer Jobs adalah 1 menit. Jam pada `trigger` mengikuti zona waktu server Wasmer, bukan `config/app.php`. Proyek ini memakai UTC di `config/app.php`; jika jam server Wasmer berbeda, sesuaikan ekspresi cron di `app.yaml`.

## Variabel lingkungan penting

| Variabel | Nilai di Wasmer | Keterangan |
|---|---|---|
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | |
| `APP_KEY` | secret | Wajib; jangan dirotasi sembarangan. |
| `DB_CONNECTION` | `mysql` | `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD` diisi Wasmer. |
| `SESSION_DRIVER` | `database` | |
| `CACHE_STORE` | `database` | |
| `QUEUE_CONNECTION` | `sync` | Tidak ada worker terpisah. |
| `LOG_CHANNEL` | `stderr` | Log muncul di dashboard Wasmer. |
| `ADMIN_EMAIL` | secret | Akun admin pertama. |
| `ADMIN_PASSWORD` | secret | Hanya dipakai saat akun belum ada; tidak mereset password lama. |
| `GIT_SYNC_ENABLED` | `false` | Jangan aktifkan di Edge: runtime tidak punya kredensial git. |

Laravel membaca `DB_DATABASE`; Wasmer menyuntikkan `DB_NAME`. Jembatannya sudah ditambahkan di `config/database.php`.

## Deploy ulang

```bash
npm run build          # jika ada perubahan aset
wasmer deploy
```

Job `pantau-deploy` bersifat idempotent: migrasi yang sudah jalan dilewati, dan akun admin tidak dibuat ulang.

## Troubleshooting

**500 setelah deploy / "No application encryption key has been specified"**
`APP_KEY` belum ada. Jalankan `wasmer app secrets create --from-file=.env.wasmer`, lalu deploy ulang.

**"could not find driver" atau koneksi database gagal**
Pastikan `DB_CONNECTION=mysql` dan runtime memakai `php/php-eh`. Build `php/php` tidak memuat `pdo_mysql`, jadi Laravel akan gagal dengan `could not find driver`. Jangan hardcode host/port; pakai variabel `DB_*` dari Wasmer.

**`wasmer run .` panic di Windows**
Pesan `not implemented: wasi::platform_clock_time_get(...ProcessCputimeId)` berasal dari host Windows Wasmer CLI, bukan dari aplikasi. Runtime `php/php-eh` belum bisa dijalankan lokal di Windows. Untuk uji lokal pakai `php artisan serve`; `wasmer deploy` tetap berjalan normal karena eksekusi terjadi di server Linux Wasmer.

**Aset CSS/JS 404**
Jalankan `npm run build` sebelum deploy. Pastikan `public/build` ada dan ikut terpaket (lihat `[fs]` di `wasmer.toml`).

**Admin tidak dibuat**
`ADMIN_PASSWORD` kosong. Tambahkan secret, deploy ulang, lalu cek log job `pantau-deploy`. Seeder tidak mengubah password akun yang sudah ada.

**Job `pantau-dispatch` timeout / pemeriksaan tidak jalan**
Cek log job di dashboard Wasmer. Kurangi jumlah monitor aktif, naikkan `timeout` job, atau perbesar interval monitor. Pemeriksaan berjalan berurutan karena queue `sync`, jadi waktu satu putaran sebanding dengan jumlah monitor yang jatuh tempo.

**Monitor port gagal padahal port terbuka**
Pemeriksaan TCP dari Edge hanya bisa menjangkau host yang dapat diakses publik. Untuk host di jaringan privat, jalankan agent atau monitor dari jaringan tersebut.

**Email insiden tidak terkirim**
Default `MAIL_MAILER=log`, jadi email hanya masuk log. Isi `MONITOR_ALERT_EMAIL` dan kredensial SMTP (`MAIL_*`) lewat secrets, lalu deploy ulang. Alternatif tanpa SMTP: tambahkan `enable_email: true` di `app.yaml` dan set `MAIL_MAILER=sendmail` untuk memakai sendmail bawaan Wasmer Edge.

## Batasan yang perlu diketahui

- Satu app hanya boleh punya satu database terkelola.
- Database dan volume terikat satu region.
- Tidak ada queue worker persisten; queue `sync` membuat request web/job menunggu pemeriksaan selesai.
- `php -S` adalah server pengembangan PHP, bukan server produksi multi-thread. Mode `single_concurrency` dipakai agar Wasmer menyesuaikan skala per instance.
- `php/php-eh` masih berlabel beta (8.3.404-beta.8). Jika ada masalah runtime, coba versi beta lain di registry atau turunkan `wasmer.toml` ke `=8.3.404-beta.4`.
- Runtime resmi Wasmer masih PHP 8.3.x. Jika nanti naik ke Laravel/Symfony yang mewajibkan PHP 8.4+, runtime harus diganti lebih dulu.
