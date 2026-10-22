@props(['learningPaths'])

<footer class="border-t border-white/10 bg-surface/40">
    <div class="mx-auto max-w-7xl px-6 py-12 lg:px-10">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">
                    <img src="{{ asset('image/maskot.webp') }}" alt="" class="size-10 rounded-full object-cover object-center">
                    <span class="font-display text-xl font-bold">ARTASWAHA</span>
                </a>
                <p class="mt-4 max-w-sm font-readable leading-7 text-foreground/60">Ruang belajar paribasa dan aksara Bali yang seru untuk dijelajahi bersama.</p>
            </div>

            @foreach (['paribasa' => 'Paribasa Bali', 'aksara' => 'Aksara Bali'] as $group => $label)
                <div>
                    <h2 class="font-display font-bold">{{ $label }}</h2>
                    <ul class="mt-4 space-y-2 text-sm text-foreground/65">
                        @foreach ($learningPaths->where('group', $group) as $learningPath)
                            <li><a class="inline-flex min-h-8 items-center transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" href="{{ route('learning.paths.show', $learningPath->slug) }}">{{ $learningPath->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-10 flex flex-col gap-3 border-t border-white/10 pt-5 text-sm text-foreground/45 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ now()->year }} · ARTASWAHA - Platform Edukasi Bahasa, Sastra, dan Aksara Bali.</p>
            <a href="#mulai" class="font-semibold transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">Kembali ke atas ↑</a>
        </div>
    </div>
</footer>
