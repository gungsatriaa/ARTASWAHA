@php
    $editing = isset($quiz);
    $questions = old('questions', $editing
        ? $quiz->questions->map(fn ($question) => [
            'id' => $question->id,
            'prompt' => $question->prompt,
            'options' => $question->options,
            'correct_option' => $question->correct_option,
            'explanation' => $question->explanation,
            'keyboard_auto_open' => $question->keyboard_auto_open,
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
            @if (session('status'))<x-flash-notice class="mt-5">{{ session('status') }}</x-flash-notice>@endif
            @if ($errors->any())<div role="alert" class="mt-6 rounded-2xl bg-danger/10 p-4 text-danger"><p class="font-bold">Periksa kolom yang ditandai sebelum menyimpan.</p><ul class="mt-2 list-disc pl-5 text-sm">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
            @unless ($editing)
            <section class="mt-8 rounded-card bg-surface p-6 sm:p-8" data-ai-quiz data-upload-url="{{ route('quizzes.drafts.store') }}">
                <h2 class="font-display text-xl font-bold">Buat draf dengan AI</h2>
                <p class="mt-2 text-sm text-foreground/65">Unggah JPG, PNG, WEBP, PDF, DOC, atau DOCX (maks. 10 MB). Periksa hasil ekstraksi dan soal sebelum menyimpan.</p>
                <div class="mt-4 flex flex-wrap gap-3"><input type="file" accept="image/jpeg,image/png,image/webp,application/pdf,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" data-ai-file class="max-w-full text-sm"><button type="button" data-ai-submit class="rounded-full bg-accent px-5 py-3 font-bold text-background">Buat draf soal</button></div>
                <p class="mt-3 text-sm text-foreground/65" role="status" data-ai-status aria-live="polite"></p>
                <details class="mt-3 hidden" data-ai-source><summary class="cursor-pointer font-semibold">Tinjau teks OCR</summary><pre class="mt-2 max-h-48 overflow-auto whitespace-pre-wrap rounded-xl bg-background p-4 text-sm" data-ai-text></pre></details>
            </section>
            @endunless
            <form method="POST" action="{{ $editing ? route('quizzes.update', $quiz) : route('quizzes.store') }}" class="mt-8 space-y-6" data-quiz-builder data-aksara-scope data-has-errors="{{ $errors->any() ? '1' : '0' }}" data-error-step="{{ $errors->has('title') || $errors->has('description') || $errors->has('seconds_per_question') ? '0' : '1' }}" data-preview-requested="{{ request()->boolean('preview') ? '1' : '0' }}">
                @csrf
                @if ($editing) @method('PUT') @endif
                <nav data-quiz-steps hidden aria-label="Tahap membuat kuis" class="flex flex-wrap gap-2">
                    <button type="button" data-step-link="0" class="min-h-11 rounded-full border border-white/15 px-4 text-sm font-bold">1. Pengaturan</button>
                    <button type="button" data-step-link="1" class="min-h-11 rounded-full border border-white/15 px-4 text-sm font-bold">2. Kelola soal</button>
                    <button type="button" data-step-link="2" class="min-h-11 rounded-full border border-white/15 px-4 text-sm font-bold">3. Preview & simpan</button>
                </nav>
                <section data-quiz-step="0" class="rounded-card bg-surface p-6 sm:p-8">
                    <h2 tabindex="-1" class="mb-5 font-display text-xl font-bold">Pengaturan kuis</h2>
                    <label class="block text-sm font-semibold">Judul kuis
                        <input data-aksara-input data-aksara-label="Judul" name="title" value="{{ old('title', $quiz->title ?? '') }}" required maxlength="120" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                        <span data-field-error class="mt-1 block text-xs text-danger" aria-live="polite">@error('title'){{ $message }}@enderror</span>
                    </label>
                    <label class="mt-5 block text-sm font-semibold">Deskripsi <span class="font-normal text-foreground/50">(opsional)</span>
                        <textarea data-aksara-input data-aksara-label="Deskripsi" name="description" maxlength="500" rows="3" class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 outline-none focus:border-accent">{{ old('description', $quiz->description ?? '') }}</textarea><span data-field-error class="mt-1 block text-xs text-danger" aria-live="polite">@error('description'){{ $message }}@enderror</span>
                    </label>
                    <label class="mt-5 block text-sm font-semibold">Waktu per soal <span class="font-normal text-foreground/50">(opsional)</span>
                        <input type="number" name="seconds_per_question" value="{{ old('seconds_per_question', $quiz->seconds_per_question ?? '') }}" min="5" max="300" step="1" placeholder="Tanpa batas waktu" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent sm:max-w-xs">
                        <span data-field-error class="mt-1 block text-xs text-danger" aria-live="polite">@error('seconds_per_question'){{ $message }}@enderror</span>
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

                <section data-quiz-step="1" class="space-y-5">
                <h2 tabindex="-1" class="sr-only">Kelola soal kuis</h2>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-display text-2xl font-bold">Pertanyaan</h2>
                    <button type="button" data-add-question class="rounded-full border border-white/15 px-5 py-3 text-sm font-bold hover:bg-surface">＋ Tambah pertanyaan</button>
                </div>
                <div class="space-y-5" data-question-list>
                    @foreach ($questions as $index => $question)
                        @php
                            $saved = $editing ? $quiz->questions->firstWhere('id', $question['id'] ?? null) : null;
                            $audio = $saved ? app(\App\Services\ContentAudioService::class)->forContent($saved) : null;
                            $currentAudio = $audio === null || app(\App\Services\ContentAudioService::class)->isCurrent($audio);
                            $playable = app(\App\Services\ContentAudioService::class)->isPlayable($audio);
                        @endphp
                        <x-quiz-question-editor :index="$index" :question="$question" :saved="$saved" :audio="$audio" :current-audio="$currentAudio" :playable="$playable" />
                    @endforeach
                </div>
                <template data-question-template><x-quiz-question-editor /></template>
                <template data-option-template>
                    <div class="flex items-center gap-2 rounded-control bg-background p-3" data-option-row>
                        <input type="radio" name="questions[__INDEX__][correct_option]" value="__OPTION__" required class="size-5 shrink-0 accent-[var(--color-accent)]">
                        <label class="min-w-0 flex-1"><span class="sr-only">Pilihan jawaban</span><input data-aksara-input data-aksara-label="Pilihan jawaban" name="questions[__INDEX__][options][]" required maxlength="140" class="min-h-11 w-full bg-transparent outline-none"><span data-field-error class="block text-xs text-danger" aria-live="polite"></span></label>
                        <button type="button" data-remove-option class="min-h-11 min-w-9 text-xl text-foreground/60">×</button>
                    </div>
                </template>
                <x-aksara-keyboard :scratch="false" />
                </section>
                <section data-quiz-step="2" hidden class="rounded-card bg-surface p-4 sm:p-7">
                    <h2 tabindex="-1" class="font-display text-2xl font-bold">Preview sebagai peserta</h2>
                    <p class="mt-2 text-sm leading-6 text-foreground/60">Coba pilihan jawaban, keyboard, dan audio yang masih sesuai. Preview tidak mengirim jawaban atau menjalankan timer.</p>
                    <p data-preview-position class="mt-5 font-bold text-accent"></p>
                    <h3 data-preview-prompt class="mt-3 whitespace-pre-wrap break-words text-xl font-bold"></h3>
                    <audio data-preview-audio controls preload="none" hidden class="mt-4 w-full"></audio>
                    <p data-preview-audio-note class="mt-2 text-xs text-foreground/60"></p>
                    <div data-preview-options class="mt-5 grid gap-3"></div>
                    <x-aksara-keyboard class="mt-5" />
                    <div class="mt-5 flex flex-wrap justify-between gap-3">
                        <button type="button" data-preview-prev class="min-h-11 rounded-full border border-white/15 px-4 text-sm">← Soal sebelumnya</button>
                        <button type="button" data-preview-next class="min-h-11 rounded-full border border-white/15 px-4 text-sm">Soal berikutnya →</button>
                    </div>
                </section>
                <p data-editor-status role="status" aria-live="polite" class="text-sm text-foreground/65"></p>
                <div class="flex flex-wrap gap-3">
                    <button type="button" data-step-prev hidden class="min-h-12 rounded-full border border-white/20 px-6 font-bold">Kembali</button>
                    <button type="button" data-step-next hidden class="min-h-12 flex-1 rounded-full bg-accent px-6 font-bold text-background">Lanjut</button>
                    <button type="submit" data-save-quiz class="min-h-12 flex-1 rounded-full bg-primary px-6 py-3 font-bold text-white">{{ $editing ? 'Simpan perubahan' : 'Simpan kuis' }}</button>
                </div>
            </form>
        </div>
</x-layouts.app>
