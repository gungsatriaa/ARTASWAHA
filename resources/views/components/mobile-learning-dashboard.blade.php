@props(['user', 'profile', 'dashboardPaths', 'lastLearningProgress' => null, 'analysis'])

@php
    $name = $user?->name ?? $profile->name;
    $avatar = $user?->avatar ?? $profile->avatar;
    $avatarUrl = str_starts_with($avatar ?? '', 'avatars/')
        ? route('profile.avatar.show')
        : asset($avatar ?? array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS));
@endphp

<div class="mx-auto max-w-lg px-4 pt-6 md:hidden">
    <header class="flex items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-foreground/55">Selamat datang kembali,</p>
            <h1 class="truncate font-display text-[1.8rem] font-bold leading-tight">{{ $name }}</h1>
            @if ($user === null)
                <p class="mt-1 text-xs font-semibold text-accent">Akun anonim · di perangkat ini</p>
            @endif
        </div>
        <a href="{{ route('profile.show') }}" class="shrink-0 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" aria-label="Lihat profil {{ $name }}">
            <img src="{{ $avatarUrl }}" alt="" class="size-13 rounded-full border-2 border-accent/70 object-cover">
        </a>
    </header>

    <section class="relative mt-7 overflow-hidden rounded-card bg-accent p-6 text-background" aria-labelledby="mobile-featured-title">
        <span class="pointer-events-none absolute -right-10 -top-16 size-44 rounded-full border-[24px] border-background/8" aria-hidden="true"></span>
        <div class="relative">
            <p class="text-sm font-semibold text-background/70">Riwayat belajar terakhir</p>
            <h2 id="mobile-featured-title" class="mt-2 break-words font-display text-2xl font-bold leading-tight">{{ $lastLearningProgress?->lesson->title ?? 'Belum ada riwayat belajar' }}</h2>
            @if ($lastLearningProgress)
                <p class="mt-3 text-sm font-semibold text-background/75">{{ $lastLearningProgress->lesson->learningPath->title }}</p>
                <p class="mt-2 text-sm text-background/70">Selesai pada <time datetime="{{ $lastLearningProgress->completed_at->toIso8601String() }}">{{ $lastLearningProgress->completed_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}</time></p>
            @else
                <p class="mt-3 text-sm leading-6 text-background/75">Selesaikan pelajaran pertamamu untuk melihat riwayat belajar di sini.</p>
            @endif
            <a href="{{ $lastLearningProgress ? route('learning.lessons.show', $lastLearningProgress->lesson->slug) : route('learning.paths.index') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-full bg-background px-5 text-sm font-bold text-accent transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-background">
                {{ $lastLearningProgress ? 'Pelajari kembali' : 'Pilih materi' }}
                <span class="ml-2" aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    <section class="mt-8" aria-labelledby="mobile-paths-title">
        <div class="flex items-end justify-between gap-3">
            <div>
                <h2 id="mobile-paths-title" class="font-display text-xl font-bold">{{ $profile ? 'Materi pilihanmu' : 'Jelajahi materi' }}</h2>
                <p class="mt-1 text-xs text-foreground/55">Belajar sedikit demi sedikit, sesuai waktumu.</p>
            </div>
            <a href="{{ route('profile.show') }}#progres" class="min-h-11 shrink-0 content-center rounded-full px-2 text-xs font-bold text-accent focus-visible:outline-2 focus-visible:outline-accent">Progresmu</a>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($dashboardPaths as $path)
                <a href="{{ route('learning.paths.show', $path->slug) }}" class="flex min-h-20 items-center gap-4 rounded-2xl border border-white/10 bg-surface p-4 transition hover:border-accent/40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary/20 text-accent" aria-hidden="true">
                        @if ($path->group === 'aksara')
                            <span class="font-balinese text-xl leading-none" lang="ban">ᬅ</span>
                        @else
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z" />
                                <path d="M8 11h.01M12 11h.01M16 11h.01" />
                            </svg>
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-display text-base font-bold">{{ $path->title }}</span>
                        <span class="mt-1 block text-xs text-foreground/55">{{ $path->lessons_count > 0 ? $path->completed_lessons_count.' dari '.$path->lessons_count.' pelajaran' : 'Pelajaran sedang disiapkan' }}</span>
                        @if ($path->lessons_count > 0)
                            <span class="mt-2 block h-1.5 overflow-hidden rounded-full bg-background" role="progressbar" aria-label="Progres {{ $path->title }}" aria-valuemin="0" aria-valuemax="{{ $path->lessons_count }}" aria-valuenow="{{ $path->completed_lessons_count }}"><span class="block h-full rounded-full bg-primary" style="width: {{ round($path->completed_lessons_count / $path->lessons_count * 100) }}%"></span></span>
                        @endif
                    </span>
                    <span class="text-lg text-foreground/45" aria-hidden="true">›</span>
                </a>
            @empty
                <p class="rounded-2xl border border-white/10 bg-surface p-5 text-sm leading-6 text-foreground/65">Materi pilihanmu belum tersedia. Buka menu untuk menjelajahi materi lain.</p>
            @endforelse
        </div>
    </section>

    @if ($profile)
        <div class="mt-7"><x-learner-analysis :analysis="$analysis" :compact="true" /></div>
    @endif
    <a href="{{ route('quizzes.index') }}" class="mt-7 flex items-center justify-between gap-4 rounded-card border border-primary/35 bg-primary/15 p-5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
        <span><span class="block font-display text-lg font-bold">Main kuis bareng</span><span class="mt-1 block text-sm text-foreground/60">{{ $user ? 'Buat kuis atau gabung dengan teman.' : 'Masukkan kode untuk gabung kuis.' }}</span></span>
        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary text-xl font-bold text-white" aria-hidden="true">→</span>
    </a>
</div>
