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
<body class="flex min-h-full items-center justify-center bg-slate-950 px-4 py-10">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-24 top-1/4 h-72 w-72 rounded-full bg-teal-500/10 blur-3xl"></div>
        <div class="absolute -right-16 bottom-1/4 h-80 w-80 rounded-full bg-emerald-500/10 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-sm">
        {{ $slot }}
    </div>
</body>
</html>
