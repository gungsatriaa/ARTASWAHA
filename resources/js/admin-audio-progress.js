const panel = document.querySelector('[data-audio-pending="1"]');

if (panel) {
    let timer;
    let controller;
    let finished = false;
    let suspended = false;
    let attempts = 0;
    let failures = 0;
    const label = panel.querySelector('[data-audio-progress-label]');
    const note = panel.querySelector('[data-audio-progress-note]');
    const bar = panel.querySelector('[data-audio-progress-bar]');
    const badge = panel.querySelector('[data-audio-status-badge] span');
    const fill = panel.querySelector('[data-audio-progress-fill]');
    const parts = panel.querySelector('[data-audio-parts]');
    const elapsed = panel.querySelector('[data-audio-elapsed]');
    const startedLabel = panel.querySelector('[data-audio-start]');
    const eta = panel.querySelector('[data-audio-eta]');
    let startedAt = Date.parse(panel.dataset.audioStartedAt);
    let serverOffset = Date.parse(panel.dataset.serverNow) - Date.now();
    let etaAt;
    let clock;
    const duration = seconds => {
        const value = Math.max(0, Math.floor(seconds));
        return [Math.floor(value / 3600), Math.floor(value / 60) % 60, value % 60].map(part => String(part).padStart(2, '0')).join(':');
    };
    const time = value => new Date(value).toLocaleTimeString('id-ID', { timeZone: panel.dataset.audioTimezone || 'Asia/Makassar', hour12: false });
    const updateClock = () => {
        const now = Date.now() + serverOffset;
        if (elapsed && Number.isFinite(startedAt)) elapsed.textContent = duration((now - startedAt) / 1000);
        if (eta && Number.isFinite(etaAt)) {
            eta.textContent = etaAt > now
                ? `Sekitar ${time(etaAt)} · ${duration((etaAt - now) / 1000)} lagi`
                : 'Lebih lama dari perkiraan; masih diproses.';
        }
    };
    const startClock = () => {
        if (clock || finished || suspended || document.visibilityState !== 'visible' || !Number.isFinite(startedAt)) return;
        updateClock();
        clock = window.setInterval(updateClock, 1000);
    };
    const pauseClock = () => { window.clearInterval(clock); clock = undefined; };
    const showProgress = (progress, serverNow) => {
        if (!progress) return;
        startedAt = Date.parse(progress.started_at);
        serverOffset = Date.parse(serverNow) - Date.now();
        if (startedLabel) startedLabel.textContent = Number.isFinite(startedAt) ? time(startedAt) : 'Menunggu worker';
        if (!Number.isFinite(startedAt)) {
            pauseClock();
            if (elapsed) elapsed.textContent = '—';
        }
        const stages = { loading_model: 'Memuat model suara', preparing_voice: 'Menyiapkan referensi suara', generating: 'Sedang membuat audio', finalizing: 'Memeriksa file audio' };
        if (stages[progress.stage]) label.textContent = stages[progress.stage];
        if (parts) parts.textContent = progress.total_parts > 0 ? `Bagian ${progress.completed_parts} dari ${progress.total_parts} selesai` : 'Menunggu informasi bagian narasi.';
        if (Number.isFinite(progress.percent) && ['generating', 'finalizing'].includes(progress.stage)) {
            const percent = Math.min(100, Math.max(0, progress.percent));
            bar.setAttribute('aria-valuenow', String(percent));
            bar.setAttribute('aria-valuemin', '0');
            bar.setAttribute('aria-valuemax', '100');
            if (fill) {
                fill.classList.remove('motion-safe:animate-pulse');
                fill.style.transform = `scaleX(${percent / 100})`;
            }
        } else {
            bar.removeAttribute('aria-valuenow');
            if (fill) {
                fill.classList.add('motion-safe:animate-pulse');
                fill.style.transform = 'scaleX(.33)';
            }
        }
        etaAt = Number.isFinite(progress.estimated_remaining_seconds) ? Date.now() + serverOffset + progress.estimated_remaining_seconds * 1000 : undefined;
        if (eta && !Number.isFinite(etaAt)) eta.textContent = progress.stage === 'finalizing' ? 'Memeriksa file audio.' : 'Estimasi menunggu hasil bagian berikutnya.';
        updateClock();
        startClock();
    };
    const stop = (message) => {
        finished = true;
        window.clearTimeout(timer);
        pauseClock();
        panel.setAttribute('aria-busy', 'false');
        bar.hidden = true;
        if (message) {
            label.textContent = 'Pembaruan otomatis dijeda';
            note.textContent = message;
        }
    };
    const schedule = () => {
        window.clearTimeout(timer);
        if (finished || suspended || controller || document.visibilityState !== 'visible') return;
        timer = window.setTimeout(poll, Math.min(3000 * (2 ** failures), 30000));
    };
    const poll = async () => {
        if (finished || suspended || controller || document.visibilityState !== 'visible') return;
        if (attempts >= 720) {
            stop('Pembaruan otomatis dijeda. Muat ulang status untuk memeriksa kembali; proses di server tetap berjalan.');
            return;
        }
        attempts += 1;
        controller = new AbortController();
        const timeout = window.setTimeout(() => controller?.abort(), 10000);
        try {
            const response = await fetch(panel.dataset.audioStatusUrl, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
                cache: 'no-store', signal: controller.signal,
            });
            if (finished || suspended) return;
            if ([401, 403, 404].includes(response.status)) {
                stop('Status tidak dapat diakses. Muat ulang halaman untuk memeriksa sesi dan materi.');
                return;
            }
            if (!response.ok) throw new Error('Status unavailable');
            const result = await response.json();
            if (finished || suspended) return;
            if (result.version !== panel.dataset.audioVersion) {
                stop('Narasi sudah diubah di sesi lain. Muat ulang halaman sebelum menyimpan kembali.');
                return;
            }
            failures = 0;
            if (['queued', 'processing'].includes(result.status)) {
                const queued = result.status === 'queued';
                label.textContent = queued ? 'Menunggu giliran pembuatan audio' : 'Sedang membuat audio';
                badge.textContent = queued ? 'Mengantre' : 'Diproses';
                bar.setAttribute('aria-valuetext', queued ? 'Mengantre' : 'Diproses');
                showProgress(result.progress, result.server_now);
                note.textContent = 'Status diperbarui otomatis. Durasi bergantung pada panjang narasi dan kemampuan server.';
            } else {
                if (typeof result.html !== 'string') throw new Error('Missing result');
                const template = document.createElement('template');
                template.innerHTML = result.html.trim();
                const replacement = template.content.firstElementChild;
                if (!replacement?.matches('[data-audio-status-panel]')) throw new Error('Invalid result');
                const restoreFocus = panel.contains(document.activeElement);
                stop();
                panel.replaceWith(replacement);
                if (restoreFocus) replacement.querySelector('h2')?.focus({ preventScroll: true });
                window.dispatchEvent(new Event('content-audio-updated'));
            }
        } catch {
            if (!finished && !suspended && document.visibilityState === 'visible') {
                failures += 1;
                note.textContent = 'Koneksi terputus sementara. Status akan diperiksa kembali otomatis.';
                if (failures >= 6) stop('Status belum dapat diperbarui. Muat ulang status untuk mencoba lagi; proses di server tetap berjalan.');
            }
        } finally {
            window.clearTimeout(timeout);
            controller = undefined;
            schedule();
        }
    };
    document.addEventListener('visibilitychange', () => {
        window.clearTimeout(timer);
        if (document.visibilityState !== 'visible') { pauseClock(); controller?.abort(); }
        else { startClock(); schedule(); }
    });
    window.addEventListener('pagehide', () => {
        suspended = true;
        pauseClock();
        window.clearTimeout(timer);
        controller?.abort();
    });
    window.addEventListener('pageshow', () => {
        suspended = false;
        startClock();
        schedule();
    });
    startClock();
    schedule();
}
