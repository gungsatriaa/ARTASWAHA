<x-layouts.app :show-footer="false" :title="$quiz->title . ' — ARTASWAHA'">
        <div class="mx-auto w-full max-w-4xl px-5 py-8 sm:px-6 sm:py-10">
            <a href="{{ route('quizzes.index') }}" class="mb-8 inline-flex min-h-10 items-center rounded-full px-4 text-sm font-semibold text-foreground/70 transition hover:bg-surface hover:text-accent">← Kembali ke kuis</a>
            @if (session('status'))<p role="status" class="mb-6 rounded-2xl bg-primary/20 p-4 text-accent">{{ session('status') }}</p>@endif
            <p class="text-sm font-semibold text-accent">Kuis pribadi</p>
            <h1 class="mt-2 break-words font-display text-3xl font-bold sm:text-4xl">{{ $quiz->title }}</h1>
            @if ($quiz->description)<p class="mt-3 font-readable text-foreground/65">{{ $quiz->description }}</p>@endif
            <p class="mt-5 text-sm text-foreground/50">{{ $quiz->questions->count() }} pertanyaan</p>
            <form method="POST" action="{{ route('rooms.store', $quiz) }}" class="mt-8">
                @csrf
                <button class="min-h-12 rounded-full bg-primary px-7 py-3 font-bold text-white">Buat room dari kuis ini</button>
            </form>
            <ol class="mt-10 space-y-4">
                @foreach ($quiz->questions as $index => $question)
                    <li class="rounded-card bg-surface p-5"><span class="text-sm font-semibold text-accent">Pertanyaan {{ $index + 1 }}</span><p class="mt-2 font-semibold">{{ $question->prompt }}</p></li>
                @endforeach
            </ol>
        </div>
</x-layouts.app>
