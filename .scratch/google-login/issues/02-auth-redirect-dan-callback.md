# 02: Alur Redirect & Callback OAuth Google (Auto-link & Buat Akun)

**What to build:** Rute dan controller untuk redirect pengguna ke Google OAuth serta penerimaan callback yang melakukan login, auto-link jika email sudah ada, buat user baru bila belum terdaftar, isolasi dashboard personal, dan penanganan kegagalan OAuth.

**Blocked by:** 01: Fondasi Skema Akun OAuth & Konfigurasi Socialite

**Status:** ready-for-agent

- [x] Rute `auth/google` mengarahkan pengguna ke Google OAuth consent screen
- [x] Rute callback `auth/google/callback` memproses data akun pengguna dari Google
- [x] User lama dengan email yang cocok otomatis ditautkan `google_id` miliknya (auto-link)
- [x] User baru dibuat otomatis tanpa password (`password: null`) dan hak akses non-admin (`is_admin: false`)
- [x] Sesi pengguna diregenerasi dan diarahkan ke dashboard personal
- [x] Kesalahan saat OAuth ditangani anggun dengan redirect kembali ke halaman login membawa pesan error
- [x] Test otomatis memvalidasi seluruh skenario redirect, callback, auto-link, user baru, dan error handling
