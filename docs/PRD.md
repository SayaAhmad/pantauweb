# PRD — Pantau

**Versi:** 1.0 (MVP)
**Tanggal:** 11 November 2026
**Status:** Disetujui untuk implementasi MVP
**Dokumen terkait:** [GLOSSARY.md](../GLOSSARY.md) · [ADR 0001–0006](adr/) · [TASKS.md](TASKS.md)

---

## 1. Ringkasan

Pantau adalah aplikasi web self-hosted untuk memantau ketersediaan website, port, dan layanan server dari satu dashboard. Aplikasi menjalankan pemeriksaan eksternal secara berkala, mencatat waktu respons dan kode status HTTP, memantau masa berlaku sertifikat SSL, serta menyajikan riwayat insiden, uptime, dan laporan yang dapat difilter.

MVP ditujukan untuk satu organisasi dengan satu instalasi. Registrasi publik tersedia; akun baru otomatis berperan sebagai pengguna biasa, sedangkan admin mengelola akun lewat menu **Pengguna**. Fokus MVP adalah **ketersediaan dari sudut pandang eksternal**, bukan metrik internal mesin.

### Tujuan MVP

1. Mengetahui dalam hitungan menit ketika sebuah website, port, atau layanan tidak dapat diakses.
2. Memiliki satu sumber kebenaran untuk status semua monitor, riwayat gangguan, dan uptime.
3. Menyediakan laporan uptime dan waktu respons yang dapat difilter per server, monitor, dan rentang waktu.

### Non-tujuan MVP

- Multi-tenant, billing, dan peran pengguna berlapis.
- Metrik internal server (CPU, RAM, disk, status proses) di antarmuka — ditunda ke Fase 2.
- Notifikasi Slack/Telegram/webhook, eskalasi, on-call, dan status page publik.
- Pemeriksaan dari banyak lokasi (distributed probing) dan SLA kontraktual.
- Ekspor PDF server-side (MVP memakai print view browser) dan API publik.

---

## 2. Latar belakang & masalah

Tim operasi saat ini memeriksa website dan layanan secara manual atau tersebar di beberapa alat. Akibatnya:

- Gangguan baru diketahui setelah pengguna melapor.
- Tidak ada catatan kapan gangguan mulai dan berapa lama berlangsung.
- Masa berlaku sertifikat SSL terlupakan sampai situs menampilkan peringatan.
- Tidak ada angka uptime yang bisa dipertanggungjawabkan saat evaluasi bulanan.

Pantau menyatukan pemeriksaan, pencatatan insiden, dan pelaporan dalam satu aplikasi yang bisa dijalankan di server internal.

---

## 3. Persona & peran

| Persona | Kebutuhan utama |
|---|---|
| **Admin/Operator (satu-satunya peran di MVP)** | Menambah monitor, mengatur interval, melihat status terkini, menelusuri insiden, mengunduh laporan. |

Tidak ada registrasi publik. Akun admin dibuat lewat seeder/env saat instalasi.

---

## 4. Definisi & aturan domain

Istilah kanonik mengikuti [GLOSSARY.md](../GLOSSARY.md). Aturan yang mengikat implementasi:

### 4.1 Status monitor

- Status monitor: `up`, `down`, `unknown`.
- Monitor baru yang belum pernah diperiksa berstatus `unknown`.
- Monitor dinyatakan `down` setelah **ambang konfirmasi** kegagalan berturut-turut (default 2).
- Monitor kembali `up` setelah **ambang pemulihan** keberhasilan berturut-turut (default 2).
- Hasil pemeriksaan terakhir tetap ditampilkan di UI, termasuk ketika belum cukup untuk mengubah status ("cek gagal, menunggu konfirmasi").

### 4.2 Insiden

- Satu insiden dibuka saat monitor bertransisi ke `down`.
- `started_at` = waktu **kegagalan pertama** dalam rentetan kegagalan, bukan waktu konfirmasi.
- Insiden ditutup pada **keberhasilan pertama** dalam rentetan pemulihan (`resolved_at`).
- `duration = resolved_at − started_at`; insiden terbuka belum memiliki durasi.
- Hanya ada satu insiden terbuka per monitor pada satu waktu.
- Insiden tidak dibuat untuk cek yang gagal ketika monitor sudah `down` (tidak ada duplikasi).

