# Antarmuka server-rendered dengan Blade, Tailwind, Alpine, dan Chart.js

Antarmuka dibangun dengan Blade yang dirender server, Tailwind CSS v4, Alpine.js untuk interaksi kecil, dan Chart.js untuk grafik. Kami menolak SPA (Inertia/React) dan Livewire karena keduanya menambah lapisan build, state, atau siklus hidup komponen yang tidak dibutuhkan oleh aplikasi baca-dominan dengan sedikit formulir; polling ringan sudah cukup untuk status yang diperbarui tiap menit.

**Konsekuensi:** pembaruan status tanpa memuat ulang halaman dilakukan lewat polling fetch, bukan reaktivitas sisi klien; tidak ada API JSON internal untuk konsumsi SPA.
