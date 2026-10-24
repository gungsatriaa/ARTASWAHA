<x-layouts.app :show-footer="false" title="Room {{ $room->code }} — ARTASWAHA">
    @php
        $isHost = auth()->id() === $room->host_id;
        $questionCount = $room->quiz->questions->count();
        $questionPosition = $activeQuestion
            ? $room->quiz->questions->search(fn ($question) => $question->id === $activeQuestion->id) + 1
            : 0;
    @endphp

    <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-6 sm:py-12 lg:px-10" data-room-code="{{ $room->code }}" data-room-status-url="{{ route('rooms.status', $room->code) }}" @if ($room->question_ends_at && $room->status === 'active') data-question-ends-at="{{ $room->question_ends_at->toIso8601String() }}" data-server-now="{{ now()->toIso8601String() }}" @endif>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('quizzes.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 text-sm font-semibold text-foreground/65 transition hover:bg-surface hover:text-foreground focus-visible:outline-2 focus-visible:outline-accent"><span aria-hidden="true">←</span> Kembali ke kuis</a>
            @if ($isHost)
                <button type="button" data-room-delete-open class="min-h-11 rounded-full border border-danger/35 px-5 text-sm font-bold text-danger transition hover:bg-danger/10 focus-visible:outline-2 focus-visible:outline-danger">Hapus room</button>
            @else
                <form method="POST" action="{{ route('rooms.leave', $room->code) }}">
                    @csrf
                    <button class="min-h-11 rounded-full border border-white/15 px-5 text-sm font-bold text-foreground/75 transition hover:bg-surface focus-visible:outline-2 focus-visible:outline-accent">Keluar dari room</button>
                </form>
            @endif
        </div>

        @if ($errors->any())
            <p role="alert" class="mb-6 rounded-2xl border border-danger/20 bg-danger/10 p-4 text-danger">{{ $errors->first() }}</p>
        @endif

        @if ($room->status === 'active')
            <section class="flex flex-wrap items-center justify-between gap-5 rounded-[1.75rem] border border-white/10 bg-surface px-6 py-5 sm:px-8" aria-labelledby="room-title">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-accent">Kuis berlangsung</p>
                    <h1 id="room-title" class="mt-1 break-words font-display text-2xl font-bold sm:text-3xl">{{ $room->quiz->title }}</h1>
                    <p data-live-status role="status" class="mt-2 text-xs text-foreground/45">Memeriksa koneksi room…</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full border border-accent/30 bg-accent/10 px-4 py-2 font-display text-lg font-bold tracking-widest text-accent" aria-label="Kode room {{ $room->code }}">{{ $room->code }}</span>
                    <button type="button" data-copy-room="{{ route('quizzes.index', ['code' => $room->code]) }}" class="min-h-11 rounded-full border border-white/15 px-4 text-sm font-bold text-foreground transition hover:border-accent/60 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Salin undangan</button>
                    <p role="status" data-copy-status class="w-full text-xs font-semibold text-foreground/55"></p>
                </div>
            </section>
        @else
            <section class="overflow-hidden rounded-[2rem] border border-white/10 bg-surface lg:grid lg:grid-cols-[minmax(0,1fr)_20rem]" aria-labelledby="room-title">
                <div class="p-6 sm:p-8 lg:p-10">
                    <span class="inline-flex items-center gap-2 rounded-full bg-primary/20 px-4 py-2 text-sm font-bold text-accent">
                        <span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>
                        {{ $room->status === 'waiting' ? 'Menunggu pemain' : 'Kuis selesai' }}
                    </span>
                    <p class="mt-7 text-sm font-bold text-foreground/55">{{ $room->quiz->title }}</p>
                    <h1 id="room-title" class="mt-2 font-display text-3xl font-bold sm:text-4xl">{{ $room->status === 'waiting' ? 'Siap belajar bareng?' : 'Permainan selesai!' }}</h1>
                    <p class="mt-4 max-w-xl font-readable leading-7 text-foreground/65">{{ $room->status === 'waiting' ? 'Bagikan kode ke temanmu. Host bisa memulai kapan saja setelah semua siap.' : 'Terima kasih sudah bermain bersama. Sampai jumpa di kuis berikutnya!' }}</p>
                    <p data-live-status role="status" class="mt-5 min-h-5 text-xs text-foreground/45">Memeriksa koneksi room…</p>
                </div>
                <div class="flex flex-col justify-between bg-accent p-6 text-background sm:p-8">
                    <div>
                        <p class="text-sm font-bold text-background/70">Kode room</p>
                        <p class="mt-2 break-all font-display text-5xl font-extrabold tracking-[0.12em] sm:text-6xl" aria-label="Kode room {{ $room->code }}">{{ $room->code }}</p>
                        <p class="mt-4 text-sm leading-6 text-background/70">Temanmu dapat memasukkan kode ini di halaman kuis.</p>
                    </div>
                    <div class="mt-7">
                        <button type="button" data-copy-room="{{ route('quizzes.index', ['code' => $room->code]) }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-full bg-background px-5 py-3 text-sm font-extrabold text-foreground transition hover:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-background">Salin tautan undangan</button>
                        <p role="status" data-copy-status class="mt-2 min-h-5 text-xs font-semibold text-background/70"></p>
                    </div>
                </div>
            </section>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_19rem]">
            <div>
                @if ($room->status === 'waiting')
                    <section class="rounded-[1.75rem] border border-white/10 bg-surface p-6 sm:p-8" aria-labelledby="participants-title">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 id="participants-title" class="font-display text-2xl font-bold">Pemain bergabung</h2>
                            <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-bold text-accent">{{ $room->participants->count() }} pemain</span>
                        </div>
                        <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                            @foreach ($room->participants as $roomParticipant)
                                <li data-room-presence-user="{{ $roomParticipant->user_id }}" class="flex items-center gap-3 rounded-2xl border border-white/10 bg-background p-3">
                                    <img src="{{ str_starts_with($roomParticipant->user->avatar ?? '', 'avatars/') ? route('rooms.participant-avatar', [$room->code, $roomParticipant->user_id]) : asset($roomParticipant->user->avatar ?? array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS)) }}" alt="" class="size-10 shrink-0 rounded-full object-cover ring-2 ring-primary/40">
                                    <span class="min-w-0 truncate text-sm font-bold">{{ $roomParticipant->user->name }}</span>
                                    @if ($roomParticipant->user_id === $room->host_id)<span class="ml-auto rounded-full bg-accent/10 px-2.5 py-1 text-xs font-bold text-accent">Host</span>@endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($isHost)
                            <form method="POST" action="{{ route('rooms.start', $room->code) }}" class="mt-8">
                                @csrf
                                <button class="inline-flex min-h-12 w-full items-center justify-center rounded-full bg-primary px-7 py-3 font-extrabold text-foreground transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent sm:w-auto">Mulai kuis</button>
                            </form>
                        @else
                            <p class="mt-7 text-sm text-foreground/55">Host akan memulai kuis saat semua pemain siap.</p>
                        @endif
                    </section>
                @elseif ($room->status === 'active' && $activeQuestion)
                    <section class="rounded-[1.75rem] border border-white/10 bg-surface p-5 sm:p-8" aria-labelledby="question-title">
                        <div class="flex items-center justify-between gap-4">
                            <span class="font-display text-lg font-bold">Soal {{ $questionPosition }} <span class="text-foreground/50">dari {{ $questionCount }}</span></span>
                            <span class="grid size-12 shrink-0 place-items-center rounded-full border-[3px] border-accent/70 bg-accent/10 font-display text-sm font-bold text-accent" aria-hidden="true">{{ $questionPosition }}/{{ $questionCount }}</span>
                        </div>
                        <div role="progressbar" aria-label="Progres kuis" aria-valuenow="{{ $questionPosition }}" aria-valuemin="0" aria-valuemax="{{ $questionCount }}" class="mt-5 h-2.5 overflow-hidden rounded-full bg-background">
                            <div class="h-full rounded-full bg-accent" style="width: {{ $questionCount ? round($questionPosition / $questionCount * 100) : 0 }}%"></div>
                        </div>

                        <div class="mt-7 flex min-h-44 items-center justify-center rounded-[1.5rem] border border-primary/35 bg-primary/15 px-5 py-10 text-center sm:min-h-52 sm:px-10">
                            <h2 id="question-title" class="max-w-2xl break-words font-display text-2xl font-bold leading-snug sm:text-3xl">{{ $activeQuestion->prompt }}</h2>
                        </div>

                        <div class="mt-7 flex items-center gap-3 text-sm font-semibold">
                            @if ($room->question_ends_at)
                                <span class="shrink-0 text-foreground/65">Sisa waktu</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-background" role="progressbar" aria-label="Sisa waktu soal" aria-valuemin="0" aria-valuemax="{{ $room->seconds_per_question }}" data-room-time-progress>
                                    <div data-room-time-bar class="h-full rounded-full bg-primary transition-[width] duration-700"></div>
                                </div>
                                <span class="min-w-14 text-right font-display text-base font-bold text-accent" role="timer" aria-live="off" data-room-countdown>—</span>
                            @else
                                <span class="text-foreground/65">Tanpa batas waktu. Kamu langsung lanjut setelah menjawab.</span>
                            @endif
                        </div>

                        @if ($currentAnswer)
                            <p role="status" class="mt-8 rounded-2xl border border-primary/40 bg-primary/20 p-5 font-semibold text-foreground">{{ $currentAnswer->is_correct ? 'Jawaban benar! Kamu mendapat '.$currentAnswer->points_awarded.' poin.' : 'Jawaban tersimpan. Tetap semangat!' }} Soal berikutnya muncul saat waktu habis.</p>
                        @else
                            <form method="POST" action="{{ route('rooms.answer', $room->code) }}" class="mt-8">
                                @csrf
                                <input type="hidden" name="question_id" value="{{ $activeQuestion->id }}">
                                <fieldset>
                                    <legend class="mb-4 text-sm font-semibold text-foreground/65">Pilih satu jawaban</legend>
                                    <div class="grid gap-3">
                                        @foreach ($activeQuestion->options as $index => $option)
                                            <label data-quiz-option class="flex min-h-16 cursor-pointer items-center gap-4 rounded-2xl border-2 border-white/10 bg-background px-4 py-3 transition-colors hover:border-accent/60 sm:min-h-18">
                                                <input required type="radio" name="answer_index" value="{{ $index }}" class="sr-only">
                                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-surface font-display text-base font-bold text-accent">{{ chr(65 + $index) }}</span>
                                                <span class="min-w-0 flex-1 break-words font-semibold">{{ $option }}</span>
                                                <span data-quiz-option-indicator class="size-5 shrink-0 rounded-full border-2 border-foreground/55" aria-hidden="true"></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                <button class="mt-7 inline-flex min-h-14 w-full items-center justify-center rounded-full bg-accent px-7 py-3 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" type="submit">Kirim jawaban</button>
                            </form>
                        @endif
                    </section>
                @else
                    <section class="rounded-[1.75rem] border border-accent/30 bg-accent/10 p-8" aria-labelledby="finished-title">
                        <span class="text-4xl text-accent" aria-hidden="true">✦</span>
                        <h2 id="finished-title" class="mt-4 font-display text-3xl font-bold">{{ $room->status === 'finished' ? 'Kuis selesai!' : 'Kamu sudah menjawab semua soal!' }}</h2>
                        <p class="mt-3 text-foreground/70">{{ $room->status === 'finished' ? 'Lihat peringkat akhir, lalu pilih kuis lain untuk dimainkan bersama.' : 'Pemain lain masih menjawab. Peringkat akan diperbarui otomatis.' }}</p>
                        <a href="{{ route('quizzes.index') }}" class="mt-6 inline-flex min-h-11 items-center rounded-full bg-accent px-5 font-bold text-background">Lihat kuis lain</a>
                    </section>
                @endif
            </div>

            @if ($room->leaderboard_enabled)
                <section class="h-fit rounded-[1.75rem] border border-white/10 bg-surface p-6" aria-labelledby="leaderboard-title">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-accent/10 text-xl text-accent" aria-hidden="true">★</span>
                        <h2 id="leaderboard-title" class="font-display text-xl font-bold">Peringkat</h2>
                    </div>
                    <ol class="mt-5 space-y-2">
                        @foreach ($room->participants as $rank => $roomParticipant)
                            <li data-room-presence-user="{{ $roomParticipant->user_id }}" data-room-leaderboard-user class="flex items-center justify-between gap-3 rounded-xl bg-background px-4 py-3 text-sm">
                                <span class="min-w-0 truncate"><span data-room-rank class="mr-2 font-bold text-accent">{{ $rank + 1 }}.</span>{{ $roomParticipant->user->name }}</span>
                                <strong class="shrink-0 text-accent">{{ $roomParticipant->score }} poin</strong>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @else
                <aside class="h-fit rounded-[1.75rem] border border-white/10 bg-surface p-6">
                    <h2 class="font-display text-xl font-bold">Bermain tanpa peringkat</h2>
                    <p class="mt-3 text-sm leading-6 text-foreground/55">Fokus pada pertanyaan dan serunya belajar bersama.</p>
                </aside>
            @endif
        </div>
    </div>

    @if ($isHost)
        <dialog data-room-delete-confirm aria-labelledby="room-delete-title" class="fixed inset-0 m-auto w-[min(28rem,calc(100vw-2rem))] rounded-[1.75rem] border border-white/15 bg-surface p-6 text-foreground shadow-2xl backdrop:bg-black/80 sm:p-8">
            <h2 id="room-delete-title" class="font-display text-2xl font-bold">Hapus room {{ $room->code }}?</h2>
            <p class="mt-3 text-sm leading-6 text-foreground/65">Semua pemain akan dikeluarkan dan jawaban di room ini dihapus. Kuis aslinya tetap tersimpan.</p>
            <div class="mt-7 flex flex-wrap justify-end gap-3">
                <button type="button" data-room-delete-cancel class="min-h-11 rounded-full border border-white/20 px-5 text-sm font-bold">Batal</button>
                <form method="POST" action="{{ route('rooms.destroy', $room->code) }}">
                    @csrf
                    @method('DELETE')
                    <button class="min-h-11 rounded-full bg-danger px-5 text-sm font-extrabold text-background">Ya, hapus room</button>
                </form>
            </div>
        </dialog>
    @endif

    <dialog data-room-deleted-dialog aria-labelledby="room-deleted-title" class="fixed inset-0 m-auto w-[min(28rem,calc(100vw-2rem))] rounded-[1.75rem] border border-accent/30 bg-surface p-6 text-foreground shadow-2xl backdrop:bg-black/85 sm:p-8">
        <span class="grid size-12 place-items-center rounded-2xl bg-accent/10 text-2xl text-accent" aria-hidden="true">!</span>
        <h2 id="room-deleted-title" class="mt-5 font-display text-2xl font-bold">Room dihapus oleh host</h2>
        <p class="mt-3 text-sm leading-6 text-foreground/65">Kamu sudah terputus dari room. Kuis lainnya masih bisa dimainkan.</p>
        <a href="{{ route('quizzes.index') }}" class="mt-7 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-accent px-5 font-extrabold text-background">Kembali ke kuis</a>
    </dialog>
</x-layouts.app>
