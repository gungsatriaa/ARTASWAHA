@props(['index' => '__INDEX__', 'question' => [], 'saved' => null, 'audio' => null, 'playable' => false, 'currentAudio' => true])
<details data-question open class="rounded-card border border-white/10 bg-surface p-4 sm:p-6" data-audio-url="{{ $playable ? route('content-audio.show', $audio) : '' }}" data-source-prompt="{{ $saved?->prompt }}" data-source-options="{{ json_encode($saved?->options ?? []) }}">
    <summary class="flex min-h-11 cursor-pointer items-center gap-3 font-bold focus-visible:outline-2 focus-visible:outline-accent">
        <span class="shrink-0 text-accent">Soal <span data-question-number>{{ is_numeric($index) ? $index + 1 : '' }}</span></span>
        <span data-question-summary class="min-w-0 flex-1 truncate text-sm text-foreground/65">{{ $question['prompt'] ?? 'Pertanyaan baru' }}</span><span data-question-chevron aria-hidden="true">⌄</span>
    </summary>
    <div data-question-fields class="space-y-4 pt-4">
        @if (! empty($question['id']))<input type="hidden" name="questions[{{ $index }}][id]" value="{{ $question['id'] }}">@endif
        <div class="flex flex-wrap gap-2" aria-label="Kelola soal">
            <button type="button" data-move-question="up" class="min-h-11 rounded-full border border-white/15 px-4 text-sm">↑ Naik</button>
            <button type="button" data-move-question="down" class="min-h-11 rounded-full border border-white/15 px-4 text-sm">↓ Turun</button>
            <button type="button" data-duplicate-question class="min-h-11 rounded-full border border-white/15 px-4 text-sm">Duplikasikan soal</button>
            <button type="button" data-remove-question class="min-h-11 rounded-full px-4 text-sm text-danger">Hapus soal</button>
        </div>
        <label class="block text-sm font-semibold">Teks pertanyaan
            <textarea data-aksara-input data-aksara-label="Teks pertanyaan" name="questions[{{ $index }}][prompt]" required maxlength="500" rows="3" class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 outline-none focus:border-accent">{{ $question['prompt'] ?? '' }}</textarea>
            <span data-field-error class="mt-1 block text-xs text-danger" aria-live="polite">@error('questions.'.$index.'.prompt'){{ $message }}@enderror</span>
        </label>
        <fieldset data-options-fieldset>
            <legend class="text-sm font-semibold">Pilihan jawaban · tandai satu yang benar</legend>
            <div class="mt-3 grid gap-3 sm:grid-cols-2" data-options>
                @foreach (($question['options'] ?? ['', '', '', '']) as $optionIndex => $option)
                    <div data-option-row class="rounded-control bg-background p-3">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="questions[{{ $index }}][correct_option]" value="{{ $optionIndex }}" @checked((string) ($question['correct_option'] ?? '') === (string) $optionIndex) required aria-label="Jawaban benar: pilihan {{ $optionIndex + 1 }}" class="size-5 shrink-0 accent-[var(--color-accent)]">
                            <label class="min-w-0 flex-1"><span class="sr-only">Pilihan {{ $optionIndex + 1 }}</span><input data-aksara-input data-aksara-label="Pilihan jawaban" name="questions[{{ $index }}][options][]" value="{{ $option }}" required maxlength="140" aria-label="Pilihan {{ $optionIndex + 1 }}" placeholder="Pilihan {{ $optionIndex + 1 }}" class="min-h-11 w-full bg-transparent outline-none focus-visible:outline-2 focus-visible:outline-accent"><span data-field-error class="block text-xs text-danger" aria-live="polite">@error('questions.'.$index.'.options.'.$optionIndex){{ $message }}@enderror</span></label>
                            <button type="button" data-remove-option aria-label="Hapus pilihan {{ $optionIndex + 1 }}" class="min-h-11 min-w-9 text-xl text-foreground/60">×</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <p data-answer-error class="mt-2 text-xs text-danger" aria-live="polite">@error('questions.'.$index.'.correct_option'){{ $message }}@enderror</p>
            <button type="button" data-add-option class="mt-3 min-h-11 rounded-full border border-white/15 px-4 text-sm">＋ Tambah pilihan</button>
        </fieldset>
        <label class="block text-sm font-semibold">Penjelasan setelah menjawab <span class="font-normal text-foreground/50">(opsional)</span>
            <textarea data-aksara-input data-aksara-label="Penjelasan jawaban" name="questions[{{ $index }}][explanation]" maxlength="500" rows="2" class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 outline-none focus:border-accent">{{ $question['explanation'] ?? '' }}</textarea>
            <span data-field-error class="block text-xs text-danger" aria-live="polite">@error('questions.'.$index.'.explanation'){{ $message }}@enderror</span>
        </label>
        <label class="flex items-start gap-3 text-sm">
            <input type="hidden" name="questions[{{ $index }}][keyboard_auto_open]" value="0">
            <input type="checkbox" name="questions[{{ $index }}][keyboard_auto_open]" value="1" @checked($question['keyboard_auto_open'] ?? false) class="mt-1 size-5 shrink-0 accent-[var(--color-accent)]">
            <span>Buka keyboard aksara otomatis saat soal ini tampil<span class="mt-1 block text-xs text-foreground/60">Peserta tetap dapat membuka atau menutupnya sendiri.</span></span>
        </label>
        <div data-question-audio class="rounded-control border border-white/10 p-4">
            <p class="text-sm font-bold">Audio soal</p>
            @if ($audio)<div class="mt-2"><x-job-status :status="$currentAudio ? $audio->status : 'stale'" /></div>@else<p class="mt-2 text-xs text-foreground/60">Belum ada audio. Simpan kuis untuk menyiapkan audio soal baru.</p>@endif
            @if ($playable)<div class="mt-3"><x-content-audio-player :src="route('content-audio.show', $audio)" label="Dengarkan audio tersimpan" /></div>@endif
            @if ($saved && auth()->user()?->is_admin)<a href="{{ route('admin.audio.questions.edit', $saved) }}" class="mt-2 inline-flex min-h-11 items-center text-sm font-bold text-accent">Kelola audio soal</a>@endif
            <p class="mt-2 text-xs leading-5 text-foreground/55">Simpan perubahan sebelum mengatur audio. Perubahan teks atau pilihan memerlukan audio baru; preview peserta hanya memakai audio yang sesuai.</p>
        </div>
    </div>
</details>
