<script>
    document.documentElement.classList.add('page-loading');
    window.setTimeout(() => {
        document.documentElement.classList.remove('page-loading');
        document.getElementById('app')?.removeAttribute('inert');
    }, 10000);
</script>
<div data-react-loader class="page-loader" role="status" aria-label="Menyiapkan ARTASWAHA">
    <div class="page-loader-content">
        <img src="{{ asset('image/maskot.webp') }}" alt="" width="112" height="112" class="page-loader-mascot" fetchpriority="high">
        <p class="mt-6 font-display text-2xl font-bold tracking-tight">ARTASWAHA</p>
        <p data-loader-message class="mt-2 text-sm text-foreground/60">Menyiapkan perjalanan belajarmu…</p>
        <div data-loader-progress role="progressbar" aria-label="Kesiapan tampilan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" class="page-loader-track"><span data-loader-bar></span></div>
        <p data-loader-percent class="mt-3 text-xs tabular-nums text-foreground/55" aria-hidden="true">0%</p>
        <button type="button" data-loader-skip class="mt-5 min-h-11 rounded-full px-5 text-xs font-semibold text-foreground/55 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" onclick="document.documentElement.classList.remove('page-loading')">Langsung masuk</button>
    </div>
</div>
<noscript><style>.page-loader { display: none !important; } .page-loading { overflow: auto; }</style></noscript>
