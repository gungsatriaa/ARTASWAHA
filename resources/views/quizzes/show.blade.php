<x-layouts.app :show-footer="false" :title="$quiz->title . ' — ARTASWAHA'">
    <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-6 sm:py-12 lg:px-10">
        <a href="{{ route('quizzes.index') }}" class="mb-6 inline-flex min-h-11 items-center gap-2 rounded-full px-4 text-sm font-semibold text-foreground/65 transition hover:bg-surface hover:text-foreground focus-visible:outline-2 focus-visible:outline-accent">
            <span aria-hidden="true">←</span> Semua kuis
        </a>

        @if (session('status'))
            <x-flash-notice class="mb-6">{{ session('status') }}</x-flash-notice>
        @endif

        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,21rem)_minmax(0,1fr)] lg:gap-10">
            <section class="rounded-card bg-surface p-6 sm:p-8 lg:sticky lg:top-28" aria-labelledby="quiz-title">
                <span class="inline-flex items-center gap-2 rounded-full bg-accent/10 px-4 py-2 text-sm font-bold text-accent">Kuis buatanmu</span>
                <h1 id="quiz-title" class="mt-5 break-words font-display text-3xl font-bold leading-tight">{{ $quiz->title }}</h1>
                <p class="mt-4 font-readable leading-7 text-foreground/65">{{ $quiz->description ?: 'Ajak teman menguji pengetahuan bahasa Bali lewat pertanyaan yang kamu buat.' }}</p>
                <div class="mt-6 flex flex-wrap items-center gap-3 text-sm font-bold">
                    <span class="rounded-full border border-white/10 px-4 py-2 text-foreground/75">{{ $quiz->questions->count() }} pertanyaan</span>
                    <span class="rounded-full border border-white/10 px-4 py-2 text-foreground/75">Bermain bersama</span>
                    <span class="rounded-full border border-white/10 px-4 py-2 text-foreground/75">{{ $quiz->show_answers_immediately ? 'Jawaban langsung' : 'Hasil di akhir' }}</span>
                </div>
                @if ($quiz->is_archived)
                    <p class="mt-8 border-t border-white/10 pt-6 text-sm leading-6 text-foreground/60">Kuis ini ada di arsip. Room dan jawaban lama tetap tersimpan. Duplikasikan untuk membuat kuis aktif tanpa mengubah riwayat.</p>
                @else
                    <div class="mt-8 space-y-3 border-t border-white/10 pt-6">
                        <a href="{{ route('quizzes.edit', $quiz) }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-full border border-white/15 px-5 py-3 font-bold transition hover:border-accent hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Edit kuis</a>
                        <a href="{{ route('quizzes.edit', ['quiz' => $quiz, 'preview' => 1]) }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-full border border-accent/30 px-5 font-bold text-accent">Preview sebagai peserta</a>
                        <form method="POST" action="{{ route('rooms.store', $quiz) }}">
                            @csrf
                            <button class="inline-flex min-h-12 w-full cursor-pointer items-center justify-center rounded-full bg-accent px-5 py-3 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Buat room kuis</button>
                        </form>
                    </div>
                @endif
                <div class="mt-4 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('quizzes.duplicate', $quiz) }}" data-single-submit>@csrf<button class="min-h-11 rounded-full border border-white/15 px-4 text-sm font-bold">Duplikasikan kuis</button></form>
                    @unless ($quiz->is_archived)<form method="POST" action="{{ route('quizzes.archive', $quiz) }}" data-single-submit data-confirm-archive>@csrf<button class="min-h-11 rounded-full px-4 text-sm text-danger">Arsipkan kuis</button></form>@endunless
                </div>
            </section>

            <section class="min-w-0" aria-labelledby="quiz-questions-title">
                <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="quiz-questions-title" class="font-display text-2xl font-bold sm:text-3xl">Daftar pertanyaan</h2>
                        <p class="mt-2 text-sm text-foreground/55">Pemain menjawab pertanyaan ini secara berurutan.</p>
                    </div>
                    <span class="text-sm font-semibold text-accent">{{ $quiz->questions->count() }} soal</span>
                </div>
                <ol class="space-y-3">
                    @foreach ($quiz->questions as $index => $question)
                        <li class="flex gap-4 rounded-card bg-surface p-5 sm:p-6">
                            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-accent/10 font-display text-lg font-bold text-accent">{{ $index + 1 }}</span>
                            <div class="min-w-0">
                                <h3 class="break-words font-display text-lg font-semibold leading-snug">{{ $question->prompt }}</h3>
                                @if (isset($audioByQuestion[$question->id]))
                                    <div class="mt-3"><x-content-audio-player :src="route('content-audio.show', $audioByQuestion[$question->id])" label="Dengarkan soal" /></div>
                                @endif
                                @if (auth()->user()?->is_admin)
                                    <a href="{{ route('admin.audio.questions.edit', $question) }}" class="mt-2 inline-flex min-h-11 items-center text-xs font-bold text-accent">Siapkan audio soal →</a>
                                @endif
                                <p class="mt-2 text-sm text-foreground/50">{{ count($question->options) }} pilihan jawaban</p>
                                <p class="mt-2 text-xs font-bold text-accent">Keyboard: {{ $question->keyboard_auto_open ? 'terbuka otomatis' : 'dibuka peserta' }}</p>
                                @if ($question->audio)<div class="mt-3"><span class="mr-2 text-xs text-foreground/60">Audio</span><x-job-status :status="$question->audio->source_hash === app(\App\Services\ContentAudioService::class)->sourceHash($question) ? $question->audio->status : 'stale'" /></div>@else<p class="mt-2 text-xs text-foreground/55">Belum ada audio soal.</p>@endif
                                <details class="mt-3"><summary class="inline-flex min-h-11 cursor-pointer items-center text-sm font-bold">Lihat pilihan dan kunci jawaban</summary><ol class="mt-2 space-y-2 text-sm">@foreach ($question->options as $optionIndex => $option)<li class="break-words">{{ chr(65 + $optionIndex) }}. {{ $option }} @if ($optionIndex === $question->correct_option)<span class="font-bold text-success">· jawaban benar</span>@endif</li>@endforeach</ol>@if ($question->explanation)<p class="mt-3 whitespace-pre-wrap text-sm text-foreground/60">{{ $question->explanation }}</p>@endif</details>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</x-layouts.app>
