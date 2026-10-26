@props(['analysis', 'compact' => false, 'poll' => true])

<section @if (! $compact) id="analisis" @endif class="mb-5 min-w-0 rounded-card border border-accent/20 bg-surface p-5 sm:p-6"
    @if (! $compact && $poll && in_array($analysis['status'], ['queued', 'processing'], true)) data-learner-analysis-url="{{ route('profile.analysis.show') }}" @endif>
    <p class="text-xs font-bold uppercase tracking-widest text-accent">Pilihanmu, langkahmu</p>
    <h2 tabindex="-1" class="mt-2 font-display text-xl font-bold">Analisis belajarmu</h2>
    @if ($analysis['summary'])
        <p class="mt-3 break-words text-sm leading-7 text-foreground/80">{{ $analysis['summary'] }}</p>
    @else
        <p data-analysis-status role="status" class="mt-3 text-sm leading-7 text-foreground/70">
            @if (in_array($analysis['status'], ['queued', 'processing'], true))
                Rekomendasi sedang disiapkan. Kamu tetap bisa mulai belajar dari materi pilihanmu.
            @elseif ($analysis['status'] === 'failed')
                Analisis belum berhasil. Jawabanmu tetap tersimpan dan kamu bisa melanjutkan belajar.
            @else
                Rekomendasi AI belum tersedia. Kamu tetap bisa belajar dari materi pilihanmu.
            @endif
        </p>
    @endif
    <p class="mt-2 text-xs leading-6 text-foreground/55">Diperbarui otomatis sekitar setiap 6 jam berdasarkan progres dan jawaban kuis yang tercatat. Analisis ini bukan penilaian kemampuan.</p>
    @if (! empty($analysis['updated_at']))
        <p class="mt-2 text-xs text-foreground/50">Terakhir diperbarui <time datetime="{{ $analysis['updated_at'] }}">{{ \Illuminate\Support\Carbon::parse($analysis['updated_at'])->timezone(config('app.timezone'))->format('d M Y, H:i') }}</time></p>
    @endif
    @if (! $compact && ! empty($analysis['activity']))
        <dl class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-background p-4"><dt class="text-xs leading-5 text-foreground/55">Hari aktif · 7 hari</dt><dd class="mt-1 font-display text-2xl font-bold">{{ $analysis['activity']['active_days_7'] }}<span class="text-sm text-foreground/45">/7</span></dd></div>
            <div class="rounded-2xl bg-background p-4"><dt class="text-xs leading-5 text-foreground/55">Pelajaran selesai · 7 hari</dt><dd class="mt-1 font-display text-2xl font-bold">{{ $analysis['activity']['lessons_completed_7'] }}</dd></div>
            <div class="rounded-2xl bg-background p-4"><dt class="text-xs leading-5 text-foreground/55">Jawaban kuis benar · 14 hari</dt><dd class="mt-1 font-display text-2xl font-bold">{{ $analysis['activity']['quiz_correct_14'] }}<span class="text-sm text-foreground/45">/{{ $analysis['activity']['quiz_answers_14'] }}</span></dd></div>
        </dl>
        <p class="mt-2 text-xs leading-5 text-foreground/50">Hari aktif dihitung saat ada pelajaran selesai atau jawaban kuis. Aktivitas membaca dan durasi belajar belum tercatat.</p>
    @endif

    @if ($compact)
        <a href="{{ route('profile.show') }}#analisis" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-accent underline underline-offset-4">Lihat analisis dan peta belajarmu <span aria-hidden="true" class="ml-2">→</span></a>
    @else
        @if ($analysis['recommendations'] !== [])
            <div class="mt-6 border-t border-white/10 pt-5">
                <h3 class="font-display text-lg font-bold">Peta belajarmu</h3>
                <p class="mt-1 text-xs leading-6 text-foreground/55">Peran → materi pilihan → langkah belajar yang disarankan.</p>
                <div class="mt-4 w-fit max-w-full break-words rounded-2xl border border-accent/40 bg-accent/10 px-4 py-3 text-sm font-bold text-accent">{{ $analysis['role'] }}</div>
                <ol class="ml-4 space-y-4 border-l-2 border-accent/25 py-4 pl-4 sm:pl-6" aria-label="Urutan rekomendasi belajar">
                    @foreach ($analysis['recommendations'] as $recommendation)
                        <li class="relative grid min-w-0 gap-3 rounded-2xl border border-white/10 bg-background p-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                            <span aria-hidden="true" class="absolute -left-4 top-6 w-4 border-t-2 border-accent/25 sm:-left-6 sm:w-6"></span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-accent">Materi pilihan · rekomendasi {{ $loop->iteration }}</p>
                                <a href="{{ $recommendation['url'] }}" class="mt-1 inline-flex min-h-11 items-center break-words font-bold underline decoration-accent/40 underline-offset-4 focus-visible:outline-2 focus-visible:outline-accent">{{ $recommendation['title'] }}</a>
                                <p class="mt-1 break-words text-sm leading-6 text-foreground/65">{{ $recommendation['reason'] }}</p>
                            </div>
                            <div class="min-w-0 rounded-xl border border-primary/25 bg-primary/10 p-4">
                                <p class="text-xs font-bold text-accent">Langkah {{ $loop->iteration }}</p>
                                <p class="mt-2 text-sm leading-6 text-foreground/75">Buka materi dan ikuti urutan pelajarannya.</p>
                                <a href="{{ $recommendation['url'] }}" class="mt-3 inline-flex min-h-11 items-center rounded-full bg-accent px-4 text-sm font-bold text-background focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Mulai materi <span aria-hidden="true" class="ml-2">→</span></a>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    @endif
</section>
