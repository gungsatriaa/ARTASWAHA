<x-layouts.app :show-footer="false" title="Buat kuis — ARTASWAHA">
        <div class="mx-auto w-full max-w-4xl px-5 py-8 sm:px-6 sm:py-10">
            <a href="{{ route('quizzes.index') }}" class="mb-8 inline-flex min-h-10 items-center rounded-full px-4 text-sm font-semibold text-foreground/70 transition hover:bg-surface hover:text-accent">← Kembali ke kuis</a>
            <h1 class="font-display text-4xl font-bold">Buat kuis</h1>
            <p class="mt-3 font-readable text-foreground/65">Tulis pertanyaan, lalu tandai satu jawaban yang benar.</p>
            @if ($errors->any())<p role="alert" class="mt-6 rounded-2xl bg-danger/10 p-4 text-danger">{{ $errors->first() }}</p>@endif
            <section class="mt-8 rounded-card bg-surface p-6 sm:p-8" data-ai-quiz data-upload-url="{{ route('quizzes.drafts.store') }}">
                <h2 class="font-display text-xl font-bold">Buat draf dengan AI</h2>
                <p class="mt-2 text-sm text-foreground/65">Unggah JPG, PNG, WEBP, PDF, DOC, atau DOCX (maks. 10 MB). Periksa hasil ekstraksi dan soal sebelum menyimpan.</p>
                <div class="mt-4 flex flex-wrap gap-3"><input type="file" accept="image/jpeg,image/png,image/webp,application/pdf,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" data-ai-file class="max-w-full text-sm"><button type="button" data-ai-submit class="rounded-full bg-accent px-5 py-3 font-bold text-background">Buat draf soal</button></div>
                <p class="mt-3 text-sm text-foreground/65" role="status" data-ai-status aria-live="polite"></p>
                <details class="mt-3 hidden" data-ai-source><summary class="cursor-pointer font-semibold">Tinjau teks OCR</summary><pre class="mt-2 max-h-48 overflow-auto whitespace-pre-wrap rounded-xl bg-background p-4 text-sm" data-ai-text></pre></details>
            </section>
            <form method="POST" action="{{ route('quizzes.store') }}" class="mt-8 space-y-6" data-quiz-builder>
                @csrf
                <section class="rounded-card bg-surface p-6 sm:p-8">
                    <label class="block text-sm font-semibold">Judul kuis
                        <input name="title" value="{{ old('title') }}" required maxlength="120" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                    </label>
                    <label class="mt-5 block text-sm font-semibold">Deskripsi <span class="font-normal text-foreground/50">(opsional)</span>
                        <textarea name="description" maxlength="500" rows="3" class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 outline-none focus:border-accent">{{ old('description') }}</textarea>
                    </label>
                    <label class="mt-5 block text-sm font-semibold">Waktu per soal <span class="font-normal text-foreground/50">(opsional)</span>
                        <input type="number" name="seconds_per_question" value="{{ old('seconds_per_question') }}" min="5" max="300" step="1" placeholder="Tanpa batas waktu" class="mt-2 min-h-12 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent sm:max-w-xs">
                    </label>
                    <p class="mt-2 text-sm text-foreground/55">Isi 5–300 detik agar semua pemain berganti soal bersamaan. Kosongkan agar setiap pemain maju sendiri setelah menjawab.</p>
                    <div class="mt-5 flex flex-wrap gap-6 text-sm">
                        <p class="text-foreground/60">Kuis hanya terlihat olehmu; room dibagikan terpisah memakai kode undangan.</p>
                        <label class="flex items-center gap-2"><input type="checkbox" name="leaderboard_enabled" value="1" checked class="accent-[var(--color-accent)]"> Tampilkan peringkat</label>
                    </div>
                </section>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-display text-2xl font-bold">Pertanyaan</h2>
                    <button type="button" data-add-question class="rounded-full border border-white/15 px-5 py-3 text-sm font-bold hover:bg-surface">＋ Tambah pertanyaan</button>
                </div>
                <div class="space-y-5" data-question-list>
                    <fieldset class="rounded-card bg-surface p-6 sm:p-8" data-question>
                        <legend class="font-bold">Pertanyaan 1</legend>
                        <label class="mt-3 block text-sm font-semibold">Teks pertanyaan
                            <textarea name="questions[0][prompt]" required maxlength="500" rows="2" class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 outline-none focus:border-accent"></textarea>
                        </label>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach (range(0, 3) as $option)
                                <label class="flex items-center gap-3 rounded-2xl bg-background p-3">
                                    <input type="radio" name="questions[0][correct_option]" value="{{ $option }}" required class="accent-[var(--color-accent)]">
                                    <input name="questions[0][options][]" required maxlength="140" aria-label="Pilihan {{ $option + 1 }}" placeholder="Pilihan {{ $option + 1 }}" class="min-h-10 min-w-0 flex-1 bg-transparent outline-none">
                                </label>
                            @endforeach
                        </div>
                        <label class="mt-5 block text-sm font-semibold">Penjelasan setelah menjawab <span class="font-normal text-foreground/50">(opsional)</span>
                            <input name="questions[0][explanation]" maxlength="500" class="mt-2 min-h-11 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                        </label>
                    </fieldset>
                </div>
                <template data-question-template>
                    <fieldset class="rounded-card bg-surface p-6 sm:p-8" data-question>
                        <legend class="font-bold">Pertanyaan <span data-question-number></span></legend>
                        <button type="button" data-remove-question class="float-right text-sm font-semibold text-danger">Hapus</button>
                        <label class="mt-3 block text-sm font-semibold">Teks pertanyaan
                            <textarea name="questions[__INDEX__][prompt]" required maxlength="500" rows="2" class="mt-2 w-full rounded-control border border-white/10 bg-background p-4 outline-none focus:border-accent"></textarea>
                        </label>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach (range(0, 3) as $option)
                                <label class="flex items-center gap-3 rounded-2xl bg-background p-3">
                                    <input type="radio" name="questions[__INDEX__][correct_option]" value="{{ $option }}" required class="accent-[var(--color-accent)]">
                                    <input name="questions[__INDEX__][options][]" required maxlength="140" aria-label="Pilihan {{ $option + 1 }}" placeholder="Pilihan {{ $option + 1 }}" class="min-h-10 min-w-0 flex-1 bg-transparent outline-none">
                                </label>
                            @endforeach
                        </div>
                        <label class="mt-5 block text-sm font-semibold">Penjelasan setelah menjawab <span class="font-normal text-foreground/50">(opsional)</span>
                            <input name="questions[__INDEX__][explanation]" maxlength="500" class="mt-2 min-h-11 w-full rounded-control border border-white/10 bg-background px-4 outline-none focus:border-accent">
                        </label>
                    </fieldset>
                </template>
                <button type="submit" class="min-h-14 w-full rounded-full bg-primary px-7 py-4 font-bold text-white hover:brightness-110">Simpan kuis</button>
            </form>
        </div>
</x-layouts.app>
