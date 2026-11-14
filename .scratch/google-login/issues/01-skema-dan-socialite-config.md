# 01: Fondasi Skema Akun OAuth & Konfigurasi Socialite

**What to build:** Dukungan skema basis data untuk akun Google OAuth (`google_id` unik dan `password` nullable pada tabel `users`) serta konfigurasi kredensial Google Socialite agar siap digunakan pada alur autentikasi.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [x] Dependensi `laravel/socialite` terpasang
- [x] Migrasi kolom `google_id` (string, unique, nullable) dan `password` dibuat nullable
- [x] Atribut `google_id` terdaftar di `$fillable` model `User`
- [x] Konfigurasi provider `google` tersedia di `config/services.php`, `.env`, dan `.env.example`
