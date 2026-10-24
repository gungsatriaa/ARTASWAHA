<x-layouts.profile :learning-paths="$learningPaths" :user="$user ?? null" :profile="$profile ?? null" active="about">
    <article class="rounded-card bg-surface p-5 sm:p-8">
        <header class="flex flex-wrap items-start justify-between gap-4 border-b border-white/10 pb-6">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold text-accent">Ruang informasi</p>
                <h2 class="mt-2 font-display text-3xl font-bold">Tentang ARTASWAHA</h2>
                <p class="mt-3 leading-7 text-foreground/65">ARTASWAHA - Platform Edukasi Bahasa, Sastra, dan Aksara Bali. Sistem ini menyajikan materi mandiri dan kuis bersama dalam room.</p>
            </div>
            <span class="rounded-full bg-primary/15 px-4 py-2 text-sm font-bold text-accent">Versi {{ config('app.version') }}</span>
        </header>

        <section class="py-6" aria-labelledby="materials-title">
            <h3 id="materials-title" class="font-display text-xl font-bold">Materi pembelajaran</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-3xl bg-background p-5">
                    <h4 class="font-bold text-accent">Paribasa Bali</h4>
                    <p class="mt-2 text-sm leading-6 text-foreground/70">Cecimpedan, Peparikan, dan Sesonggakan.</p>
                </div>
                <div class="rounded-3xl bg-background p-5">
                    <h4 class="font-bold text-accent">Aksara Bali</h4>
                    <p class="mt-2 text-sm leading-6 text-foreground/70">Aksara Wreastra beserta gantungannya, Pengangge Suara, Pengangge Tengenan, Pengangge Arda Suara, dan angka Aksara Bali.</p>
                </div>
            </div>
        </section>

        <section class="border-t border-white/10 pt-6" aria-labelledby="privacy-title">
            <h3 id="privacy-title" class="font-display text-xl font-bold">Data dan privasi</h3>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-foreground/65">Lihat data yang dikumpulkan saat belajar tanpa akun, membuat akun, dan bermain kuis; cara data dipakai, berapa lama disimpan, serta cara meminta akses atau penghapusan.</p>
            <a href="{{ route('privacy-policy') }}" class="mt-4 inline-flex min-h-11 items-center rounded-full border border-white/15 px-5 text-sm font-bold text-accent transition hover:border-accent/50 hover:bg-accent/10 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Baca kebijakan privasi</a>
        </section>
    </article>
</x-layouts.profile>
