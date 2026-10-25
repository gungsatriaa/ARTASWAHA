<x-layouts.app :show-footer="false" title="Room {{ $room->code }} — ARTASWAHA">
    @php
        $isHost = auth()->id() === $room->host_id;
        $questionCount = $room->quiz->questions->count();
        $questionPosition = $activeQuestion
            ? $room->quiz->questions->search(fn ($question) => $question->id === $activeQuestion->id) + 1
            : 0;
    @endphp

    <div class="mx-auto w-full max-w-6xl px-5 sm:px-6 lg:px-10 {{ $room->status === 'active' || $isFeedback ? 'py-4 sm:py-6' : 'py-8 sm:py-12' }}" data-room-code="{{ $room->code }}" data-room-status="{{ $room->status }}" data-room-participants="{{ $room->participants->count() }}" @if (auth()->guest()) data-room-anonymous @endif data-room-status-url="{{ route('rooms.status', $room->code) }}" @if ($isHost && in_array($room->status, ['waiting', 'active'], true)) data-room-heartbeat-url="{{ route('rooms.heartbeat', $room->code) }}" @endif @if ($isFeedback) data-room-feedback-next="{{ route('rooms.show', $room->code) }}" @endif @if ($participant?->question_ends_at && $room->status === 'active' && ! $isFeedback) data-question-ends-at="{{ $participant->question_ends_at->toIso8601String() }}" data-server-now="{{ now()->toIso8601String() }}" @endif>
        <div class="flex flex-wrap items-center justify-between gap-3 {{ $room->status === 'active' ? 'mb-4' : 'mb-6' }}">
            @if (in_array($room->status, ['waiting', 'active'], true))
                <form method="POST" action="{{ route('rooms.leave', $room->code) }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 text-sm font-semibold text-foreground/65 transition hover:bg-surface hover:text-foreground focus-visible:outline-2 focus-visible:outline-accent"><span aria-hidden="true">←</span> {{ $isHost ? 'Keluar & nonaktifkan room' : 'Keluar dari room' }}</button>
                </form>
            @else
                <a href="{{ route('quizzes.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-full px-4 text-sm font-semibold text-foreground/65 transition hover:bg-surface hover:text-foreground focus-visible:outline-2 focus-visible:outline-accent"><span aria-hidden="true">←</span> Kembali ke kuis</a>
            @endif
            @if ($isHost)
                <button type="button" data-room-delete-open class="min-h-11 rounded-full border border-danger/35 px-5 text-sm font-bold text-danger transition hover:bg-danger/10 focus-visible:outline-2 focus-visible:outline-danger">Hapus room</button>
            @endif
        </div>

        @if ($errors->any())
            <p role="alert" class="mb-6 rounded-2xl border border-danger/20 bg-danger/10 p-4 text-danger">{{ $errors->first() }}</p>
        @endif

        @if ($room->status === 'active' || $isFeedback)
            <h1 class="sr-only">{{ $room->quiz->title }}</h1>
            <p data-live-status role="status" class="sr-only">Memeriksa koneksi room…</p>
        @elseif ($room->status === 'waiting')
            <section class="overflow-hidden rounded-[2rem] border border-white/10 bg-surface lg:grid lg:grid-cols-[minmax(0,1fr)_20rem]" aria-labelledby="room-title">
                <div class="p-6 sm:p-8 lg:p-10">
                    <span class="inline-flex items-center gap-2 rounded-full bg-primary/20 px-4 py-2 text-sm font-bold text-accent">
                        <span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>
                        Menunggu pemain
                    </span>
                    <p class="mt-7 text-sm font-bold text-foreground/55">{{ $room->quiz->title }}</p>
                    <h1 id="room-title" class="mt-2 font-display text-3xl font-bold sm:text-4xl">Siap belajar bareng?</h1>
                    <p class="mt-4 max-w-xl font-readable leading-7 text-foreground/65">Bagikan kode ke temanmu. Host bisa memulai kapan saja setelah semua siap.</p>
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
        @else
            <p data-live-status role="status" class="sr-only">Memeriksa koneksi room…</p>
        @endif

        <div class="{{ $room->status === 'waiting' ? 'mt-6' : 'mx-auto max-w-3xl' }}">
            <div>
                @if ($room->status === 'waiting')
                    <section class="rounded-[1.75rem] border border-white/10 bg-surface p-6 sm:p-8" aria-labelledby="participants-title">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 id="participants-title" class="font-display text-2xl font-bold">Pemain bergabung</h2>
                            <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-bold text-accent">{{ $room->participants->count() }} pemain</span>
                        </div>
                        <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                            @foreach ($room->participants as $roomParticipant)
                                @php $member = $roomParticipant->user ?? $roomParticipant->learnerProfile; @endphp
                                <li data-room-presence-user="{{ $roomParticipant->user_id ?? 'anon-'.$roomParticipant->learner_profile_id }}" class="flex items-center gap-3 rounded-2xl border border-white/10 bg-background p-3">
                                    <img src="{{ str_starts_with($member?->avatar ?? '', 'avatars/') ? ($roomParticipant->user_id ? route('rooms.participant-avatar', [$room->code, $roomParticipant->user_id]) : route('rooms.anonymous-avatar', [$room->code, $roomParticipant->learner_profile_id])) : asset($member?->avatar ?? array_key_first(\App\Services\ProfileAvatarService::DEFAULT_AVATARS)) }}" alt="" class="size-10 shrink-0 rounded-full object-cover ring-2 ring-primary/40">
                                    <span class="min-w-0 truncate text-sm font-bold">{{ $member?->name ?? $roomParticipant->display_name }}</span>
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
                @elseif ($room->status === 'inactive')
                    <section class="rounded-[1.75rem] border border-accent/25 bg-surface p-7 sm:p-10" aria-labelledby="inactive-title">
                        <span class="inline-flex rounded-full bg-accent/10 px-4 py-2 text-sm font-bold text-accent">Room nonaktif</span>
                        <h1 id="inactive-title" class="mt-5 font-display text-3xl font-bold">Host sudah keluar dari room</h1>
                        <p class="mt-3 max-w-xl text-foreground/65">Permainan dihentikan. Jawaban yang sudah tercatat tetap tersimpan, tetapi room ini tidak menerima pemain atau jawaban baru.</p>
                        <a href="{{ route('quizzes.index') }}" class="mt-7 inline-flex min-h-12 items-center justify-center rounded-full bg-accent px-6 font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-accent">Lihat kuis lain</a>
                    </section>
                @elseif ($activeQuestion)
                    <section class="rounded-[1.75rem] border border-white/10 bg-surface p-5 sm:p-6" aria-labelledby="question-title">
                        <div class="flex items-center justify-between gap-4">
                            <span class="font-display text-lg font-bold">Soal {{ $questionPosition }} <span class="text-foreground/50">dari {{ $questionCount }}</span></span>
                            <span class="grid size-10 shrink-0 place-items-center rounded-full border-[3px] border-accent/70 bg-accent/10 font-display text-xs font-bold text-accent" aria-hidden="true">{{ $questionPosition }}/{{ $questionCount }}</span>
                        </div>
                        <div role="progressbar" aria-label="Progres kuis" aria-valuenow="{{ $questionPosition }}" aria-valuemin="0" aria-valuemax="{{ $questionCount }}" class="mt-3 h-2 overflow-hidden rounded-full bg-background">
                            <div class="h-full rounded-full bg-accent" style="width: {{ $questionCount ? round($questionPosition / $questionCount * 100) : 0 }}%"></div>
                        </div>

                        <div class="mt-4 flex min-h-28 items-center justify-center rounded-[1.5rem] border border-primary/35 bg-primary/15 px-5 py-6 text-center sm:min-h-32 sm:px-8">
                            <h2 id="question-title" class="max-w-2xl break-words font-display text-xl font-bold leading-snug sm:text-2xl">{{ $activeQuestion->prompt }}</h2>
                        </div>

                        <div class="mt-4 flex items-center gap-3 text-sm font-semibold">
                            @if ($participant?->question_ends_at && ! $isFeedback)
                                <span class="shrink-0 text-foreground/65">Sisa waktu</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-background" role="progressbar" aria-label="Sisa waktu soal" aria-valuemin="0" aria-valuemax="{{ $room->seconds_per_question }}" data-room-time-progress>
                                    <div data-room-time-bar class="h-full rounded-full bg-primary transition-[width] duration-700"></div>
                                </div>
                                <span class="min-w-14 text-right font-display text-base font-bold text-accent" role="timer" aria-live="off" data-room-countdown>—</span>
                            @elseif (! $room->seconds_per_question)
                                <span class="text-foreground/65">Tanpa batas waktu. Kamu langsung lanjut setelah menjawab.</span>
                            @endif
                        </div>

                        @if ($currentAnswer)
                            @if ($room->quiz->show_answers_immediately)
                                <x-flash-notice :tone="$currentAnswer->is_correct ? 'success' : 'info'" class="mt-5">{{ $currentAnswer->is_correct ? 'Jawaban benar! Kamu mendapat '.$currentAnswer->points_awarded.' poin.' : 'Jawaban belum tepat. Lihat jawaban yang benar di bawah.' }}</x-flash-notice>
                                <ol class="mt-4 grid gap-2" aria-label="Hasil jawaban">
                                    @foreach ($activeQuestion->options as $index => $option)
                                        @php
                                            $isSelected = $currentAnswer->answer_index === $index;
                                            $isCorrectOption = $activeQuestion->correct_option === $index;
                                        @endphp
                                        <li class="flex min-h-14 items-center gap-3 rounded-2xl border-2 px-4 py-2 {{ $isCorrectOption ? 'border-success/60 bg-success/10' : ($isSelected ? 'border-danger/60 bg-danger/10' : 'border-white/10 bg-background') }}">
                                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-surface font-display text-sm font-bold {{ $isCorrectOption ? 'text-success' : ($isSelected ? 'text-danger' : 'text-accent') }}">{{ chr(65 + $index) }}</span>
                                            <span class="min-w-0 flex-1 break-words font-semibold">{{ $option }}</span>
                                            @if ($isCorrectOption)<span class="shrink-0 text-xs font-bold text-success">{{ $isSelected ? 'Benar' : 'Jawaban benar' }}</span>@elseif ($isSelected)<span class="shrink-0 text-xs font-bold text-danger">Jawabanmu</span>@endif
                                        </li>
                                    @endforeach
                                </ol>
                                <p class="mt-3 text-sm text-foreground/60">Soal berikutnya muncul sebentar lagi.</p>
                            @else
                                <x-flash-notice tone="info" class="mt-5">Jawaban tersimpan. Hasil benar atau salah akan terlihat setelah kuis selesai.</x-flash-notice>
                            @endif
                            @if ($isFeedback)
                                <a href="{{ route('rooms.show', $room->code) }}" class="mt-4 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-accent px-6 font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Lanjut</a>
                            @endif
                        @else
                            <form method="POST" action="{{ route('rooms.answer', $room->code) }}" class="mt-5">
                                @csrf
                                <input type="hidden" name="question_id" value="{{ $activeQuestion->id }}">
                                <fieldset>
                                    <legend class="mb-2 text-sm font-semibold text-foreground/65">Pilih satu jawaban</legend>
                                    <div class="grid gap-2">
                                        @foreach ($activeQuestion->options as $index => $option)
                                            <label data-quiz-option class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border-2 border-white/10 bg-background px-4 py-2 transition-colors hover:border-accent/60">
                                                <input required type="radio" name="answer_index" value="{{ $index }}" class="sr-only">
                                                <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-surface font-display text-sm font-bold text-accent">{{ chr(65 + $index) }}</span>
                                                <span class="min-w-0 flex-1 break-words font-semibold">{{ $option }}</span>
                                                <span data-quiz-option-indicator class="size-5 shrink-0 rounded-full border-2 border-foreground/55" aria-hidden="true"></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                                <button class="mt-4 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-accent px-7 py-3 font-extrabold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent" type="submit">Kirim jawaban</button>
                            </form>
                        @endif
                    </section>
                @else
                    <section class="rounded-[1.75rem] border border-white/10 bg-surface p-6 sm:p-10" aria-labelledby="finished-title">
                        <span class="inline-flex items-center gap-2 rounded-full bg-accent/10 px-4 py-2 text-sm font-bold text-accent"><span aria-hidden="true">✦</span> {{ $room->status === 'finished' ? 'Selesai bermain' : 'Jawaban terkirim' }}</span>
                        <p class="mt-7 text-sm font-semibold text-foreground/55">{{ $room->quiz->title }}</p>
                        @if ($room->status === 'finished')
                            <h1 id="finished-title" class="mt-2 font-display text-3xl font-bold sm:text-4xl">Kuis selesai!</h1>
                        @else
                            <h2 id="finished-title" class="mt-2 font-display text-3xl font-bold sm:text-4xl">Kamu sudah menjawab semua soal!</h2>
                        @endif
                        <p class="mt-3 max-w-lg text-foreground/65">{{ $room->status === 'finished' ? 'Terima kasih sudah bermain. Siap mencoba kuis lainnya?' : 'Pemain lain masih menjawab. Tunggu sampai kuis selesai.' }}</p>
                        <a href="{{ route('quizzes.index') }}" class="mt-8 inline-flex min-h-12 items-center justify-center rounded-full bg-accent px-6 font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent">Lihat kuis lain</a>
                        @if ($room->status === 'finished' && ! $room->quiz->show_answers_immediately)
                            <div class="mt-8 border-t border-white/10 pt-7">
                                <h2 class="font-display text-xl font-bold">Hasil jawabanmu</h2>
                                <p class="mt-1 text-sm text-foreground/60">{{ $participant?->score ?? 0 }} poin terkumpul</p>
                                <ol class="mt-5 space-y-3">
                                    @foreach ($room->quiz->questions as $question)
                                        @php $answer = $answersByQuestion->get($question->id); @endphp
                                        <li class="rounded-2xl border border-white/10 bg-background p-4">
                                            <p class="font-semibold">{{ $loop->iteration }}. {{ $question->prompt }}</p>
                                            <p class="mt-2 text-sm {{ $answer?->is_correct ? 'text-success' : 'text-danger' }}">{{ $answer ? ($answer->is_correct ? 'Benar' : 'Salah') : 'Tidak dijawab' }} — Jawabanmu: {{ $answer ? $question->options[$answer->answer_index] : '—' }}</p>
                                            @if (! $answer?->is_correct)<p class="mt-1 text-sm text-success">Jawaban benar: {{ $question->options[$question->correct_option] }}</p>@endif
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </section>
                @endif
            </div>
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
    <dialog data-room-inactive-dialog aria-labelledby="room-inactive-dialog-title" class="fixed inset-0 m-auto w-[min(28rem,calc(100vw-2rem))] rounded-[1.75rem] border border-accent/30 bg-surface p-6 text-foreground shadow-2xl backdrop:bg-black/85 sm:p-8">
        <span class="grid size-12 place-items-center rounded-2xl bg-accent/10 text-2xl text-accent" aria-hidden="true">!</span>
        <h2 id="room-inactive-dialog-title" class="mt-5 font-display text-2xl font-bold">Room dinonaktifkan</h2>
        <p class="mt-3 text-sm leading-6 text-foreground/65">Host telah keluar. Kuis di room ini tidak dapat dilanjutkan, tetapi jawaban yang sudah tersimpan tidak dihapus.</p>
        <a href="{{ route('quizzes.index') }}" class="mt-7 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-accent px-5 font-extrabold text-background">Kembali ke kuis</a>
    </dialog>
</x-layouts.app>
