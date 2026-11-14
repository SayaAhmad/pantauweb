# Intent: Google OAuth Authentication via Laravel Socialite

## Statement of Intent

- **Outcome**: Login dan registrasi menggunakan akun Google via Laravel Socialite, auto-link email yang sudah terdaftar, dan simpan `google_id`.
- **User**: Pengguna aplikasi umum (non-admin), otomatis diarahkan ke dashboard personal yang terisolasi (hanya melihat monitor dan server miliknya sendiri).
- **Why now**: Mempermudah proses masuk dan registrasi pengguna tanpa perlu mengingat password secara manual.
- **Success**:
  - Tombol login Google tersedia di halaman Login dan Register.
  - Alur redirect ke Google dan callback berhasil melakukan autentikasi sesi user.
  - Kolom `password` di tabel `users` dibuat nullable untuk akun yang terdaftar murni via Google OAuth.
  - User lama yang login via Google dengan email yang sama langsung ditautkan akunnya tanpa duplikasi.
- **Constraint**: Menggunakan pustaka resmi `laravel/socialite`, konfigurasi kredensial Google via `.env` dan `config/services.php`.
- **Out of scope**: Provider OAuth selain Google, sinkronisasi avatar/foto profil pengguna.

## Glosarium Terkait

- **OAuth 2.0**: Protokol otorisasi standar industri yang memungkinkan aplikasi pihak ketiga memperoleh akses terbatas ke layanan pengguna.
- **Laravel Socialite**: Pustaka resmi Laravel untuk menangani alur OAuth dengan berbagai penyedia identitas (Google, GitHub, Facebook, dll.).
- **Auto-link**: Mekanisme menyambungkan identitas OAuth (`google_id`) ke entitas pengguna yang sudah ada berdasarkan pencocokan alamat email unik.
- **Isolated Personal Dashboard**: Ruang kerja pengguna di mana query data (seperti `monitors`, `servers`, `incidents`) secara ketat dibatasi berdasarkan `user_id` yang sedang login.
