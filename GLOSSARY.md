# Pantau — Glosarium Domain

Pantau adalah alat pemantauan ketersediaan website, port, dan layanan server yang di-host sendiri untuk satu organisasi. Glosarium ini menetapkan istilah kanonik yang dipakai di dokumen, kode, dan antarmuka.

## Bahasa

**Monitor**:
Entitas yang dipantau secara berkala: sebuah website, port, atau layanan.
_Avoid_: Target, resource, endpoint

**Jenis Monitor**:
Klasifikasi cara sebuah monitor diperiksa: `http`, `port`, atau `service`.
_Avoid_: Kategori, tipe target

**Pemeriksaan**:
Satu eksekusi uji terhadap sebuah monitor pada satu waktu.
_Avoid_: Test, probe, ping, request

**Hasil Pemeriksaan**:
Catatan hasil satu pemeriksaan: status, kode HTTP, waktu respons, dan pesan galat.
_Avoid_: Log, metrik, sampel

**Status Monitor**:
Status terkonfirmasi sebuah monitor — `up`, `down`, atau `unknown` — yang hanya berubah setelah ambang konfirmasi terpenuhi.
_Avoid_: State, status live, health

**Ambang Konfirmasi**:
Jumlah kegagalan berturut-turut yang diperlukan sebelum monitor dinyatakan `down`.
_Avoid_: Retry, toleransi, threshold gagal

**Ambang Pemulihan**:
Jumlah keberhasilan berturut-turut yang diperlukan sebelum monitor dinyatakan `up` kembali.
_Avoid_: Threshold sukses, retry pulih

**Insiden**:
Periode ketika sebuah monitor berada pada status `down`, dengan waktu mulai, waktu pulih, dan durasi.
_Avoid_: Outage, gangguan, alert

**Uptime**:
Persentase hasil pemeriksaan berstatus `up` terhadap seluruh pemeriksaan dalam rentang waktu tertentu.
_Avoid_: Availability, SLA, ketersediaan

**Downtime**:
Total durasi insiden sebuah monitor dalam rentang waktu tertentu.
_Avoid_: Uptime negatif, outage time

**Server**:
Mesin atau host yang menaungi monitor, dipakai untuk pengelompokan dan penyaringan.
_Avoid_: Node, device, mesin

**Layanan**:
Aplikasi atau proses di server yang diperiksa melalui endpoint kesehatan HTTP.
_Avoid_: Aplikasi, daemon, proses

**Masa Berlaku SSL**:
Tanggal kedaluwarsa sertifikat TLS yang terbaca saat pemeriksaan HTTP.
_Avoid_: SSL check, sertifikat

**Rollup Harian**:
Agregat pemeriksaan per monitor per hari yang disimpan permanen setelah data mentah dipangkas.
_Avoid_: Summary, cache, statistik

**Pemeriksaan Eksternal**:
Pemeriksaan yang dijalankan dari luar server target melalui jaringan.
_Avoid_: Blackbox, remote check

**Agent**:
Program yang dipasang di server untuk mengirim metrik internal (CPU, RAM, disk, status proses) ke Pantau. Di luar cakupan MVP.
_Avoid_: Bot, collector, exporter
