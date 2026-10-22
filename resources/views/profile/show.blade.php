<x-layouts.app :learning-paths="$learningPaths" :show-footer="false" title="Profil — ARTASWAHA">
    <div class="mx-auto flex min-h-[calc(100svh-6rem)] max-w-7xl items-center px-6 py-5 lg:px-10">
        <div class="grid w-full gap-5 lg:grid-cols-[16rem_minmax(0,1fr)]">
            <aside class="rounded-card bg-surface p-5 sm:p-6" aria-label="Profil dan navigasi akun">
                <div class="flex items-center gap-4 lg:flex-col lg:items-start">
                    <span class="grid size-16 shrink-0 place-items-center rounded-full bg-accent font-display text-2xl font-extrabold text-background" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <h1 class="break-words font-display text-xl font-bold">{{ $user->name }}</h1>
                        <p class="mt-1 break-all text-sm text-foreground/55">{{ $user->email }}</p>
                    </div>
                </div>

                <nav class="mt-6 grid gap-2 border-t border-white/10 pt-5" aria-label="Menu profil">
                    <a href="#progres" class="flex min-h-11 items-center gap-3 rounded-2xl bg-primary/15 px-4 py-3 text-sm font-bold text-accent focus-visible:outline-2 focus-visible:outline-accent">
                        <span aria-hidden="true">✦</span> Progres belajar
                    </a>
                    <a href="{{ route('home') }}#tentang" class="flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold text-foreground/75 transition hover:bg-background hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">
                        <span aria-hidden="true">ⓘ</span> Tentang aplikasi ini
                    </a>
                    <a href="{{ route('quizzes.index') }}" class="flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold text-foreground/75 transition hover:bg-background hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">
                        <span aria-hidden="true">♫</span> Kuis saya
                    </a>
                </nav>
            </aside>

            <section id="progres" class="min-w-0" aria-labelledby="progress-title">
                <header class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-accent">Langkah kecilmu</p>
                        <h2 id="progress-title" class="mt-1 font-display text-2xl font-bold sm:text-3xl">Progres belajar</h2>
                    </div>
                    <p class="rounded-full bg-surface px-4 py-2 text-sm font-semibold text-foreground/70">{{ $completedLessons }} dari {{ $totalLessons }} pelajaran selesai</p>
                </header>

                <div class="mb-4 rounded-3xl bg-surface px-5 py-4" role="progressbar" aria-label="Progres belajar keseluruhan" aria-valuemin="0" aria-valuemax="{{ max(1, $totalLessons) }}" aria-valuenow="{{ $completedLessons }}" aria-valuetext="{{ $completedLessons }} dari {{ $totalLessons }} pelajaran selesai">
                    <div class="h-3 overflow-hidden rounded-full bg-background">
                        <div class="h-full rounded-full bg-primary transition-[width] duration-500" style="width: {{ $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0 }}%"></div>
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach (['paribasa' => 'Paribasa Bali', 'aksara' => 'Aksara Bali'] as $group => $label)
                        @php($paths = $learningPathsByGroup->get($group, collect()))
                        @php($groupCompleted = $paths->sum('completed_lessons_count'))
                        @php($groupTotal = $paths->sum('lessons_count'))

                        <article class="rounded-card bg-surface p-5 sm:p-6">
                            <header class="mb-4 flex items-center justify-between gap-3">
                                <h3 class="font-display text-lg font-bold">{{ $label }}</h3>
                                <span class="rounded-full bg-background px-3 py-1.5 text-xs font-bold text-accent">{{ $groupCompleted }}/{{ $groupTotal }}</span>
                            </header>

                            <ul class="space-y-3">
                                @forelse ($paths as $learningPath)
                                    @php($pathTotal = $learningPath->lessons_count)
                                    @php($pathCompleted = $learningPath->completed_lessons_count)
                                    @php($pathProgress = $pathTotal > 0 ? round(($pathCompleted / $pathTotal) * 100) : 0)

                                    <li>
                                        <div class="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
                                            <a href="{{ route('learning.paths.show', $learningPath->slug) }}" class="min-w-0 truncate font-semibold text-foreground/80 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">{{ $learningPath->title }}</a>
                                            <span class="shrink-0 text-xs text-foreground/50">{{ $pathTotal > 0 ? "$pathCompleted/$pathTotal" : 'Segera' }}</span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-background" role="progressbar" aria-label="Progres {{ $learningPath->title }}: {{ $pathCompleted }} dari {{ $pathTotal }} pelajaran" aria-valuemin="0" aria-valuemax="{{ max(1, $pathTotal) }}" aria-valuenow="{{ $pathCompleted }}" aria-valuetext="{{ $pathTotal > 0 ? "$pathCompleted dari $pathTotal pelajaran selesai" : 'Materi belum tersedia' }}">
                                            <div class="h-full rounded-full bg-primary" style="width: {{ $pathProgress }}%"></div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="rounded-2xl bg-background p-4 text-sm text-foreground/60">Materi untuk bagian ini sedang disiapkan.</li>
                                @endforelse
                            </ul>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-layouts.app>
