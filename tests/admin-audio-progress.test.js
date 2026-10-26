import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../resources/js/admin-audio-progress.js', import.meta.url), 'utf8');

function setup({ visible = true, response, fetcher, focused = false } = {}) {
    const timers = new Map();
    const intervals = new Map();
    const documentEvents = new Map();
    const windowEvents = new Map();
    const label = { textContent: '' };
    const note = { textContent: '' };
    const badge = { textContent: '' };
    const bar = { hidden: false, attributes: {}, setAttribute(key, value) { this.attributes[key] = value; }, removeAttribute(key) { delete this.attributes[key]; } };
    const fill = { style: {}, classList: { add() {}, remove() {} } };
    const parts = { textContent: '' };
    const elapsed = { textContent: '' };
    const eta = { textContent: '' };
    const started = { textContent: '' };
    const state = { calls: 0, replacement: null, focusMoved: false, events: [], delay: 0, attributes: {} };
    const replacement = { matches: () => true, querySelector: () => ({ focus: () => { state.focusMoved = true; } }) };
    const panel = {
        dataset: { audioStatusUrl: '/admin/audio/1/status', audioVersion: 'v1' },
        querySelector: selector => ({
            '[data-audio-progress-label]': label,
            '[data-audio-progress-note]': note,
            '[data-audio-progress-bar]': bar,
            '[data-audio-status-badge] span': badge,
            '[data-audio-progress-fill]': fill,
            '[data-audio-parts]': parts,
            '[data-audio-elapsed]': elapsed,
            '[data-audio-eta]': eta,
            '[data-audio-start]': started,
        })[selector],
        setAttribute: (key, value) => { state.attributes[key] = value; },
        contains: () => focused,
        replaceWith: value => { state.replacement = value; },
    };
    const document = {
        visibilityState: visible ? 'visible' : 'hidden',
        activeElement: {},
        querySelector: () => panel,
        createElement: () => ({ content: { firstElementChild: replacement } }),
        addEventListener: (event, callback) => documentEvents.set(event, callback),
    };
    let timerId = 0;
    const window = {
        setTimeout(callback, delay) {
            state.delay = delay;
            timers.set(++timerId, callback);
            return timerId;
        },
        clearTimeout: id => timers.delete(id),
        setInterval: callback => { intervals.set(++timerId, callback); return timerId; },
        clearInterval: id => intervals.delete(id),
        addEventListener: (event, callback) => windowEvents.set(event, callback),
        dispatchEvent: event => state.events.push(event.type),
    };
    vm.runInNewContext(source, {
        window, document, AbortController, Event,
        fetch: async (url, options) => {
            state.calls += 1;
            state.options = options;
            if (fetcher) return fetcher(url, options);
            return { ok: true, status: 200, json: async () => response ?? { version: 'v1', status: 'queued', html: null } };
        },
    });
    return {
        state, timers, intervals, label, note, bar, badge, fill, parts, elapsed, eta,
        visibility(value) { document.visibilityState = value; documentEvents.get('visibilitychange')(); },
        leave() { windowEvents.get('pagehide')(); },
        returnToPage() { windowEvents.get('pageshow')(); },
        async tick() {
            const [id, callback] = timers.entries().next().value ?? [];
            assert.ok(callback, 'Expected a scheduled status check');
            timers.delete(id);
            await callback();
        },
    };
}

test('updates actual processing status and schedules just one authenticated status check', async () => {
    const harness = setup({ response: { version: 'v1', status: 'processing' } });
    await harness.tick();
    assert.equal(harness.label.textContent, 'Sedang membuat audio');
    assert.equal(harness.badge.textContent, 'Diproses');
    assert.equal(harness.timers.size, 1);
    assert.equal(harness.state.delay, 3000);
    assert.equal(harness.state.options.credentials, 'same-origin');
    assert.equal(harness.state.options.cache, 'no-store');
});

test('hidden tabs pause requests and visibility changes do not create overlapping fetches', async () => {
    let resolveFetch;
    const harness = setup({ visible: false, fetcher: () => new Promise(resolve => { resolveFetch = resolve; }) });
    assert.equal(harness.timers.size, 0);
    harness.visibility('visible');
    const pending = harness.tick();
    harness.visibility('hidden');
    assert.equal(harness.state.options.signal.aborted, true);
    harness.visibility('visible');
    assert.equal(harness.timers.size, 1, 'Only the request timeout remains');
    resolveFetch({ ok: true, status: 200, json: async () => ({ version: 'v1', status: 'queued' }) });
    await pending;
    assert.equal(harness.state.calls, 1);
    assert.equal(harness.timers.size, 1);
});

