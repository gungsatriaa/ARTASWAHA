<x-layouts.profile :learning-paths="$learningPaths" :user="$user ?? null" :profile="$profile ?? null">
    <section class="mb-6" aria-labelledby="overview-title">
        <h2 id="overview-title" class="mb-3 font-display text-xl font-bold">Ringkasan belajar</h2>
        <dl class="grid grid-cols-3 gap-2 sm:gap-4">
            <div class="rounded-3xl bg-surface p-4 sm:p-5">
                <svg class="mb-3 size-5 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4 4L19 6" /><circle cx="12" cy="12" r="9" /></svg>
                <dt class="text-xs leading-5 text-foreground/55 sm:text-sm">Pelajaran selesai</dt>
                <dd class="mt-1 font-display text-3xl font-bold sm:text-4xl">{{ $completedLessons }}</dd>
            </div>
            <div class="rounded-3xl bg-surface p-4 sm:p-5">
                <svg class="mb-3 size-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h6a3 3 0 0 1 3 3v14a4 4 0 0 0-4-2H4zM13 7a3 3 0 0 1 3-3h4v15h-3a4 4 0 0 0-4 2" /></svg>
                <dt class="text-xs leading-5 text-foreground/55 sm:text-sm">Materi dipelajari</dt>
                <dd class="mt-1 font-display text-3xl font-bold sm:text-4xl">{{ $learningPathsByGroup->flatten()->count() }}</dd>
            </div>
            <div class="rounded-3xl bg-surface p-4 sm:p-5">
                <svg class="mb-3 size-5 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3" /><path d="M16 3v4M8 3v4M3 11h18m-12 5 2 2 4-4" /></svg>
                <dt class="text-xs leading-5 text-foreground/55 sm:text-sm">Hari aktif minggu ini</dt>
                <dd class="mt-1 font-display text-3xl font-bold sm:text-4xl">{{ $activity['active_days_7'] ?? '—' }}<span class="text-base font-medium text-foreground/40">/7</span></dd>
            </div>
        </dl>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            @if ($activity)
                <section class="rounded-card bg-surface p-5 sm:p-6" aria-labelledby="week-title">
                    <header class="flex items-start justify-between gap-3">
                        <div>
                            <h2 id="week-title" class="font-display text-xl font-bold">Sedikit setiap hari</h2>
                            <p class="mt-1 text-xs leading-5 text-foreground/55">Aktivitas belajarmu dalam tujuh hari terakhir.</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-primary/15 px-3 py-1.5 text-xs font-bold text-accent">{{ $activity['active_days_7'] }} hari aktif</span>
                    </header>
                    <ol class="mt-5 grid grid-cols-7 gap-1.5 sm:gap-3" aria-label="Aktivitas belajar tujuh hari terakhir">
                        @foreach (array_slice($activity['daily'], -7) as $day)
                            @php
                                $date = \Illuminate\Support\Carbon::parse($day['date']);
                                $isActive = $day['lessons_completed'] + $day['quiz_answers'] > 0;
                            @endphp
                            <li class="min-w-0 text-center" aria-label="{{ $date->format('d M') }}: {{ $day['lessons_completed'] }} pelajaran selesai, {{ $day['quiz_answers'] }} jawaban kuis">
                                <span class="text-[10px] font-semibold text-foreground/50 sm:text-xs">{{ ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][$date->dayOfWeek] }}</span>
                                <span @class(['mx-auto mt-2 grid aspect-square w-full max-w-11 place-items-center rounded-full', 'bg-accent text-background' => $isActive, 'bg-background text-foreground/30' => ! $isActive, 'ring-2 ring-accent/50 ring-offset-2 ring-offset-surface' => $loop->last]) aria-hidden="true">
                                    @if ($isActive)
                                        <svg class="size-4 sm:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6" /></svg>
                                    @else
                                        <span class="size-1.5 rounded-full bg-current"></span>
                                    @endif
                                </span>
                                <time datetime="{{ $day['date'] }}" class="mt-2 block text-[10px] text-foreground/40 sm:text-xs">{{ $date->format('d') }}</time>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-5 text-xs leading-5 text-foreground/45">Selesaikan pelajaran atau jawab kuis untuk mencatat hari aktif.</p>
                    @if ($activity['quiz_answers_14'] > 0)
                        <p class="mt-3 text-xs leading-5 text-foreground/60">Kuis 14 hari terakhir: <span class="font-bold text-foreground">{{ $activity['quiz_correct_14'] }} dari {{ $activity['quiz_answers_14'] }} jawaban benar</span>.</p>
                    @endif
                </section>
            @endif

            <section id="progres" class="scroll-mt-28 rounded-card bg-surface p-5 sm:p-6" aria-labelledby="progress-title">
                <header class="mb-5 flex items-start justify-between gap-3">
                    <div>
                        <h2 id="progress-title" class="font-display text-xl font-bold">Progres belajar</h2>
                        <p class="mt-1 text-xs leading-5 text-foreground/55">Materi yang sudah kamu mulai pelajari.</p>
                    </div>
                    <a href="{{ route('learning.paths.index') }}" class="shrink-0 rounded-full py-1 text-xs font-bold text-accent focus-visible:outline-2 focus-visible:outline-accent">Lihat materi</a>
                </header>
                @if ($totalLessons > 0)
                    <p class="text-sm font-semibold text-foreground/75">{{ $completedLessons }} dari {{ $totalLessons }} pelajaran selesai</p>
                    <div class="mb-6 mt-3 h-2.5 overflow-hidden rounded-full bg-background" role="progressbar" aria-label="Progres belajar keseluruhan" aria-valuemin="0" aria-valuemax="{{ $totalLessons }}" aria-valuenow="{{ $completedLessons }}" aria-valuetext="{{ $completedLessons }} dari {{ $totalLessons }} pelajaran selesai">
                        <div class="h-full rounded-full bg-primary" style="width: {{ round($completedLessons / $totalLessons * 100) }}%"></div>
                    </div>
                    @foreach ($learningPathsByGroup as $group => $paths)
                        <h3 class="mb-3 mt-5 text-xs font-bold text-foreground/50">{{ $group === 'paribasa' ? 'Paribasa Bali' : 'Aksara Bali' }}</h3>
                        <ul class="space-y-3">
                            @foreach ($paths as $learningPath)
                                @php($percent = round($learningPath->completed_lessons_count / $learningPath->lessons_count * 100))
                                <li>
                                    <a href="{{ route('learning.paths.show', $learningPath->slug) }}" class="group block rounded-2xl bg-background p-4 transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                                        <div class="flex items-start justify-between gap-3">
                                            <span class="min-w-0 break-words text-sm font-bold leading-6 group-hover:text-accent">{{ $learningPath->title }}</span>
                                            <span class="shrink-0 text-xs font-bold leading-6 text-accent">{{ $percent }}%</span>
                                        </div>
                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/8" role="progressbar" aria-label="Progres {{ $learningPath->title }}: {{ $learningPath->completed_lessons_count }} dari {{ $learningPath->lessons_count }} pelajaran" aria-valuemin="0" aria-valuemax="{{ $learningPath->lessons_count }}" aria-valuenow="{{ $learningPath->completed_lessons_count }}">
                                            <div class="h-full rounded-full bg-primary" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <span class="mt-2 block text-xs text-foreground/45">{{ $learningPath->completed_lessons_count }} dari {{ $learningPath->lessons_count }} pelajaran</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                @else
                    <div class="py-5 text-center">
                        <span class="mx-auto grid size-14 place-items-center rounded-full bg-primary/15 text-accent" aria-hidden="true"><svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h6a3 3 0 0 1 3 3v14a4 4 0 0 0-4-2H4zM13 7a3 3 0 0 1 3-3h4v15h-3a4 4 0 0 0-4 2" /></svg></span>
                        <h3 class="mt-4 font-display text-lg font-bold">Belum ada progres belajar</h3>
                        <p class="mx-auto mt-2 max-w-xs text-sm leading-6 text-foreground/55">Mulai dari satu pelajaran. Setiap langkahmu akan tercatat di sini.</p>
                        <a href="{{ route('learning.paths.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-full bg-accent px-6 text-sm font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Pilih materi</a>
                    </div>
                @endif
            </section>
        </div>

        <div class="min-w-0 space-y-6">
            @if ($analysisProfile)
                <x-learner-analysis :analysis="$analysis" />
                @php($selectedPaths = $learningPaths->whereIn('id', $analysisProfile->learning_path_ids ?: [$analysisProfile->learning_path_id]))
                @if ($selectedPaths->isNotEmpty())
                    <section class="rounded-card bg-surface p-5 sm:p-6" aria-labelledby="interests-title">
                        <h2 id="interests-title" class="font-display text-lg font-bold">Minat belajarmu</h2>
                        <p class="mt-1 text-xs leading-5 text-foreground/50">{{ $analysis['role'] }} · Pilihan dari perkenalan awalmu.</p>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($selectedPaths as $path)
                                <li><a href="{{ route('learning.paths.show', $path->slug) }}" class="inline-flex min-h-10 items-center rounded-full bg-background px-4 py-2 text-xs font-semibold leading-5 text-foreground/75 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">{{ $path->title }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            @endif
            @if (isset($profile))
                <section class="rounded-card bg-primary/10 p-5 sm:p-6" aria-labelledby="anonymous-title">
                    <p class="text-xs font-bold text-accent">Akun anonim · hanya di perangkat ini</p>
                    <h2 id="anonymous-title" class="mt-2 font-display text-lg font-bold">Belajar sebagai {{ $profile->name }}</h2>
                    <p class="mt-2 text-sm leading-6 text-foreground/60">Progres tersimpan di browser ini. Hubungkan ke akun agar bisa melanjutkan di perangkat lain.</p>
                    <a href="{{ route('register') }}" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-full bg-accent px-5 text-sm font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Daftar akun</a>
                </section>
            @endif
        </div>
    </div>
</x-layouts.profile>
