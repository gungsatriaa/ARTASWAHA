@props(['title' => 'Masuk — ARTASWAHA'])

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a0a0a">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Masuk atau daftar untuk belajar bahasa dan aksara Bali bersama ARTASWAHA.">
        <meta name="robots" content="noindex, nofollow">
        <link rel="icon" type="image/webp" href="{{ asset('image/maskot.webp') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400..800&family=Plus+Jakarta+Sans:wght@400..800&display=swap" rel="stylesheet">
        <title>{{ $title }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body data-presence-url="{{ route('presence.ping') }}" class="min-h-dvh overflow-x-hidden bg-background font-sans text-foreground antialiased">
        <main class="grid min-h-dvh lg:grid-cols-2">
                <aside class="hidden flex-col justify-between border-b border-white/10 bg-surface px-5 py-8 sm:px-10 sm:py-10 lg:flex lg:min-h-dvh lg:border-b-0 lg:border-r lg:px-12 lg:py-12 xl:px-20">
                    <div class="flex flex-1 flex-col">
                        <a href="{{ route('home') }}" class="inline-flex w-fit items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" aria-label="ARTASWAHA beranda">
                            <img src="{{ asset('image/maskot.webp') }}" alt="" class="size-10 rounded-full object-cover lg:size-12">
                            <span class="font-display text-lg font-bold lg:text-xl">ARTASWAHA</span>
                        </a>
                        <div class="my-auto max-w-2xl py-10 lg:py-0">
                            <p class="mt-4 flex w-fit items-center gap-2 rounded-full bg-primary/20 px-3 py-1.5 text-xs font-bold text-accent lg:mt-10 lg:px-4 lg:py-2 lg:text-sm">
                                <span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>
                                Belajar budaya Bali
                            </p>
                            <p class="mt-3 font-display text-2xl font-bold leading-tight tracking-tight sm:text-3xl lg:mt-5 lg:text-4xl xl:text-5xl">Belajar Bali,<br><span class="text-accent">sambil bermain!</span></p>
                            <p class="mt-2 max-w-[38rem] font-readable text-sm leading-6 text-foreground/65 lg:mt-4 lg:text-base lg:leading-7">Dari makna paribasa sampai bentuk aksara, semuanya bisa kamu pelajari lewat contoh sederhana dan kuis seru bersama teman.</p>
                            <ul class="mt-6 grid max-w-2xl grid-cols-1 gap-3 border-t border-white/10 pt-5 text-sm font-semibold text-foreground/75 sm:grid-cols-3 lg:mt-8 lg:gap-4 lg:pt-6">
                                <li class="flex items-center gap-2"><span class="size-2 shrink-0 rounded-full bg-accent" aria-hidden="true"></span>Temukan makna paribasa</li>
                                <li class="flex items-center gap-2"><span class="size-2 shrink-0 rounded-full bg-accent" aria-hidden="true"></span>Kenali aksara Bali</li>
                                <li class="flex items-center gap-2"><span class="size-2 shrink-0 rounded-full bg-accent" aria-hidden="true"></span>Uji diri lewat kuis</li>
                            </ul>
                        </div>
                    </div>
                    <div class="mt-4 hidden items-end lg:flex">
                        <p class="max-w-48 border-t border-white/10 pt-4 text-sm font-semibold leading-6 text-foreground/55">Belajar santai, budaya Bali tetap melekat.</p>
                    </div>
                </aside>

                <section class="flex flex-col bg-background px-5 py-8 sm:px-10 sm:py-10 lg:min-h-dvh lg:px-12 lg:py-12 xl:px-20">
                    <div class="mx-auto flex w-full max-w-md items-center justify-between gap-3 lg:max-w-none lg:justify-end">
                        <a href="{{ route('home') }}" class="flex w-fit shrink-0 items-center gap-2 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent lg:hidden" aria-label="ARTASWAHA beranda">
                            <img src="{{ asset('image/maskot.webp') }}" alt="" class="size-10 rounded-full object-cover">
                            <span class="font-display text-base font-bold sm:text-lg">ARTASWAHA</span>
                        </a>
                        <a href="{{ route('home') }}" class="inline-flex min-h-10 items-center gap-2 rounded-full px-2 text-xs font-semibold text-foreground/60 transition hover:bg-surface hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:px-3 sm:text-sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                            Kembali ke beranda
                        </a>
                    </div>
                    <div class="flex flex-1 items-center">
                        <div class="mx-auto w-full max-w-md">
                        {{ $slot }}
                        </div>
                    </div>
                </section>
        </main>
    </body>
</html>