### 4.3 Uptime

- `uptime% = hasil pemeriksaan up / total hasil pemeriksaan × 100` pada rentang waktu.
- Jika tidak ada hasil pemeriksaan pada rentang tersebut, uptime ditampilkan sebagai `—`, bukan 0% atau 100%.
- Waktu respons yang dilaporkan (rata-rata, p95, maksimum) hanya dihitung dari pemeriksaan yang berhasil (`up`).

### 4.4 Masa berlaku SSL

- Dibaca dari handshake TLS saat pemeriksaan monitor `http`.
- Peringatan bertingkat: **kritis** ≤ 7 hari, **peringatan** ≤ 14 hari, **perhatian** ≤ 30 hari.
- Sertifikat yang gagal diverifikasi (kedaluwarsa/tidak tepercaya) membuat pemeriksaan berstatus `down` dengan pesan galat yang jelas.

### 4.5 Waktu

- Disimpan dalam **UTC**, ditampilkan dalam **Asia/Jakarta**.
- Format tanggal Indonesia (`11 Nov 2026 14:05`), durasi dalam bahasa manusia (`2 jam 15 menit`).

---

## 5. Ruang lingkup MVP

### Termasuk

- Login admin, logout, proteksi seluruh halaman aplikasi.
- CRUD monitor (HTTP/website, port, layanan) dengan interval dan timeout per monitor.
- CRUD server sebagai pengelompokan dan basis filter.
- Penjadwalan pemeriksaan otomatis per menit + queue worker.
- Halaman detail monitor: status, uptime 24 jam/7 hari/30 hari, grafik respons, info SSL, riwayat pemeriksaan, riwayat insiden, tombol "Periksa sekarang".
- Dashboard ringkasan semua monitor, grafik respons 24 jam, dan insiden terbaru.
- Daftar insiden dengan filter server, monitor, dan rentang waktu.
- Laporan uptime/waktu respons per monitor dengan filter dan ekspor CSV.
- Retensi data mentah 30 hari + rollup harian permanen (otomatis).
- Notifikasi in-app (daftar insiden + banner) dan email opsional via SMTP.
- Endpoint ingest metrik agent (disiapkan, tanpa UI) — lihat §12.

### Tidak termasuk (Fase 2+)

- Antarmuka metrik agent (CPU/RAM/disk/proses) dan manajemen token agent di UI.
- Channel notifikasi webhook/Slack/Telegram, jadwal on-call, eskalasi.
- Status page publik, multi-user & peran, audit log.
- Distributed probing, pengukuran dari beberapa wilayah.
- Retensi/rollup yang dapat dikonfigurasi dari UI.

---

## 6. Kebutuhan fungsional

### 6.1 Autentikasi

| ID | Kebutuhan | Kriteria penerimaan |
|---|---|---|
| FR-AUTH-01 | Admin dapat login dengan email & password. | Kredensial benar → masuk dashboard; salah → pesan galat generik, tidak membocorkan field mana yang salah. |
| FR-AUTH-02 | Seluruh halaman aplikasi memerlukan login. | Akses tanpa sesi dialihkan ke `/login`. |
| FR-AUTH-03 | Admin dapat logout. | Sesi berakhir dan diarahkan ke halaman login. |
| FR-AUTH-04 | Percobaan login dibatasi. | Maksimum 5 percobaan per menit per IP/kredensial, selebihnya ditolak sementara. |

### 6.2 Monitoring website (HTTP)

| ID | Kebutuhan | Kriteria penerimaan |
|---|---|---|
| FR-WEB-01 | Tambah/ubah/hapus monitor HTTP dengan nama, URL, metode, kode status yang diharapkan, kata kunci opsional, interval, dan timeout. | Form memvalidasi URL, interval ≥ 60 detik, timeout 1–60 detik. |
| FR-WEB-02 | Pemeriksaan berkala mencatat kode status, waktu respons, dan status up/down. | Setiap pemeriksaan menghasilkan tepat satu hasil pemeriksaan. |
| FR-WEB-03 | Kegagalan jaringan/timeout/kode tak terduga menghasilkan `down` beserta pesan galat. | Pesan galat tersimpan dan tampil di detail monitor. |
| FR-WEB-04 | Masa berlaku dan issuer sertifikat SSL dicatat setiap pemeriksaan HTTP. | Detail monitor menampilkan tanggal kedaluwarsa, sisa hari, dan tingkat peringatan. |
| FR-WEB-05 | Riwayat pemeriksaan dan insiden ditampilkan. | 20 hasil terakhir dan seluruh insiden monitor terlihat, terbaru lebih dulu. |
| FR-WEB-06 | Uptime 24 jam, 7 hari, dan 30 hari ditampilkan. | Angka sesuai rumus §4.3; tanpa data → `—`. |

