<x-layouts.app :show-footer="false" :title="$lesson->title . ' — ARTASWAHA'" :description="\Illuminate\Support\Str::limit($lesson->summary ?: 'Pelajari ' . $lesson->title . ' dalam materi ' . $lesson->learningPath->title . ' di ARTASWAHA.', 160)" :canonical="route('learning.lessons.show', $lesson->slug)">
    <div class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 sm:py-9">
        <a href="{{ route('learning.paths.show', $lesson->learningPath->slug) }}" class="mb-5 inline-flex min-h-10 items-center gap-2 rounded-full text-sm font-semibold text-foreground/65 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
            <span aria-hidden="true">←</span> Daftar pelajaran
        </a>

        <header class="rounded-card border border-white/10 bg-surface p-5 sm:p-7 lg:p-9">
            <p class="text-sm font-bold text-accent">{{ $lesson->learningPath->title }}</p>
            <h1 class="mt-2 break-words font-display text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">{{ $lesson->title }}</h1>
            @if ($lesson->summary)
                <p class="mt-3 text-sm leading-7 text-foreground/65 sm:text-base">{{ $lesson->summary }}</p>
            @endif
        </header>

        @if (session('status'))
            <x-flash-notice :tone="session('status_tone', 'success')" class="mt-5">{{ session('status') }}</x-flash-notice>
        @endif

        <article class="mt-5 rounded-card border border-white/10 bg-surface p-5 sm:p-7 lg:p-9" aria-labelledby="lesson-content-title">
            <h2 id="lesson-content-title" class="mb-4 font-display text-xl font-bold">Materi pelajaran</h2>
            <div class="whitespace-pre-line break-words font-readable text-base leading-8 text-foreground/85 sm:text-lg">{{ $lesson->content }}</div>
        </article>

        @if ($progress?->completed_at)
            <section class="mt-5 rounded-card border border-accent/25 bg-accent/10 p-5 sm:p-6" aria-label="Hasil pelajaran">
                <p class="font-bold text-accent">Pelajaran selesai · {{ $progress->points }} poin</p>
                @if ($lesson->exercise_explanation)
                    <p class="mt-3 whitespace-pre-line break-words text-sm leading-7 text-foreground/75">{{ $lesson->exercise_explanation }}</p>
                @endif
            </section>
        @elseif ($lesson->exercise_prompt)
            <section class="mt-5 rounded-card border border-white/10 bg-surface p-5 sm:p-7 lg:p-9" aria-labelledby="exercise-title">
                <p class="text-sm font-bold text-accent">Latihan singkat</p>
                <h2 id="exercise-title" class="mt-2 break-words font-display text-xl font-bold leading-snug sm:text-2xl">{{ $lesson->exercise_prompt }}</h2>
                <p class="mt-2 text-sm text-foreground/60">Pilih satu jawaban yang paling tepat.</p>
                <form method="POST" action="{{ route('learning.exercises.answer', $lesson->slug) }}" class="mt-5">
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($lesson->exercise_options as $index => $option)
                            <label class="flex min-h-16 cursor-pointer items-center gap-3 rounded-2xl border border-white/10 bg-background p-4 text-sm font-semibold transition-colors hover:border-accent/40 has-[:checked]:border-accent has-[:checked]:bg-accent/10 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-accent sm:text-base">
                                <input type="radio" name="answer_index" value="{{ $index }}" required class="size-5 shrink-0 accent-[var(--color-accent)]">
                                <span class="min-w-0 break-words">{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('answer_index') <p role="alert" class="mt-3 text-sm text-danger">{{ $message }}</p> @enderror
                    <div class="mt-5 flex justify-end border-t border-white/10 pt-4">
                        <button class="min-h-12 w-full rounded-full bg-accent px-6 font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:w-auto" type="submit">Periksa jawaban</button>
                    </div>
                </form>
            </section>
        @else
            <form method="POST" action="{{ route('learning.progress.store', $lesson->slug) }}" class="mt-5 flex justify-end">
                @csrf
                <button class="min-h-12 w-full rounded-full bg-accent px-6 font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:w-auto" type="submit">Tandai pelajaran selesai</button>
            </form>
        @endif
    </div>
</x-layouts.app>
