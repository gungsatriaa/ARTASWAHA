<x-layouts.app :show-footer="false" :title="$lesson->title . ' — ARTASWAHA'">
        <div class="mx-auto w-full max-w-3xl px-5 py-8 sm:px-6 sm:py-12 lg:py-16">
            <a href="{{ route('learning.paths.show', $lesson->learningPath->slug) }}" class="mb-8 inline-flex min-h-10 items-center rounded-full px-4 text-sm font-semibold text-foreground/70 transition hover:bg-surface hover:text-accent">← Daftar pelajaran</a>
            <p class="font-semibold text-accent">{{ $lesson->learningPath->title }}</p>
            <h1 class="mt-2 break-words font-display text-3xl font-bold sm:text-5xl">{{ $lesson->title }}</h1>
            <p class="mt-4 font-readable text-lg leading-8 text-foreground/70">{{ $lesson->summary }}</p>

            @if (session('status'))
                <p role="status" class="mt-8 rounded-2xl bg-primary/20 p-4 font-semibold text-accent">{{ session('status') }}</p>
            @endif

            <article class="mt-8 rounded-card bg-surface p-6 font-readable text-lg leading-8 sm:p-8">
                {{ $lesson->content }}
            </article>

            @if ($progress?->completed_at)
                <p class="mt-6 rounded-2xl bg-surface px-5 py-4 font-semibold text-accent">Pelajaran selesai · {{ $progress->points }} poin</p>
                @if ($lesson->exercise_explanation)
                    <p class="mt-4 rounded-2xl bg-primary/15 p-5 font-readable leading-7 text-foreground/80">{{ $lesson->exercise_explanation }}</p>
                @endif
            @elseif ($lesson->exercise_prompt)
                <section class="mt-8 rounded-card bg-surface p-6 sm:p-8" aria-labelledby="exercise-title">
                    <p class="text-sm font-semibold text-accent">Latihan singkat</p>
                    <h2 id="exercise-title" class="mt-2 font-display text-2xl font-bold">{{ $lesson->exercise_prompt }}</h2>
                    <form method="POST" action="{{ route('learning.exercises.answer', $lesson->slug) }}" class="mt-6 space-y-3">
                        @csrf
                        @foreach ($lesson->exercise_options as $index => $option)
                            <label class="flex cursor-pointer items-center gap-3 rounded-2xl bg-background p-4 transition hover:bg-white/5">
                                <input type="radio" name="answer_index" value="{{ $index }}" required class="size-5 accent-[var(--color-accent)]">
                                <span>{{ $option }}</span>
                            </label>
                        @endforeach
                        @error('answer_index') <span role="alert" class="block text-danger">{{ $message }}</span> @enderror
                        <button class="min-h-12 w-full rounded-full bg-primary px-7 py-3 font-bold text-white transition hover:brightness-110 sm:w-auto" type="submit">Periksa jawaban</button>
                    </form>
                </section>
            @else
                <form method="POST" action="{{ route('learning.progress.store', $lesson->slug) }}" class="mt-8">
                    @csrf
                    <button class="min-h-14 w-full rounded-full bg-primary px-7 py-4 font-bold text-white transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" type="submit">Tandai pelajaran selesai</button>
                </form>
            @endif
        </div>
</x-layouts.app>