### 6.3 Monitoring port

| ID | Kebutuhan | Kriteria penerimaan |
|---|---|---|
| FR-PORT-01 | Tambah monitor TCP dengan host, port (1–65535), interval, dan timeout. | Validasi host wajib, port dalam rentang valid. |
| FR-PORT-02 | Pemeriksaan membuka koneksi TCP dan mengukur waktu koneksi. | Terhubung → `up` + waktu respons; gagal/timeout → `down` + pesan galat. |
| FR-PORT-03 | Status port ditampilkan sebagai aktif (`up`), tidak aktif/menunggu konfirmasi (`down` transitif), atau pemeriksaan gagal (`down` dengan galat), plus status `unknown` sebelum pemeriksaan pertama. | Badge status konsisten dengan aturan §4.1. |

### 6.4 Monitoring layanan server

| ID | Kebutuhan | Kriteria penerimaan |
|---|---|---|
| FR-SVC-01 | Tambah monitor `service` berupa endpoint kesehatan HTTP (URL), dengan ekspektasi kode status dan kata kunci. | Sama seperti HTTP, tanpa penekanan SSL. |
| FR-SVC-02 | Monitor dapat dikaitkan ke server. | Server opsional; filter server bekerja pada monitor terkait. |
| FR-SVC-03 | Endpoint ingest metrik agent menerima metrik dan menyimpannya (tanpa UI). | Token valid → 201; token tidak valid → 401. |

### 6.5 Dashboard

| ID | Kebutuhan | Kriteria penerimaan |
|---|---|---|
| FR-DASH-01 | Ringkasan jumlah monitor per status, jumlah insiden terbuka, rata-rata respons 24 jam. | Angka konsisten dengan data pada rentang yang sama. |
| FR-DASH-02 | Daftar semua monitor dengan status, uptime 24 jam, respons terakhir, dan waktu cek terakhir. | Dapat difilter jenis & server; monitor `down` terlihat paling menonjol. |
| FR-DASH-03 | Grafik rata-rata waktu respons per jam selama 24 jam. | Kosong ditangani dengan state "belum ada data". |
| FR-DASH-04 | Panel peringatan SSL (≤ 30 hari) dan insiden terbaru. | Klik item menuju detail monitor. |

### 6.6 Insiden

| ID | Kebutuhan | Kriteria penerimaan |
|---|---|---|
| FR-INC-01 | Daftar insiden dengan waktu mulai, waktu pulih, durasi, penyebab, dan monitor. | Insiden terbuka ditandai "berlangsung" dan durasi terus bertambah. |
| FR-INC-02 | Filter berdasarkan server, monitor, dan rentang tanggal. | Kombinasi filter bekerja bersamaan; reset filter tersedia. |

### 6.7 Laporan

| ID | Kebutuhan | Kriteria penerimaan |
|---|---|---|
| FR-REP-01 | Laporan per monitor: total pemeriksaan, uptime, rata-rata/p95/maks respons, jumlah & total durasi insiden. | Angka sesuai rumus §4.3 dan data pada rentang terpilih. |
| FR-REP-02 | Grafik uptime harian dan respons harian pada rentang terpilih. | Rentang default 7 hari; maksimum 90 hari. |
| FR-REP-03 | Ekspor CSV mengikuti filter yang aktif. | File CSV berisi header dan baris sesuai tabel laporan. |
| FR-REP-04 | Tampilan siap cetak. | Tombol cetak membuka print dialog tanpa sidebar. |

### 6.8 Server

| ID | Kebutuhan | Kriteria penerimaan |
|---|---|---|
| FR-SRV-01 | Tambah/ubah/hapus server (nama, hostname, IP, deskripsi). | Monitor terkait tidak terhapus saat server dihapus; relasinya dikosongkan. |

