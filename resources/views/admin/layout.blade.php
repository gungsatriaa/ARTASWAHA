<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a0a0a">
        <title>@yield('title') — Admin ARTASWAHA</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="sticky top-0 z-20 border-b border-white/10 bg-background/95 backdrop-blur-sm">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-8 sm:py-4">
                <a href="{{ route('admin.lessons.index') }}" class="font-display text-lg font-bold">ARTASWAHA <span class="text-accent">/ Admin</span></a>
                <nav class="hidden items-center gap-2 text-sm font-semibold md:flex" aria-label="Navigasi admin">
                    <a href="{{ route('admin.lessons.index') }}" @class(['rounded-full px-4 py-2 hover:bg-surface', 'bg-surface text-accent' => request()->routeIs('admin.lessons.*')])>Pelajaran</a>
                    <a href="{{ route('admin.learning-paths.index') }}" @class(['rounded-full px-4 py-2 hover:bg-surface', 'bg-surface text-accent' => request()->routeIs('admin.learning-paths.*')])>Jalur belajar</a>
                    <a href="{{ route('home') }}" class="rounded-full px-4 py-2 hover:bg-surface">Lihat situs</a>
                </nav>
                <button type="button" data-mobile-nav-open aria-controls="admin-mobile-navigation" aria-expanded="false" aria-label="Buka menu admin" class="grid size-10 place-items-center rounded-full border border-white/10 bg-surface text-foreground transition hover:border-accent/50 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent md:hidden">
                    <span class="grid gap-1.5" aria-hidden="true"><span class="h-0.5 w-5 rounded-full bg-current"></span><span class="h-0.5 w-5 rounded-full bg-current"></span><span class="h-0.5 w-5 rounded-full bg-current"></span></span>
                </button>
            </div>
        </header>
        <div data-mobile-nav-backdrop aria-controls="admin-mobile-navigation" aria-hidden="true"></div>
        <aside id="admin-mobile-navigation" data-mobile-nav role="dialog" aria-modal="true" aria-label="Navigasi admin" aria-hidden="true" inert class="z-50 flex h-dvh flex-col border-l border-white/10 bg-surface p-5 text-foreground shadow-2xl shadow-black/50">
                <div class="flex items-center justify-between border-b border-white/10 pb-5">
                    <span class="font-display text-lg font-bold">ARTASWAHA <span class="text-accent">/ Admin</span></span>
                    <button type="button" data-mobile-nav-close aria-label="Tutup menu" class="grid size-10 place-items-center rounded-full bg-background text-xl text-foreground/70 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">&times;</button>
                </div>
                <nav class="mt-5 flex-1 space-y-2" aria-label="Navigasi admin mobile">
                    <a href="{{ route('admin.lessons.index') }}" @class(['flex min-h-12 items-center rounded-2xl px-4 font-semibold transition', 'bg-primary/20 text-accent' => request()->routeIs('admin.lessons.*'), 'text-foreground/75 hover:bg-background hover:text-accent' => ! request()->routeIs('admin.lessons.*')])>Pelajaran</a>
                    <a href="{{ route('admin.learning-paths.index') }}" @class(['flex min-h-12 items-center rounded-2xl px-4 font-semibold transition', 'bg-primary/20 text-accent' => request()->routeIs('admin.learning-paths.*'), 'text-foreground/75 hover:bg-background hover:text-accent' => ! request()->routeIs('admin.learning-paths.*')])>Jalur belajar</a>
                    <a href="{{ route('home') }}" class="mt-4 flex min-h-12 items-center rounded-2xl px-4 font-semibold text-foreground/75 transition hover:bg-background hover:text-accent">Lihat situs</a>
                </nav>
        </aside>
        <main class="mx-auto max-w-6xl px-5 py-8 sm:px-8 sm:py-12">
            @if (session('status'))
                <p role="status" class="mb-6 rounded-2xl bg-primary/20 p-4 text-sm font-semibold text-accent">{{ session('status') }}</p>
            @endif
            @yield('content')
        </main>
    </body>
</html>
