# Status terkonfirmasi, ambang 2/2, dan waktu insiden yang retroaktif

Status monitor tidak mengikuti hasil pemeriksaan terakhir begitu saja. Monitor baru dinyatakan `down` setelah 2 kegagalan berturut-turut dan kembali `up` setelah 2 keberhasilan berturut-turut (keduanya dapat dikonfigurasi per monitor). Insiden dibuka saat transisi ke `down`, tetapi `started_at`-nya adalah waktu kegagalan pertama dalam rentetan tersebut, sehingga durasi insiden mencakup masa konfirmasi. Insiden ditutup pada keberhasilan pertama dalam rentetan pemulihan, bukan saat ambang pemulihan terpenuhi.

**Konsekuensi:** dashboard dapat menampilkan monitor yang "sedang gagal tetapi belum `down`". Uptime tetap dihitung dari seluruh hasil pemeriksaan, bukan dari durasi insiden, sehingga dua angka ini bisa berbeda secara wajar.
