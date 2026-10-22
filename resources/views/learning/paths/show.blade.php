<x-layouts.app :show-footer="false" :title="$learningPath->title . ' — ARTASWAHA'">
        <div class="mx-auto w-full max-w-5xl px-5 py-8 sm:px-6 sm:py-12 lg:py-16">
            <a href="{{ route('home') }}" class="mb-8 inline-flex min-h-10 items-center rounded-full px-4 text-sm font-semibold text-foreground/70 transition hover:bg-surface hover:text-accent">← Beranda</a>
            <p class="font-semibold text-accent">{{ $learningPath->group === 'paribasa' ? 'Paribasa Bali' : 'Aksara Bali' }}</p>
            <h1 class="mt-2 break-words font-display text-3xl font-bold sm:text-5xl">{{ $learningPath->title }}</h1>
            <p class="mt-4 max-w-2xl font-readable text-lg leading-8 text-foreground/70">{{ $learningPath->description }}</p>

            <section class="mt-10" aria-labelledby="lesson-list-title">
                <h2 id="lesson-list-title" class="font-display text-2xl font-bold">Pelajaran</h2>
                @forelse ($learningPath->lessons as $lesson)
                    <a href="{{ route('learning.lessons.show', $lesson->slug) }}" class="mt-4 flex items-center justify-between gap-4 rounded-card bg-surface p-5 transition hover:bg-surface/80 sm:p-6">
                        <span>
                            <span class="block font-bold">{{ $lesson->title }}</span>
                            <span class="mt-1 block font-readable text-sm text-foreground/65">{{ $lesson->summary }}</span>
                        </span>
                        <span class="text-accent" aria-hidden="true">→</span>
                    </a>
                @empty
                    <p class="mt-4 rounded-card bg-surface p-6 font-readable leading-7 text-foreground/65">Materi untuk bagian ini sedang disiapkan dan akan ditampilkan setelah ditinjau.</p>
                @endforelse
            </section>
        </div>
</x-layouts.app>
