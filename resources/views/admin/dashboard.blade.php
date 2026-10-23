@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <section class="overflow-hidden rounded-[2rem] border border-white/10 bg-surface">
        <div class="grid gap-8 p-6 sm:p-9 lg:grid-cols-[1fr_auto] lg:items-end lg:p-11">
            <div class="max-w-2xl">
                <p class="flex items-center gap-2 font-semibold text-accent">
                    <span class="size-2 rounded-full bg-accent" aria-hidden="true"></span>
                    Ruang kendali pembelajaran
                </p>
                <h1 class="mt-4 font-display text-3xl font-bold tracking-tight sm:text-4xl">Materi Bali, tertata dan siap dipelajari.</h1>
                <p class="mt-3 max-w-xl font-readable leading-7 text-foreground/65">Pantau modul yang sudah terbit, rapikan draf, dan lanjutkan pengelolaan materi dari satu tempat.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.lessons.create') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-primary px-5 font-bold text-white transition hover:bg-primary/85 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-5" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah modul
                </a>
                <a href="{{ route('admin.learning-paths.create') }}" class="inline-flex min-h-12 items-center justify-center rounded-full border border-white/15 px-5 font-bold transition hover:border-accent/60 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Buat jalur</a>
            </div>
        </div>
        <div class="grid border-t border-white/10 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['label' => 'Semua modul', 'value' => $lessonCounts->total ?? 0, 'tone' => 'text-foreground'], ['label' => 'Modul terbit', 'value' => $lessonCounts->published ?? 0, 'tone' => 'text-success'], ['label' => 'Perlu ditinjau', 'value' => $lessonCounts->drafts ?? 0, 'tone' => 'text-accent'], ['label' => 'Diarsipkan', 'value' => $lessonCounts->archived ?? 0, 'tone' => 'text-foreground/60']] as $stat)
                <div class="flex items-center justify-between gap-4 border-b border-white/10 px-6 py-5 last:border-b-0 sm:px-8 sm:py-6 xl:border-b-0 xl:border-r xl:last:border-r-0">
                    <span class="font-semibold text-foreground/65">{{ $stat['label'] }}</span>
                    <span class="font-display text-3xl font-bold {{ $stat['tone'] }}">{{ $stat['value'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-10" aria-labelledby="paths-heading">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="paths-heading" class="font-display text-2xl font-bold">Jalur belajar</h2>
                <p class="mt-1 text-sm text-foreground/60">Atur kelompok materi dan urutan belajar.</p>
            </div>
            <a href="{{ route('admin.learning-paths.index') }}" class="text-sm font-bold text-accent hover:text-accent/80">Kelola semua jalur</a>
        </div>
        @if ($learningPaths->isEmpty())
            <div class="mt-5 rounded-2xl border border-dashed border-white/15 p-6 text-foreground/65">
                <p class="font-semibold text-foreground">Belum ada jalur belajar.</p>
                <a href="{{ route('admin.learning-paths.create') }}" class="mt-2 inline-block font-bold text-accent">Buat jalur pertama</a>
            </div>
        @else
            <div class="mt-5 divide-y divide-white/10 border-y border-white/10">
                @foreach ($learningPaths as $learningPath)
                    <a href="{{ route('admin.learning-paths.edit', $learningPath) }}" class="group flex flex-wrap items-center justify-between gap-4 py-5 focus-visible:outline-2 focus-visible:outline-accent">
                        <div class="flex min-w-0 items-center gap-4">
                            <span @class(['grid size-11 shrink-0 place-items-center rounded-2xl', 'bg-accent/10 text-accent' => $learningPath->group === 'paribasa', 'bg-primary/20 text-accent' => $learningPath->group !== 'paribasa'])>
                                @if ($learningPath->group === 'paribasa')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/></svg>
                                @else
                                    <span class="font-display text-lg font-bold" aria-hidden="true">Aa</span>
                                @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-display text-lg font-bold group-hover:text-accent">{{ $learningPath->title }}</span>
                                <span class="mt-1 block text-sm text-foreground/55">{{ $learningPath->group === 'paribasa' ? 'Paribasa Bali' : 'Aksara Bali' }} · {{ $learningPath->all_lessons_count }} modul</span>
                            </span>
                        </div>
                        <span class="flex items-center gap-4 text-sm">
                            <span @class(['font-semibold', 'text-success' => $learningPath->is_published, 'text-foreground/45' => ! $learningPath->is_published])>{{ $learningPath->is_published ? 'Tampil di situs' : 'Tidak ditampilkan' }}</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4 text-foreground/40 group-hover:text-accent" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-11" aria-labelledby="recent-lessons-heading">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="recent-lessons-heading" class="font-display text-2xl font-bold">Modul terakhir diubah</h2>
                <p class="mt-1 text-sm text-foreground/60">Periksa draf dan lanjutkan pekerjaan yang tertunda.</p>
            </div>
            <a href="{{ route('admin.lessons.index') }}" class="text-sm font-bold text-accent hover:text-accent/80">Kelola semua modul</a>
        </div>
        @if ($recentLessons->isEmpty())
            <div class="mt-5 rounded-2xl border border-dashed border-white/15 p-6 text-foreground/65">
                <p class="font-semibold text-foreground">Belum ada modul pembelajaran.</p>
                <a href="{{ route('admin.lessons.create') }}" class="mt-2 inline-block font-bold text-accent">Tambahkan modul pertama</a>
            </div>
        @else
            <div class="mt-5 overflow-hidden rounded-2xl border border-white/10 bg-surface">
                @foreach ($recentLessons as $lesson)
                    <article class="flex flex-wrap items-center justify-between gap-4 border-b border-white/10 px-5 py-4 last:border-b-0 sm:px-6">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-accent">{{ $lesson->learningPath->title }}</p>
                            <h3 class="mt-1 truncate font-bold">{{ $lesson->title }}</h3>
                            <p class="mt-1 line-clamp-1 font-readable text-sm text-foreground/55">{{ $lesson->summary }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-success/10 text-success' => $lesson->is_published && $lesson->reviewed_at, 'bg-accent/10 text-accent' => ! $lesson->reviewed_at, 'bg-white/5 text-foreground/55' => ! $lesson->is_published && $lesson->reviewed_at])>
                                @if ($lesson->is_published && $lesson->reviewed_at) Terbit
                                @elseif ($lesson->reviewed_at) Arsip
                                @else Draf @endif
                            </span>
                            <a href="{{ route('admin.lessons.edit', $lesson) }}" class="rounded-full border border-white/15 px-4 py-2 text-sm font-bold transition hover:border-accent/60 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">Ubah</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
