@props(['user', 'profile', 'dashboardPaths', 'featuredPath'])

@php
    $name = $user?->name ?? $profile->name;
    $avatar = $user?->avatar ?? $profile->avatar;
    $avatarUrl = str_starts_with($avatar ?? '', 'avatars/')
        ? route('profile.avatar.show')
        : asset($avatar ?? array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS));
    $featuredTotal = $featuredPath?->lessons_count ?? 0;
    $featuredCompleted = $featuredPath?->completed_lessons_count ?? 0;
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
            <p class="text-sm font-semibold text-background/70">{{ $profile === null ? 'Mulai petualangan belajarmu' : 'Lanjutkan belajarmu' }}</p>
            <h2 id="mobile-featured-title" class="mt-2 max-w-[17rem] font-display text-2xl font-bold leading-tight">{{ $profile ? ($featuredPath?->title ?? 'Materi pilihanmu') : 'Pilih materi pertamamu' }}</h2>
            @if ($profile && $featuredPath && $featuredTotal > 0)
                <p class="mt-4 text-sm font-semibold">{{ $featuredCompleted }} dari {{ $featuredTotal }} pelajaran selesai</p>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-background/20" role="progressbar" aria-label="Progres {{ $featuredPath->title }}" aria-valuemin="0" aria-valuemax="{{ $featuredTotal }}" aria-valuenow="{{ $featuredCompleted }}">
                    <div class="h-full rounded-full bg-background" style="width: {{ round($featuredCompleted / $featuredTotal * 100) }}%"></div>
                </div>
            @else
                <p class="mt-3 max-w-[16rem] text-sm leading-6 text-background/75">{{ $profile && $featuredPath ? 'Pelajaran untuk materi ini sedang disiapkan.' : 'Jelajahi materi bahasa dan aksara Bali.' }}</p>
            @endif
            <a href="{{ $profile === null ? route('learning.interview.show') : ($featuredPath ? route('learning.paths.show', $featuredPath->slug) : route('profile.show')) }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-full bg-background px-5 text-sm font-bold text-accent transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-background">
                {{ $profile === null ? 'Pilih minat belajar' : ($featuredTotal > 0 ? 'Lanjut belajar' : 'Lihat materi') }}
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
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary/20 font-balinese text-2xl text-accent" lang="ban" aria-hidden="true">{{ $path->group === 'aksara' ? 'ᬅ' : 'ᬒᬁ' }}</span>
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

    <a href="{{ route('quizzes.index') }}" class="mt-7 flex items-center justify-between gap-4 rounded-2xl border border-primary/35 bg-primary/15 p-5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
        <span><span class="block font-display text-lg font-bold">Main kuis bareng</span><span class="mt-1 block text-sm text-foreground/60">{{ $user ? 'Buat kuis atau gabung dengan teman.' : 'Masukkan kode untuk gabung kuis.' }}</span></span>
        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-primary text-xl font-bold text-white" aria-hidden="true">→</span>
    </a>
</div>
