const analysisPanel = document.querySelector('[data-learner-analysis-url]');

if (analysisPanel) {
    let timer;
    let controller;
    let stopped = false;
    let attempts = 0;

    const stop = () => {
        stopped = true;
        window.clearTimeout(timer);
        controller?.abort();
        document.removeEventListener('visibilitychange', onVisibilityChange);
    };
    const schedule = () => {
        if (stopped || controller || document.visibilityState !== 'visible') return;
        window.clearTimeout(timer);
        timer = window.setTimeout(poll, Math.min(5000 + attempts * 2000, 30000));
    };
    const poll = async () => {
        if (stopped || document.visibilityState !== 'visible') return;
        if (attempts >= 20) {
            const status = analysisPanel.querySelector('[data-analysis-status]');
            if (status) status.textContent = 'Analisis masih menunggu. Kamu bisa belajar sekarang dan memeriksa lagi nanti.';
            stop();
            return;
        }
        attempts += 1;
        controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(analysisPanel.dataset.learnerAnalysisUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                signal: controller.signal,
            });
            if (response.status === 401 || response.status === 403 || response.status === 404 || response.status === 429) {
                stop();
                return;
            }
            if (!response.ok) throw new Error('Status unavailable');
            const result = await response.json();
            if (stopped) return;
            if (!['queued', 'processing'].includes(result.analysis?.status)) {
                if (typeof result.html === 'string') {
                    const template = document.createElement('template');
                    template.innerHTML = result.html.trim();
                    const replacement = template.content.firstElementChild;
                    if (replacement?.id === 'analisis') {
                        const restoreFocus = analysisPanel.contains(document.activeElement);
                        analysisPanel.replaceWith(replacement);
                        if (restoreFocus) replacement.querySelector('h2')?.focus({ preventScroll: true });
                    }
                }
                stop();
                return;
            }
        } catch {
            // A temporary connection failure waits for the next bounded status check.
        } finally {
            window.clearTimeout(timeout);
            controller = undefined;
        }
        schedule();
    };
    const onVisibilityChange = () => {
        window.clearTimeout(timer);
        if (document.visibilityState === 'visible') schedule();
    };
    document.addEventListener('visibilitychange', onVisibilityChange);
    window.addEventListener('pagehide', stop, { once: true });
    schedule();
}
