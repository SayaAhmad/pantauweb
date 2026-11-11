# MVP hanya memeriksa dari luar server; metrik agent ditunda

Seluruh pemeriksaan di MVP bersifat eksternal (HTTP/TLS dan TCP). Skema `servers`, `server_metrics`, serta endpoint ingest agent sudah disiapkan, tetapi antarmuka metrik internal (CPU, RAM, disk, status proses) belum dibangun. Ini keputusan lingkup yang disengaja: memantau ketersediaan dari sudut pandang pengguna adalah nilai inti produk, sedangkan agent membawa kebutuhan instalasi, keamanan token, dan operasi tambahan yang akan memperlambat MVP.

**Konsekuensi:** klaim "monitoring layanan server" di MVP berarti kesehatan layanan yang dapat dijangkau lewat jaringan, bukan kesehatan mesin di dalamnya.
