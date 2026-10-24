@extends('admin.layout')

@section('title', 'Analitik')
@section('section', 'Wawasan penggunaan')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-bold text-accent">Data nyata, tanpa estimasi kunjungan</p>
            <h1 class="mt-1 font-display text-3xl font-bold tracking-tight sm:text-4xl">Analitik ARTASWAHA</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-foreground/60">Aktivitas belajar dan kuis dari data yang masih tersimpan. Grafik harian memakai waktu Bali (WITA).</p>
        </div>
        <span class="rounded-full border border-white/10 bg-surface px-4 py-2 text-xs font-bold text-foreground/60">Diperbarui saat halaman dibuka</span>
    </div>

    <section class="mt-7 overflow-hidden rounded-2xl border border-primary/40 bg-[linear-gradient(115deg,#102a2b_0%,#101113_55%)] p-6 sm:p-8" aria-labelledby="online-heading" data-online-url="{{ route('admin.analytics.online') }}">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div>
                <p class="flex items-center gap-2 text-xs font-bold uppercase tracking-[.16em] text-success"><span class="size-2 rounded-full bg-success"></span> Aktivitas langsung</p>
                <h2 id="online-heading" class="mt-3 font-display text-2xl font-bold">Pengunjung aktif</h2>
                <p class="mt-1 text-sm text-foreground/60">Browser yang mengirim aktivitas dalam 2 menit terakhir; diperbarui tiap 15 detik.</p>
            </div>
            <p class="font-display text-6xl font-bold tabular-nums text-accent" data-online-total aria-live="polite">—</p>
        </div>
        <div class="mt-7 flex flex-wrap gap-3 text-sm">
            <span class="rounded-xl border border-white/10 bg-background/70 px-4 py-2">Akun <strong class="ml-2 tabular-nums text-foreground" data-online-accounts>—</strong></span>
            <span class="rounded-xl border border-white/10 bg-background/70 px-4 py-2">Tanpa akun <strong class="ml-2 tabular-nums text-foreground" data-online-anonymous>—</strong></span>
            <span class="self-center text-xs text-foreground/45" data-online-status>Memuat aktivitas…</span>
        </div>
    </section>

    <section class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Angka utama">
        @foreach ([
            ['Akun terdaftar', $metrics['users'], 'Seluruh akun tersimpan'],
            ['Profil anonim', $metrics['anonymousProfiles'], 'Profil belajar tanpa akun'],
            ['Pelajaran selesai', $metrics['completedLessons'], $metrics['learners'].' pelajar unik'],
            ['Kuis dibuat', $metrics['quizzes'], 'Kuis yang masih tersimpan'],
        ] as [$label, $value, $note])
            <article class="rounded-2xl border border-white/10 bg-surface p-5 sm:p-6">
                <p class="text-sm font-semibold text-foreground/60">{{ $label }}</p>
                <p class="mt-4 font-display text-4xl font-bold tabular-nums">{{ number_format($value) }}</p>
                <p class="mt-2 text-xs text-foreground/45">{{ $note }}</p>
            </article>
        @endforeach
    </section>

    <div class="mt-6 grid gap-5 2xl:grid-cols-[minmax(0,1.7fr)_minmax(19rem,1fr)]">
        <section class="min-w-0 rounded-2xl border border-white/10 bg-surface p-5 sm:p-6" aria-labelledby="learning-chart-heading">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><h2 id="learning-chart-heading" class="font-display text-xl font-bold">Aktivitas pembelajaran</h2><p class="mt-1 text-sm text-foreground/50">Penyelesaian pelajaran dan jawaban kuis · 14 hari terakhir</p></div><span class="rounded-full bg-primary/20 px-3 py-1 text-xs font-bold text-[#58d7d2]">Tren harian</span></div>
            <div class="mt-6 h-72"><canvas id="learning-activity-chart" role="img" aria-label="Grafik jumlah pelajaran selesai dan jawaban kuis per hari dalam 14 hari terakhir"></canvas></div>
        </section>

        <section class="min-w-0 rounded-2xl border border-white/10 bg-surface p-5 sm:p-6" aria-labelledby="registration-chart-heading">
            <h2 id="registration-chart-heading" class="font-display text-xl font-bold">Akun baru</h2>
            <p class="mt-1 text-sm text-foreground/50">Pendaftaran per hari · 14 hari terakhir</p>
            <div class="mt-6 h-72"><canvas id="registration-chart" role="img" aria-label="Grafik pendaftaran akun per hari dalam 14 hari terakhir"></canvas></div>
        </section>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <section class="rounded-2xl border border-white/10 bg-surface p-5 sm:p-6" aria-labelledby="quiz-heading">
            <h2 id="quiz-heading" class="font-display text-xl font-bold">Aktivitas kuis</h2>
            <p class="mt-1 text-sm text-foreground/50">Hanya room dan jawaban yang masih tersimpan.</p>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-background p-4"><p class="text-xs text-foreground/50">Room tersimpan</p><p class="mt-2 font-display text-3xl font-bold tabular-nums">{{ number_format($metrics['rooms']) }}</p></div>
                <div class="rounded-xl bg-background p-4"><p class="text-xs text-foreground/50">Jawaban tercatat</p><p class="mt-2 font-display text-3xl font-bold tabular-nums">{{ number_format($metrics['answers']) }}</p></div>
            </div>
            <div class="mt-5 flex items-baseline gap-3 border-t border-white/10 pt-5"><span class="font-display text-4xl font-bold text-accent">{{ $metrics['accuracy'] === null ? '—' : $metrics['accuracy'].'%' }}</span><span class="text-sm text-foreground/60">jawaban benar dari semua jawaban tersimpan</span></div>
        </section>

        <section class="rounded-2xl border border-white/10 bg-surface p-5 sm:p-6" aria-labelledby="top-paths-heading">
            <h2 id="top-paths-heading" class="font-display text-xl font-bold">Jalur paling banyak diselesaikan</h2>
            <p class="mt-1 text-sm text-foreground/50">Jumlah penyelesaian pelajaran, bukan jumlah kunjungan.</p>
            <ol class="mt-5 space-y-3">
                @forelse ($topPaths as $path)
                    <li class="flex items-center justify-between gap-3 rounded-xl bg-background px-4 py-3"><span class="min-w-0 truncate text-sm font-semibold">{{ $path->title }}</span><span class="shrink-0 text-sm font-bold tabular-nums text-accent">{{ number_format($path->completions) }}</span></li>
                @empty
                    <li class="rounded-xl bg-background px-4 py-5 text-sm text-foreground/55">Belum ada pelajaran yang diselesaikan.</li>
                @endforelse
            </ol>
        </section>
    </div>

    <p class="mt-6 text-xs leading-5 text-foreground/45">Hitungan online mendekati waktu nyata, bukan daftar koneksi WebSocket. Browser yang menutup halaman bisa tetap dihitung hingga 2 menit; JavaScript atau cookie yang diblokir tidak terhitung. Room yang dihapus beserta jawabannya tidak masuk statistik historis.</p>
    <script type="application/json" id="analytics-chart-data">@json($chart)</script>
@endsection
