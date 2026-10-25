@extends('admin.layout')

@section('title', 'Kelola jalur belajar')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="font-semibold text-accent">Konten pembelajaran</p>
            <h1 class="mt-2 font-display text-3xl font-bold">Jalur belajar</h1>
        </div>
        <a href="{{ route('admin.learning-paths.create') }}" class="rounded-full bg-primary px-5 py-3 font-bold text-white">Tambah jalur</a>
    </div>

    <div class="mt-7 grid gap-4">
        @forelse ($learningPaths as $learningPath)
            <article class="flex flex-wrap items-center justify-between gap-4 rounded-card bg-surface p-5 sm:p-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-accent">{{ $learningPath->group === 'paribasa' ? 'Paribasa Bali' : 'Aksara Bali' }} · {{ $learningPath->all_lessons_count }} modul</p>
                    <h2 class="mt-1 font-display text-xl font-bold">{{ $learningPath->title }}</h2>
                    <p class="mt-1 font-readable text-sm text-foreground/65">{{ $learningPath->description }}</p>
                    <p class="mt-2 text-xs {{ $learningPath->is_published ? 'text-success' : 'text-muted' }}">{{ $learningPath->is_published ? 'Tampil di situs' : 'Diarsipkan' }}</p>
                </div>
                <a href="{{ route('admin.lessons.create', ['learning_path_id' => $learningPath->id]) }}" class="inline-flex min-h-11 items-center rounded-full border border-accent/30 px-4 text-sm font-bold text-accent">Tambah modul</a>
                <a href="{{ route('admin.lessons.index', ['learning_path_id' => $learningPath->id]) }}" class="inline-flex min-h-11 items-center px-4 text-sm font-bold text-accent">Lihat modul</a>
                <a href="{{ route('admin.learning-paths.edit', $learningPath) }}" class="rounded-full border border-white/15 px-5 py-2.5 text-sm font-bold hover:bg-white/5">Ubah jalur</a>
            </article>
        @empty
            <p class="rounded-card bg-surface p-6 font-readable text-foreground/65">Belum ada jalur belajar.</p>
        @endforelse
    </div>
@endsection
