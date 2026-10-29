<script>
    document.documentElement.classList.add('page-loading');
    window.setTimeout(() => {
        document.documentElement.classList.remove('page-loading');
        document.getElementById('app')?.removeAttribute('inert');
    }, 10000);
</script>
<div data-react-loader class="page-loader" role="status" aria-live="polite" aria-atomic="true">
    <div class="page-loader-content">
        <div class="page-loader-art" aria-hidden="true">
            <img src="{{ asset('image/maskot.webp') }}" alt="" width="176" height="176" class="page-loader-mascot" fetchpriority="high">
        </div>
        <p class="mt-5 font-display text-2xl font-bold tracking-tight sm:text-3xl">ARTASWAHA</p>
        <p class="mt-2 text-sm leading-6 text-foreground/55">Bahasa Bali jadi seru dipelajari.</p>
        <div class="mt-7 inline-flex max-w-full items-center justify-center gap-3 rounded-full bg-primary/20 px-6 py-3.5 text-sm font-semibold text-accent">
            <span data-loader-spinner class="page-loader-spinner" aria-hidden="true"></span>
            <p data-loader-message class="text-left leading-6">Loading Data</p>
        </div>
    </div>
</div>
<noscript><style>.page-loader { display: none !important; } .page-loading { overflow: auto; }</style></noscript>
