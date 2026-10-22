@props(['learningPaths'])

<header class="sticky top-0 z-50 border-b border-white/10 bg-background/95 backdrop-blur-sm">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-10">
        <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" aria-label="ArtaSwaha beranda">
            <img src="{{ asset('image/maskot.webp') }}" alt="" class="size-12 rounded-full object-cover object-center">
            <span class="font-display text-2xl font-bold tracking-tight">ARTASWAHA</span>
        </a>
        <nav class="flex items-center gap-1 sm:gap-3" aria-label="Navigasi utama">
            @foreach (['paribasa' => 'Paribasa Bali', 'aksara' => 'Aksara Bali'] as $group => $label)
                <div class="group relative hidden sm:block">
                    <button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 py-3 text-sm font-semibold text-foreground/80 transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent" aria-haspopup="true">
                        {{ $label }}
                        <span class="mt-[-3px] size-2 rotate-45 border-b-2 border-r-2 border-current transition-transform group-hover:rotate-[225deg]" aria-hidden="true"></span>
                    </button>
                    <div class="invisible absolute left-0 top-full z-20 w-64 translate-y-2 pt-3 opacity-0 transition duration-150 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100">
                        <div class="rounded-2xl border border-white/10 bg-surface p-2 shadow-2xl shadow-black/40">
                            @foreach ($learningPaths->where('group', $group) as $learningPath)
                                <a class="block rounded-xl px-4 py-3 text-sm font-medium text-foreground/80 transition-colors hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" href="{{ route('learning.paths.show', $learningPath->slug) }}">{{ $learningPath->title }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
            <a class="hidden min-h-11 items-center rounded-full px-4 py-3 text-sm font-semibold text-foreground/80 transition hover:bg-surface sm:inline-flex" href="{{ auth()->check() ? route('quizzes.index') : route('login') }}">Kuis</a>
            @guest
                <a class="hidden min-h-11 items-center rounded-full px-4 py-3 text-sm font-semibold text-foreground/80 transition hover:bg-surface sm:inline-flex" href="{{ route('login') }}">Login</a>
                <a class="inline-flex min-h-11 items-center rounded-full bg-accent px-6 py-3 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" href="{{ route('register') }}">Daftar</a>
            @else
                <span class="hidden text-sm font-semibold text-foreground/75 sm:inline">{{ auth()->user()->name }}</span>
                @if (auth()->user()->is_admin)
                    <a href="{{ route('admin.lessons.index') }}" class="hidden min-h-11 items-center rounded-full px-4 py-3 text-sm font-semibold text-accent transition hover:bg-surface sm:inline-flex">Kelola materi</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="inline-flex min-h-11 items-center rounded-full bg-accent px-5 py-3 font-extrabold text-background" type="submit">Keluar</button>
                </form>
            @endguest
        </nav>
    </div>
</header>
