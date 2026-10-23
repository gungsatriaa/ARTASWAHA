<x-layouts.app :show-footer="false" title="Kuis — ARTASWAHA">
        <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-6 sm:py-12">
            <div class="mb-6 flex justify-end"><a href="{{ route('quizzes.create') }}" class="inline-flex min-h-11 items-center rounded-full bg-accent px-5 py-3 text-sm font-extrabold text-background">Buat kuis</a></div>
            <h1 class="font-display text-4xl font-bold">Kuis bersama</h1>
            <p class="mt-3 font-readable text-foreground/65">Buat kuis sendiri atau gunakan kode untuk bergabung ke room.</p>

            @if (session('status'))<p role="status" class="mt-6 rounded-2xl bg-primary/20 p-4 text-accent">{{ session('status') }}</p>@endif
            @if ($errors->any())<p role="alert" class="mt-6 rounded-2xl bg-danger/10 p-4 text-danger">{{ $errors->first() }}</p>@endif

            <form method="POST" action="{{ route('rooms.join') }}" class="mt-8 flex flex-col gap-3 rounded-card bg-surface p-5 sm:flex-row sm:items-end">
                @csrf
                <label class="flex-1 text-sm font-semibold">Kode room
                    <input name="code" value="{{ old('code', request('code')) }}" required minlength="10" maxlength="10" pattern="[A-Za-z0-9]{10}" autocomplete="off" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 uppercase tracking-widest outline-none focus:border-accent">
                </label>
                <button class="min-h-12 rounded-full bg-primary px-7 py-3 font-bold text-white" type="submit">Gabung room</button>
            </form>

            <section class="mt-12" aria-labelledby="quiz-list-title">
                <h2 id="quiz-list-title" class="font-display text-2xl font-bold">Kuis milikmu dan kuis publik</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @forelse ($quizzes as $quiz)
                        <a href="{{ route('quizzes.show', $quiz) }}" class="rounded-card bg-surface p-6 transition hover:bg-surface/80">
                            <span class="text-sm font-semibold text-accent">Kuis pribadi</span>
                            <h3 class="mt-2 font-display text-xl font-bold">{{ $quiz->title }}</h3>
                            <p class="mt-2 font-readable text-sm text-foreground/65">{{ $quiz->description ?: 'Belum ada deskripsi.' }}</p>
                            <p class="mt-4 text-sm text-foreground/50">{{ $quiz->questions_count }} pertanyaan</p>
                        </a>
                    @empty
                        <p class="rounded-card bg-surface p-6 font-readable text-foreground/65">Belum ada kuis. Buat kuis pertamamu untuk mulai bermain bersama.</p>
                    @endforelse
                </div>
                <div class="mt-6">{{ $quizzes->links() }}</div>
            </section>
        </div>
</x-layouts.app>
