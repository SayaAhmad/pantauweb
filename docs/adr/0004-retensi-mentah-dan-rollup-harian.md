# Simpan hasil pemeriksaan mentah 30 hari, rollup harian permanen

Hasil pemeriksaan mentah tumbuh sangat cepat (satu monitor interval 1 menit menghasilkan 1.440 baris per hari) sehingga dipangkas setelah 30 hari. Sebelum dipangkas, sebuah perintah harian mengagregasinya menjadi `daily_rollups` (jumlah pemeriksaan, jumlah sukses, rata-rata dan maksimum waktu respons) yang disimpan permanen dan menjadi sumber laporan jangka panjang.

**Konsekuensi:** grafik respons dan uptime untuk rentang di luar 30 hari memakai resolusi harian, bukan per pemeriksaan; angka p95 jangka panjang tidak tersedia.
