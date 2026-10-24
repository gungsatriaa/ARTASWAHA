<x-layouts.app :show-footer="false" title="Profil anonim — ARTASWAHA">
    <div class="mx-auto grid min-h-[calc(100svh-6rem)] max-w-7xl gap-5 px-6 py-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:items-center lg:px-10">
        <aside class="rounded-card bg-surface p-5 sm:p-6 lg:sticky lg:top-28 lg:self-start" aria-label="Profil anonim">
            <div class="flex items-center gap-4 lg:flex-col lg:items-start">
                <img src="{{ str_starts_with($profile->avatar ?? '', 'avatars/') ? route('learning.interview.avatar') : asset($profile->avatar ?? array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS)) }}" alt="Avatar {{ $profile->name }}" class="size-16 shrink-0 rounded-full object-cover ring-2 ring-primary/40">
                <div class="min-w-0">
                    <h1 class="break-words font-display text-xl font-bold">{{ $profile->name }}</h1>
                    <p class="mt-1 text-sm font-semibold text-accent">Akun anonim</p>
                </div>
            </div>
            <nav class="mt-6 grid gap-2 border-t border-white/10 pt-5" aria-label="Menu profil anonim">
                <a href="{{ route('learning.paths.show', $profile->learningPath->slug) }}" class="flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold text-foreground/75 transition hover:bg-background hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><span aria-hidden="true">✦</span> Lanjut belajar</a>
                <a href="{{ route('home') }}#mulai" class="flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold text-foreground/75 transition hover:bg-background hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><span aria-hidden="true">↗</span> Pilih materi lain</a>
            </nav>
        </aside>

        <section class="min-w-0 rounded-card bg-surface p-6 sm:p-8" aria-labelledby="anonymous-profile-title">
            <p class="text-sm font-semibold text-accent">Profil di perangkat ini</p>
            <h2 id="anonymous-profile-title" class="mt-2 font-display text-2xl font-bold sm:text-3xl">Belajar sebagai {{ $profile->name }}</h2>
            <p class="mt-3 max-w-2xl font-readable leading-7 text-foreground/65">Profil anonim ini tersimpan di perangkatmu. Kamu bisa belajar tanpa masuk, tetapi profil ini tidak otomatis tersedia di perangkat lain.</p>
            <dl class="mt-7 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl bg-background p-5"><dt class="text-sm text-foreground/55">Materi pilihan</dt><dd class="mt-2 font-display text-lg font-bold">{{ $profile->learningPath->title }}</dd></div>
                <div class="rounded-2xl bg-background p-5"><dt class="text-sm text-foreground/55">Peran</dt><dd class="mt-2 font-display text-lg font-bold">{{ ['murid' => 'Murid', 'guru' => 'Guru', 'pendatang' => 'Pendatang', 'masyarakat' => 'Masyarakat umum'][$profile->role] ?? $profile->role }}</dd></div>
            </dl>
            <div class="mt-7 border-t border-white/10 pt-6">
                <h3 class="font-display text-lg font-bold">Ingin menyimpan progres dengan akun?</h3>
                <p class="mt-2 text-sm leading-6 text-foreground/60">Masuk atau daftar untuk menghubungkan progres di perangkat ini dengan akunmu.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center justify-center rounded-full bg-accent px-6 font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-accent">Daftar akun</a>
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-full border border-white/15 px-6 font-bold text-foreground transition hover:border-accent/50 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">Masuk</a>
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