### 6.9 Operasional & pemeliharaan

| ID | Kebutuhan | Kriteria penerimaan |
|---|---|---|
| FR-OPS-01 | Pemeriksaan terjadwal otomatis tiap menit sesuai interval monitor. | `next_check_at` maju sesuai interval setelah enqueue. |
| FR-OPS-02 | Pemeriksaan manual tersedia dari UI. | Tombol "Periksa sekarang" menjalankan pemeriksaan dan memperbarui status. |
| FR-OPS-03 | Rollup harian dibuat otomatis. | Satu baris per monitor per tanggal (idempoten). |
| FR-OPS-04 | Data mentah lebih tua dari retensi dihapus otomatis. | Retensi default 30 hari, dapat diatur lewat env. |

---

## 7. Kebutuhan non-fungsional

| Aspek | Target MVP |
|---|---|
| Performa | Dashboard < 1,5 detik untuk 50 monitor × 30 hari data mentah; query diindeks `(monitor_id, checked_at)`. |
| Ketahanan | Kegagalan satu pemeriksaan (timeout/DNS/galat TLS) tidak boleh menggagalkan job lain; setiap pengecualian dicatat sebagai hasil `down`. |
| Keamanan | Seluruh halaman butuh sesi; password di-hash; CSRF aktif; token agent disimpan ter-hash dan dibandingkan secara aman. |
| Aksesibilitas | Kontras teks memadai; status tidak hanya dibedakan lewat warna (ada teks/bentuk). |
| Lokalisasi | UI Bahasa Indonesia, waktu `Asia/Jakarta`. |
| Portabilitas | SQLite (default) dan MySQL/PostgreSQL; tidak memakai fitur khas satu basis data. |
| Operasional | Satu perintah terjadwal + satu queue worker; instruksi jelas di README. |

---

## 8. Model data ringkas

```
servers 1 ──< monitors 1 ──< check_results
                     │
                     ├──< incidents
                     ├──< daily_rollups
                     └──< (server) 1 ──< server_metrics
```

| Tabel | Kolom inti |
|---|---|
| `servers` | name, hostname, ip, description |
| `monitors` | name, type, server_id, url, host, port, method, expected_status, expected_keyword, interval_seconds, timeout_seconds, confirmation_threshold, recovery_threshold, is_active, status, streak counters, next_check_at, last_* , ssl_* |
| `check_results` | monitor_id, status, http_status, response_time_ms, error_message, ssl_expires_at, checked_at |
| `incidents` | monitor_id, started_at, resolved_at, duration_seconds, cause |
| `daily_rollups` | monitor_id, date, total_checks, successful_checks, avg_response_ms, max_response_ms (unik per monitor+tanggal) |
| `server_metrics` | server_id, cpu_percent, ram_percent, disk_percent, load_average, collected_at |

Rincian mengikuti migration; ADR terkait: [0001](adr/0001-satu-tabel-monitor-dengan-diskriminator.md), [0004](adr/0004-retensi-mentah-dan-rollup-harian.md).

---

## 9. Arsitektur runtime

1. **Scheduler** (`schedule:work`/cron) menjalankan `monitors:dispatch` setiap menit → enqueue `CheckMonitorJob` untuk monitor yang jatuh tempo.
2. **Queue worker** mengeksekusi job → memilih pemeriksa sesuai jenis monitor → mencatat `check_results` → menjalankan mesin status → membuka/menutup insiden → mengirim notifikasi.
3. **Web** (Blade) membaca status, menghitung uptime dari `check_results`/`daily_rollups`, dan menyajikan grafik.
4. Perintah harian: rollup (`monitors:rollup`) dan pemangkasan data mentah (`monitors:prune`).

Keputusan terkait: [ADR 0002](adr/0002-tick-satu-menit-dan-antrean.md), [ADR 0003](adr/0003-konfirmasi-status-dan-waktu-insiden.md).

---

## 10. Rancangan antarmuka (MVP)

