<x-layouts.app :show-footer="false" :title="$quiz->title . ' — ARTASWAHA'">
    <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-6 sm:py-12 lg:px-10">
        <a href="{{ route('quizzes.index') }}" class="mb-6 inline-flex min-h-11 items-center gap-2 rounded-full px-4 text-sm font-semibold text-foreground/65 transition hover:bg-surface hover:text-foreground focus-visible:outline-2 focus-visible:outline-accent">
            <span aria-hidden="true">←</span> Semua kuis
        </a>

        @if (session('status'))
            <p role="status" class="mb-6 rounded-2xl border border-primary/30 bg-primary/15 p-4 text-foreground">{{ session('status') }}</p>
        @endif

        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,21rem)_minmax(0,1fr)] lg:gap-10">
            <section class="rounded-[1.75rem] border border-white/10 bg-surface p-6 sm:p-8" aria-labelledby="quiz-title">
                <span class="inline-flex items-center gap-2 rounded-full bg-accent/10 px-4 py-2 text-sm font-bold text-accent">Kuis buatanmu</span>
                <h1 id="quiz-title" class="mt-5 break-words font-display text-3xl font-bold leading-tight">{{ $quiz->title }}</h1>
                <p class="mt-4 font-readable leading-7 text-foreground/65">{{ $quiz->description ?: 'Ajak teman menguji pengetahuan bahasa Bali lewat pertanyaan yang kamu buat.' }}</p>
                <div class="mt-6 flex flex-wrap items-center gap-3 text-sm font-bold">
                    <span class="rounded-full border border-white/10 px-4 py-2 text-foreground/75">{{ $quiz->questions->count() }} pertanyaan</span>
                    <span class="rounded-full border border-white/10 px-4 py-2 text-foreground/75">Bermain bersama</span>
                </div>
                <form method="POST" action="{{ route('rooms.store', $quiz) }}" class="mt-8 border-t border-white/10 pt-6">
                    @csrf
                    <button class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl bg-accent px-5 py-3 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Buat room kuis</button>
                </form>
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
                        <li class="flex gap-4 rounded-[1.5rem] border border-white/10 bg-surface p-5 sm:p-6">
                            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-accent/10 font-display text-lg font-bold text-accent">{{ $index + 1 }}</span>
                            <div class="min-w-0">
                                <h3 class="break-words font-display text-lg font-semibold leading-snug">{{ $question->prompt }}</h3>
                                <p class="mt-2 text-sm text-foreground/50">{{ count($question->options) }} pilihan jawaban</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</x-layouts.app>
