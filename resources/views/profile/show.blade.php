<x-layouts.profile :learning-paths="$learningPaths" :user="$user ?? null" :profile="$profile ?? null">
    @if (isset($profile))
        <div class="mb-5 rounded-card border border-primary/25 bg-primary/10 p-5 sm:p-6">
            <p class="text-sm font-bold text-accent">Akun anonim · hanya di perangkat ini</p>
            <h2 class="mt-2 font-display text-xl font-bold">Belajar sebagai {{ $profile->name }}</h2>
            <p class="mt-2 text-sm leading-6 text-foreground/65">Progres belajarmu tersimpan di perangkat ini dan tidak otomatis tersedia di perangkat lain.</p>
            <p class="mt-2 text-sm text-foreground/65">Materi pilihan: {{ collect($profile->learning_path_ids ?? [$profile->learning_path_id])->map(fn (int $id) => $learningPaths->firstWhere('id', $id)?->title)->filter()->join(', ') }} · Peran: {{ ['murid' => 'Murid', 'guru' => 'Guru', 'pendatang' => 'Pendatang', 'masyarakat' => 'Masyarakat umum'][$profile->role] ?? $profile->role }}</p>
            <a href="{{ route('register') }}" class="mt-4 inline-flex min-h-11 items-center rounded-full bg-accent px-5 text-sm font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-accent">Daftar akun</a>
        </div>
    @endif
    @if ($analysisProfile)
        <x-learner-analysis :analysis="$analysis" />
    @endif
    <section id="progres" class="min-w-0" aria-labelledby="progress-title">
        <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="progress-title" class="font-display text-2xl font-bold sm:text-3xl">Progres belajar</h2>
                <p class="mt-2 text-sm leading-6 text-foreground/60">Perjalananmu di materi yang sudah dipelajari.</p>
            </div>
            @if ($totalLessons > 0)
                <p class="rounded-full bg-surface px-4 py-2 text-sm font-semibold text-foreground/75">{{ $completedLessons }} dari {{ $totalLessons }} pelajaran selesai</p>
            @endif
        </header>

        @if ($totalLessons > 0)
            <div class="mb-5 rounded-3xl bg-surface px-5 py-4" role="progressbar" aria-label="Progres belajar keseluruhan" aria-valuemin="0" aria-valuemax="{{ $totalLessons }}" aria-valuenow="{{ $completedLessons }}" aria-valuetext="{{ $completedLessons }} dari {{ $totalLessons }} pelajaran selesai">
                <div class="h-3 overflow-hidden rounded-full bg-background">
                    <div class="h-full rounded-full bg-primary transition-[width] duration-500" style="width: {{ round(($completedLessons / $totalLessons) * 100) }}%"></div>
                </div>
            </div>
        @else
            <div class="rounded-card bg-surface p-6 sm:p-8">
                <span class="grid size-12 place-items-center rounded-full bg-primary/20 text-accent" aria-hidden="true"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22zM4 5.5v14A2.5 2.5 0 0 1 6.5 17H20" /></svg></span>
                <h3 class="mt-4 font-display text-xl font-bold">Belum ada progres belajar</h3>
                <p class="mt-2 max-w-md text-sm leading-6 text-foreground/60">Selesaikan pelajaran pertamamu. Materi yang sudah kamu pelajari akan muncul di sini.</p>
                <a href="{{ route('learning.paths.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-full bg-accent px-6 text-sm font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Pilih materi</a>
            </div>
        @endif

        <div class="grid gap-4 xl:grid-cols-2">
            @foreach (['paribasa' => 'Paribasa Bali', 'aksara' => 'Aksara Bali'] as $group => $label)
                @php($paths = $learningPathsByGroup->get($group, collect()))
                @continue($paths->isEmpty())
                @php($groupCompleted = $paths->sum('completed_lessons_count'))
                @php($groupTotal = $paths->sum('lessons_count'))

                <article class="rounded-card bg-surface p-5 sm:p-6">
                    <header class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-display text-lg font-bold">{{ $label }}</h3>
                            <p class="mt-1 text-xs text-foreground/50">{{ $groupCompleted }} dari {{ $groupTotal }} pelajaran selesai</p>
                        </div>
                        <span class="rounded-full bg-primary/15 px-3 py-1.5 text-xs font-bold text-accent">{{ round($groupCompleted / $groupTotal * 100) }}%</span>
                    </header>

                    <ul class="space-y-3">
                        @foreach ($paths as $learningPath)
                            @php($pathTotal = $learningPath->lessons_count)
                            @php($pathCompleted = $learningPath->completed_lessons_count)

                            <li class="rounded-2xl bg-background px-4 py-4">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                        <a href="{{ route('learning.paths.show', $learningPath->slug) }}" class="min-w-0 break-words font-semibold leading-6 text-foreground/85 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">{{ $learningPath->title }}</a>
                                        <span class="shrink-0 text-xs text-foreground/60">{{ $pathCompleted }}/{{ $pathTotal }}</span>
                                </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-background" role="progressbar" aria-label="Progres {{ $learningPath->title }}: {{ $pathCompleted }} dari {{ $pathTotal }} pelajaran" aria-valuemin="0" aria-valuemax="{{ $pathTotal }}" aria-valuenow="{{ $pathCompleted }}" aria-valuetext="{{ $pathCompleted }} dari {{ $pathTotal }} pelajaran selesai">
                                        <div class="h-full rounded-full bg-primary" style="width: {{ round(($pathCompleted / $pathTotal) * 100) }}%"></div>
                                    </div>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.profile>
