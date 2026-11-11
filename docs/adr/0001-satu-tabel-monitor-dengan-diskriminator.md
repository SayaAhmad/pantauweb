# Satu tabel `monitors` dengan diskriminator jenis monitor

Kami memakai satu tabel `monitors` dengan kolom `type` (`http`, `port`, `service`) dan kolom konfigurasi bertipe, bukan tiga tabel terpisah. Dashboard, daftar insiden, perhitungan uptime, filter laporan, dan penjadwalan pemeriksaan semuanya beroperasi pada kumpulan monitor yang seragam; tiga tabel akan memaksa penggabungan (UNION) dan polimorfisme di hampir setiap query. Menambah jenis monitor baru cukup menambah nilai `type` dan satu pemeriksa (checker), bukan tabel dan relasi baru.

**Konsekuensi:** kolom yang tidak relevan untuk suatu jenis monitor bernilai `NULL`, dan validasi bentuk konfigurasi menjadi tanggung jawab lapisan aplikasi (`MonitorRequest`), bukan skema basis data.
