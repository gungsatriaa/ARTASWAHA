@php
    $editing = isset($quiz);
    $questions = old('questions', $editing
        ? $quiz->questions->map(fn ($question) => [
            'prompt' => $question->prompt,
            'options' => $question->options,
            'correct_option' => $question->correct_option,
            'explanation' => $question->explanation,
        ])->all()
        : [['prompt' => '', 'options' => ['', '', '', ''], 'correct_option' => null, 'explanation' => '']]);
@endphp
<x-layouts.app :show-footer="false" :title="($editing ? 'Edit kuis' : 'Buat kuis') . ' — ARTASWAHA'">
        <div class="mx-auto w-full max-w-4xl px-5 py-8 sm:px-6 sm:py-10">
            <a href="{{ $editing ? route('quizzes.show', $quiz) : route('quizzes.index') }}" class="mb-8 inline-flex min-h-10 items-center rounded-full px-4 text-sm font-semibold text-foreground/70 transition hover:bg-surface hover:text-accent">← Kembali ke kuis</a>
            <h1 class="font-display text-4xl font-bold">{{ $editing ? 'Edit kuis' : 'Buat kuis' }}</h1>
            <p class="mt-3 font-readable text-foreground/65">{{ $editing ? 'Perbarui pengaturan dan pertanyaan kuis.' : 'Tulis pertanyaan, lalu tandai satu jawaban yang benar.' }}</p>
            @if ($editing && $hasRooms)
                <p class="mt-5 rounded-2xl border border-accent/25 bg-accent/10 p-4 text-sm leading-6 text-foreground/80">Kuis ini sudah memiliki room. Saat disimpan, perubahan menjadi versi baru agar room dan jawaban lama tetap aman.</p>
            @endif
            @if ($errors->any())<p role="alert" class="mt-6 rounded-2xl bg-danger/10 p-4 text-danger">{{ $errors->first() }}</p>@endif
            @unless ($editing)
            <section class="mt-8 rounded-card bg-surface p-6 sm:p-8" data-ai-quiz data-upload-url="{{ route('quizzes.drafts.store') }}">
                <h2 class="font-display text-xl font-bold">Buat draf dengan AI</h2>
                <p class="mt-2 text-sm text-foreground/65">Unggah JPG, PNG, WEBP, PDF, DOC, atau DOCX (maks. 10 MB). Periksa hasil ekstraksi dan soal sebelum menyimpan.</p>
                <div class="mt-4 flex flex-wrap gap-3"><input type="file" accept="image/jpeg,image/png,image/webp,application/pdf,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" data-ai-file class="max-w-full text-sm"><button type="button" data-ai-submit class="rounded-full bg-accent px-5 py-3 font-bold text-background">Buat draf soal</button></div>
                <p class="mt-3 text-sm text-foreground/65" role="status" data-ai-status aria-live="polite"></p>
                <details class="mt-3 hidden" data-ai-source><summary class="cursor-pointer font-semibold">Tinjau teks OCR</summary><pre class="mt-2 max-h-48 overflow-auto whitespace-pre-wrap rounded-xl bg-background p-4 text-sm" data-ai-text></pre></details>
            </section>
            @endunless
            <form method="POST" action="{{ $editing ? route('quizzes.update', $quiz) : route('quizzes.store') }}" class="mt-8 space-y-6" data-quiz-builder>
                @csrf
                @if ($editing) @method('PUT') @endif
                <section class="rounded-card bg-surface p-6 sm:p-8">
                    <label class="block text-sm font-semibold">Judul kuis
                        <input name="title" value="{{ old('title', $quiz->title ?? '') }}" required maxlength="120" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                    </label>
                    <label class="mt-5 block text-sm font-semibold">Deskripsi <span class="font-normal text-foreground/50">(opsional)</span>
                        <textarea name="description" maxlength="500" rows="3" class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 outline-none focus:border-accent">{{ old('description', $quiz->description ?? '') }}</textarea>
                    </label>
                    <label class="mt-5 block text-sm font-semibold">Waktu per soal <span class="font-normal text-foreground/50">(opsional)</span>
                        <input type="number" name="seconds_per_question" value="{{ old('seconds_per_question', $quiz->seconds_per_question ?? '') }}" min="5" max="300" step="1" placeholder="Tanpa batas waktu" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent sm:max-w-xs">
                    </label>
                    <p class="mt-2 text-sm text-foreground/55">Isi 5–300 detik sebagai batas waktu setiap soal. Setelah menjawab, pemain langsung lanjut sendiri. Kosongkan untuk bermain tanpa batas waktu.</p>
                    <label class="mt-6 flex cursor-pointer items-start gap-3 rounded-2xl border border-white/10 bg-background p-4">
                        <input type="hidden" name="show_answers_immediately" value="0">
                        <input type="checkbox" name="show_answers_immediately" value="1" @checked(old('show_answers_immediately', $quiz->show_answers_immediately ?? true)) class="mt-1 size-5 shrink-0 accent-[var(--color-accent)]">
                        <span><span class="block font-semibold">Tampilkan jawaban benar setelah menjawab</span><span class="mt-1 block text-sm font-normal leading-6 text-foreground/60">Jika dimatikan, hasil benar atau salah baru terlihat setelah kuis selesai.</span></span>
                    </label>
                    <div class="mt-5 flex flex-wrap gap-6 text-sm">
                        <p class="text-foreground/60">Kuis hanya terlihat olehmu; room dibagikan terpisah memakai kode undangan.</p>
                    </div>
                </section>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-display text-2xl font-bold">Pertanyaan</h2>
                    <button type="button" data-add-question class="rounded-full border border-white/15 px-5 py-3 text-sm font-bold hover:bg-surface">＋ Tambah pertanyaan</button>
                </div>
                <div class="space-y-5" data-question-list>
                    @foreach ($questions as $index => $question)
                    <fieldset class="rounded-card bg-surface p-6 sm:p-8" data-question>
                        <legend class="font-bold">Pertanyaan <span data-question-number>{{ $index + 1 }}</span></legend>
                        <button type="button" data-remove-question class="float-right cursor-pointer rounded-full px-3 py-2 text-sm font-semibold text-danger transition hover:bg-danger/10">Hapus soal</button>
                        <label class="mt-3 block text-sm font-semibold">Teks pertanyaan
                            <textarea name="questions[{{ $index }}][prompt]" required maxlength="500" rows="2" class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 outline-none focus:border-accent">{{ $question['prompt'] ?? '' }}</textarea>
                        </label>
                        <p class="mt-5 text-sm font-semibold">Pilihan jawaban <span class="font-normal text-foreground/50">(pilih satu yang benar)</span></p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2" data-options>
                            @foreach (($question['options'] ?? []) as $optionIndex => $option)
                                <div class="flex items-center gap-2 rounded-2xl bg-background p-3" data-option-row>
                                    <input type="radio" name="questions[{{ $index }}][correct_option]" value="{{ $optionIndex }}" @checked((string) ($question['correct_option'] ?? '') === (string) $optionIndex) required aria-label="Jawaban benar: pilihan {{ $optionIndex + 1 }}" class="size-4 shrink-0 cursor-pointer accent-[var(--color-accent)]">
                                    <input name="questions[{{ $index }}][options][]" value="{{ $option }}" required maxlength="140" aria-label="Pilihan {{ $optionIndex + 1 }}" placeholder="Pilihan {{ $optionIndex + 1 }}" class="min-h-10 min-w-0 flex-1 bg-transparent outline-none">
                                    <button type="button" data-remove-option aria-label="Hapus pilihan {{ $optionIndex + 1 }}" class="shrink-0 cursor-pointer rounded-full px-2 py-1 text-sm text-foreground/60 transition hover:bg-white/10 hover:text-foreground">×</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" data-add-option class="mt-3 cursor-pointer rounded-full border border-white/15 px-4 py-2 text-sm font-semibold transition hover:bg-background">＋ Tambah pilihan</button>
                        <label class="mt-5 block text-sm font-semibold">Penjelasan setelah menjawab <span class="font-normal text-foreground/50">(opsional)</span>
                            <input name="questions[{{ $index }}][explanation]" value="{{ $question['explanation'] ?? '' }}" maxlength="500" class="mt-2 min-h-11 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                        </label>
                    </fieldset>
                    @endforeach
                </div>
                <template data-question-template>
                    <fieldset class="rounded-card bg-surface p-6 sm:p-8" data-question>
                        <legend class="font-bold">Pertanyaan <span data-question-number></span></legend>
                        <button type="button" data-remove-question class="float-right cursor-pointer rounded-full px-3 py-2 text-sm font-semibold text-danger transition hover:bg-danger/10">Hapus soal</button>
                        <label class="mt-3 block text-sm font-semibold">Teks pertanyaan
                            <textarea name="questions[__INDEX__][prompt]" required maxlength="500" rows="2" class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 outline-none focus:border-accent"></textarea>
                        </label>
                        <p class="mt-5 text-sm font-semibold">Pilihan jawaban <span class="font-normal text-foreground/50">(pilih satu yang benar)</span></p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2" data-options>
                            @foreach (range(0, 3) as $option)
                                <div class="flex items-center gap-2 rounded-2xl bg-background p-3" data-option-row>
                                    <input type="radio" name="questions[__INDEX__][correct_option]" value="{{ $option }}" required aria-label="Jawaban benar: pilihan {{ $option + 1 }}" class="size-4 shrink-0 cursor-pointer accent-[var(--color-accent)]">
                                    <input name="questions[__INDEX__][options][]" required maxlength="140" aria-label="Pilihan {{ $option + 1 }}" placeholder="Pilihan {{ $option + 1 }}" class="min-h-10 min-w-0 flex-1 bg-transparent outline-none">
                                    <button type="button" data-remove-option aria-label="Hapus pilihan {{ $option + 1 }}" class="shrink-0 cursor-pointer rounded-full px-2 py-1 text-sm text-foreground/60 transition hover:bg-white/10 hover:text-foreground">×</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" data-add-option class="mt-3 cursor-pointer rounded-full border border-white/15 px-4 py-2 text-sm font-semibold transition hover:bg-background">＋ Tambah pilihan</button>
                        <label class="mt-5 block text-sm font-semibold">Penjelasan setelah menjawab <span class="font-normal text-foreground/50">(opsional)</span>
                            <input name="questions[__INDEX__][explanation]" maxlength="500" class="mt-2 min-h-11 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                        </label>
                    </fieldset>
                </template>
                <template data-option-template>
                    <div class="flex items-center gap-2 rounded-2xl bg-background p-3" data-option-row>
                        <input type="radio" name="questions[__INDEX__][correct_option]" value="__OPTION__" required class="size-4 shrink-0 cursor-pointer accent-[var(--color-accent)]">
                        <input name="questions[__INDEX__][options][]" required maxlength="140" class="min-h-10 min-w-0 flex-1 bg-transparent outline-none">
                        <button type="button" data-remove-option class="shrink-0 cursor-pointer rounded-full px-2 py-1 text-sm text-foreground/60 transition hover:bg-white/10 hover:text-foreground">×</button>
                    </div>
                </template>
                <button type="submit" class="min-h-14 w-full cursor-pointer rounded-full bg-primary px-7 py-4 font-bold text-white hover:brightness-110">{{ $editing ? 'Simpan perubahan' : 'Simpan kuis' }}</button>
            </form>
        </div>
</x-layouts.app>
