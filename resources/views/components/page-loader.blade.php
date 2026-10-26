<script>
    try {
        if (!sessionStorage.getItem('artaswaha:welcome-seen')) {
            sessionStorage.setItem('artaswaha:welcome-seen', '1');
            document.documentElement.classList.add('page-loading');
            window.setTimeout(() => document.documentElement.classList.remove('page-loading'), 10000);
        }
    } catch {}
</script>
<div data-page-loader class="page-loader" role="status" aria-label="Menyiapkan ARTASWAHA">
    <div class="page-loader-content">
        <img data-load-critical src="{{ asset('image/maskot.webp') }}" alt="" width="112" height="112" class="page-loader-mascot" fetchpriority="high">
        <p class="mt-6 font-display text-2xl font-bold tracking-tight">ARTASWAHA</p>
        <p class="mt-2 text-sm text-foreground/60">Menyiapkan perjalanan belajarmu…</p>
        <div data-page-load-progress role="progressbar" aria-label="Kesiapan tampilan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" class="page-loader-track"><span data-page-load-bar></span></div>
        <p data-page-load-percent class="mt-2 text-xs tabular-nums text-foreground/55" aria-hidden="true">0%</p>
        <button type="button" data-page-loader-skip class="mt-5 min-h-11 rounded-full px-5 text-xs font-semibold text-foreground/55 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" onclick="document.documentElement.classList.remove('page-loading')">Langsung masuk</button>
    </div>
</div>
