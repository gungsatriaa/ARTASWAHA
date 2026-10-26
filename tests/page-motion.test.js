import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../resources/js/page-motion.js', import.meta.url), 'utf8');
const bootstrap = readFileSync(new URL('../resources/views/components/page-loader.blade.php', import.meta.url), 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1];

function element() {
    const listeners = new Map();
    const classes = new Set();
    return {
        style: {}, attributes: {}, hidden: true, complete: false, naturalWidth: 0,
        classList: { add: value => classes.add(value), remove: value => classes.delete(value), contains: value => classes.has(value) },
        addEventListener: (event, callback) => listeners.set(event, callback),
        removeEventListener: event => listeners.delete(event),
        emit: (event, data = {}) => listeners.get(event)?.(data),
        setAttribute(name, value) { this.attributes[name] = value; },
        getAttribute(name) { return this.attributes[name]; },
        hasAttribute(name) { return name in this.attributes; },
        getClientRects: () => [1],
        contains: () => false,
        querySelector: () => null,
        animations: [],
        animate(frames) { this.animations.push(frames); },
    };
}

function setup({ loading = true, images = [], lazy = [], reduced = false, targets = [], fonts } = {}) {
    const root = element();
    if (loading) root.classList.add('page-loading');
    const loader = element();
    const progress = element();
    const bar = element();
    const skip = element();
    const mascot = element();
    const message = element();
    message.setAttribute('data-mascot-message', '');
    mascot.nextElementSibling = message;
    loader.querySelector = selector => ({ '[data-page-load-progress]': progress, '[data-page-load-bar]': bar, '[data-page-loader-skip]': skip })[selector];
    const timers = new Map();
    const frames = [];
    let timerId = 0;
    let observer;
    class Observer {
        constructor(callback) { this.callback = callback; this.targets = new Set(); observer = this; }
        observe(target) { this.targets.add(target); }
        unobserve(target) { this.targets.delete(target); }
        enter(target) { if (this.targets.has(target)) this.callback([{ target, isIntersecting: true }]); }
    }
    const window = Object.assign(element(), {
        matchMedia: () => ({ matches: reduced }),
        setTimeout(callback, delay) { timers.set(++timerId, { callback, delay }); return timerId; },
        clearTimeout: id => timers.delete(id),
        IntersectionObserver: Observer,
        requestAnimationFrame: callback => frames.push(callback),
    });
    const document = {
        fonts,
        documentElement: root,
        querySelector: () => loader,
        querySelectorAll: selector => ({
            '[data-load-critical]': images,
            '[data-mascot]': [mascot],
            'img[loading="lazy"]': lazy,
            '[data-content-preview], [data-motion-reveal], main article, main [role="progressbar"]': targets,
        })[selector] ?? [],
    };
    vm.runInNewContext(source, { window, document, IntersectionObserver: Observer });
    return { root, window, loader, progress, bar, skip, mascot, message, timers, observer: () => observer,
        paint() { frames.shift()?.(); frames.shift()?.(); },
        runTimer(delay) {
            const [id, timer] = [...timers].find(([, value]) => value.delay === delay) ?? [];
            assert.ok(timer, `Expected ${delay}ms timer`);
            timers.delete(id);
            timer.callback();
        },
    };
}

const flush = () => new Promise(resolve => setImmediate(resolve));

test('loader bootstrap runs once per tab session and has a fail-safe without module JS', () => {
    const root = element();
    const values = new Map();
    let fallback;
    const context = { document: { documentElement: root }, sessionStorage: { getItem: key => values.get(key), setItem: (key, value) => values.set(key, value) }, window: { setTimeout(callback, delay) { assert.equal(delay, 10000); fallback = callback; } } };
    vm.runInNewContext(bootstrap, context);
    assert.equal(root.classList.contains('page-loading'), true);
    fallback();
    assert.equal(root.classList.contains('page-loading'), false);
    vm.runInNewContext(bootstrap, context);
    assert.equal(root.classList.contains('page-loading'), false);
});

