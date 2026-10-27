import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../resources/js/content-audio.js', import.meta.url), 'utf8');
function setup({ timed = false, ready = true, total = null } = {}) {
    const windowEvents = {};
    const documentEvents = {};
    const timers = [];
    const players = [0, 1].map(() => {
        const events = {};
        const button = {};
        const error = { textContent: '' };
        const container = { querySelector: (selector) => selector === '[data-audio-stop]' ? button : error };
        button.addEventListener = (name, callback) => { button[name] = callback; };
        return {
            events, button, error, readyState: ready ? 1 : 0, currentTime: 8, pauses: 0,
            pause() { this.pauses += 1; },
            closest: () => container,
            addEventListener(name, callback) { events[name] = callback; },
        };
    });
    const document = {
        visibilityState: 'visible',
        querySelectorAll: () => players,
        querySelector: () => timed || total ? { dataset: { questionEndsAt: timed ? '2026-10-25T10:00:05Z' : undefined, roomEndsAt: total, serverNow: '2026-10-25T10:00:00Z' } } : null,
        addEventListener: (name, callback) => { documentEvents[name] = callback; },
    };
    const window = {
        addEventListener: (name, callback) => { windowEvents[name] = callback; },
        setTimeout: (callback, delay) => timers.push({ callback, delay }),
    };
    vm.runInNewContext(source, { document, window, Date });
    return { players, document, windowEvents, documentEvents, timers };
}

test('playing one recording stops and resets other recordings', () => {
    const { players } = setup();
    players[0].events.play();
    assert.equal(players[0].pauses, 0);
    assert.equal(players[1].pauses, 1);
    assert.equal(players[1].currentTime, 0);
});

test('stop button resets the recording without waiting for another event', () => {
    const { players } = setup();
    players[0].button.click();
    assert.equal(players[0].pauses, 1);
    assert.equal(players[0].currentTime, 0);
});

test('players inserted after generation are initialized without duplicate listeners', () => {
    const { players, windowEvents } = setup();
    const added = { ...players[0], events: {}, pauses: 0,
        addEventListener(name, callback) { this.events[name] = callback; } };
    players.push(added);
    windowEvents['content-audio-updated']();
    windowEvents['content-audio-updated']();
    added.events.play();
    assert.equal(players[0].pauses, 1);
    assert.equal(players[1].pauses, 1);
    assert.equal(added.pauses, 0);
});

test('leaving or hiding the page stops all audio', () => {
    const harness = setup();
    harness.windowEvents.pagehide();
    harness.document.visibilityState = 'hidden';
    harness.documentEvents.visibilitychange();
    assert.ok(harness.players.every((player) => player.pauses === 2 && player.currentTime === 0));
});

test('unloaded recordings can be stopped safely', () => {
    const { players } = setup({ ready: false });
    players[0].button.click();
    assert.equal(players[0].pauses, 1);
    assert.equal(players[0].currentTime, 8);
});

test('playback errors expose a readable fallback', () => {
    const { players } = setup();
    players[0].events.error();
    assert.match(players[0].error.textContent, /Materi tetap bisa dibaca/);
    assert.equal(players[1].error.textContent, '');
});

test('timed questions stop audio at the server-adjusted deadline', () => {
    const { players, timers } = setup({ timed: true });
    assert.equal(timers.length, 1);
    assert.ok(Math.abs(timers[0].delay - 5000) < 10);
    timers[0].callback();
    assert.ok(players.every((player) => player.pauses === 1));
});

test('total limit stops audio on a question without its own timer', () => {
    const { timers } = setup({ total: '2026-10-25T10:00:03Z' });
    assert.equal(timers.length, 1);
    assert.ok(Math.abs(timers[0].delay - 3000) < 10);
});

test('audio uses the earlier of question and total deadlines', () => {
    const { timers } = setup({ timed: true, total: '2026-10-25T10:00:02Z' });
    assert.equal(timers.length, 1);
    assert.ok(Math.abs(timers[0].delay - 2000) < 10);
});
