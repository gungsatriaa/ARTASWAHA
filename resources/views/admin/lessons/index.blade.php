@extends('admin.layout')

@section('title', 'Kelola modul')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="font-semibold text-accent">Tinjau sebelum tampil</p>
            <h1 class="mt-2 font-display text-3xl font-bold">Modul</h1>
        </div>
        <a href="{{ route('admin.lessons.create') }}" class="rounded-full bg-primary px-5 py-3 font-bold text-white">Tambah modul</a>
    </div>

    <p class="mt-3 text-sm leading-6 text-foreground/60">Pilih jalur ? simpan draf ? preview & tinjau ? terbitkan. Audio bersifat opsional.</p>
    <form method="GET" class="mt-5 flex flex-wrap items-end gap-3"><label class="text-xs font-bold">Jalur belajar<select name="learning_path_id" class="mt-2 block min-h-11 rounded-xl border border-white/15 bg-surface px-3"><option value="">Semua jalur</option>@foreach ($learningPaths as $path)<option value="{{ $path->id }}" @selected((string) request('learning_path_id') === (string) $path->id)>{{ $path->title }}</option>@endforeach</select></label><label class="text-xs font-bold">Status<select name="status" class="mt-2 block min-h-11 rounded-xl border border-white/15 bg-surface px-3"><option value="">Semua</option>@foreach (['draft' => 'Draf', 'published' => 'Terbit', 'archived' => 'Arsip'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label><button class="min-h-11 rounded-xl bg-accent px-4 text-sm font-bold text-background">Terapkan</button></form>
    <div class="mt-7 grid gap-4">
        @forelse ($lessons as $lesson)
            <article class="rounded-card bg-surface p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-accent">{{ $lesson->learningPath->title }}</p>
                        <h2 class="mt-1 font-display text-xl font-bold">{{ $lesson->title }}</h2>
                        <p class="mt-1 font-readable text-sm text-foreground/65">{{ $lesson->summary }}</p>
                        <p class="mt-2 text-xs {{ $lesson->is_published && $lesson->reviewed_at ? 'text-success' : 'text-muted' }}">
                            @if ($lesson->is_published && $lesson->reviewed_at)
                                Terbit · ditinjau {{ $lesson->reviewed_at->format('d M Y') }}
                            @elseif ($lesson->reviewed_at)
                                Diarsipkan · terakhir ditinjau {{ $lesson->reviewed_at->format('d M Y') }}
                            @else
                                Draf · belum ditinjau
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.lessons.preview', $lesson) }}" class="inline-flex min-h-11 items-center rounded-full border border-accent/30 px-4 text-sm font-bold text-accent">Preview & tinjau</a>
                        <a href="{{ route('admin.audio.lessons.edit', $lesson) }}" class="inline-flex min-h-11 items-center rounded-full border border-white/15 px-4 text-sm font-bold">Audio</a>
                        <a href="{{ route('admin.lessons.edit', $lesson) }}" class="rounded-full border border-white/15 px-4 py-2 text-sm font-bold hover:bg-white/5">Ubah</a>
                        @if (! $lesson->is_published || ! $lesson->reviewed_at)
                        @else
                            <form method="POST" action="{{ route('admin.lessons.archive', $lesson) }}">@csrf<button class="rounded-full border border-white/15 px-4 py-2 text-sm font-bold hover:bg-white/5">Arsipkan</button></form>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <p class="rounded-card bg-surface p-6 font-readable text-foreground/65">Belum ada modul. Tambahkan modul ke salah satu jalur.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $lessons->links() }}</div>
@endsection
