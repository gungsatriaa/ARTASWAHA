let ready;
let fail;

export const markBootReady = () => ready?.();
export const failBootLoader = () => fail?.();

export function startBootLoader() {
    const loader = document.querySelector('[data-react-loader]');
    if (!loader) return () => {};
    const root = document.documentElement;
    const app = document.getElementById('app');
    app?.setAttribute('inert', '');
    const bar = loader.querySelector('[data-loader-bar]');
    const progress = loader.querySelector('[data-loader-progress]');
    const percent = loader.querySelector('[data-loader-percent]');
    const skip = loader.querySelector('[data-loader-skip]');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const timers = new Set();
    let value = 0,
        disposed = false,
        completing = false;
    const later = (callback, delay) => {
        const id = setTimeout(() => {
            timers.delete(id);
            callback();
        }, delay);
        timers.add(id);
    };
    const update = (next) => {
        value = next;
        progress?.setAttribute('aria-valuenow', String(next));
        if (percent) percent.textContent = `${next}%`;
        if (bar) bar.style.transform = `scaleX(${next / 100})`;
    };
    const dispose = () => {
        disposed = true;
        timers.forEach(clearTimeout);
        timers.clear();
        skip?.removeEventListener('click', dispose);
        window.removeEventListener('pagehide', dispose);
        root.classList.remove('page-loading');
        app?.removeAttribute('inert');
        if (loader.contains(document.activeElement)) document.querySelector('main')?.focus({ preventScroll: true });
        ready = undefined;
        fail = undefined;
    };
    const advance = () => {
        if (disposed || completing) return;
        update(Math.min(94, value + Math.max(1, Math.round((95 - value) / 5))));
        later(advance, 90);
    };
    const minimum = new Promise((resolve) => later(resolve, reduced ? 0 : 850));
    const fonts = Promise.race([
        document.fonts?.ready ?? Promise.resolve(),
        new Promise((resolve) => later(resolve, 2000)),
    ]);
    ready = () => {
        Promise.all([minimum, fonts]).then(() => {
            if (disposed || completing) return;
            completing = true;
            update(100);
            later(
                () => {
                    loader.classList.add('is-leaving');
                    later(dispose, reduced ? 0 : 240);
                },
                reduced ? 100 : 450,
            );
        });
    };
    fail = () => {
        if (disposed || completing) return;
        completing = true;
        timers.forEach(clearTimeout);
        timers.clear();
        const message = loader.querySelector('[data-loader-message]');
        if (message) message.textContent = 'Tampilan belum dapat dimuat. Periksa koneksi lalu muat ulang.';
        if (skip) skip.textContent = 'Tutup pemberitahuan';
        later(dispose, 5000);
    };
    later(advance, 50);
    later(fail, 8000);
    skip?.addEventListener('click', dispose, { once: true });
    window.addEventListener('pagehide', dispose, { once: true });
    return dispose;
}
