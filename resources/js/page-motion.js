const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const loader = document.querySelector('[data-page-loader]');

const pageReady = new Promise((resolve) => {
    if (!loader || !document.documentElement.classList.contains('page-loading')) {
        resolve();
        return;
    }

    let finished = false;
    let completing = false;
    let painted = false;
    let completed = 0;
    let percentage = 0;
    const cleanup = [];
    const timers = [];
    const schedule = (callback, delay) => timers.push(window.setTimeout(callback, delay));
    const updateProgress = (value) => {
        percentage = value;
        loader.querySelector('[data-page-load-progress]')?.setAttribute('aria-valuenow', String(value));
        const label = loader.querySelector('[data-page-load-percent]');
        if (label) label.textContent = `${value}%`;
        const bar = loader.querySelector('[data-page-load-bar]');
        if (painted && bar) bar.style.transform = `scaleX(${value / 100})`;
    };
    const finish = () => {
        if (finished) return;
        finished = true;
        timers.forEach(timer => window.clearTimeout(timer));
        cleanup.forEach(callback => callback());
        document.documentElement.classList.remove('page-loading');
        resolve();
    };
    const completeLoading = () => {
        if (finished || completing) return;
        completing = true;
        updateProgress(100);
        schedule(() => {
            loader.classList.add('is-leaving');
            schedule(finish, reducedMotion.matches ? 0 : 240);
        }, reducedMotion.matches ? 0 : 450);
    };
    schedule(completeLoading, 6000);
    const minimum = new Promise(ready => schedule(ready, reducedMotion.matches ? 0 : 1200));
    window.requestAnimationFrame(() => window.requestAnimationFrame(() => {
        if (finished) return;
        painted = true;
        updateProgress(percentage);
    }));
    const images = [...document.querySelectorAll('[data-load-critical]')]
        .filter(image => image.getClientRects().length > 0);
    const tasks = images.map(image => new Promise((settled) => {
        if (image.complete) { settled(); return; }
        const done = () => { remove(); settled(); };
        const remove = () => {
            image.removeEventListener('load', done);
            image.removeEventListener('error', done);
        };
        image.addEventListener('load', done, { once: true });
        image.addEventListener('error', done, { once: true });
        cleanup.push(done);
    }));
    if (document.fonts?.ready) tasks.push(document.fonts.ready);

    const settled = () => {
        if (finished || completing) return;
        updateProgress(Math.round(++completed / tasks.length * 90));
    };
    Promise.all([minimum, Promise.all(tasks.map(task => task.then(settled, settled)))]).then(completeLoading);
    loader.querySelector('[data-page-loader-skip]')?.addEventListener('click', finish, { once: true });
    window.addEventListener('pagehide', finish, { once: true });
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted) document.documentElement.classList.remove('page-loading');
});

pageReady.then(() => {
    if (!('IntersectionObserver' in window) || reducedMotion.matches) return;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(({ target, isIntersecting }) => {
            if (!isIntersecting) return;
            observer.unobserve(target);
            if (target.hasAttribute('data-content-preview')) {
                if (reducedMotion.matches || target.contains(document.activeElement)) return;
                const finish = () => {
                    window.clearTimeout(timer);
                    target.classList.remove('is-previewing');
                    target.removeEventListener('focusin', finish);
                    target.removeEventListener('pointerdown', finish);
                    window.removeEventListener('pagehide', finish);
                };
                const timer = window.setTimeout(finish, 400);
                target.addEventListener('focusin', finish, { once: true });
                target.addEventListener('pointerdown', finish, { once: true });
                window.addEventListener('pagehide', finish, { once: true });
                target.classList.add('is-previewing');
                return;
            }
            if (target.hasAttribute('data-room-time-progress') || target.hasAttribute('data-audio-progress-bar')) return;
            if (reducedMotion.matches || !target.animate) return;
            if (target.getAttribute('role') === 'progressbar') {
                target.firstElementChild?.animate([
                    { transform: 'scaleX(0)', transformOrigin: 'left' },
                    { transform: 'scaleX(1)', transformOrigin: 'left' },
                ], { duration: 480, easing: 'cubic-bezier(.2,.8,.2,1)' });
            } else {
                target.animate([
                    { opacity: 0, transform: 'translateY(10px)' },
                    { opacity: 1, transform: 'translateY(0)' },
                ], { duration: 360, easing: 'cubic-bezier(.2,.8,.2,1)' });
            }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('[data-content-preview], [data-motion-reveal], main article, main [role="progressbar"]').forEach(target => observer.observe(target));
});

document.querySelectorAll('[data-mascot]').forEach((trigger) => {
    const message = trigger.nextElementSibling;
    let greetingTimer;
    let waveTimer;
    trigger.addEventListener('click', () => {
        window.clearTimeout(greetingTimer);
        window.clearTimeout(waveTimer);
        trigger.classList.add('is-greeting');
        if (message?.hasAttribute('data-mascot-message')) message.hidden = false;
        waveTimer = window.setTimeout(() => trigger.classList.remove('is-greeting'), 650);
        greetingTimer = window.setTimeout(() => {
            if (message?.hasAttribute('data-mascot-message')) message.hidden = true;
        }, 3500);
    });
});

document.querySelectorAll('img[loading="lazy"]').forEach((image) => {
    const ready = () => {
        image.classList.remove('is-image-pending');
        if (!reducedMotion.matches && image.naturalWidth > 0 && image.animate) {
            image.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 220, easing: 'ease-out' });
        }
    };
    if (image.complete) return;
    image.classList.add('is-image-pending');
    image.addEventListener('load', ready, { once: true });
    image.addEventListener('error', ready, { once: true });
});
