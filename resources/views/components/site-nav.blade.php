@props(['learningPaths', 'mobileLearningPath' => null])

@php
    $currentLearningPath = request()->route('learningPath');
    $currentLearningPathSlug = $currentLearningPath instanceof \App\Models\LearningPath ? $currentLearningPath->slug : $currentLearningPath;
    $anonymousProfile = auth()->guest()
        ? \App\Models\LearnerProfile::query()
            ->where('visitor_id', request()->cookie('artaswaha_device'))
            ->where('mode', 'anonymous')
            ->whereNull('user_id')
            ->first()
        : null;
    $anonymousAvatar = $anonymousProfile
        ? (str_starts_with($anonymousProfile->avatar ?? '', 'avatars/')
            ? route('profile.avatar.show')
            : asset($anonymousProfile->avatar ?? array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS)))
        : null;
@endphp

<header class="sticky top-0 z-50 border-b border-white/10 bg-background">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 sm:py-5 lg:px-10 lg:py-6">
        <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" aria-label="ARTASWAHA beranda">
            <img src="{{ asset('image/maskot.webp') }}" alt="" class="size-10 rounded-full object-cover object-center sm:size-12">
            <span class="font-display text-lg font-bold tracking-tight sm:text-2xl">ARTASWAHA</span>
        </a>
        <nav class="hidden items-center gap-1 lg:flex lg:gap-3" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" @class([
                'inline-flex min-h-11 items-center gap-2 rounded-full px-3 py-3 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent',
                'bg-primary/20 text-accent' => request()->routeIs('home'),
                'text-foreground/80 hover:bg-surface' => ! request()->routeIs('home'),
            ]) @if (request()->routeIs('home')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9M9 21v-7h6v7"/></svg>
                Beranda
            </a>
            @foreach (['paribasa' => 'Paribasa Bali', 'aksara' => 'Aksara Bali'] as $group => $label)
                <div class="group relative hidden sm:block">
                    <button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-full px-3 py-3 text-sm font-semibold text-foreground/80 transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent" aria-haspopup="true">
                        @if ($group === 'paribasa')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="m4 19 6-14 5 14M6 14h7M15 19l3-7 3 7M16 17h4"/></svg>
                        @endif
                        {{ $label }}
                        <span class="mt-[-3px] size-2 rotate-45 border-b-2 border-r-2 border-current transition-transform group-hover:rotate-[225deg]" aria-hidden="true"></span>
                    </button>
                    <div class="invisible absolute left-0 top-full z-20 w-64 translate-y-2 pt-3 opacity-0 transition duration-150 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-has-[:focus-visible]:visible group-has-[:focus-visible]:translate-y-0 group-has-[:focus-visible]:opacity-100">
                        <div class="rounded-card bg-surface p-2 shadow-2xl shadow-black/40">
                            @foreach ($learningPaths->where('group', $group) as $learningPath)
                                <a class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-foreground/80 transition-colors hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" href="{{ route('learning.paths.show', $learningPath->slug) }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0 text-accent" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22z"/><path d="M4 5.5v14A2.5 2.5 0 0 1 6.5 17H20"/></svg>
                                    <span>{{ $learningPath->title }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
            <a class="hidden min-h-11 items-center gap-2 rounded-full px-3 py-3 text-sm font-semibold text-foreground/80 transition hover:bg-surface sm:inline-flex" href="{{ route('quizzes.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M8 5V3h8v2"/><path d="M6 5h12v16H6z"/><path d="M9 10h6M9 14h6M9 18h3"/></svg>
                Kuis
            </a>
            @guest
                @if ($anonymousProfile)
                    <div class="group relative">
                        <button type="button" class="relative grid size-11 place-items-center overflow-hidden rounded-full bg-accent font-display font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" aria-label="Menu profil anonim {{ $anonymousProfile->name }}">
                            <img src="{{ $anonymousAvatar }}" alt="" class="absolute inset-0 size-full object-cover">
                        </button>
                        <div class="invisible absolute right-0 top-full z-20 w-56 translate-y-2 pt-3 opacity-0 transition duration-150 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-has-[:focus-visible]:visible group-has-[:focus-visible]:translate-y-0 group-has-[:focus-visible]:opacity-100">
                            <div class="rounded-card bg-surface p-2 shadow-2xl shadow-black/40">
                                <div class="px-4 py-3"><p class="truncate text-sm font-semibold text-foreground">{{ $anonymousProfile->name }}</p><p class="text-xs text-accent">Akun anonim</p></div>
                                <a href="{{ route('profile.show') }}" class="block rounded-xl px-4 py-3 text-sm font-medium text-foreground/80 transition-colors hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">Profil</a>
                                <a href="{{ route('login') }}" class="block rounded-xl px-4 py-3 text-sm font-medium text-foreground/80 transition-colors hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">Masuk</a>
                                <a href="{{ route('register') }}" class="block rounded-xl px-4 py-3 text-sm font-medium text-foreground/80 transition-colors hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">Daftar akun</a>
                            </div>
                        </div>
                    </div>
                @else
                <a class="hidden min-h-11 items-center gap-2 rounded-full px-3 py-3 text-sm font-semibold text-foreground/80 transition hover:bg-surface sm:inline-flex" href="{{ route('login') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                    Login
                </a>
                <a class="inline-flex min-h-11 items-center gap-2 rounded-full bg-accent px-4 py-3 text-sm font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" href="{{ route('register') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                    Daftar
                </a>
                @endif
            @else
                <div class="group relative">
                    <button type="button" class="relative grid size-11 place-items-center overflow-hidden rounded-full bg-accent font-display font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" aria-label="Menu profil {{ auth()->user()->name }}">
                        <img src="{{ str_starts_with(auth()->user()->avatar ?? '', 'avatars/') ? route('profile.avatar.show') : asset(auth()->user()->avatar ?? array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS)) }}" alt="" class="absolute inset-0 size-full object-cover">
                    </button>
                    <div class="invisible absolute right-0 top-full z-20 w-56 translate-y-2 pt-3 opacity-0 transition duration-150 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-has-[:focus-visible]:visible group-has-[:focus-visible]:translate-y-0 group-has-[:focus-visible]:opacity-100">
                        <div class="rounded-card bg-surface p-2 shadow-2xl shadow-black/40">
                            <p class="truncate px-4 py-3 text-sm font-semibold text-foreground/70">{{ auth()->user()->name }}</p>
                            <a href="{{ route('profile.show') }}" class="block rounded-xl px-4 py-3 text-sm font-medium text-foreground/80 transition-colors hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">Profil</a>
                            @if (auth()->user()->is_admin)
                                <a href="{{ route('admin.lessons.index') }}" class="block rounded-xl px-4 py-3 text-sm font-medium text-foreground/80 transition-colors hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">Kelola materi</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="block w-full rounded-xl px-4 py-3 text-left text-sm font-semibold text-foreground/80 transition-colors hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" type="submit">Keluar</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endguest
        </nav>
        <button type="button" data-mobile-nav-open aria-controls="mobile-navigation" aria-expanded="false" aria-label="Buka menu navigasi" class="grid size-10 place-items-center rounded-full border border-white/10 bg-surface text-foreground transition hover:border-accent/50 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent lg:hidden">
            <span class="sr-only">Buka menu</span>
            <span class="grid gap-1.5" aria-hidden="true"><span class="h-0.5 w-5 rounded-full bg-current"></span><span class="h-0.5 w-5 rounded-full bg-current"></span><span class="h-0.5 w-5 rounded-full bg-current"></span></span>
        </button>
    </div>
</header>
<div data-mobile-nav-backdrop aria-controls="mobile-navigation" aria-hidden="true"></div>
<aside id="mobile-navigation" data-mobile-nav role="dialog" aria-modal="true" aria-label="Navigasi utama" aria-hidden="true" inert class="z-50 flex h-dvh flex-col border-l border-white/10 bg-background p-5 text-foreground shadow-2xl shadow-black/50">
            <div class="flex items-center justify-between border-b border-white/10 pb-5">
                <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-full font-display text-lg font-bold focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"><img src="{{ asset('image/maskot.webp') }}" alt="" class="size-10 rounded-full object-cover">ARTASWAHA</a>
                <button type="button" data-mobile-nav-close aria-label="Tutup menu" class="grid size-10 place-items-center rounded-full bg-background text-foreground/70 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="size-4" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
            </div>
            <nav class="mt-5 flex-1 space-y-3 overflow-y-auto overscroll-contain pb-6" aria-label="Navigasi mobile">
                <a href="{{ route('home') }}" @class([
                    'flex min-h-12 items-center gap-3 rounded-full px-4 py-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-accent',
                    'bg-primary/20 text-accent shadow-[inset_0_0_0_1px_rgba(0,124,128,0.35)]' => request()->routeIs('home'),
                    'text-foreground/75 hover:bg-surface hover:text-accent' => ! request()->routeIs('home'),
                ]) @if (request()->routeIs('home')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><path d="m3 10 9-7 9 7"/><path d="M5 9v12h14V9M9 21v-7h6v7"/></svg>
                    Beranda
                </a>
                @foreach (['paribasa' => 'Paribasa Bali', 'aksara' => 'Aksara Bali'] as $group => $label)
                    <details @if ($currentLearningPath instanceof \App\Models\LearningPath && $currentLearningPath->group === $group) open @endif class="group border-b border-white/10 pb-2">
                        <summary @class([
                            'flex min-h-12 cursor-pointer list-none items-center justify-between rounded-full px-4 py-3 font-display font-bold transition hover:bg-surface hover:text-accent focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden',
                            'bg-primary/20 text-accent' => $currentLearningPath instanceof \App\Models\LearningPath && $currentLearningPath->group === $group,
                            'text-foreground/85' => ! ($currentLearningPath instanceof \App\Models\LearningPath && $currentLearningPath->group === $group),
                        ])>
                            <span class="flex items-center gap-3">
                                @if ($group === 'paribasa')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><path d="m4 19 6-14 5 14M6 14h7M15 19l3-7 3 7M16 17h4"/></svg>
                                @endif
                                {{ $label }}
                            </span>
                            <span class="size-2 rotate-45 border-b-2 border-r-2 border-current transition-transform group-open:rotate-[225deg]" aria-hidden="true"></span>
                        </summary>
                        <div class="mt-2 space-y-1 pl-3">
                            @foreach ($learningPaths->where('group', $group) as $learningPath)
                                <a href="{{ route('learning.paths.show', $learningPath->slug) }}" @class([
                                    'flex min-h-11 items-center gap-3 rounded-full px-4 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-accent',
                                    'bg-primary/20 text-accent shadow-[inset_0_0_0_1px_rgba(0,124,128,0.35)]' => $currentLearningPathSlug === $learningPath->slug,
                                    'text-foreground/70 hover:bg-surface hover:text-accent' => $currentLearningPathSlug !== $learningPath->slug,
                                ])>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22z"/><path d="M4 5.5v14A2.5 2.5 0 0 1 6.5 17H20"/></svg>
                                    <span class="min-w-0 flex-1">{{ $learningPath->title }}</span>
                                    @if ($currentLearningPathSlug === $learningPath->slug)
                                        <span class="size-2 shrink-0 rounded-full bg-accent" aria-hidden="true"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endforeach
                <div class="space-y-1 border-t border-white/10 pt-4">
                    <a href="{{ route('quizzes.index') }}" @class([
                        'flex min-h-12 items-center gap-3 rounded-full px-4 py-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-accent',
                        'bg-primary/20 text-accent shadow-[inset_0_0_0_1px_rgba(0,124,128,0.35)]' => request()->routeIs('quizzes.*', 'rooms.*'),
                        'text-foreground/75 hover:bg-surface hover:text-accent' => ! request()->routeIs('quizzes.*', 'rooms.*'),
                    ])>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><path d="M8 5V3h8v2"/><path d="M6 5h12v16H6z"/><path d="M9 10h6M9 14h6M9 18h3"/></svg>
                        Kuis
                    </a>
                @auth
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.lessons.index') }}" class="flex min-h-12 items-center gap-3 rounded-full px-4 py-3 text-sm font-bold text-foreground/75 transition hover:bg-surface hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>
                            Kelola materi
                        </a>
                    @endif
                @else
                    @if ($anonymousProfile)
                    <a href="{{ route('profile.show') }}" class="flex min-h-12 items-center gap-3 rounded-full px-4 py-3 text-sm font-bold text-foreground/75 transition hover:bg-surface hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"><img src="{{ $anonymousAvatar }}" alt="" class="size-7 rounded-full object-cover"> Profil anonim</a>
                    @else
                    <a href="{{ route('login') }}" @class([
                        'flex min-h-12 items-center gap-3 rounded-full px-4 py-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-accent',
                        'bg-primary/20 text-accent' => request()->routeIs('login'),
                        'text-foreground/75 hover:bg-surface hover:text-accent' => ! request()->routeIs('login'),
                    ])>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="mt-2 flex min-h-12 items-center justify-center gap-2 rounded-full bg-accent px-5 py-3 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 8v6M23 11h-6"/></svg>
                        Daftar
                    </a>
                    @endif
                @endauth
                </div>
            </nav>
            @auth
                <div class="flex items-center gap-3 border-t border-white/10 pt-4">
                    <a href="{{ route('profile.show') }}" class="flex min-w-0 flex-1 items-center gap-3 rounded-2xl p-2 transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-accent" aria-label="Profil {{ auth()->user()->name }}">
                        <img src="{{ str_starts_with(auth()->user()->avatar ?? '', 'avatars/') ? route('profile.avatar.show') : asset(auth()->user()->avatar ?? array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS)) }}" alt="" class="size-11 shrink-0 rounded-full object-cover ring-1 ring-white/10">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-foreground">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-xs text-foreground/55">{{ auth()->user()->email }}</span>
                        </span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="Keluar" title="Keluar" class="grid size-11 shrink-0 place-items-center rounded-full text-red-400 transition hover:bg-red-400/10 hover:text-red-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-400">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                        </button>
                    </form>
                </div>
            @endauth
            @if ($anonymousProfile)
                <a href="{{ route('profile.show') }}" class="flex min-w-0 items-center gap-3 border-t border-white/10 pt-4 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" aria-label="Profil anonim {{ $anonymousProfile->name }}">
                    <img src="{{ $anonymousAvatar }}" alt="" class="size-11 shrink-0 rounded-full object-cover ring-1 ring-white/10">
                    <span class="min-w-0"><span class="block truncate text-sm font-bold">{{ $anonymousProfile->name }}</span><span class="block text-xs text-accent">Akun anonim</span></span>
                </a>
            @endif
</aside>
@if (request()->routeIs('home', 'learning.paths.*', 'quizzes.index', 'profile.show') && (auth()->check() || $anonymousProfile))
    <x-mobile-app-nav />
@endif
