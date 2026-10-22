<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#000000">
        <link rel="icon" type="image/webp" href="{{ asset('image/maskot.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('image/maskot.webp') }}">
        <title>ARTASWAHA — Belajar Bali dengan gembira</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-sans text-foreground antialiased">
        <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-10">
            <a href="/" class="flex items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" aria-label="ArtaSwaha beranda">
                <img src="{{ asset('image/maskot.webp') }}" alt="" class="size-12 rounded-full object-cover object-center">
                <span class="font-display text-2xl font-bold tracking-tight">ARTASWAHA</span>
            </a>
            <nav class="flex items-center gap-1 sm:gap-3" aria-label="Navigasi utama">
                <div class="group relative hidden sm:block">
                    <a class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 py-3 text-sm font-semibold text-foreground/80 transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent" href="#jalur" aria-haspopup="true">Paribasa Bali <span aria-hidden="true">⌄</span></a>
                    <div class="invisible absolute left-0 top-full z-20 w-56 translate-y-2 pt-2 opacity-0 transition group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100">
                        <div class="rounded-2xl border border-white/10 bg-surface p-2 shadow-xl">
                            <a class="block rounded-xl px-4 py-3 text-sm hover:bg-white/5 focus-visible:outline-2 focus-visible:outline-accent" href="#jalur">Cecimpedan</a>
                            <a class="block rounded-xl px-4 py-3 text-sm hover:bg-white/5 focus-visible:outline-2 focus-visible:outline-accent" href="#jalur">Peparikan</a>
                            <a class="block rounded-xl px-4 py-3 text-sm hover:bg-white/5 focus-visible:outline-2 focus-visible:outline-accent" href="#jalur">Sesonggakan</a>
                        </div>
                    </div>
                </div>
                <div class="group relative hidden sm:block">
                    <a class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 py-3 text-sm font-semibold text-foreground/80 transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent" href="#jalur" aria-haspopup="true">Aksara Bali <span aria-hidden="true">⌄</span></a>
                    <div class="invisible absolute left-0 top-full z-20 w-64 translate-y-2 pt-2 opacity-0 transition group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100">
                        <div class="rounded-2xl border border-white/10 bg-surface p-2 shadow-xl">
                            <a class="block rounded-xl px-4 py-3 text-sm hover:bg-white/5 focus-visible:outline-2 focus-visible:outline-accent" href="#jalur">Aksara Wreastra &amp; gantungan</a>
                            <a class="block rounded-xl px-4 py-3 text-sm hover:bg-white/5 focus-visible:outline-2 focus-visible:outline-accent" href="#jalur">Pengangge Suara</a>
                            <a class="block rounded-xl px-4 py-3 text-sm hover:bg-white/5 focus-visible:outline-2 focus-visible:outline-accent" href="#jalur">Pengangge Tengenan</a>
                            <a class="block rounded-xl px-4 py-3 text-sm hover:bg-white/5 focus-visible:outline-2 focus-visible:outline-accent" href="#jalur">Pengangge Arda Suara</a>
                            <a class="block rounded-xl px-4 py-3 text-sm hover:bg-white/5 focus-visible:outline-2 focus-visible:outline-accent" href="#jalur">Angka Aksara Bali</a>
                        </div>
                    </div>
                </div>
                <a class="inline-flex min-h-11 items-center rounded-full bg-accent px-6 py-3 text-sm font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" href="#mulai">Mulai belajar</a>
            </nav>
        </header>

        <main>
            <section class="mx-auto grid max-w-7xl items-center gap-12 px-6 pb-20 pt-10 md:grid-cols-[1.05fr_.95fr] md:pb-28 md:pt-16 lg:px-10">
                <div class="max-w-2xl">
                    <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/10 bg-surface px-4 py-2 text-sm font-semibold text-accent">
                        <span aria-hidden="true">✦</span> Belajar budaya Bali, satu langkah kecil
                    </p>
                    <h1 class="font-display text-5xl font-bold leading-[1.08] tracking-tight sm:text-6xl lg:text-7xl">Bahasa Bali jadi <span class="text-accent">seru</span> dipelajari.</h1>
                    <p class="mt-6 max-w-xl font-readable text-lg leading-8 text-foreground/75 sm:text-xl">Kenali paribasa dan aksara Bali lewat cerita, latihan singkat, dan permainan yang bisa langsung dicoba. Mulai tanpa akun; progres tersimpan di perangkatmu.</p>
                    <div id="mulai" class="mt-9 flex flex-wrap gap-3">
                        <a href="#jalur" class="inline-flex min-h-14 items-center justify-center rounded-full bg-primary px-8 py-4 font-bold text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Mulai belajar gratis</a>
                        <a href="#room" class="inline-flex min-h-14 items-center justify-center rounded-full border border-white/15 px-7 py-4 font-bold transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Gabung kuis</a>
                    </div>
                    <p class="mt-4 text-sm text-foreground/55">Belajar mandiri tanpa akun. Untuk kuis bersama, masuk atau daftar terlebih dahulu.</p>
                </div>

                <div class="mx-auto w-full max-w-lg py-4 md:pl-8">
                    <div class="relative overflow-hidden rounded-card border border-white/10 bg-surface p-7 sm:p-9">
                        <div class="flex items-center gap-3 text-sm font-semibold text-accent">
                            <span aria-hidden="true">✦</span>
                            <span>Cecimpedan</span>
                        </div>
                        <p class="mt-8 font-display text-2xl font-bold leading-relaxed sm:text-3xl">“Bawak-bawak madaging banyu, apa?”</p>
                        <p class="mt-3 font-readable leading-7 text-foreground/65">Teka-teki: kecil, berisi air. Coba tebak sebelum membuka jawabannya.</p>
                        <details class="group mt-8">
                            <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between rounded-full bg-primary px-6 py-4 font-bold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                                Lihat jawaban
                                <span class="text-xl" aria-hidden="true">＋</span>
                            </summary>
                            <p class="mt-5 border-l-2 border-accent pl-4 font-readable text-lg font-semibold text-accent">Kelapa muda</p>
                        </details>
                        <div class="mt-9 flex items-center gap-2 text-xs font-semibold text-foreground/45">
                            <span class="h-px flex-1 bg-white/10"></span>
                            Belajar lewat cerita dan permainan
                            <span class="h-px flex-1 bg-white/10"></span>
                        </div>
                    </div>
                </div>
            </section>

            <section id="jalur" class="mx-auto max-w-7xl px-6 pb-20 lg:px-10">
                <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="font-semibold text-accent">Jalur belajar</p>
                        <h2 class="mt-2 font-display text-3xl font-bold sm:text-4xl">Pilih yang ingin kamu pelajari</h2>
                    </div>
                    <p class="max-w-md font-readable text-foreground/65">Mulai dari pengenalan, lalu lanjutkan ke latihan. Setiap bagian dirancang singkat dan mudah diulang.</p>
                </div>
                <div class="grid gap-5 lg:grid-cols-2">
                    <article class="rounded-card bg-surface p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-accent">Jalur 1</p>
                                <h3 class="mt-2 font-display text-2xl font-bold">Paribasa Bali</h3>
                                <p class="mt-3 max-w-md font-readable leading-7 text-foreground/65">Belajar ungkapan, permainan kata, dan teka-teki yang hidup dalam keseharian Bali.</p>
                            </div>
                            <span class="grid size-12 shrink-0 place-items-center rounded-full bg-accent/10 text-xl text-accent" aria-hidden="true">✦</span>
                        </div>
                        <ul class="mt-7 flex flex-wrap gap-2 text-sm font-semibold">
                            <li class="rounded-full bg-background px-4 py-2">Cecimpedan</li>
                            <li class="rounded-full bg-background px-4 py-2">Peparikan</li>
                            <li class="rounded-full bg-background px-4 py-2">Sesonggakan</li>
                        </ul>
                        <a href="#room" class="mt-8 inline-flex min-h-12 items-center rounded-full bg-primary px-6 py-3 font-bold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Jelajahi paribasa</a>
                    </article>
                    <article class="rounded-card bg-surface p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-accent">Jalur 2</p>
                                <h3 class="mt-2 font-display text-2xl font-bold">Aksara Bali</h3>
                                <p class="mt-3 max-w-md font-readable leading-7 text-foreground/65">Kenali bentuk aksara, tanda bunyi, pasangan, dan angka melalui contoh serta latihan menulis.</p>
                            </div>
                            <span class="grid size-12 shrink-0 place-items-center rounded-full bg-primary/15 font-balinese text-2xl text-accent" lang="ban" aria-hidden="true">ᬅ</span>
                        </div>
                        <ul class="mt-7 flex flex-wrap gap-2 text-sm font-semibold">
                            <li class="rounded-full bg-background px-4 py-2">Wreastra &amp; gantungan</li>
                            <li class="rounded-full bg-background px-4 py-2">Pengangge</li>
                            <li class="rounded-full bg-background px-4 py-2">Angka Bali</li>
                        </ul>
                        <a href="#room" class="mt-8 inline-flex min-h-12 items-center rounded-full bg-primary px-6 py-3 font-bold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Jelajahi aksara</a>
                    </article>
                </div>
            </section>

            <section id="room" class="mx-6 mb-16 rounded-[2rem] border border-white/10 bg-surface px-6 py-10 sm:px-10 md:mx-auto md:max-w-7xl md:py-14 lg:px-14">
                <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-center">
                    <div>
                        <p class="font-semibold text-accent">Belajar bareng</p>
                        <h2 class="mt-2 font-display text-3xl font-bold">Ada kode room dari teman?</h2>
                        <p class="mt-3 max-w-xl font-readable leading-7 text-foreground/65">Masuk ke kuis bersama, jawab pertanyaan, dan lihat progres sesi secara langsung.</p>
                    </div>
                    <a href="#mulai" class="inline-flex min-h-12 shrink-0 items-center justify-center rounded-full bg-accent px-7 py-3 font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Masuk untuk ikut kuis</a>
                </div>
            </section>
        </main>

        <footer class="mx-auto max-w-7xl px-6 pb-8 text-sm text-foreground/50 lg:px-10">
            <p>ArtaSwaha — belajar bahasa dan budaya Bali dengan gembira.</p>
        </footer>
    </body>
</html>
