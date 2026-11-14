@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Masuk' }} · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative flex min-h-full items-center justify-center overflow-x-hidden bg-slate-950 px-4 py-10 selection:bg-teal-500 selection:text-white">
    <!-- Animated background ambient glowing gradients -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="animate-pulse absolute -left-20 top-1/4 h-96 w-96 rounded-full bg-gradient-to-tr from-teal-500/20 to-emerald-500/10 blur-[100px] transition-all duration-1000"></div>
        <div class="animate-pulse absolute -right-20 bottom-1/4 h-[28rem] w-[28rem] rounded-full bg-gradient-to-bl from-cyan-500/15 via-teal-500/15 to-emerald-600/10 blur-[110px] [animation-delay:2s]"></div>
        <div class="absolute left-1/2 top-10 h-72 w-72 -translate-x-1/2 rounded-full bg-teal-400/10 blur-[90px]"></div>
        <!-- Grid pattern overlay for high-tech monitor feel -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b12_1px,transparent_1px),linear-gradient(to_bottom,#1e293b12_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)]"></div>
    </div>

    <div class="relative w-full max-w-sm">
        {{ $slot }}
    </div>
<!-- impeccable-live-start -->
<script src="http://localhost:8400/live.js?token=4db41107-660c-4d64-b3c1-325c3446b093"></script>
<!-- impeccable-live-end -->
</body>
</html>
