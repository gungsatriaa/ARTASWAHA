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
    const stop = (message) => {
        finished = true;
        window.clearTimeout(timer);
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
        if (attempts >= 200) {
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
        if (document.visibilityState !== 'visible') controller?.abort();
        else schedule();
    });
    window.addEventListener('pagehide', () => {
        suspended = true;
        window.clearTimeout(timer);
        controller?.abort();
    });
    window.addEventListener('pageshow', () => {
        suspended = false;
        schedule();
    });
    schedule();
}
