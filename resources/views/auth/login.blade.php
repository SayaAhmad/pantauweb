<x-guest-layout title="Masuk">
    <style>
        @keyframes pulse-path {
            0% { stroke-dashoffset: 80; }
            50% { stroke-dashoffset: 0; }
            100% { stroke-dashoffset: -80; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(2deg); }
        }
        @keyframes shimmer {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .ecg-path {
            stroke-dasharray: 80;
            animation: pulse-path 3.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .animated-gradient-btn {
            background-size: 200% 200%;
            animation: shimmer 5s ease infinite;
        }
        .float-logo {
            animation: float 6s ease-in-out infinite;
        }
        .title-shimmer {
            background-size: 200% 100%;
            animation: shimmer 4s linear infinite;
        }
    </style>

    <div class="mb-6 flex flex-col items-center text-center">
        <!-- Live status indicator badge -->
        <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-teal-500/30 bg-teal-950/50 px-3.5 py-1 text-xs font-medium text-teal-300 shadow-[0_0_20px_rgba(20,184,166,0.2)] backdrop-blur-md">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-teal-400"></span>
            </span>
            Sistem Pemantauan Online
        </div>

        <!-- Logo with animated glow ring and gradient -->
        <div class="float-logo relative flex items-center justify-center">
            <span class="absolute -inset-2 animate-pulse rounded-2xl bg-gradient-to-r from-teal-500 via-cyan-400 to-emerald-400 opacity-50 blur-lg"></span>
            <span class="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900 ring-1 ring-inset ring-teal-400/60 shadow-2xl shadow-teal-950">
                <svg class="h-8 w-8 text-teal-300 drop-shadow-[0_0_10px_rgba(45,212,191,0.7)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <path class="ecg-path" stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h3l2.25-6 3.75 12 2.25-6h5.25" />
                </svg>
            </span>
        </div>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-white via-teal-100 to-emerald-300 title-shimmer">
            Pantau
        </h1>
        <p class="mt-1.5 text-sm text-slate-400">Masuk untuk memantau website, port, dan layanan.</p>
    </div>

    <!-- Gradient border wrapper for glassmorphism card -->
    <div class="group relative rounded-2xl p-[1px] bg-gradient-to-b from-teal-400/40 via-emerald-500/20 to-slate-800 shadow-[0_0_40px_rgba(13,148,136,0.15)]">
        <div class="rounded-2xl bg-slate-900/90 p-7 backdrop-blur-2xl transition duration-300">
            @if ($errors->any())
                <div id="form-errors" class="mb-5 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200 backdrop-blur-sm" role="alert">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-300">Email</label>
                    <div class="relative">
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="admin@pantau.test"
                            @error('email') aria-invalid="true" aria-describedby="form-errors" @enderror
                            class="min-h-11 w-full rounded-xl border border-slate-700/80 bg-slate-950/70 px-3.5 py-2.5 text-base text-white outline-none transition-all duration-200 placeholder:text-slate-500 focus:border-teal-400 focus:bg-slate-950 focus:ring-2 focus:ring-teal-400/40 focus:shadow-[0_0_20px_rgba(45,212,191,0.2)] sm:text-sm"
                        >
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-medium text-slate-300">Kata sandi</label>
                    </div>
                    <div class="relative">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Kata sandi"
                            @error('password') aria-invalid="true" aria-describedby="form-errors" @enderror
                            class="min-h-11 w-full rounded-xl border border-slate-700/80 bg-slate-950/70 px-3.5 py-2.5 text-base text-white outline-none transition-all duration-200 placeholder:text-slate-500 focus:border-teal-400 focus:bg-slate-950 focus:ring-2 focus:ring-teal-400/40 focus:shadow-[0_0_20px_rgba(45,212,191,0.2)] sm:text-sm"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm py-1">
                    <label class="flex items-center gap-2 text-slate-400 cursor-pointer select-none group/remember">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-teal-500 focus:ring-teal-400/50">
                        <span class="group-hover/remember:text-slate-300 transition-colors">Ingat saya</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="animated-gradient-btn group relative min-h-11 w-full overflow-hidden rounded-xl bg-gradient-to-r from-teal-500 via-emerald-400 to-cyan-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-[0_0_25px_rgba(20,184,166,0.35)] transition-all duration-300 hover:shadow-[0_0_35px_rgba(20,184,166,0.55)] hover:-translate-y-0.5 active:translate-y-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900"
                >
                    <span class="relative z-10 flex items-center justify-center gap-2 font-bold tracking-wide">
                        Masuk
                        <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-slate-400">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-semibold text-teal-400 transition-colors duration-150 hover:text-teal-300 underline underline-offset-4 decoration-teal-500/30 hover:decoration-teal-400">Buat akun</a>
            </p>

            @if (app()->environment('local'))
                <div class="mt-6 rounded-xl border border-teal-500/20 bg-gradient-to-r from-slate-950/90 to-teal-950/30 p-3.5 text-xs text-slate-400 backdrop-blur-sm">
                    <div class="flex items-center gap-1.5 font-medium text-teal-400 mb-1">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        Akun demo cepat:
                    </div>
                    <div class="font-mono text-slate-300 select-all">
                        admin@pantau.test <span class="text-slate-500">/</span> password
                    </div>
                </div>
            @endif
        </div>
    </div>

    <p class="mt-6 text-center text-[11px] text-slate-500 tracking-wider uppercase font-medium">Satu instalasi untuk satu organisasi · MVP</p>
</x-guest-layout>
