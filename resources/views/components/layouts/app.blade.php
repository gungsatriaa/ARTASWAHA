@props(['learningPaths' => null, 'title' => 'ARTASWAHA - Platform Edukasi Bahasa, Sastra, dan Aksara Bali.', 'showFooter' => true])

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a0a0a">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="ARTASWAHA - Platform Edukasi Bahasa, Sastra, dan Aksara Bali.">
        <link rel="icon" type="image/webp" href="{{ asset('image/maskot.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('image/maskot.webp') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400..800&family=Plus+Jakarta+Sans:wght@400..800&display=swap" rel="stylesheet">
        <title>{{ $title }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body @class(['min-h-screen bg-background font-sans text-foreground antialiased', 'flex flex-col' => ! $showFooter])>
        <x-site-nav :learning-paths="$learningPaths" />
        <main @class(['flex-1' => ! $showFooter])>
            {{ $slot }}
        </main>
        @if ($showFooter)
            <x-site-footer :learning-paths="$learningPaths" />
        @endif
    </body>
</html>
