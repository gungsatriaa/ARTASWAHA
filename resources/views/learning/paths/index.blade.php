<x-layouts.app :learning-paths="$learningPaths" :show-footer="false" title="Materi belajar — ARTASWAHA" :canonical="route('learning.paths.index')">
    <div class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 sm:py-10">
        <header class="mb-7">
            <h1 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">Mau belajar apa hari ini?</h1>
            <p class="mt-2 text-sm leading-6 text-foreground/60">Jelajahi materi, pilih yang kamu suka, lalu mulai belajar.</p>
        </header>

        @forelse ($learningPaths->groupBy('group') as $group => $paths)
            <section class="mb-8" aria-labelledby="materials-{{ $group }}">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 id="materials-{{ $group }}" class="font-display text-xl font-bold">{{ $group === 'aksara' ? 'Aksara Bali' : 'Paribasa Bali' }}</h2>
                    <span class="text-xs text-foreground/55">{{ $paths->count() }} materi</span>
                </div>
                <x-content-preview>
                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach ($paths as $path)
                        <li>
                            <a href="{{ route('learning.paths.show', $path->slug) }}" class="flex h-full items-center gap-4 rounded-card bg-surface p-5 transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary/20 text-accent" aria-hidden="true">
                                    @if ($group === 'aksara')
                                        <span class="font-balinese text-xl leading-none" lang="ban">ᬅ</span>
                                    @else
                                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11a8 8 0 0 1-8 8H8l-5 3V11a9 9 0 0 1 18 0ZM8 11h.01M12 11h.01M16 11h.01" /></svg>
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-display text-base font-bold leading-snug">{{ $path->title }}</span>
                                    @if ($path->description)
                                        <span class="mt-1 block text-xs leading-5 text-foreground/60">{{ $path->description }}</span>
                                    @endif
                                    <span class="mt-2 block text-xs font-semibold text-accent">{{ $path->lessons_count > 0 ? $path->lessons_count.' pelajaran' : 'Pelajaran sedang disiapkan' }}</span>
                                </span>
                                <span class="shrink-0 text-xl text-foreground/45" aria-hidden="true">›</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                </x-content-preview>
            </section>
        @empty
            <p class="rounded-card bg-surface p-6 text-sm leading-6 text-foreground/65">Materi belajar sedang disiapkan. Silakan kembali lagi nanti.</p>
        @endforelse
    </div>
</x-layouts.app>
