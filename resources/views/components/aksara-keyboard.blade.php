@props(['autoOpen' => false, 'scratch' => true])
<div {{ $attributes->class(['aksara-workspace', 'aksara-editor-keyboard' => ! $scratch]) }} @if ($scratch) data-aksara-scope @endif>
    <details data-aksara-keyboard class="aksara-keyboard mt-3" @if ($autoOpen) open @endif>
        <summary class="flex min-h-11 cursor-pointer items-center justify-between gap-2 rounded-control border border-white/15 px-4 py-2 text-sm font-bold text-accent focus-visible:outline-2 focus-visible:outline-accent">
            <span><span aria-hidden="true">⌨</span> Keyboard aksara Bali</span><span class="aksara-disclosure" aria-hidden="true">⌄</span>
        </summary>
        <div class="aksara-keyboard-body">
            @if ($scratch)
                <label class="mb-2 block text-sm font-bold">Coba tulis aksara
                    <textarea data-aksara-input data-aksara-label="Coretan aksara" rows="2" maxlength="2000" spellcheck="false" autocapitalize="off" autocomplete="off" class="aksara-writing mt-2 w-full rounded-control border border-white/15 bg-background px-3 py-2" placeholder="Tulis aksara di sini…"></textarea>
                </label>
                <p class="mb-3 text-xs leading-5 text-foreground/60">Area latihan pribadi. Untuk menjawab soal, tetap pilih salah satu pilihan jawaban.</p>
            @endif
            <p data-aksara-target class="mb-2 truncate text-xs text-foreground/65">{{ $scratch ? 'Coretan aksara' : 'Pilih kolom materi, pertanyaan, atau pilihan jawaban.' }}</p>
            <div class="aksara-modes" role="group" aria-label="Kelompok aksara">
                <button type="button" data-aksara-mode="dasar" aria-pressed="true">Aksara dasar</button>
                <button type="button" data-aksara-mode="pasangan" aria-pressed="false">Gantungan</button>
                <button type="button" data-aksara-mode="pangangge" aria-pressed="false">Pangangge</button>
            </div>
            <div data-aksara-keys class="aksara-keys" role="group" aria-label="Tombol aksara dasar"></div>
            <div class="aksara-actions" role="group" aria-label="Kontrol tulisan">
                <button type="button" data-aksara-action="left" aria-label="Geser kursor ke kiri">←</button>
                <button type="button" data-aksara-action="right" aria-label="Geser kursor ke kanan">→</button>
                <button type="button" data-aksara-action="backspace" aria-label="Hapus karakter sebelum kursor">⌫</button>
                <button type="button" data-aksara-action="space" class="aksara-space">Spasi</button>
                <button type="button" data-aksara-action="enter" aria-label="Baris baru">↵</button>
            </div>
            <p data-aksara-status role="status" aria-live="polite" class="mt-2 min-h-5 text-xs leading-5 text-foreground/65">Pilih aksara dasar, lalu pangangge atau gantungan. Geser kursor untuk menyisipkan aksara di tengah tulisan.</p>
            <noscript><p class="text-sm text-danger">Aktifkan JavaScript untuk memakai keyboard aksara.</p></noscript>
        </div>
    </details>
</div>
