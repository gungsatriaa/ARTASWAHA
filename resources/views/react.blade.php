<!DOCTYPE html>
<html lang="id" class="react-ui">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a0a0a">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        <link rel="icon" type="image/webp" href="{{ asset('image/maskot.webp') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400..800&family=Plus+Jakarta+Sans:wght@400..800&display=swap" rel="stylesheet">
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/react/app.jsx'])
        <x-inertia::head>
            <title>{{ $page['props']['meta']['title'] ?? 'ARTASWAHA' }}</title>
        </x-inertia::head>
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <x-inertia::app />
        <noscript><p class="mx-auto max-w-xl p-6">Aktifkan JavaScript untuk membuka aplikasi belajar. <a href="{{ route('home') }}" class="text-accent underline">Kembali ke beranda</a>.</p></noscript>
    </body>
</html>
