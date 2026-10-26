@php($pending = $current && in_array($audio->status, ['queued', 'processing'], true))
@php($progress = $progress ?? app(\App\Services\ContentAudioService::class)->generationProgress($audio))
<section class="mt-5 rounded-card border border-white/10 bg-surface p-5" data-audio-status-panel data-audio-status-url="{{ route('admin.audio.status', $audio) }}" data-audio-version="{{ $audio->version }}" data-audio-pending="{{ $pending ? '1' : '0' }}" data-audio-started-at="{{ $progress['started_at'] ?? '' }}" data-audio-timezone="{{ config('app.timezone') }}" data-server-now="{{ now()->toISOString() }}" aria-busy="{{ $pending ? 'true' : 'false' }}">
    <div data-audio-status-badge><x-job-status :status="$current ? $audio->status : 'stale'" /></div>
    @if ($pending)
        <p class="mt-3 text-sm font-bold" role="status" aria-live="polite" data-audio-progress-label>{{ $audio->status === 'queued' ? 'Menunggu giliran pembuatan audio' : 'Sedang membuat audio' }}</p>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-background" role="progressbar" aria-label="Pembuatan audio" aria-valuetext="{{ $audio->status === 'queued' ? 'Mengantre' : 'Diproses' }}" data-audio-progress-bar><div data-audio-progress-fill class="h-full w-full origin-left rounded-full bg-accent motion-safe:animate-pulse motion-safe:transition-transform motion-safe:duration-300" style="transform:scaleX(.33)"></div></div>
        <p class="mt-2 text-xs text-foreground/60" data-audio-parts>Menunggu informasi bagian narasi.</p>
        <p class="mt-3 text-xs leading-6 text-foreground/60" data-audio-progress-note>Status diperbarui otomatis. Durasi bergantung pada panjang narasi dan kemampuan server.</p>
        <a href="{{ url()->current() }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-accent" data-audio-refresh>Muat ulang status</a>
    @endif
    @if ($pending || $progress['started_at'])
        <dl class="mt-4 grid gap-3 text-xs text-foreground/65 sm:grid-cols-3">
            <div><dt>Mulai diproses</dt><dd class="mt-1 font-bold text-foreground" data-audio-start>{{ $audio->status !== 'queued' ? ($audio->generation_started_at?->format('H:i:s') ?? 'Menunggu worker') : 'Menunggu worker' }}</dd></div>
            <div><dt>Durasi proses</dt><dd class="mt-1 font-bold text-foreground" data-audio-elapsed>{{ $progress['elapsed_seconds'] !== null ? gmdate('H:i:s', $progress['elapsed_seconds']) : '—' }}</dd></div>
            @if ($pending)<div><dt>Perkiraan selesai</dt><dd class="mt-1 font-bold text-foreground" data-audio-eta>Menunggu bagian pertama selesai.</dd></div>
            @else<div><dt>Selesai diproses</dt><dd class="mt-1 font-bold text-foreground">{{ $audio->generation_finished_at?->format('H:i:s') ?? '—' }}</dd></div>@endif
        </dl>
    @endif
    @if ($audio->status === 'failed')<p class="mt-3 text-sm text-danger">Pembuatan audio gagal. Coba buat audio kembali.</p>@endif
    @if ($audio->status === 'unavailable')<p class="mt-3 text-sm text-foreground/65">Mesin TTS belum tersedia. Periksa konfigurasi sebelum mencoba kembali.</p>@endif
    @if (! $current)<p class="mt-3 text-sm text-foreground/65">Materi sudah berubah. Audio lama tidak akan digunakan pelajar. Simpan narasi dan buat ulang audio.</p>@endif
    @if ($audio->path)
        <h2 class="mt-4 font-display text-lg font-bold" tabindex="-1">Dengarkan sebelum menyetujui</h2>
        <div class="mt-4"><x-content-audio-player :src="route('content-audio.show', $audio)" label="Preview audio" /></div>
        @if ($audio->status === 'review' && $current)
            <form method="POST" action="{{ route('admin.audio.approve', $audio) }}" class="mt-4">@csrf<input type="hidden" name="version" value="{{ $audio->version }}"><button class="min-h-11 rounded-full bg-accent px-5 text-sm font-bold text-background">Sudah diperiksa, setujui audio</button></form>
        @endif
    @endif
</section>
