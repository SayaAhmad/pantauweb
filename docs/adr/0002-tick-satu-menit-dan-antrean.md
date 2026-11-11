# Penjadwalan pemeriksaan lewat "tick" satu menit dan antrean

Satu perintah terjadwal (`monitors:dispatch`) berjalan setiap menit, mengambil monitor aktif yang `next_check_at`-nya sudah lewat, meng-enqueue satu job per monitor, lalu memajukan `next_check_at` sesuai interval monitor tersebut. Alternatifnya — satu entri cron per monitor — membutuhkan penulisan ulang crontab setiap kali monitor dibuat atau diubah, sulit dijalankan di dalam container, dan tidak dapat diuji.

**Konsekuensi:** interval minimum efektif adalah 60 detik; job membutuhkan queue worker yang hidup. Tanpa worker, pemeriksaan tidak berjalan meski jadwal sudah terpicu.
