<x-layouts.app :show-footer="false" title="Kuis — ARTASWAHA">
    <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-6 sm:py-12 lg:px-10">
        <header class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <p class="inline-flex items-center gap-2 rounded-full bg-accent/10 px-3 py-1.5 text-sm font-bold text-accent">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m13 2-3 8h7l-6 12 2-9H6l7-11Z"/></svg>
                    Arena kuis Bali
                </p>
                <h1 class="mt-4 font-display text-4xl font-bold leading-tight tracking-tight sm:text-5xl">Belajar lebih seru kalau dimainkan.</h1>
                <p class="mt-3 max-w-xl font-readable leading-7 text-foreground/65">Pilih kuis, tantang teman, dan kenali bahasa Bali sambil bermain.</p>
            </div>
            <a href="{{ route('quizzes.create') }}" class="inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-full bg-accent px-6 py-3 font-extrabold text-background transition hover:-translate-y-0.5 hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Buat kuis
            </a>
        </header>

        @if (session('status'))
            <x-flash-notice class="mb-6">{{ session('status') }}</x-flash-notice>
        @endif
        @if ($errors->any() && ! $errors->has('room_started') && ! $errors->has('room_finished'))
            <p role="alert" class="mb-6 rounded-2xl border border-danger/20 bg-danger/10 p-4 text-danger">{{ $errors->first() }}</p>
        @endif

        @if ($errors->has('room_started') || $errors->has('room_finished'))
            <dialog data-room-join-closed-dialog aria-labelledby="room-join-closed-title" aria-describedby="room-join-closed-description" class="fixed inset-0 m-auto w-[min(28rem,calc(100vw-2rem))] rounded-[1.75rem] border border-accent/30 bg-surface p-6 text-foreground shadow-2xl backdrop:bg-black/85 sm:p-8">
                <span class="grid size-12 place-items-center rounded-2xl bg-accent/10 text-2xl text-accent" aria-hidden="true">!</span>
                <h2 id="room-join-closed-title" class="mt-5 font-display text-2xl font-bold">{{ $errors->has('room_started') ? 'Kuis sudah dimulai' : 'Kuis sudah selesai' }}</h2>
                <p id="room-join-closed-description" class="mt-3 text-sm leading-6 text-foreground/65">{{ $errors->first('room_started') ?: $errors->first('room_finished') }} Pilih room lain yang masih menunggu peserta.</p>
                <button type="button" data-room-join-closed-close class="mt-7 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-accent px-5 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Mengerti</button>
            </dialog>
        @endif

        <section aria-labelledby="join-room-title" class="rounded-[2rem] border border-white/10 bg-surface p-6 sm:p-8 lg:p-10">
            <div class="max-w-3xl">
                <p class="text-sm font-bold text-accent">Sudah ada yang mengajak?</p>
                <h2 id="join-room-title" class="mt-1 font-display text-2xl font-bold sm:text-3xl">Masukkan kode room</h2>
                <p class="mt-2 max-w-xl text-sm leading-6 text-foreground/60">Gabung ke permainan temanmu. Kode baru terdiri dari 4 huruf atau angka.</p>
                <form method="POST" action="{{ route('rooms.join') }}" class="mt-6 flex flex-col gap-3 sm:flex-row">
                    @csrf
                    <label for="room-code" class="sr-only">Kode room</label>
                    <input id="room-code" name="code" value="{{ old('code', request('code')) }}" required minlength="4" maxlength="10" pattern="[A-Za-z0-9]{4}([A-Za-z0-9]{6})?" autocomplete="off" autocapitalize="characters" placeholder="AB12" class="min-h-14 min-w-0 flex-1 rounded-2xl border border-white/15 bg-background px-5 font-bold uppercase tracking-[0.22em] text-foreground placeholder:text-foreground/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                    <button class="inline-flex min-h-14 items-center justify-center gap-2 rounded-full bg-accent px-7 py-4 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent" type="submit">
                        Gabung room
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                    </button>
                </form>
            </div>
        </section>

        <section class="mt-12 sm:mt-16" aria-labelledby="quiz-list-title">
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-accent">Koleksimu</p>
                    <h2 id="quiz-list-title" class="mt-1 font-display text-2xl font-bold sm:text-3xl">Kuis buatanmu</h2>
                </div>
                <span class="rounded-full bg-surface px-3 py-1.5 text-sm font-semibold text-foreground/65">{{ $quizzes->total() }} kuis</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($quizzes as $quiz)
                    <a href="{{ route('quizzes.show', $quiz) }}" class="group flex min-h-64 flex-col rounded-[1.75rem] border border-white/8 bg-surface p-5 transition duration-150 hover:-translate-y-1 hover:border-accent/35 hover:bg-surface/80 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <span class="grid size-12 place-items-center rounded-2xl bg-primary/20 text-accent transition-colors group-hover:bg-accent group-hover:text-background" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-6"><path d="M5 4.5A2.5 2.5 0 0 1 7.5 2H20v17H7.5A2.5 2.5 0 0 0 5 21.5z"/><path d="M5 4.5v17M9 7h7M9 11h7"/></svg>
                            </span>
                            <span class="rounded-full bg-background px-3 py-1 text-xs font-bold text-foreground/55">Kuis pribadi</span>
                        </div>
                        <h3 class="mt-5 line-clamp-2 font-display text-xl font-bold leading-snug transition-colors group-hover:text-accent">{{ $quiz->title }}</h3>
                        <p class="mt-2 line-clamp-2 flex-1 font-readable text-sm leading-6 text-foreground/60">{{ $quiz->description ?: 'Siap dimainkan sendiri atau bersama teman.' }}</p>
                        <div class="mt-5 flex items-center justify-between gap-3 border-t border-white/10 pt-4">
                            <span class="inline-flex items-center gap-2 text-sm font-semibold text-foreground/60">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 text-accent" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                                {{ $quiz->questions_count }} soal
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-sm font-extrabold text-accent">Buka kuis <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 transition-transform group-hover:translate-x-1" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg></span>
                        </div>
                    </a>
                @empty
                    <div class="flex min-h-64 flex-col items-start justify-center rounded-[1.75rem] border border-dashed border-white/15 bg-surface p-6 sm:p-8">
                        <span class="grid size-12 place-items-center rounded-2xl bg-accent/10 text-accent" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-6"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                        </span>
                        <h3 class="mt-4 font-display text-xl font-bold">Koleksimu masih kosong</h3>
                        <p class="mt-2 max-w-sm text-sm leading-6 text-foreground/60">Buat kuis pertamamu, tambahkan soal, lalu ajak teman bermain.</p>
                        <a href="{{ route('quizzes.create') }}" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-full bg-accent px-5 py-2.5 text-sm font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Buat kuis pertama</a>
                    </div>
                @endforelse
            </div>
            <div class="mt-6">{{ $quizzes->links() }}</div>
        </section>
    </div>
</x-layouts.app>