test('blocked session storage leaves the page accessible', () => {
    const root = element();
    vm.runInNewContext(bootstrap, { document: { documentElement: root }, sessionStorage: { getItem() { throw new Error('Denied'); } } });
    assert.equal(root.classList.contains('page-loading'), false);
});

test('progress follows settled critical assets, including failures, then unlocks', async () => {
    const first = element();
    const second = element();
    const page = setup({ images: [first, second] });
    first.emit('load');
    await flush();
    assert.equal(page.progress.attributes['aria-valuenow'], '45');
    assert.equal(page.root.classList.contains('page-loading'), true);
    second.emit('error');
    await flush();
    assert.equal(page.progress.attributes['aria-valuenow'], '90');
    page.paint();
    page.runTimer(1200);
    await flush();
    assert.equal(page.progress.attributes['aria-valuenow'], '100');
    assert.equal(page.bar.style.transform, 'scaleX(1)');
    assert.equal(page.root.classList.contains('page-loading'), true);
    page.runTimer(450);
    assert.equal(page.loader.classList.contains('is-leaving'), true);
    page.runTimer(240);
    assert.equal(page.root.classList.contains('page-loading'), false);
    assert.equal(page.timers.size, 0);
});

test('skip and deadline both unlock despite stalled assets', async () => {
    for (const action of ['skip', 'deadline']) {
        const image = element();
        const page = setup({ images: [image] });
        if (action === 'skip') page.skip.emit('click');
        else {
            page.runTimer(6000);
            assert.equal(page.progress.attributes['aria-valuenow'], '100');
            page.runTimer(450);
            page.runTimer(240);
        }
        await flush();
        assert.equal(page.root.classList.contains('page-loading'), false);
        image.emit('load');
        assert.equal(page.progress.attributes['aria-valuenow'], action === 'skip' ? undefined : '100');
    }
});

test('loader waits for fonts and skips hidden critical images', async () => {
    let finishFonts;
    const ready = new Promise(resolve => { finishFonts = resolve; });
    const image = element();
    image.getClientRects = () => [];
    const page = setup({ images: [image], fonts: { ready } });
    await flush();
    assert.equal(page.root.classList.contains('page-loading'), true);
    finishFonts();
    await flush();
    assert.equal(page.root.classList.contains('page-loading'), true);
    page.runTimer(1200);
    await flush();
    page.runTimer(450);
    page.runTimer(240);
    assert.equal(page.root.classList.contains('page-loading'), false);
    assert.equal(page.progress.attributes['aria-valuenow'], '100');
});

test('cached assets still paint a visible bar before completing the introduction', async () => {
    const image = element();
    image.complete = true;
    const page = setup({ images: [image] });
    await flush();
    assert.equal(page.bar.style.transform, undefined);
    assert.equal(page.root.classList.contains('page-loading'), true);
    page.paint();
    assert.equal(page.bar.style.transform, 'scaleX(0.9)');
    page.runTimer(1200);
    await flush();
    assert.equal(page.bar.style.transform, 'scaleX(1)');
    page.runTimer(450);
    assert.equal(page.root.classList.contains('page-loading'), true);
    page.runTimer(240);
    assert.equal(page.root.classList.contains('page-loading'), false);
});

test('reduced motion skips the minimum introduction and fade durations', async () => {
    const page = setup({ reduced: true });
    page.runTimer(0);
    await flush();
    assert.equal(page.progress.attributes['aria-valuenow'], '100');
    page.runTimer(0);
    page.runTimer(0);
    assert.equal(page.root.classList.contains('page-loading'), false);
    assert.equal(page.timers.size, 0);
});

test('browser back cache cannot leave the overlay visible', () => {
    const page = setup({ images: [element()] });
    page.window.emit('pageshow', { persisted: true });
    assert.equal(page.root.classList.contains('page-loading'), false);
});

