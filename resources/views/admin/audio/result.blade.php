@php($pending = $current && in_array($audio->status, ['queued', 'processing'], true))
<section class="mt-5 rounded-card border border-white/10 bg-surface p-5" data-audio-status-panel data-audio-status-url="{{ route('admin.audio.status', $audio) }}" data-audio-version="{{ $audio->version }}" data-audio-pending="{{ $pending ? '1' : '0' }}" aria-busy="{{ $pending ? 'true' : 'false' }}">
    <div data-audio-status-badge><x-job-status :status="$current ? $audio->status : 'stale'" /></div>
    @if ($pending)
        <p class="mt-3 text-sm font-bold" role="status" aria-live="polite" data-audio-progress-label>{{ $audio->status === 'queued' ? 'Menunggu giliran pembuatan audio' : 'Sedang membuat audio' }}</p>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-background" role="progressbar" aria-label="Pembuatan audio" aria-valuetext="{{ $audio->status === 'queued' ? 'Mengantre' : 'Diproses' }}" data-audio-progress-bar><div class="h-full w-1/3 rounded-full bg-accent motion-safe:animate-pulse"></div></div>
        <p class="mt-3 text-xs leading-6 text-foreground/60" data-audio-progress-note>Status diperbarui otomatis. Durasi bergantung pada panjang narasi dan kemampuan server.</p>
        <a href="{{ url()->current() }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-accent" data-audio-refresh>Muat ulang status</a>
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
