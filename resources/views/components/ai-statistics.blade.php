@props(['snapshot'])
<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    @foreach (['new_accounts' => 'Akun baru', 'new_interviews' => 'Interview tersimpan', 'completed_lessons' => 'Penyelesaian modul', 'quiz_answers' => 'Jawaban kuis'] as $key => $label)
        <div class="rounded-2xl border border-white/10 bg-surface p-5"><p class="text-xs font-semibold text-foreground/55">{{ $label }}</p><p class="mt-2 font-display text-3xl font-bold">{{ $snapshot[$key] ?? 0 }}</p></div>
    @endforeach
</div>
<p class="mt-3 text-xs leading-6 text-foreground/55">Angka dihitung dari database untuk periode ini. Jawaban benar: {{ $snapshot['correct_answers'] ?? 0 }} dari {{ $snapshot['quiz_answers'] ?? 0 }} jawaban{{ ($snapshot['quiz_answers'] ?? 0) > 0 ? ' ('.round(100 * $snapshot['correct_answers'] / $snapshot['quiz_answers'], 1).'%)' : ' · belum ada data akurasi' }}. Penyelesaian modul adalah jumlah aktivitas, bukan jumlah pelajar unik.</p>