test('repeated mascot taps extend the greeting without accumulating timers', () => {
    const page = setup({ loading: false });
    page.mascot.emit('click');
    page.mascot.emit('click');
    assert.equal(page.message.hidden, false);
    assert.equal(page.mascot.classList.contains('is-greeting'), true);
    assert.equal(page.timers.size, 2);
    page.runTimer(650);
    page.runTimer(3500);
    assert.equal(page.message.hidden, true);
    assert.equal(page.mascot.classList.contains('is-greeting'), false);
});

test('reveals run only once when entering view and respect reduced motion', async () => {
    const card = element();
    const page = setup({ loading: false, targets: [card] });
    await flush();
    assert.equal(card.animations.length, 0);
    page.observer().enter(card);
    page.observer().enter(card);
    assert.equal(card.animations.length, 1);
    const reduced = setup({ loading: false, reduced: true, targets: [card] });
    await flush();
    assert.equal(reduced.observer(), undefined);
});

test('lazy placeholders clear on success and failure, with no animation for reduced motion', () => {
    for (const [event, reduced, expected] of [['load', false, 1], ['error', false, 0], ['load', true, 0]]) {
        const image = element();
        setup({ loading: false, lazy: [image], reduced });
        assert.equal(image.classList.contains('is-image-pending'), true);
        image.naturalWidth = event === 'load' ? 48 : 0;
        image.emit(event);
        assert.equal(image.classList.contains('is-image-pending'), false);
        assert.equal(image.animations.length, expected);
    }
});

test('progress entrance preserves its actual value and never animates live timers', async () => {
    const progress = element();
    progress.setAttribute('role', 'progressbar');
    progress.setAttribute('aria-valuenow', '35');
    progress.firstElementChild = element();
    progress.firstElementChild.style.width = '35%';
    const timer = element();
    timer.setAttribute('data-room-time-progress', '');
    timer.firstElementChild = element();
    const page = setup({ loading: false, targets: [progress, timer] });
    await flush();
    page.observer().enter(progress);
    page.observer().enter(timer);
    assert.equal(progress.firstElementChild.animations.length, 1);
    assert.equal(progress.firstElementChild.style.width, '35%');
    assert.equal(progress.attributes['aria-valuenow'], '35');
    assert.equal(timer.firstElementChild.animations.length, 0);
});

test('content previews last 400ms and run once when entering the viewport', async () => {
    const target = element();
    target.setAttribute('data-content-preview', '');
    const page = setup({ loading: false, targets: [target] });
    await flush();
    assert.equal(target.classList.contains('is-previewing'), false);
    page.observer().enter(target);
    assert.equal(target.classList.contains('is-previewing'), true);
    page.runTimer(400);
    assert.equal(target.classList.contains('is-previewing'), false);
    page.observer().enter(target);
    assert.equal(page.timers.size, 0);
});

test('previews never delay pointer or keyboard interaction and clear on departure', async () => {
    for (const event of ['pointerdown', 'focusin', 'pagehide']) {
        const target = element();
        target.setAttribute('data-content-preview', '');
        const page = setup({ loading: false, targets: [target] });
        await flush();
        page.observer().enter(target);
        (event === 'pagehide' ? page.window : target).emit(event);
        assert.equal(target.classList.contains('is-previewing'), false);
        assert.equal(page.timers.size, 0);
    }
});

test('focused content and reduced motion bypass decorative previews', async () => {
    const target = element();
    target.setAttribute('data-content-preview', '');
    target.contains = () => true;
    const page = setup({ loading: false, targets: [target] });
    await flush();
    page.observer().enter(target);
    assert.equal(page.timers.size, 0);
    const reduced = setup({ loading: false, reduced: true, targets: [target] });
    await flush();
    assert.equal(reduced.observer(), undefined);
    assert.equal(target.classList.contains('is-previewing'), false);
});
