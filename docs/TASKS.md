# TASKS - Tracker Implementasi Pantau

Dokumen ini adalah papan tugas (task list tracker) untuk implementasi dan pengembangan Pantau. Sumber kebenaran requirement tetap [`docs/PRD.md`](PRD.md); dokumen ini hanya memecahnya menjadi pekerjaan yang bisa dicentang.

**Cara pakai**

- `- [ ]` belum dikerjakan · `- [x]` selesai · `- [~]` sedang dikerjakan · `- [!]` terblokir (tulis alasannya di baris yang sama).
- Satu tugas = satu perubahan yang bisa diuji. Jangan gabungkan backend, UI, dan test dalam satu baris.
- Tandai selesai hanya jika **Definisi Selesai** di bawah terpenuhi, lalu catat di bagian [Log progres](#log-progres).
- Jangan hapus tugas yang sudah selesai; coret dengan `[x]` agar jejaknya tetap ada.
- Menambah pekerjaan baru: pakai ID berikutnya pada seri yang sama, jangan menomori ulang.
- Jika membangun ulang proyek dari nol, salin file ini dan kosongkan centang MVP, lalu kerjakan berurutan M0 → M6.

**Definisi Selesai (berlaku untuk semua tugas)**

- [ ] Perilaku sesuai kriteria penerimaan PRD yang dirujuk.
- [ ] Tidak ada error di log; `php artisan test` tetap hijau.
- [ ] Teks antarmuka berbahasa Indonesia, waktu disimpan UTC dan ditampilkan `Asia/Jakarta`.
- [ ] Perubahan skema disertai migration yang bisa `migrate:fresh` tanpa error.
- [ ] README/PRD diperbarui bila perilaku atau konfigurasi berubah.

---

## Ringkasan progres

| Milestone | Cakupan | Tugas | Selesai | Status |
|---|---|---|---|---|
| M0 | Fondasi & dokumen | 5 | 5 | ✅ selesai |
| M1 | Autentikasi | 5 | 5 | ✅ selesai |
| M2 | Skema & domain inti | 9 | 9 | ✅ selesai |
| M3 | Mesin pemeriksaan | 9 | 9 | ✅ selesai |
| M4 | Penjadwalan & operasional | 8 | 8 | ✅ selesai |
| M5 | Halaman & antarmuka | 13 | 13 | ✅ selesai |
| M6 | QA & rilis MVP | 14 | 14 | ✅ selesai |
| M7 | Fase 2 (metrik agent) | 6 | 0 | ⬜ rencana |
| M8 | Fase 3 (notifikasi & skala) | 5 | 0 | ⬜ rencana |
| **Total MVP (M0–M6)** | | **63** | **63** | **100%** |

Terakhir diperbarui: **11 Nov 2026**

---

## M0 - Fondasi & dokumen

- [x] **T-001** Inisialisasi Laravel, konfigurasi `.env` (SQLite, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, locale `id`).
- [x] **T-002** Tulis glosarium domain kanonik di `GLOSSARY.md`.
- [x] **T-003** Tulis PRD MVP di `docs/PRD.md` (FR, NFR, kriteria penerimaan).
- [x] **T-004** Catat 6 keputusan arsitektur di `docs/adr/0001`–`0006`.
- [x] **T-005** Tulis `README.md`: instalasi, scheduler, queue worker, variabel lingkungan.

## M1 - Autentikasi (FR-AUTH)

- [x] **T-101** Migrasi `users`, model `User`, factory.
- [x] **T-102** Halaman login (`/login`) dengan validasi email & kata sandi.
- [x] **T-103** Logout + invalidasi sesi + regenerasi token CSRF.
- [x] **T-104** Middleware `auth` pada seluruh route aplikasi; guest diarahkan ke `/login`.
- [x] **T-105** Rate limit login 5 percobaan/menit per email+IP (`FR-AUTH-04`).

## M2 - Skema & domain inti

- [x] **T-201** Migrasi `servers` (termasuk `agent_token_hash`, `last_seen_at`).
- [x] **T-202** Migrasi `monitors` dengan diskriminator `type` dan kolom konfigurasi bertipe (`ADR 0001`).
- [x] **T-203** Migrasi `check_results` + indeks `(monitor_id, checked_at)`.
- [x] **T-204** Migrasi `incidents` + indeks `(monitor_id, started_at)`.
- [x] **T-205** Migrasi `daily_rollups` + unik `(monitor_id, date)`.
- [x] **T-206** Migrasi `server_metrics` (disiapkan untuk Fase 2).
- [x] **T-207** Enum `MonitorType`, `MonitorStatus`, `CheckStatus` beserta label Indonesia.
- [x] **T-208** Model + relasi + cast + scope `active()`/`due()` + helper target/SSL.
- [x] **T-209** Factory semua model + `DatabaseSeeder` (admin, 2 server, 5 monitor demo).

## M3 - Mesin pemeriksaan

- [x] **T-301** `CheckOutcome` sebagai nilai hasil pemeriksaan (up/down, status, waktu, galat, SSL).
- [x] **T-302** `HttpChecker`: kode status, kata kunci, redirect, timeout, pemetaan galat cURL, tangkapan sertifikat SSL (`FR-WEB-01`–`04`).
- [x] **T-303** `PortChecker`: koneksi TCP, pengukuran waktu, pesan timeout/refused (`FR-PORT-01`–`02`).
- [x] **T-304** `CheckRunner`: pilih pemeriksa per jenis, tangkap exception jadi hasil `down`.
- [x] **T-305** `StatusEngine`: ambang konfirmasi kegagalan, buka insiden dengan `started_at` = kegagalan pertama (`FR-WEB-03`, §4.1–4.2).
- [x] **T-306** `StatusEngine`: ambang pemulihan, tutup insiden pada keberhasilan pertama, hitung durasi.
- [x] **T-307** `CheckMonitorJob` (`tries=1`, `timeout=120`) + update `next_check_at`.
- [x] **T-308** `Metrics`: ringkasan uptime/p95/maks, seri per bucket, seri harian (`§4.3`).
- [x] **T-309** `Support\Format`: tanggal Indonesia, durasi manusiawi, persen, waktu respons, interval.

## M4 - Penjadwalan & operasional (FR-OPS)

- [x] **T-401** Perintah `monitors:dispatch`: ambil monitor jatuh tempo, majukan `next_check_at`, enqueue job (`ADR 0002`).
- [x] **T-402** Jadwal `monitors:dispatch` tiap menit dengan `withoutOverlapping()`.
- [x] **T-403** Perintah `monitors:rollup` idempoten (`updateOrCreate` per monitor+tanggal).
- [x] **T-404** Perintah `monitors:prune` menghapus data mentah melewati retensi (`ADR 0004`).
- [x] **T-405** Jadwal harian rollup (00:15) dan prune (02:00).
- [x] **T-406** Perintah `servers:agent-token` (token acak, disimpan sebagai hash).
- [x] **T-407** Notifikasi email opsional saat insiden dibuka/ditutup, dibungkus try/catch.
- [x] **T-408** Notifikasi in-app: banner insiden terbuka di dashboard + badge pada navigasi.

## M5 - Halaman & antarmuka (FR-DASH, FR-INC, FR-REP, FR-SRV)

- [x] **T-501** Layout aplikasi (sidebar gelap, header, toast status, responsif) + layout guest untuk login.
- [x] **T-502** Komponen Blade: `status-badge`, `type-badge`, `stat-card`, `empty-state`, `input-error`.
- [x] **T-503** Dashboard: 4 kartu ringkasan + filter jenis/server (`FR-DASH-01`–`02`).
- [x] **T-504** Dashboard: grafik rata-rata respons per jam 24 jam (`FR-DASH-03`).
- [x] **T-505** Dashboard: panel peringatan SSL ≤ 30 hari + daftar insiden terbaru (`FR-DASH-04`).
- [x] **T-506** Daftar monitor: pencarian, filter, urutan `down` lebih dulu, aksi periksa/ubah (`FR-DASH-02`).
- [x] **T-507** Form monitor dinamis per jenis (HTTP/port/layanan) + validasi (`FR-WEB-01`, `FR-PORT-01`, `FR-SVC-01`).
- [x] **T-508** Detail monitor: header status, uptime 24j/7h/30h, grafik respons+uptime, info SSL, 20 cek terakhir, riwayat insiden (`FR-WEB-05`–`06`).
- [x] **T-509** Halaman insiden: tabel, filter server/monitor/status/tanggal, paginasi (`FR-INC-01`–`02`).
- [x] **T-510** Halaman laporan: filter, agregat, grafik harian, tabel per monitor (`FR-REP-01`–`02`).
- [x] **T-511** Ekspor CSV mengikuti filter aktif (`FR-REP-03`).
- [x] **T-512** Tampilan siap cetak: tombol cetak + CSS `@media print` (`FR-REP-04`).
- [x] **T-513** CRUD server; hapus server mengosongkan relasi monitor (`FR-SRV-01`).

## M6 - QA & rilis MVP

- [x] **T-601** Test mesin status: ambang konfirmasi, tanpa insiden duplikat, pemulihan, email.
- [x] **T-602** Test uptime/series: hanya cek sukses untuk respons, bucket kosong `null`.
- [x] **T-603** Test autentikasi: guest redirect, login valid/invalid, dashboard.
- [x] **T-604** Test filter laporan + isi CSV + halaman insiden.
- [x] **T-605** Validasi manual semua halaman terautentikasi → HTTP 200 tanpa error.
- [x] **T-606** Uji cek nyata: HTTP up, port up, URL gagal → `down` dengan pesan jelas.
- [x] **T-607** Uji API agent: token valid → 201, token salah → 401, metrik tersimpan.
- [x] **T-608** Build aset produksi (`npm run build`) dan pastikan manifest terpakai.
- [x] **T-609** Lengkapi `README.md` + `.env.example` (admin, retensi, timezone, email).
- [x] **T-610** Uji migrasi & query di MySQL dan PostgreSQL. MySQL 8.4.6 dan PostgreSQL 16.9 portable dijalankan pada port terpisah. `migrate:fresh` dan seluruh suite lulus di kedua driver: 19 test, 92 assertion per driver. Query laporan, rollup, insiden, dan CSV yang memakai ekspresi tanggal per driver ikut tervalidasi.
- [x] **T-611** Uji UI di Chrome, Firefox, Safari + viewport mobile 375px. Playwright 1.65.0: Chromium/Edge, Firefox, dan WebKit (mewakili Safari; Safari asli tidak tersedia di Windows) lulus 6 halaman × 375px dan 1440px, Edge juga 768px. Hasil: HTTP 200, 0 scroll horizontal, 0 kontrol < 44px, 0 input tanpa label, 0 error konsol. Menu mobile terbuka dan tertutup dengan Escape; Tab pertama menuju "Lewati ke konten". Bukti di `storage/app/ui-cross.json`, `storage/app/ui-result.json`, dan `storage/app/ui-evidence/`.
- [x] **T-612** Audit aksesibilitas: kontras, fokus keyboard, label form, status tidak hanya warna. Skip link + `main#konten`, fokus global `:focus-visible` 2px teal-700, target sentuh minimum 44px, `aria-current`, `aria-label` pada filter dan aksi ikon, `role="status"`/`role="alert"`, `scope="col"` pada semua tabel, `prefers-reduced-motion`, dan perbaikan kontras teks. Animasi denyut dihapus. Verifikasi otomatis: 0 kontrol < 44px dan 0 input tanpa label di 3 mesin browser. Audit kontras Playwright pada 6 halaman x 2 viewport (375px, 1440px) menemukan 0 pelanggaran rasio WCAG AA; fokus keyboard terukur `outline` 2px solid teal-700 (`rgb(15, 118, 110)`). Bukti di `storage/app/contrast-audit.json`.
- [x] **T-613** Uji beban: 50 monitor x 30 hari data mentah, target dashboard < 1,5 detik. Benchmark SQLite 66 MB (50 monitor, 432.000 cek, 1.500 rollup, 122 insiden) dengan `memory_limit=64M`: dashboard 140–149 ms, `/monitors` 129–138 ms, detail monitor 577–599 ms, `/reports` 395–411 ms, `/reports` rentang 90 hari 1.248–1.263 ms. Sebelum perbaikan, dashboard dan laporan gagal dengan OOM 128 MB. Dashboard, daftar monitor, dan laporan kini memakai agregasi SQL; laporan menggabungkan data mentah dan rollup.
- [x] **T-614** Dokumentasi deployment produksi: cron `schedule:run`, supervisor/systemd untuk `queue:work`, backup database. Lihat [`docs/DEPLOYMENT.md`](DEPLOYMENT.md).

---

## M7 - Fase 2: metrik agent (rencana)

Prasyarat: M6 selesai, khususnya T-610 dan T-614.

- [ ] **T-701** Halaman metrik per server: CPU, RAM, disk, load average.
- [ ] **T-702** Manajemen token agent di UI (terbitkan, rotasi, cabut) dengan tampilan sekali pakai.
- [ ] **T-703** Grafik time-series metrik + penanda `last_seen_at` agent.
- [ ] **T-704** Heartbeat scheduler/worker: peringatan bila pemeriksaan tidak berjalan (mitigasi risiko PRD §15).
- [ ] **T-705** Retensi dan jadwal rollup dapat dikonfigurasi dari UI.
- [ ] **T-706** Pemeriksaan dari beberapa lokasi (distributed probing) + label lokasi pada hasil.

## M8 - Fase 3: notifikasi & skala (rencana)

- [ ] **T-801** Channel notifikasi webhook/Slack/Telegram dengan percobaan ulang.
- [ ] **T-802** Status page publik (read-only, tanpa login).
- [ ] **T-803** Multi-user, peran (admin/viewer), dan audit log.
- [ ] **T-804** Laporan SLA + ekspor PDF server-side.
- [ ] **T-805** Eskalasi, jadwal on-call, dan kebijakan notifikasi per monitor.

---

## Log progres

| Tanggal | Tugas | Catatan |
|---|---|---|
| 11 Nov 2026 | T-001 – T-005 | Dokumen PRD, glosarium, dan 6 ADR selesai. |
| 11 Nov 2026 | T-101 – T-513 | MVP Laravel + antarmuka selesai, data demo 72 jam terisi. |
| 11 Nov 2026 | T-601 – T-609 | 16 test lulus (63 assertion), build aset, verifikasi manual halaman. |
| 11 Nov 2026 | T-611 | Playwright 1.65.0 pada Chromium/Edge, Firefox, WebKit: 36 kombinasi halaman dan viewport, 0 temuan. Safari asli tidak tersedia di Windows, digantikan mesin WebKit. |
| 11 Nov 2026 | T-612 | Audit aksesibilitas dan perbaikan kontras, fokus, target 44px, label, dan status. Screenshot 375px/1440px tersimpan di `storage/app/ui-evidence/`. |
| 11 Nov 2026 | T-613 | Benchmark 50 monitor x 30 hari (432.000 cek). OOM 128 MB diperbaiki dengan agregasi SQL. Semua halaman < 1,5 detik pada `memory_limit=64M`. Suite menjadi 19 test, 92 assertion. |
| 11 Nov 2026 | T-614 | Panduan produksi di `docs/DEPLOYMENT.md`: nginx/PHP-FPM, cron, supervisor/systemd, backup, upgrade, keamanan. |
| 11 Nov 2026 | T-610 | MySQL 8.4.6 dan PostgreSQL 16.9 portable: migrasi dan suite 19 test / 92 assertion lulus di kedua driver. |

---

### Delivery Gate M6

Status: **PASS** untuk seluruh tugas MVP.

| Kriteria | Status | Bukti |
|---|---|---|
| Perilaku sesuai kriteria penerimaan PRD | PASS | Seluruh halaman terautentikasi HTTP 200; laporan dan CSV mengikuti filter; rollup dipakai untuk rentang lebih dari retensi. |
| `php artisan test` hijau | PASS | 19 test, 92 assertion lulus. |
| Kompatibilitas database | PASS | MySQL 8.4.6 dan PostgreSQL 16.9: migrasi dan suite 19 test / 92 assertion lulus di kedua driver. |
| Build aset produksi | PASS | `npm run build` menghasilkan manifest dan aset `public/build`. |
| UI lintas browser dan mobile | PASS | Chromium/Edge, Firefox, WebKit pada 375px dan 1440px; 0 error, 0 overflow, 0 kontrol kecil. |
| Aksesibilitas WCAG AA | PASS | Audit kontras 6 halaman x 375px/1440px: 0 pelanggaran; fokus 2px teal-700; target 44px; label form lengkap; status memakai teks selain warna; reduced motion. |
| Kinerja | PASS | Dashboard 140–149 ms dan laporan 90 hari 1.248–1.263 ms pada data benchmark, `memory_limit=64M`. |
| Dokumentasi rilis | PASS | `README.md`, `docs/DEPLOYMENT.md`, PRD, dan ADR tersedia. |

---

## Catatan & risiko terbuka

- Tanpa queue worker, scheduler tetap berjalan tetapi tidak ada pemeriksaan yang dieksekusi - pantau lewat kolom "cek terakhir" di dashboard (PRD §15).
- `DemoDataSeeder` menghapus seluruh `check_results` dan `incidents`; jangan dijalankan pada data sungguhan.
- Interval minimum efektif 60 detik karena tick scheduler satu menit (`ADR 0002`).
- Data mentah > 30 hari dipangkas; laporan jangka panjang memakai rollup harian dan tidak menyediakan p95 (`ADR 0004`).
- Klaim "monitoring layanan server" di MVP berarti kesehatan yang dapat dijangkau jaringan, bukan metrik internal mesin (`ADR 0006`).
