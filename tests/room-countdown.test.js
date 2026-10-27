import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../resources/js/room-countdown.js', import.meta.url), 'utf8');
function setup({ question = 20, total = 60, pending = false } = {}) {
    let now = Date.parse('2026-10-27T02:00:00Z');
    const server = now + 120000;
    const intervals = new Map();
    const documentEvents = {};
    const windowEvents = {};
    const label = () => ({ textContent: '', classList: { toggle() {} } });
    const questionLabel = label();
    const totalLabel = label();
    const bar = { style: {} };
    const progress = { value: null, getAttribute: () => '20', setAttribute(name, value) { this.value = value; } };
    const room = {
        dataset: { serverNow: new Date(server).toISOString(), questionEndsAt: question === null ? undefined : new Date(server + question * 1000).toISOString(), roomEndsAt: total === null ? undefined : new Date(server + total * 1000).toISOString() },
        querySelector: selector => ({ '[data-room-countdown]': question === null ? null : questionLabel, '[data-room-total-countdown]': total === null ? null : totalLabel, '[data-room-time-progress]': progress, '[data-room-time-bar]': bar })[selector],
    };
    const document = { visibilityState: 'visible', addEventListener: (event, handler) => { documentEvents[event] = handler; } };
    let id = 0;
    const window = {
        setInterval: handler => { intervals.set(++id, handler); return id; }, clearInterval: key => intervals.delete(key),
        addEventListener: (event, handler) => { windowEvents[event] = handler; },
    };
    let calls = 0;
    let resolve;
    const initialize = new Function('document', 'window', 'Date', source.replace('export function', 'function') + '\nreturn initializeRoomCountdown;')(document, window, { now: () => now, parse: Date.parse });
    initialize(room, () => { calls++; return pending ? new Promise(done => { resolve = done; }) : Promise.resolve(); });
    return {
        questionLabel, totalLabel, bar, progress, intervals, document, windowEvents,
        get calls() { return calls; }, advance(seconds) { now += seconds * 1000; }, tick() { [...intervals.values()].forEach(handler => handler()); },
        visible(value) { document.visibilityState = value ? 'visible' : 'hidden'; documentEvents.visibilitychange(); },
        finishRequest() { resolve(); },
    };
}

test('question and total clocks share one interval and correct for client clock offset', () => {
    const ui = setup();
    assert.equal(ui.intervals.size, 1);
    assert.equal(ui.questionLabel.textContent, '0:20');
    assert.equal(ui.totalLabel.textContent, '1:00');
    ui.advance(7); ui.tick();
    assert.equal(ui.questionLabel.textContent, '0:13');
    assert.equal(ui.totalLabel.textContent, '0:53');
    assert.equal(ui.bar.style.width, '65%');
    assert.equal(ui.progress.value, 13);
});

test('total countdown works without question timer and checks expiry once', async () => {
    const ui = setup({ question: null, total: 5 });
    ui.advance(5); ui.tick();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(ui.totalLabel.textContent, '0:00');
    assert.equal(ui.calls, 1);
    ui.tick();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(ui.calls, 1);
});

test('simultaneous expiry never overlaps status requests and retries after three seconds', async () => {
    const ui = setup({ question: 5, total: 5, pending: true });
    ui.advance(5); ui.tick();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(ui.calls, 1);
    ui.advance(5); ui.tick();
    assert.equal(ui.calls, 1);
    ui.finishRequest();
    await new Promise(resolve => setImmediate(resolve));
    ui.tick();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(ui.calls, 2);
});

test('hidden tab pauses work and resumes from deadline rather than resetting time', () => {
    const ui = setup();
    ui.visible(false);
    assert.equal(ui.intervals.size, 0);
    ui.advance(8);
    ui.visible(true);
    assert.equal(ui.intervals.size, 1);
    assert.equal(ui.questionLabel.textContent, '0:12');
    assert.equal(ui.totalLabel.textContent, '0:52');
    ui.windowEvents.pagehide();
    assert.equal(ui.intervals.size, 0);
    ui.advance(2);
    ui.windowEvents.pageshow();
    assert.equal(ui.questionLabel.textContent, '0:10');
    assert.equal(ui.intervals.size, 1);
});
