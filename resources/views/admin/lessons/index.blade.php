@extends('admin.layout')

@section('title', 'Kelola pelajaran')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="font-semibold text-accent">Tinjau sebelum tampil</p>
            <h1 class="mt-2 font-display text-3xl font-bold">Pelajaran</h1>
        </div>
        <a href="{{ route('admin.lessons.create') }}" class="rounded-full bg-primary px-5 py-3 font-bold text-white">Tambah pelajaran</a>
    </div>

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
                        <a href="{{ route('admin.lessons.edit', $lesson) }}" class="rounded-full border border-white/15 px-4 py-2 text-sm font-bold hover:bg-white/5">Ubah</a>
                        @if (! $lesson->is_published || ! $lesson->reviewed_at)
                            <form method="POST" action="{{ route('admin.lessons.publish', $lesson) }}">@csrf<button class="rounded-full bg-primary px-4 py-2 text-sm font-bold text-white">{{ $lesson->reviewed_at ? 'Terbitkan kembali' : 'Tinjau &amp; terbitkan' }}</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.lessons.archive', $lesson) }}">@csrf<button class="rounded-full border border-white/15 px-4 py-2 text-sm font-bold hover:bg-white/5">Arsipkan</button></form>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <p class="rounded-card bg-surface p-6 font-readable text-foreground/65">Belum ada pelajaran. Tambahkan pelajaran ke salah satu jalur.</p>
        @endforelse
    </div>
@endsection
