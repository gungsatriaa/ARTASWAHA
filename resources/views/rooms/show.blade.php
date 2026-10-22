<x-layouts.app :show-footer="false" title="Room {{ $room->code }} — ARTASWAHA">
        <div class="mx-auto w-full max-w-5xl px-5 py-8 sm:px-6 sm:py-10" data-room-code="{{ $room->code }}">
            <div class="mb-6 flex justify-end"><a href="{{ route('quizzes.index') }}" class="inline-flex min-h-10 items-center rounded-full px-4 text-sm font-semibold text-accent transition hover:bg-surface">Keluar dari room</a></div>
            @if ($errors->any())<p role="alert" class="mb-6 rounded-2xl bg-danger/10 p-4 text-danger">{{ $errors->first() }}</p>@endif
            <p data-live-status class="mb-3 min-h-5 text-sm text-foreground/50">Segarkan halaman untuk memeriksa pembaruan room.</p>
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div><p class="text-sm font-semibold text-accent">{{ $room->quiz->title }}</p><h1 class="mt-2 font-display text-4xl font-bold">Room {{ $room->code }}</h1><p class="mt-2 text-sm capitalize text-foreground/55">{{ $room->status === 'waiting' ? 'Menunggu' : ($room->status === 'active' ? 'Sedang berlangsung' : 'Selesai') }}</p></div>
                <button type="button" data-copy-room="{{ route('rooms.show', $room->code) }}" class="rounded-full bg-surface px-5 py-3 text-sm font-bold hover:bg-white/10">Salin tautan undangan</button>
            </div>
            <p role="status" data-copy-status class="mt-3 min-h-6 text-sm text-accent"></p>

            @if ($room->status === 'waiting')
                <section class="mt-8 rounded-card bg-surface p-6 sm:p-8">
                    <h2 class="font-display text-2xl font-bold">Pemain bergabung</h2>
                    <ul class="mt-4 flex flex-wrap gap-3">
                        @foreach ($room->participants as $roomParticipant)
                            <li class="rounded-full bg-background px-4 py-2 text-sm">{{ $roomParticipant->display_name }}{{ $roomParticipant->user_id === $room->host_id ? ' · Host' : '' }}</li>
                        @endforeach
                    </ul>
                    @if (auth()->id() === $room->host_id)
                        <form method="POST" action="{{ route('rooms.start', $room->code) }}" class="mt-7">@csrf<button class="min-h-12 rounded-full bg-primary px-7 py-3 font-bold text-white">Mulai kuis</button></form>
                    @else
                        <p class="mt-6 font-readable text-foreground/60">Room akan dimulai oleh host. Segarkan halaman untuk melihat perubahan.</p>
                    @endif
                </section>
            @elseif ($room->status === 'active' && $room->currentQuestion)
                <section class="mt-8 rounded-card bg-surface p-6 sm:p-8">
                    <p class="text-sm font-semibold text-accent">Pertanyaan {{ $room->currentQuestion->sort_order }}</p>
                    <h2 class="mt-3 font-display text-2xl font-bold">{{ $room->currentQuestion->prompt }}</h2>
                    @if ($currentAnswer)
                        <p role="status" class="mt-6 rounded-2xl bg-primary/20 p-4 font-semibold text-accent">{{ $currentAnswer->is_correct ? 'Benar! Kamu mendapat 10 poin.' : 'Jawabanmu sudah tersimpan. Tetap semangat!' }} Tunggu host melanjutkan.</p>
                    @else
                        <form method="POST" action="{{ route('rooms.answer', $room->code) }}" class="mt-6 space-y-3">
                            @csrf<input type="hidden" name="question_id" value="{{ $room->currentQuestion->id }}">
                            @foreach ($room->currentQuestion->options as $index => $option)
                                <label class="flex cursor-pointer items-center gap-3 rounded-2xl bg-background p-4 hover:bg-white/5"><input required type="radio" name="answer_index" value="{{ $index }}" class="accent-[var(--color-accent)]"><span>{{ $option }}</span></label>
                            @endforeach
                            <button class="min-h-12 w-full rounded-full bg-primary px-7 py-3 font-bold text-white sm:w-auto" type="submit">Kirim jawaban</button>
                        </form>
                    @endif
                    @if (auth()->id() === $room->host_id)
                        <form method="POST" action="{{ route('rooms.advance', $room->code) }}" class="mt-6 border-t border-white/10 pt-6">@csrf<button class="rounded-full border border-white/15 px-6 py-3 font-bold hover:bg-white/5">Pertanyaan berikutnya</button></form>
                    @endif
                </section>
            @else
                <p class="mt-8 rounded-card bg-surface p-6 font-readable">Kuis selesai. Terima kasih sudah bermain bersama!</p>
            @endif

            @if ($room->leaderboard_enabled)
                <section class="mt-8 rounded-card bg-surface p-6 sm:p-8" aria-labelledby="leaderboard-title">
                    <h2 id="leaderboard-title" class="font-display text-2xl font-bold">Peringkat</h2>
                    <ol class="mt-4 space-y-3">
                        @foreach ($room->participants as $rank => $roomParticipant)
                            <li class="flex items-center justify-between rounded-2xl bg-background px-4 py-3"><span>{{ $rank + 1 }}. {{ $roomParticipant->display_name }}</span><strong class="text-accent">{{ $roomParticipant->score }} poin</strong></li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>
</x-layouts.app>
