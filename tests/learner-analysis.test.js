import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../resources/js/learner-analysis.js', import.meta.url), 'utf8');

function setup({ visible = true, response, fetcher } = {}) {
    const timers = new Map();
    const listeners = new Map();
    const pageListeners = new Map();
    const status = { textContent: '' };
    const heading = { focus: () => { state.focused = true; } };
    const replacement = { id: 'analisis', querySelector: () => heading };
    const panel = {
        dataset: { learnerAnalysisUrl: '/profil/analisis' },
        querySelector: (selector) => selector === '[data-analysis-status]' ? status : null,
        contains: () => true,
        replaceWith: (value) => { state.replacement = value; },
    };
    const state = { calls: 0, focused: false, replacement: null, lastDelay: 0 };
    const document = {
        visibilityState: visible ? 'visible' : 'hidden',
        activeElement: {},
        querySelector: () => panel,
        createElement: () => ({ innerHTML: '', content: { firstElementChild: replacement } }),
        addEventListener: (event, callback) => listeners.set(event, callback),
        removeEventListener: (event) => listeners.delete(event),
    };
    let timerId = 0;
    const window = {
        setTimeout: (callback, delay) => {
            state.lastDelay = delay;
            timers.set(++timerId, callback);
            return timerId;
        },
        clearTimeout: (id) => timers.delete(id),
        addEventListener: (event, callback) => pageListeners.set(event, callback),
    };
    vm.runInNewContext(source, {
        window, document, AbortController,
        fetch: async (...args) => {
            state.calls += 1;
            if (fetcher) return fetcher(...args);
            return { ok: true, status: 200, json: async () => response ?? { analysis: { status: 'queued' }, html: null } };
        },
    });
    return {
        state, timers, document, status,
        visibility(value) { document.visibilityState = value; listeners.get('visibilitychange')?.(); },
        leave() { pageListeners.get('pagehide')?.(); },
        async tick() {
            const [id, callback] = timers.entries().next().value ?? [];
            assert.ok(callback, 'Expected a scheduled status check');
            timers.delete(id);
            await callback();
        },
    };
}

test('does not poll hidden tabs and resumes with a bounded delay', async () => {
    const harness = setup({ visible: false });
    assert.equal(harness.timers.size, 0);
    harness.visibility('visible');
    assert.equal(harness.timers.size, 1);
    await harness.tick();
    assert.equal(harness.state.calls, 1);
    harness.visibility('hidden');
    assert.equal(harness.timers.size, 0);
});

test('does not overlap status requests after a visibility change', async () => {
    let resolveFetch;
    const harness = setup({ fetcher: () => new Promise((resolve) => { resolveFetch = resolve; }) });
    const pending = harness.tick();
    harness.visibility('hidden');
    harness.visibility('visible');
    assert.equal(harness.timers.size, 1, 'Only the request timeout should remain');
    resolveFetch({ ok: true, status: 200, json: async () => ({ analysis: { status: 'queued' } }) });
    await pending;
    assert.equal(harness.state.calls, 1);
    assert.equal(harness.timers.size, 1);
});

test('replaces only the panel on completion, restores focus, and stops polling', async () => {
    const harness = setup({ response: { analysis: { status: 'completed' }, html: '<section id="analisis"></section>' } });
    await harness.tick();
    assert.equal(harness.state.replacement.id, 'analisis');
    assert.equal(harness.state.focused, true);
    assert.equal(harness.timers.size, 0);
    harness.visibility('visible');
    assert.equal(harness.timers.size, 0);
});

test('stops after twenty checks without retrying inference', async () => {
    const harness = setup();
    for (let attempt = 0; attempt < 21; attempt += 1) await harness.tick();
    assert.equal(harness.state.calls, 20);
    assert.equal(harness.timers.size, 0);
    assert.match(harness.status.textContent, /memeriksa lagi nanti/);
});

test('network errors schedule another check and leaving the page cancels it', async () => {
    const harness = setup({ fetcher: async () => { throw new Error('offline'); } });
    await harness.tick();
    assert.equal(harness.state.calls, 1);
    assert.equal(harness.timers.size, 1);
    harness.leave();
    assert.equal(harness.timers.size, 0);
});

test('rate limited responses stop polling', async () => {
    const harness = setup({ fetcher: async () => ({ ok: false, status: 429 }) });
    await harness.tick();
    assert.equal(harness.timers.size, 0);
    assert.equal(harness.state.calls, 1);
});
