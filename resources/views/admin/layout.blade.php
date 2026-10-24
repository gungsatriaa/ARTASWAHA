<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a0a0a">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/webp" href="{{ asset('image/maskot.webp') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400..800&family=Plus+Jakarta+Sans:wght@400..800&display=swap" rel="stylesheet">
        <title>@yield('title') — Admin ARTASWAHA</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-presence-url="{{ route('presence.ping') }}" class="min-h-screen bg-background font-sans text-foreground antialiased">
        <div class="lg:grid lg:min-h-screen lg:grid-cols-[16rem_minmax(0,1fr)]">
            <aside class="sticky top-0 hidden h-screen flex-col border-r border-white/10 bg-surface lg:flex" aria-label="Navigasi admin">
                <a href="{{ route('admin.index') }}" class="flex min-h-20 items-center gap-3 border-b border-white/10 px-6 focus-visible:outline-2 focus-visible:outline-accent" aria-label="Dashboard admin ARTASWAHA">
                    <img src="{{ asset('image/maskot.webp') }}" alt="" class="size-10 rounded-full object-cover">
                    <span class="min-w-0"><span class="block truncate font-display text-lg font-bold">ARTASWAHA</span><span class="block text-xs font-semibold text-accent">Panel admin</span></span>
                </a>
                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-6">@include('admin.navigation')</div>
                <div class="border-t border-white/10 p-4">
                    <div class="flex items-center gap-3 rounded-2xl bg-background p-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary/25 font-display font-bold text-accent">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                        <span class="min-w-0"><span class="block truncate text-sm font-bold">{{ auth()->user()->name }}</span><span class="block truncate text-xs text-foreground/50">Administrator</span></span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">@csrf<button type="submit" class="flex min-h-11 w-full items-center rounded-xl px-3 text-sm font-semibold text-foreground/65 transition hover:bg-background hover:text-danger focus-visible:outline-2 focus-visible:outline-accent">Keluar akun</button></form>
                </div>
            </aside>

            <div class="min-w-0">
                <header class="sticky top-0 z-20 border-b border-white/10 bg-background/95 backdrop-blur-sm">
                    <div class="flex min-h-20 items-center justify-between gap-4 px-5 sm:px-8 xl:px-10">
                        <div class="flex min-w-0 items-center gap-3">
                            <button type="button" data-mobile-nav-open aria-controls="admin-mobile-navigation" aria-expanded="false" aria-label="Buka menu admin" class="grid size-11 shrink-0 place-items-center rounded-xl border border-white/10 text-foreground/80 transition hover:border-accent/50 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent lg:hidden"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="size-5" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
                            <div class="min-w-0"><p class="text-xs font-semibold text-foreground/45">Panel admin / @yield('section', 'Konten pembelajaran')</p><p class="truncate font-display text-lg font-bold">@yield('title')</p></div>
                        </div>
                        <a href="{{ route('home') }}" class="inline-flex min-h-10 shrink-0 items-center rounded-xl border border-white/10 px-3 text-xs font-bold text-foreground/75 transition hover:border-accent/50 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent sm:px-4 sm:text-sm">Lihat situs <span class="ml-2" aria-hidden="true">↗</span></a>
                    </div>
                </header>
                <main id="konten-admin" class="mx-auto w-full max-w-[100rem] px-5 py-7 sm:px-8 sm:py-9 xl:px-10">
                    @if (session('status')) <x-flash-notice class="mb-6">{{ session('status') }}</x-flash-notice> @endif
                    @yield('content')
                </main>
            </div>
        </div>

        <div data-mobile-nav-backdrop aria-controls="admin-mobile-navigation" aria-hidden="true"></div>
        <aside id="admin-mobile-navigation" data-mobile-nav role="dialog" aria-modal="true" aria-label="Navigasi admin" aria-hidden="true" inert class="z-50 flex h-dvh flex-col border-l border-white/10 bg-surface p-5 text-foreground shadow-2xl shadow-black/50 lg:hidden">
            <div class="flex items-center justify-between gap-3 border-b border-white/10 pb-5">
                <span class="flex items-center gap-3 font-display font-bold"><img src="{{ asset('image/maskot.webp') }}" alt="" class="size-10 rounded-full object-cover"> ARTASWAHA <span class="text-accent">/ Admin</span></span>
                <button type="button" data-mobile-nav-close aria-label="Tutup menu" class="grid size-10 shrink-0 place-items-center rounded-xl bg-background text-foreground/70 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="size-5" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto py-6">@include('admin.navigation')</div>
            <div class="border-t border-white/10 pt-4">
                <p class="truncate text-sm font-bold">{{ auth()->user()->name }}</p><p class="text-xs text-foreground/50">Administrator</p>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button type="submit" class="min-h-11 text-sm font-bold text-foreground/65 hover:text-danger focus-visible:outline-2 focus-visible:outline-accent">Keluar akun</button></form>
            </div>
        </aside>
    </body>
</html>
