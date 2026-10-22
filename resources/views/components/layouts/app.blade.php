@props(['learningPaths', 'title' => 'ARTASWAHA — Belajar Bali dengan gembira'])

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#111315">
        <link rel="icon" type="image/webp" href="{{ asset('image/maskot.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('image/maskot.webp') }}">
        <title>{{ $title }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <x-site-nav :learning-paths="$learningPaths" />
        <main>
            {{ $slot }}
        </main>
        <x-site-footer :learning-paths="$learningPaths" />
    </body>
</html>
