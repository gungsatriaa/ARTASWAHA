@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="text-sm font-semibold text-accent">Ruang kendali pembelajaran</p>
            <h1 class="mt-1 font-display text-3xl font-bold tracking-tight sm:text-4xl">Ringkasan konten</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-foreground/60">Pantau materi yang sudah tersedia dan selesaikan draf yang menunggu peninjauan.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.learning-paths.create') }}" class="inline-flex min-h-11 items-center rounded-xl border border-white/15 px-4 text-sm font-bold text-foreground transition hover:border-accent/50 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">Buat jalur</a>
            <a href="{{ route('admin.lessons.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-accent px-4 text-sm font-bold text-background transition hover:brightness-110 focus-visible:outline-2 focus-visible:outline-accent">+ Tambah modul</a>
        </div>
    </div>

    <section class="mt-6 grid gap-3 sm:grid-cols-3" aria-label="Tindak lanjut AI dan audio">
        <a href="{{ route('admin.ai.index', ['tab' => 'interviews', 'status' => 'failed']) }}" class="rounded-card bg-surface p-4"><span class="text-xs text-foreground/55">Analisis interview gagal</span><span class="mt-2 block font-display text-2xl font-bold">{{ $aiTasks['failed_interviews'] }}</span></a>
        <a href="{{ route('admin.audio.index', ['status' => 'review']) }}" class="rounded-card bg-surface p-4"><span class="text-xs text-foreground/55">Audio menunggu tinjauan</span><span class="mt-2 block font-display text-2xl font-bold text-accent">{{ $aiTasks['audio_review'] }}</span></a>
        <a href="{{ route('admin.audio.index') }}" class="rounded-card bg-surface p-4"><span class="text-xs text-foreground/55">Audio gagal/kedaluwarsa</span><span class="mt-2 block font-display text-2xl font-bold">{{ $aiTasks['audio_failed'] }}</span></a>
    </section>
    <section class="mt-7 grid gap-3 sm:grid-cols-2 2xl:grid-cols-4" aria-label="Statistik modul">
        @foreach ([
            ['label' => 'Semua modul', 'value' => $lessonCounts->total ?? 0, 'note' => 'Seluruh materi tercatat', 'tone' => 'text-foreground'],
            ['label' => 'Modul terbit', 'value' => $lessonCounts->published ?? 0, 'note' => 'Dapat dilihat pengunjung', 'tone' => 'text-success'],
            ['label' => 'Perlu ditinjau', 'value' => $lessonCounts->drafts ?? 0, 'note' => 'Draf menunggu pemeriksaan', 'tone' => 'text-accent'],
            ['label' => 'Diarsipkan', 'value' => $lessonCounts->archived ?? 0, 'note' => 'Tidak tampil di situs', 'tone' => 'text-foreground/70'],
        ] as $stat)
            <article class="rounded-card bg-surface p-5 sm:p-6">
                <p class="text-sm font-semibold text-foreground/65">{{ $stat['label'] }}</p>
                <p class="mt-5 font-display text-4xl font-bold tabular-nums {{ $stat['tone'] }}">{{ number_format($stat['value']) }}</p>
                <p class="mt-2 text-xs text-foreground/45">{{ $stat['note'] }}</p>
            </article>
        @endforeach
    </section>

    <div class="mt-6 grid items-start gap-5 2xl:grid-cols-[minmax(0,1.65fr)_minmax(20rem,1fr)]">
        <div class="min-w-0 space-y-5">
            <section class="overflow-hidden rounded-card bg-surface" aria-labelledby="recent-lessons-heading">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-5 sm:px-6">
                    <div><h2 id="recent-lessons-heading" class="font-display text-xl font-bold">Modul terakhir diubah</h2><p class="mt-1 text-sm text-foreground/50">Lanjutkan pekerjaan dari perubahan terbaru.</p></div>
                    <a href="{{ route('admin.lessons.index') }}" class="text-sm font-bold text-accent hover:text-foreground focus-visible:outline-2 focus-visible:outline-accent">Lihat semua</a>
                </div>
                @forelse ($recentLessons as $lesson)
                    <article class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4 last:border-b-0 sm:px-6">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-foreground/50">{{ $lesson->learningPath->title }}</p>
                            <h3 class="mt-1 truncate font-bold">{{ $lesson->title }}</h3>
                            <p class="mt-1 text-xs text-foreground/45">Diubah {{ $lesson->updated_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-success/10 text-success' => $lesson->is_published && $lesson->reviewed_at, 'bg-accent/10 text-accent' => ! $lesson->reviewed_at, 'bg-white/5 text-foreground/55' => ! $lesson->is_published && $lesson->reviewed_at])>
                                @if ($lesson->is_published && $lesson->reviewed_at) Terbit @elseif ($lesson->reviewed_at) Arsip @else Draf @endif
                            </span>
                            <a href="{{ route('admin.lessons.edit', $lesson) }}" class="inline-flex min-h-10 items-center rounded-lg border border-white/15 px-3 text-xs font-bold transition hover:border-accent/50 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">Ubah</a>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-8"><p class="font-semibold">Belum ada modul pembelajaran.</p><a href="{{ route('admin.lessons.create') }}" class="mt-2 inline-block text-sm font-bold text-accent">Tambahkan modul pertama</a></div>
                @endforelse
            </section>
        </div>

        <div class="min-w-0 space-y-5">
            <section class="rounded-card bg-accent/10 p-5 sm:p-6" aria-labelledby="review-heading">
                <div class="flex items-start justify-between gap-4"><h2 id="review-heading" class="font-display text-xl font-bold">Antrean peninjauan</h2><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-accent/15 font-display text-lg font-bold text-accent">{{ $lessonCounts->drafts ?? 0 }}</span></div>
                <p class="mt-3 text-sm leading-6 text-foreground/65">{{ ($lessonCounts->drafts ?? 0) > 0 ? 'Periksa draf sebelum menerbitkannya ke pengunjung.' : 'Tidak ada draf yang menunggu. Semua perubahan sudah tertangani.' }}</p>
                <a href="{{ route('admin.lessons.index') }}" class="mt-5 inline-flex min-h-10 items-center rounded-xl border border-accent/30 px-4 text-sm font-bold text-accent transition hover:bg-accent/10 focus-visible:outline-2 focus-visible:outline-accent">Buka modul</a>
            </section>

            <section class="overflow-hidden rounded-card bg-surface" aria-labelledby="paths-heading">
                <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-5 sm:px-6"><div><h2 id="paths-heading" class="font-display text-xl font-bold">Jalur belajar</h2><p class="mt-1 text-sm text-foreground/50">{{ $learningPaths->count() }} jalur tersusun</p></div><a href="{{ route('admin.learning-paths.index') }}" class="text-sm font-bold text-accent hover:text-foreground focus-visible:outline-2 focus-visible:outline-accent">Kelola</a></div>
                @forelse ($learningPaths as $learningPath)
                    <a href="{{ route('admin.learning-paths.edit', $learningPath) }}" class="flex min-h-16 items-center justify-between gap-3 border-b border-white/10 px-5 py-3 transition last:border-b-0 hover:bg-background/70 focus-visible:outline-2 focus-visible:outline-accent sm:px-6">
                        <span class="min-w-0"><span class="block truncate text-sm font-bold">{{ $learningPath->title }}</span><span class="mt-1 block text-xs text-foreground/50">{{ $learningPath->group === 'paribasa' ? 'Paribasa Bali' : 'Aksara Bali' }} · {{ $learningPath->all_lessons_count }} modul</span></span>
                        <span @class(['shrink-0 text-xs font-bold', 'text-success' => $learningPath->is_published, 'text-foreground/45' => ! $learningPath->is_published])>{{ $learningPath->is_published ? 'Tampil' : 'Draf' }}</span>
                    </a>
                @empty
                    <div class="px-6 py-8"><p class="font-semibold">Belum ada jalur belajar.</p><a href="{{ route('admin.learning-paths.create') }}" class="mt-2 inline-block text-sm font-bold text-accent">Buat jalur pertama</a></div>
                @endforelse
            </section>
        </div>
    </div>
@endsection
