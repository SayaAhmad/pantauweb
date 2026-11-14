# Autentikasi Pengguna Menggunakan Google OAuth via Laravel Socialite

Kami menggunakan Laravel Socialite untuk mendukung login dan registrasi berbasis Google OAuth 2.0 dengan penautan otomatis (auto-link) ke akun pengguna berdasarkan alamat email. Kolom `password` pada tabel `users` dijadikan opsional (`nullable`), dan kolom `google_id` ditambahkan untuk melacak identitas eksternal.

Pengguna yang masuk melalui alur ini langsung diarahkan ke dashboard personal mereka yang terisolasi tanpa hak akses administratif (`is_admin: false` secara default), memastikan prinsip isolasi data antar pengguna tetap terjaga.

**Konsekuensi:**
1. Akun pengguna yang mendaftar murni via Google tidak memiliki password lokal, sehingga proses login manual harus diblokir atau diarahkan ke reset password/OAuth.
2. Skema tabel `users` perlu dimigrasi untuk membuat `password` nullable dan menambahkan indeks unik pada `google_id` yang juga nullable.
3. Dependensi eksternal bertambah berupa paket `laravel/socialite` serta kebutuhan konfigurasi kredensial Google OAuth (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`) di environment.
