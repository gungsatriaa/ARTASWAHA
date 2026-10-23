<x-layouts.app :show-footer="false" :title="$learningPath->title . ' — ARTASWAHA'">
    <div class="mx-auto grid w-full max-w-6xl gap-6 px-5 py-8 sm:px-6 sm:py-12 lg:grid-cols-[.82fr_1.18fr] lg:items-start lg:gap-8 lg:px-10 lg:py-14">
        <aside class="rounded-card border border-white/8 bg-surface p-6 sm:p-8 lg:sticky lg:top-32">
            <a href="{{ route('home') }}" class="inline-flex min-h-10 items-center gap-2 rounded-full px-3 text-sm font-semibold text-foreground/65 transition hover:bg-background hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg>
                Beranda
            </a>

            <div class="mt-8 grid size-14 place-items-center rounded-2xl {{ $learningPath->group === 'paribasa' ? 'bg-accent/10 text-accent' : 'bg-primary/20 text-accent' }}" aria-hidden="true">
                @if ($learningPath->group === 'paribasa')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-7"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/></svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-7"><path d="m4 19 6-14 5 14M6 14h7M15 19l3-7 3 7M16 17h4"/></svg>
                @endif
            </div>

            <p class="mt-5 text-sm font-bold text-accent">{{ $learningPath->group === 'paribasa' ? 'Paribasa Bali' : 'Aksara Bali' }}</p>
            <h1 class="mt-2 break-words font-display text-3xl font-bold leading-tight sm:text-4xl">{{ $learningPath->title }}</h1>
            <p class="mt-4 font-readable leading-7 text-foreground/65">{{ $learningPath->description }}</p>

            <div class="mt-7 flex items-center gap-3 border-t border-white/10 pt-5">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-background font-display text-lg font-bold text-accent" aria-hidden="true">{{ $learningPath->lessons->count() }}</span>
                <span>
                    <span class="block font-bold">{{ $learningPath->lessons->count() }} pelajaran</span>
                    <span class="mt-0.5 block text-sm text-foreground/55">Pilih satu untuk mulai belajar</span>
                </span>
            </div>
        </aside>

        <section aria-labelledby="lesson-list-title">
            <div class="mb-5 sm:mb-6">
                <p class="text-sm font-semibold text-accent">Petualangan belajar</p>
                <h2 id="lesson-list-title" class="mt-1 font-display text-2xl font-bold sm:text-3xl">Pilih pelajaran</h2>
                <p class="mt-2 text-sm leading-6 text-foreground/60">Buka modul yang kamu suka. Setiap pelajaran berisi materi dan latihan singkat.</p>
            </div>

            <ol class="space-y-3">
                @forelse ($learningPath->lessons as $lesson)
                    <li>
                        <a href="{{ route('learning.lessons.show', $lesson->slug) }}" class="group flex min-h-24 items-center gap-4 rounded-2xl border border-white/8 bg-surface p-4 transition-colors hover:border-accent/35 hover:bg-surface/80 focus-visible:border-accent/50 focus-visible:outline-2 focus-visible:outline-accent sm:gap-5 sm:p-5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-background font-display text-sm font-bold text-foreground/55 transition-colors group-hover:bg-accent group-hover:text-background" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block break-words font-display font-bold text-foreground transition-colors group-hover:text-accent">{{ $lesson->title }}</span>
                                @if ($lesson->summary)
                                    <span class="mt-1 block text-sm leading-6 text-foreground/60">{{ $lesson->summary }}</span>
                                @endif
                            </span>
                            <span class="grid size-10 shrink-0 place-items-center rounded-full text-foreground/45 transition-colors group-hover:bg-accent/10 group-hover:text-accent" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="rounded-2xl border border-dashed border-white/15 bg-surface p-6 text-sm leading-6 text-foreground/60">Belum ada pelajaran di kategori ini.</li>
                @endforelse
            </ol>
        </section>
    </div>
</x-layouts.app>
