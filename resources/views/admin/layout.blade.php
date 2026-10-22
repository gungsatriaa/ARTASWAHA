<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#000000">
        <title>@yield('title') — Admin ArtaSwaha</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="sticky top-0 z-20 border-b border-white/10 bg-background/95 backdrop-blur-sm">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
                <a href="{{ route('admin.lessons.index') }}" class="font-display text-lg font-bold">ARTASWAHA <span class="text-accent">/ Admin</span></a>
                <nav class="flex items-center gap-2 text-sm font-semibold" aria-label="Navigasi admin">
                    <a href="{{ route('admin.lessons.index') }}" @class(['rounded-full px-4 py-2 hover:bg-surface', 'bg-surface text-accent' => request()->routeIs('admin.lessons.*')])>Pelajaran</a>
                    <a href="{{ route('admin.learning-paths.index') }}" @class(['rounded-full px-4 py-2 hover:bg-surface', 'bg-surface text-accent' => request()->routeIs('admin.learning-paths.*')])>Jalur belajar</a>
                    <a href="{{ route('home') }}" class="rounded-full px-4 py-2 hover:bg-surface">Lihat situs</a>
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-5 py-8 sm:px-8 sm:py-12">
            @if (session('status'))
                <p role="status" class="mb-6 rounded-2xl bg-primary/20 p-4 text-sm font-semibold text-accent">{{ session('status') }}</p>
            @endif
            @yield('content')
        </main>
    </body>
</html>
