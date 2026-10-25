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
    @if (session('status'))
        <x-flash-notice class="mb-5">{{ session('status') }}</x-flash-notice>
    @endif
    @if ($analysisProfile)
        <x-learner-analysis :analysis="$analysis" />
    @endif
    <section id="progres" class="min-w-0" aria-labelledby="progress-title">
        <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-accent">Langkah kecilmu</p>
                <h2 id="progress-title" class="mt-1 font-display text-2xl font-bold sm:text-3xl">Progres belajar</h2>
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
            <div class="mb-5 flex items-center gap-4 rounded-3xl bg-primary/10 px-5 py-4 text-sm text-foreground/75">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary/20 text-xl text-accent" aria-hidden="true">✦</span>
                <p>Materi belajar sedang disiapkan. Progresmu akan tercatat di sini setelah pelajaran tersedia.</p>
            </div>
        @endif

        <div class="grid gap-4 xl:grid-cols-2">
            @foreach (['paribasa' => 'Paribasa Bali', 'aksara' => 'Aksara Bali'] as $group => $label)
                @php($paths = $learningPathsByGroup->get($group, collect()))
                @php($groupCompleted = $paths->sum('completed_lessons_count'))
                @php($groupTotal = $paths->sum('lessons_count'))

                <article class="rounded-card bg-surface p-5 sm:p-6">
                    <header class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-display text-lg font-bold">{{ $label }}</h3>
                            <p class="mt-1 text-xs text-foreground/50">{{ $groupTotal > 0 ? "$groupCompleted dari $groupTotal pelajaran selesai" : 'Materi sedang disiapkan' }}</p>
                        </div>
                        @if ($groupTotal > 0)
                            <span class="rounded-full bg-background px-3 py-1.5 text-xs font-bold text-accent">{{ $groupCompleted }}/{{ $groupTotal }}</span>
                        @else
                            <span class="grid size-10 place-items-center rounded-full bg-primary/15 text-accent" aria-hidden="true">{{ $group === 'paribasa' ? '✦' : 'ᬅ' }}</span>
                        @endif
                    </header>

                    <ul class="space-y-3">
                        @forelse ($paths as $learningPath)
                            @php($pathTotal = $learningPath->lessons_count)
                            @php($pathCompleted = $learningPath->completed_lessons_count)

                            <li @class(['rounded-2xl px-3 py-2.5', 'bg-background/60' => $pathTotal === 0])>
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    @if ($pathTotal > 0)
                                        <a href="{{ route('learning.paths.show', $learningPath->slug) }}" class="min-w-0 truncate font-semibold text-foreground/85 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">{{ $learningPath->title }}</a>
                                        <span class="shrink-0 text-xs text-foreground/60">{{ $pathCompleted }}/{{ $pathTotal }}</span>
                                    @else
                                        <span class="min-w-0 truncate font-semibold text-foreground/75">{{ $learningPath->title }}</span>
                                        <span class="shrink-0 text-xs text-accent/80">Segera hadir</span>
                                    @endif
                                </div>
                                @if ($pathTotal > 0)
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-background" role="progressbar" aria-label="Progres {{ $learningPath->title }}: {{ $pathCompleted }} dari {{ $pathTotal }} pelajaran" aria-valuemin="0" aria-valuemax="{{ $pathTotal }}" aria-valuenow="{{ $pathCompleted }}" aria-valuetext="{{ $pathCompleted }} dari {{ $pathTotal }} pelajaran selesai">
                                        <div class="h-full rounded-full bg-primary" style="width: {{ round(($pathCompleted / $pathTotal) * 100) }}%"></div>
                                    </div>
                                @endif
                            </li>
                        @empty
                            <li class="rounded-2xl bg-background p-4 text-sm text-foreground/60">Materi untuk bagian ini sedang disiapkan.</li>
                        @endforelse
                    </ul>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.profile>
