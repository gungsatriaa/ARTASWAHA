<x-layouts.app :learning-paths="$learningPaths" :show-footer="false" title="Profil — ARTASWAHA">
    <div class="mx-auto flex min-h-[calc(100svh-6rem)] max-w-5xl items-center px-6 py-5 lg:px-10">
        <div class="w-full">
            <header class="mb-5 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <p class="inline-flex items-center gap-2 text-sm font-semibold text-accent"><span aria-hidden="true">✦</span> Ruang belajar kamu</p>
                    <h1 class="mt-1 font-display text-2xl font-bold sm:text-3xl">Profil belajar</h1>
                </div>
                <p class="max-w-md font-readable text-sm text-foreground/60">Lihat progres dan lanjutkan langkah kecil berikutnya.</p>
            </header>

            <section class="overflow-hidden rounded-card bg-surface" aria-labelledby="profile-title">
                <div class="grid lg:grid-cols-[1fr_0.85fr]">
                    <div class="p-5 sm:p-7">
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="relative grid size-16 shrink-0 place-items-center rounded-full bg-accent font-display text-2xl font-extrabold text-background" aria-hidden="true">
                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                <span class="absolute -bottom-1 -right-1 grid size-6 place-items-center rounded-full bg-primary text-[10px] text-white">✦</span>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-foreground/55">Senang kamu kembali!</p>
                                <h2 id="profile-title" class="mt-1 break-words font-display text-xl font-bold sm:text-2xl">{{ $user->name }}</h2>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('learning.paths.show', 'cecimpedan') }}" class="inline-flex min-h-11 items-center justify-center rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Lanjut belajar</a>
                            <a href="{{ route('quizzes.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-full bg-background px-5 py-2.5 text-sm font-bold transition hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Lihat kuis</a>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 bg-background/60 p-4 sm:p-5">
                        <article class="flex min-h-28 flex-col justify-between rounded-3xl bg-surface p-4" aria-label="Pelajaran selesai: {{ $user->completed_lessons_count }}">
                            <span class="grid size-9 place-items-center rounded-full bg-primary/20 font-balinese text-lg text-accent" lang="ban" aria-hidden="true">ᬅ</span>
                            <div>
                                <p class="font-display text-2xl font-extrabold text-accent">{{ $user->completed_lessons_count }}</p>
                                <h3 class="mt-1 text-xs font-semibold text-foreground/65 sm:text-sm">Pelajaran selesai</h3>
                            </div>
                        </article>
                        <article class="flex min-h-28 flex-col justify-between rounded-3xl bg-surface p-4" aria-label="Kuis dibuat: {{ $user->quizzes_count }}">
                            <span class="grid size-9 place-items-center rounded-full bg-accent/10 text-lg text-accent" aria-hidden="true">✦</span>
                            <div>
                                <p class="font-display text-2xl font-extrabold text-accent">{{ $user->quizzes_count }}</p>
                                <h3 class="mt-1 text-xs font-semibold text-foreground/65 sm:text-sm">Kuis dibuat</h3>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <details class="group mt-3 rounded-3xl bg-surface px-5 py-3">
                <summary class="flex min-h-10 cursor-pointer list-none items-center justify-between gap-4 font-display text-sm font-bold focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                    Detail akun
                    <span class="text-xl text-accent transition-transform group-open:rotate-45" aria-hidden="true">＋</span>
                </summary>
                <dl class="grid gap-3 pb-2 sm:grid-cols-2">
                    <div class="rounded-2xl bg-background p-4">
                        <dt class="text-xs font-semibold text-foreground/55">Email akun</dt>
                        <dd class="mt-1 break-all text-sm font-semibold">{{ $user->email }}</dd>
                    </div>
                    <div class="rounded-2xl bg-background p-4">
                        <dt class="text-xs font-semibold text-foreground/55">Bergabung</dt>
                        <dd class="mt-1 text-sm font-semibold">{{ $user->created_at->format('d/m/Y') }}</dd>
                    </div>
                </dl>
            </details>
        </div>
    </div>
</x-layouts.app>
