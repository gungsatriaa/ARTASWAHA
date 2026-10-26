@props(['analysis', 'compact' => false, 'poll' => true])

<section @if (! $compact) id="analisis" @endif class="min-w-0 rounded-card bg-surface p-5 sm:p-6"
    @if (! $compact && $poll && in_array($analysis['status'], ['queued', 'processing'], true)) data-learner-analysis-url="{{ route('profile.analysis.show') }}" @endif>
    <header class="flex items-center gap-3">
        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary/15 text-accent" aria-hidden="true"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5M4 19h16M8 14l4-5 4 3 4-7" /></svg></span>
        <h2 tabindex="-1" class="font-display text-xl font-bold">Analisis belajarmu</h2>
    </header>
    @if ($analysis['summary'])
        <p class="mt-4 break-words text-sm leading-7 text-foreground/75">{{ $analysis['summary'] }}</p>
    @else
        <p data-analysis-status role="status" class="mt-4 text-sm leading-7 text-foreground/65">
            @if ($analysis['status'] === 'queued')
                Analisis menunggu antrean pemrosesan. Kamu bisa melanjutkan belajar sementara menunggu.
            @elseif ($analysis['status'] === 'processing')
                Rekomendasi sedang disiapkan. Kamu tetap bisa mulai belajar dari materi pilihanmu.
            @elseif ($analysis['status'] === 'failed')
                Analisis belum berhasil. Jawabanmu tetap tersimpan dan kamu bisa melanjutkan belajar.
            @else
                Rekomendasi AI belum tersedia. Kamu tetap bisa belajar dari materi pilihanmu.
            @endif
        </p>
    @endif
    @if ($analysis['status'] === 'processing')
        <x-loading-skeleton data-analysis-skeleton class="mt-4" />
    @endif
    @if (! empty($analysis['updated_at']))
        <p class="mt-3 text-xs text-foreground/45">Terakhir diperbarui <time datetime="{{ $analysis['updated_at'] }}">{{ \Illuminate\Support\Carbon::parse($analysis['updated_at'])->timezone(config('app.timezone'))->format('d M Y, H:i') }}</time></p>
    @endif

    @if ($compact)
        <a href="{{ route('profile.show') }}#analisis" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-accent underline underline-offset-4">Lihat analisis dan peta belajarmu <span aria-hidden="true" class="ml-2">→</span></a>
    @else
        @if ($analysis['recommendations'] !== [])
            <div class="mt-5 border-t border-white/8 pt-5">
                <h3 class="font-display text-lg font-bold">Peta belajarmu</h3>
                <p class="mt-1 text-xs leading-5 text-foreground/50">Langkah berikutnya yang disarankan untukmu.</p>
                <ol class="mt-4 space-y-3" aria-label="Urutan rekomendasi belajar">
                    @foreach ($analysis['recommendations'] as $recommendation)
                        <li>
                            <a href="{{ $recommendation['url'] }}" class="group flex items-start gap-3 rounded-2xl bg-background p-4 transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-primary/15 text-xs font-bold text-accent" aria-hidden="true">{{ $loop->iteration }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block break-words text-sm font-bold leading-6 group-hover:text-accent">{{ $recommendation['title'] }}</span>
                                    <span class="mt-1 block break-words text-xs leading-6 text-foreground/55">{{ $recommendation['reason'] }}</span>
                                    <span class="mt-2 block text-xs font-bold text-accent">Mulai materi <span aria-hidden="true">→</span></span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
        <details class="mt-5 border-t border-white/8 pt-3">
            <summary class="min-h-9 cursor-pointer content-center rounded-full text-xs font-semibold text-foreground/50 focus-visible:outline-2 focus-visible:outline-accent">Tentang analisis ini</summary>
            <p class="mt-2 text-xs leading-6 text-foreground/45">Diperbarui otomatis sekitar setiap 6 jam berdasarkan progres dan jawaban kuis yang tercatat. Analisis ini bukan penilaian kemampuan. Aktivitas membaca dan durasi belajar belum tercatat.</p>
        </details>
    @endif
</section>