| Halaman | Isi utama |
|---|---|
| `/login` | Form login, tanpa navigasi aplikasi. |
| `/` Dashboard | 4 kartu ringkasan, grafik respons 24 jam, tabel monitor, panel SSL, insiden terbaru. |
| `/monitors` | Tabel monitor + filter jenis/server + tombol tambah. |
| `/monitors/create`, `/monitors/{id}/edit` | Form tunggal dengan field berubah sesuai jenis monitor. |
| `/monitors/{id}` | Header status + aksi, kartu uptime 24j/7h/30h, grafik respons, info SSL, 20 cek terakhir, riwayat insiden. |
| `/incidents` | Tabel insiden + filter server/monitor/tanggal. |
| `/reports` | Filter, ringkasan agregat, grafik harian, tabel per monitor, ekspor CSV, cetak. |
| `/servers` | Daftar server + form tambah/ubah. |

Komponen visual: badge status (hijau/merah/abu), kartu putih di atas latar abu muda, sidebar gelap, grafik garis (Chart.js), tabel responsif.

---

## 11. Notifikasi

- **In-app:** panel insiden terbuka di dashboard, badge pada navigasi, riwayat insiden.
- **Email opsional:** dikirim saat insiden dibuka dan ditutup jika `MONITOR_ALERT_EMAIL` diisi. Kegagalan pengiriman email tidak boleh menggagalkan pencatatan insiden.

---

## 12. Kontrak API agent (disiapkan, Fase 2)

`POST /api/agent/metrics` — header `X-Agent-Token: <token>`.

```json
{
  "cpu_percent": 12.5,
  "ram_percent": 43.1,
  "disk_percent": 61.0,
  "load_average": 0.42
}
```

Respons `201` saat tersimpan, `401` bila token tidak cocok. Token dibuat saat server dibuat (kolom `agent_token` di-hash). Antarmuka grafik metrik belum termasuk MVP.

---

## 13. Kriteria penerimaan MVP

1. Instalasi baru dapat dijalankan mengikuti README: migrate, seed admin, build aset, scheduler + queue worker.
2. Admin dapat membuat ketiga jenis monitor dan melihatnya di dashboard dengan status yang benar.
3. Kegagalan berturut-turut mengubah status menjadi `down` tepat pada ambang, membuka satu insiden dengan `started_at` = kegagalan pertama; pemulihan menutup insiden dan mengisi durasi.
4. Grafik respons dan angka uptime 24 jam/7 hari/30 hari tampil dan konsisten dengan data tersimpan.
5. Info SSL menampilkan kedaluwarsa dan peringatan ≤ 30 hari.
6. Filter insiden dan laporan bekerja untuk server, monitor, dan rentang tanggal; ekspor CSV sesuai filter.
7. Retensi memangkas data mentah > 30 hari dan rollup harian tetap tersedia.
8. Seluruh halaman terlindungi login; percobaan login dibatasi.
9. Test otomatis untuk mesin status, perhitungan uptime, autentikasi, dan filter laporan lulus.

---

## 14. Rencana rilis

| Fase | Cakupan |
|---|---|
| **MVP (sekarang)** | Semua di §5 "Termasuk". |
| **Fase 2** | UI metrik agent, manajemen token, grafik CPU/RAM/disk, status proses. |
| **Fase 3** | Webhook/Slack/Telegram, status page publik, peran pengguna, distributed probing, SLA report. |

---

## 15. Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Queue worker mati tanpa disadari → tidak ada pemeriksaan | Tampilkan "cek terakhir" mencolok di dashboard; dokumentasikan `queue:work`; (Fase 2) heartbeat scheduler. |
| False alarm karena jaringan checker sendiri bermasalah | Ambang konfirmasi 2/2; pesan galat spesifik; (Fase 2) multi-lokasi. |
| Data mentah tumbuh cepat | Retensi 30 hari + rollup harian; indeks `(monitor_id, checked_at)`. |
| Verifikasi TLS gagal karena sertifikat kedaluwarsa | Ditandai `down` dengan pesan jelas; probe cadangan mengambil detail sertifikat untuk peringatan dini. |
| Email notifikasi gagal | Dibungkus try/catch; insiden tetap tercatat. |

---

## 16. Pertanyaan terbuka (di luar MVP)

- Apakah pemeriksaan perlu dijalankan dari lebih dari satu lokasi?
- Perlukah aturan eskalasi dan jadwal on-call?
- Apakah laporan perlu dikirim otomatis (email terjadwal) dalam bentuk PDF?
- Berapa lama rollup harian perlu disimpan (sekarang: permanen)?
