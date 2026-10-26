@extends('admin.layout')
@section('title', 'Audio pembelajaran')
@section('content')
<h1 class="font-display text-3xl font-bold">Audio pembelajaran</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-foreground/60">Siapkan narasi modul dan soal, dengarkan hasilnya, lalu setujui sebelum digunakan pelajar. Rekaman pengajar dapat dipakai tanpa mesin TTS.</p>
<p class="mt-3 text-xs text-foreground/55">{{ $configured ? 'Konfigurasi TTS offline siap.' : 'TTS belum dikonfigurasi. Unggah rekaman tetap tersedia.' }}</p>
<nav class="my-5 flex flex-wrap gap-2" aria-label="Sumber audio">@foreach (['records' => 'Audio tersimpan', 'modules' => 'Pilih modul', 'questions' => 'Pilih soal kuis'] as $key => $label)<a href="{{ route('admin.audio.index', ['type' => $key]) }}" @if ($type === $key) aria-current="page" @endif @class(['inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-bold', 'border-accent/40 bg-accent/10 text-accent' => $type === $key, 'border-white/15 text-foreground/65' => $type !== $key])>{{ $label }}</a>@endforeach</nav>
<form method="GET" class="mb-5 flex flex-wrap items-end gap-3"><input type="hidden" name="type" value="{{ $type }}">@if ($type !== 'records')<label class="text-xs font-bold">Cari judul/pertanyaan<input name="q" maxlength="100" value="{{ request('q') }}" class="mt-2 block min-h-11 rounded-xl border border-white/15 bg-surface px-3 text-sm"></label>@else<label class="text-xs font-bold">Status<select name="status" class="mt-2 block min-h-11 rounded-xl border border-white/15 bg-surface px-3 text-sm"><option value="">Semua</option>@foreach (['draft' => 'Draf', 'queued' => 'Mengantre', 'processing' => 'Diproses', 'review' => 'Perlu ditinjau', 'ready' => 'Siap', 'stale' => 'Kedaluwarsa', 'failed' => 'Gagal', 'unavailable' => 'Belum tersedia'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label>@endif<button class="min-h-11 rounded-xl bg-accent px-4 text-sm font-bold text-background">Terapkan</button></form>
<div class="space-y-3">
@forelse ($items as $item)
    @php
        $content = $type === 'records' ? ($item->lesson ?? $item->question) : $item;
        $isLesson = $content instanceof \App\Models\Lesson;
        $link = $content ? route($isLesson ? 'admin.audio.lessons.edit' : 'admin.audio.questions.edit', $content) : null;
    @endphp
    <article class="flex flex-wrap items-center justify-between gap-4 rounded-card bg-surface p-5"><div class="min-w-0 flex-1"><p class="text-xs font-bold text-accent">{{ $isLesson ? 'Modul' : 'Soal kuis' }}</p><h2 class="mt-1 break-words font-bold">{{ $content?->title ?? $content?->prompt ?? 'Materi sudah tidak tersedia' }}</h2>@if ($type === 'records')<div class="mt-3"><x-job-status :status="$service->isCurrent($item) ? $item->status : 'stale'" /></div>@endif</div>@if ($link)<a href="{{ $link }}" class="inline-flex min-h-11 items-center rounded-full border border-accent/30 px-4 text-sm font-bold text-accent">Kelola audio</a>@endif</article>
@empty
    <div class="rounded-card bg-surface p-6"><p class="text-sm text-foreground/60">Belum ada data pada bagian ini.</p><a href="{{ route('admin.audio.index', ['type' => 'modules']) }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-accent">Pilih modul untuk menyiapkan audio →</a></div>
@endforelse
</div><div class="mt-5">{{ $items->links() }}</div>
@endsection
