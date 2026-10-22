<x-layouts.app :learning-paths="$learningPaths" title="Profil — ARTASWAHA">
    <main class="mx-auto max-w-5xl px-6 py-10 sm:py-14 lg:px-10">
        <header class="mb-8 max-w-2xl">
            <p class="inline-flex items-center gap-2 font-semibold text-accent"><span aria-hidden="true">✦</span> Ruang belajar kamu</p>
            <h1 class="mt-3 font-display text-3xl font-bold sm:text-4xl">Profil belajar</h1>
            <p class="mt-3 font-readable leading-7 text-foreground/65">Lihat perjalanan belajarmu dan lanjutkan langkah kecil berikutnya.</p>
        </header>

        <section class="overflow-hidden rounded-card bg-surface" aria-labelledby="profile-title">
            <div class="grid lg:grid-cols-[1fr_0.85fr]">
                <div class="p-6 sm:p-9">
                    <div class="flex flex-wrap items-center gap-5">
                        <span class="relative grid size-20 shrink-0 place-items-center rounded-full bg-accent font-display text-3xl font-extrabold text-background" aria-hidden="true">
                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                            <span class="absolute -bottom-1 -right-1 grid size-7 place-items-center rounded-full bg-primary text-xs text-white">✦</span>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-foreground/55">Senang kamu kembali!</p>
                            <h2 id="profile-title" class="mt-1 break-words font-display text-2xl font-bold sm:text-3xl">{{ $user->name }}</h2>
                            <p class="mt-1 break-all font-readable text-foreground/65">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('learning.paths.show', 'cecimpedan') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-primary px-6 py-3 font-bold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Lanjut belajar</a>
                        <a href="{{ route('quizzes.index') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-background px-6 py-3 font-bold transition hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Lihat kuis</a>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 bg-background/60 p-5 sm:p-7">
                    <article class="flex min-h-36 flex-col justify-between rounded-3xl bg-surface p-5" aria-label="Pelajaran selesai: {{ $user->completed_lessons_count }}">
                        <span class="grid size-10 place-items-center rounded-full bg-primary/20 font-balinese text-xl text-accent" lang="ban" aria-hidden="true">ᬅ</span>
                        <div>
                            <p class="font-display text-3xl font-extrabold text-accent">{{ $user->completed_lessons_count }}</p>
                            <h3 class="mt-1 text-sm font-semibold text-foreground/65">Pelajaran selesai</h3>
                        </div>
                    </article>
                    <article class="flex min-h-36 flex-col justify-between rounded-3xl bg-surface p-5" aria-label="Kuis dibuat: {{ $user->quizzes_count }}">
                        <span class="grid size-10 place-items-center rounded-full bg-accent/10 text-xl text-accent" aria-hidden="true">✦</span>
                        <div>
                            <p class="font-display text-3xl font-extrabold text-accent">{{ $user->quizzes_count }}</p>
                            <h3 class="mt-1 text-sm font-semibold text-foreground/65">Kuis dibuat</h3>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <details class="group mt-5 rounded-card bg-surface p-5 sm:p-6">
            <summary class="flex min-h-10 cursor-pointer list-none items-center justify-between gap-4 font-display font-bold focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                Detail akun
                <span class="text-xl text-accent transition-transform group-open:rotate-45" aria-hidden="true">＋</span>
            </summary>
            <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl bg-background p-4">
                    <dt class="text-sm font-semibold text-foreground/55">Nama lengkap</dt>
                    <dd class="mt-1 break-words font-semibold">{{ $user->name }}</dd>
                </div>
                <div class="rounded-2xl bg-background p-4">
                    <dt class="text-sm font-semibold text-foreground/55">Email</dt>
                    <dd class="mt-1 break-all font-semibold">{{ $user->email }}</dd>
                </div>
            </dl>
        </details>
    </main>
</x-layouts.app>
