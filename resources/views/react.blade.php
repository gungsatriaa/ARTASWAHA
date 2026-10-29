<!DOCTYPE html>
<html lang="id" class="react-ui">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a0a0a">
        <link rel="apple-touch-icon" href="{{ asset('image/maskot.webp') }}">
        <link rel="icon" type="image/webp" href="{{ asset('image/maskot.webp') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400..800&family=Plus+Jakarta+Sans:wght@400..800&display=swap" rel="stylesheet">
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/react/app.jsx'])
        @if ($ssr)
            {!! implode("\n", $ssr['head']) !!}
        @else
            <title data-react-head="title">{{ $page['props']['meta']['title'] ?? 'ARTASWAHA' }}</title>
            <meta name="csrf-token" content="{{ csrf_token() }}" data-react-head="csrf-token">
            <meta name="description" content="{{ $page['props']['meta']['description'] ?? '' }}" data-react-head="description">
            <meta name="robots" content="{{ $page['props']['meta']['robots'] ?? 'noindex, nofollow' }}" data-react-head="robots">
            <link rel="canonical" href="{{ $page['props']['meta']['canonical'] ?? url()->current() }}" data-react-head="canonical">
            <meta property="og:title" content="{{ $page['props']['meta']['title'] ?? 'ARTASWAHA' }}" data-react-head="og:title">
            <meta property="og:description" content="{{ $page['props']['meta']['description'] ?? '' }}" data-react-head="og:description">
            <meta property="og:locale" content="id_ID" data-react-head="og:locale">
            <meta property="og:site_name" content="ARTASWAHA" data-react-head="og:site_name">
            <meta property="og:type" content="website" data-react-head="og:type">
            <meta property="og:url" content="{{ $page['props']['meta']['canonical'] ?? url()->current() }}" data-react-head="og:url">
            <meta name="twitter:card" content="summary" data-react-head="twitter:card">
            @if (! empty($page['props']['meta']['schema']))
                <script type="application/ld+json" data-react-head="structured-data">{!! json_encode($page['props']['meta']['schema'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
            @endif
        @endif
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <x-react-loader />
        <script id="react-page" type="application/json">{!! json_encode($page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
        <div id="app">{!! $ssr['body'] ?? '' !!}</div>
        <noscript><p class="mx-auto max-w-xl p-6">Aktifkan JavaScript untuk membuka aplikasi belajar. <a href="{{ route('home') }}" class="text-accent underline">Kembali ke beranda</a>.</p></noscript>
    </body>
</html>
