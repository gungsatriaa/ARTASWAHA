@props(['learningPaths'])

<footer class="border-t border-white/10 bg-surface/40">
    <div class="mx-auto max-w-7xl px-6 py-12 lg:px-10">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">
                    <img src="{{ asset('image/maskot.webp') }}" alt="" class="size-10 rounded-full object-cover object-center">
                    <span class="font-display text-xl font-bold">ARTASWAHA</span>
                </a>
                <p class="mt-4 max-w-sm font-readable leading-7 text-foreground/60">Bahasa Bali jadi seru dipelajari lewat cerita, latihan, dan permainan paribasa serta aksara.</p>
            </div>

            @foreach (['paribasa' => 'Paribasa Bali', 'aksara' => 'Aksara Bali'] as $group => $label)
                <div>
                    <h2 class="inline-flex items-center gap-2 font-display font-bold">
                        @if ($group === 'paribasa')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 text-accent" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H6l-3 2v-6.5A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 text-accent" aria-hidden="true"><path d="m4 19 6-14 5 14M6 14h7M15 19l3-7 3 7M16 17h4"/></svg>
                        @endif
                        {{ $label }}
                    </h2>
                    <ul class="mt-4 space-y-2 text-sm text-foreground/65">
                        @foreach ($learningPaths->where('group', $group) as $learningPath)
                            <li>
                                <a class="inline-flex min-h-8 items-center gap-2 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" href="{{ route('learning.paths.show', $learningPath->slug) }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0 text-accent/80" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22z"/><path d="M4 5.5v14A2.5 2.5 0 0 1 6.5 17H20"/></svg>
                                    {{ $learningPath->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div>
                <h2 class="font-display font-bold">Kontak</h2>
                <ul class="mt-4 space-y-2 text-sm text-foreground/65">
                    <li>
                        <a class="group inline-flex min-h-9 items-center gap-3 break-all transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" href="mailto:kerthajayasoftware@gmail.com">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-primary/20 text-accent">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                            </span>
                            <span>kerthajayasoftware@gmail.com</span>
                        </a>
                    </li>
                    <li>
                        <a class="group inline-flex min-h-9 items-center gap-3 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" href="tel:+6281337253221">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-primary/20 text-accent">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.2 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.74a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z"/></svg>
                            </span>
                            <span>0813 3725 3221</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-10 border-t border-white/10 pt-5 text-sm text-foreground/45">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p>© {{ now()->year }} ARTASWAHA. All rights reserved.</p>
                <a href="{{ route('privacy-policy') }}" class="rounded-full font-semibold text-foreground/70 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Kebijakan privasi</a>
            </div>
        </div>
    </div>
</footer>
