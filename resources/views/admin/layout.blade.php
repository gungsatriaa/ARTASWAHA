<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a0a0a">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400..800&family=Plus+Jakarta+Sans:wght@400..800&display=swap" rel="stylesheet">
        <title>@yield('title') — Admin ARTASWAHA</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="sticky top-0 z-20 border-b border-white/10 bg-background/95 backdrop-blur-sm">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-3 sm:px-8 sm:py-4">
                <a href="{{ route('admin.index') }}" class="flex items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" aria-label="Dashboard admin ARTASWAHA">
                    <img src="{{ asset('image/maskot.webp') }}" alt="" class="size-10 rounded-full object-cover sm:size-12">
                    <span class="font-display text-lg font-bold tracking-tight sm:text-xl">ARTASWAHA <span class="text-accent">/ Admin</span></span>
                </a>
                <nav class="hidden items-center gap-1 text-sm font-semibold lg:flex lg:gap-2" aria-label="Navigasi admin">
                    <a href="{{ route('admin.index') }}" @class(['inline-flex min-h-11 items-center gap-2 rounded-full px-4 py-3 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent', 'bg-primary/20 text-accent' => request()->routeIs('admin.index'), 'text-foreground/80 hover:bg-surface' => ! request()->routeIs('admin.index')]) @if (request()->routeIs('admin.index')) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.lessons.index') }}" @class(['inline-flex min-h-11 items-center gap-2 rounded-full px-4 py-3 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent', 'bg-primary/20 text-accent' => request()->routeIs('admin.lessons.*'), 'text-foreground/80 hover:bg-surface' => ! request()->routeIs('admin.lessons.*')]) @if (request()->routeIs('admin.lessons.*')) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>
                        Pelajaran
                    </a>
                    <a href="{{ route('admin.learning-paths.index') }}" @class(['inline-flex min-h-11 items-center gap-2 rounded-full px-4 py-3 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent', 'bg-primary/20 text-accent' => request()->routeIs('admin.learning-paths.*'), 'text-foreground/80 hover:bg-surface' => ! request()->routeIs('admin.learning-paths.*')]) @if (request()->routeIs('admin.learning-paths.*')) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><circle cx="6" cy="18" r="2"/><circle cx="18" cy="6" r="2"/><path d="M8 18h3a5 5 0 0 0 5-5V8"/></svg>
                        Jalur belajar
                    </a>
                    <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 py-3 text-foreground/80 transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9M9 21v-7h6v7"/></svg>
                        Lihat situs
                    </a>
                </nav>
                <button type="button" data-mobile-nav-open aria-controls="admin-mobile-navigation" aria-expanded="false" aria-label="Buka menu admin" class="grid size-10 place-items-center rounded-full border border-white/10 bg-surface text-foreground transition hover:border-accent/50 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent lg:hidden">
                    <span class="grid gap-1.5" aria-hidden="true"><span class="h-0.5 w-5 rounded-full bg-current"></span><span class="h-0.5 w-5 rounded-full bg-current"></span><span class="h-0.5 w-5 rounded-full bg-current"></span></span>
                </button>
            </div>
        </header>
        <div data-mobile-nav-backdrop aria-controls="admin-mobile-navigation" aria-hidden="true"></div>
        <aside id="admin-mobile-navigation" data-mobile-nav role="dialog" aria-modal="true" aria-label="Navigasi admin" aria-hidden="true" inert class="z-50 flex h-dvh flex-col border-l border-white/10 bg-background p-5 text-foreground shadow-2xl shadow-black/50">
                <div class="flex items-center justify-between border-b border-white/10 pb-5">
                    <a href="{{ route('admin.index') }}" class="flex items-center gap-3 rounded-full font-display text-lg font-bold focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"><img src="{{ asset('image/maskot.webp') }}" alt="" class="size-10 rounded-full object-cover">ARTASWAHA <span class="text-accent">/ Admin</span></a>
                    <button type="button" data-mobile-nav-close aria-label="Tutup menu" class="grid size-10 place-items-center rounded-full bg-background text-foreground/70 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="size-4" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
                </div>
                <nav class="mt-5 flex-1 space-y-3 overflow-y-auto overscroll-contain pb-6" aria-label="Navigasi admin mobile">
                    <a href="{{ route('admin.index') }}" @class(['flex min-h-12 items-center gap-3 rounded-full px-4 font-semibold transition focus-visible:outline-2 focus-visible:outline-accent', 'bg-primary/20 text-accent shadow-[inset_0_0_0_1px_rgba(0,124,128,0.35)]' => request()->routeIs('admin.index'), 'text-foreground/75 hover:bg-surface hover:text-accent' => ! request()->routeIs('admin.index')])>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.lessons.index') }}" @class(['flex min-h-12 items-center gap-3 rounded-full px-4 font-semibold transition focus-visible:outline-2 focus-visible:outline-accent', 'bg-primary/20 text-accent shadow-[inset_0_0_0_1px_rgba(0,124,128,0.35)]' => request()->routeIs('admin.lessons.*'), 'text-foreground/75 hover:bg-surface hover:text-accent' => ! request()->routeIs('admin.lessons.*')])>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>
                        Pelajaran
                    </a>
                    <a href="{{ route('admin.learning-paths.index') }}" @class(['flex min-h-12 items-center gap-3 rounded-full px-4 font-semibold transition focus-visible:outline-2 focus-visible:outline-accent', 'bg-primary/20 text-accent shadow-[inset_0_0_0_1px_rgba(0,124,128,0.35)]' => request()->routeIs('admin.learning-paths.*'), 'text-foreground/75 hover:bg-surface hover:text-accent' => ! request()->routeIs('admin.learning-paths.*')])>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><circle cx="6" cy="18" r="2"/><circle cx="18" cy="6" r="2"/><path d="M8 18h3a5 5 0 0 0 5-5V8"/></svg>
                        Jalur belajar
                    </a>
                    <a href="{{ route('home') }}" class="mt-4 flex min-h-12 items-center gap-3 rounded-full px-4 font-semibold text-foreground/75 transition hover:bg-surface hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9M9 21v-7h6v7"/></svg>
                        Lihat situs
                    </a>
                </nav>
                <div class="flex items-center gap-3 border-t border-white/10 pt-4">
                    <div class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl p-2">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-accent font-display font-extrabold text-background">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-xs text-foreground/55">{{ auth()->user()->email }}</span>
                        </span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="Keluar" title="Keluar" class="grid size-11 shrink-0 place-items-center rounded-full text-red-400 transition hover:bg-red-400/10 hover:text-red-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-400">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                        </button>
                    </form>
                </div>
        </aside>
        <main class="mx-auto max-w-6xl px-5 py-8 sm:px-8 sm:py-12">
            @if (session('status'))
                <p role="status" class="mb-6 rounded-2xl bg-primary/20 p-4 text-sm font-semibold text-accent">{{ session('status') }}</p>
            @endif
            @yield('content')
        </main>
    </body>
</html>