for (const status of ['review', 'failed', 'unavailable', 'stale']) {
    test(`${status} replaces only the result panel and stops checking`, async () => {
        const harness = setup({ response: { version: 'v1', status, html: '<section data-audio-status-panel></section>' } });
        await harness.tick();
        assert.ok(harness.state.replacement);
        assert.equal(harness.state.focusMoved, false, 'Edits outside the panel keep focus');
        assert.deepEqual(harness.state.events, ['content-audio-updated']);
        assert.equal(harness.timers.size, 0);
        harness.returnToPage();
        assert.equal(harness.timers.size, 0);
    });
}

test('completion restores focus only when focus was inside the replaced panel', async () => {
    const harness = setup({ focused: true, response: { version: 'v1', status: 'review', html: '<section></section>' } });
    await harness.tick();
    assert.equal(harness.state.focusMoved, true);
});

test('a changed narration version stops and asks for reload instead of displaying another result', async () => {
    const harness = setup({ response: { version: 'v2', status: 'review', html: '<section></section>' } });
    await harness.tick();
    assert.equal(harness.state.replacement, null);
    assert.match(harness.note.textContent, /sesi lain/);
    assert.equal(harness.bar.hidden, true);
    assert.equal(harness.timers.size, 0);
});

test('authorization failures stop polling', async () => {
    for (const status of [401, 403, 404]) {
        const harness = setup({ fetcher: async () => ({ ok: false, status }) });
        await harness.tick();
        assert.equal(harness.timers.size, 0);
        assert.match(harness.note.textContent, /sesi dan materi/);
    }
});

test('rate limits back off and repeated network failures eventually pause polling', async () => {
    const harness = setup({ fetcher: async () => ({ ok: false, status: 429 }) });
    await harness.tick();
    assert.equal(harness.state.delay, 6000);
    for (let attempt = 0; attempt < 5; attempt += 1) await harness.tick();
    assert.equal(harness.state.calls, 6);
    assert.equal(harness.timers.size, 0);
    assert.match(harness.note.textContent, /proses di server tetap berjalan/);
});

test('page navigation ignores late responses and resumes checks after returning', async () => {
    let resolveFetch;
    const harness = setup({ fetcher: () => new Promise(resolve => { resolveFetch = resolve; }) });
    const pending = harness.tick();
    harness.leave();
    resolveFetch({ ok: true, status: 200, json: async () => ({ version: 'v1', status: 'review', html: '<section></section>' }) });
    await pending;
    assert.equal(harness.state.replacement, null);
    assert.equal(harness.timers.size, 0);
    harness.returnToPage();
    assert.equal(harness.timers.size, 1);
});

test('long queues stop automatic checking after a bounded number of requests', async () => {
    const harness = setup();
    for (let attempt = 0; attempt < 721; attempt += 1) await harness.tick();
    assert.equal(harness.state.calls, 720);
    assert.equal(harness.timers.size, 0);
    assert.match(harness.note.textContent, /Pembaruan otomatis dijeda/);
});

test('real chunk progress updates the bar, worker timer, and measured ETA', async () => {
    const now = Date.now();
    const harness = setup({ response: { version: 'v1', status: 'processing', server_now: new Date(now).toISOString(),
        progress: { stage: 'generating', started_at: new Date(now - 60000).toISOString(), completed_parts: 2, total_parts: 4, percent: 50, estimated_remaining_seconds: 75 } } });
    await harness.tick();
    assert.equal(harness.fill.style.transform, 'scaleX(0.5)');
    assert.equal(harness.bar.attributes['aria-valuenow'], '50');
    assert.equal(harness.parts.textContent, 'Bagian 2 dari 4 selesai');
    assert.equal(harness.elapsed.textContent, '00:01:00');
    assert.match(harness.eta.textContent, /Sekitar/);
    assert.equal(harness.intervals.size, 1);
    harness.visibility('hidden');
    assert.equal(harness.intervals.size, 0);
    harness.visibility('visible');
    assert.equal(harness.intervals.size, 1);
    harness.leave();
    assert.equal(harness.intervals.size, 0);
});

test('an exceeded estimate keeps polling and does not claim generation is complete', async () => {
    const now = Date.now();
    const harness = setup({ response: { version: 'v1', status: 'processing', server_now: new Date(now).toISOString(),
        progress: { stage: 'generating', started_at: new Date(now - 60000).toISOString(), completed_parts: 1, total_parts: 4, percent: 25, estimated_remaining_seconds: 0 } } });
    await harness.tick();
    assert.match(harness.eta.textContent, /Lebih lama dari perkiraan/);
    assert.equal(harness.timers.size, 1);
    assert.equal(harness.state.replacement, null);
});
