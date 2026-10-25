@props(['learningPaths' => null, 'title' => 'ARTASWAHA - Platform Edukasi Bahasa, Sastra, dan Aksara Bali.', 'description' => 'Pelajari bahasa, sastra, paribasa, dan aksara Bali melalui materi interaktif dan kuis di ARTASWAHA.', 'canonical' => null, 'showFooter' => true, 'mobileDashboard' => false, 'mobileLearningPath' => null])

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a0a0a">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $description }}">
        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
            <meta property="og:type" content="website">
            <meta property="og:locale" content="id_ID">
            <meta property="og:site_name" content="ARTASWAHA">
            <meta property="og:title" content="{{ $title }}">
            <meta property="og:description" content="{{ $description }}">
            <meta property="og:url" content="{{ $canonical }}">
        @else
            <meta name="robots" content="noindex, nofollow">
        @endif
        <link rel="icon" type="image/webp" href="{{ asset('image/maskot.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('image/maskot.webp') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400..800&family=Plus+Jakarta+Sans:wght@400..800&display=swap" rel="stylesheet">
        <title>{{ $title }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-presence-url="{{ route('presence.ping') }}" @class(['min-h-screen bg-background font-sans text-foreground antialiased', 'flex flex-col' => ! $showFooter, 'mobile-dashboard' => $mobileDashboard])>
        <x-site-nav :learning-paths="$learningPaths" :mobile-learning-path="$mobileLearningPath" />
        <main @class(['flex-1' => ! $showFooter])>
            {{ $slot }}
        </main>
        @if ($showFooter)
            <x-site-footer :learning-paths="$learningPaths" />
        @endif
    </body>
</html>
