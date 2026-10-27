import test from 'node:test';
import assert from 'node:assert/strict';
import { editAksara } from '../resources/js/aksara-edit.js';

function field(value = '', start = value.length, end = start) {
    return {
        value, selectionStart: start, selectionEnd: end, maxLength: -1, tagName: 'TEXTAREA',
        dataset: { aksaraLabel: 'Pertanyaan' }, isConnected: true, inScope: true, attributes: {}, inputs: 0,
        getAttribute(name) { return this.attributes[name] ?? null; },
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        matches(selector) { return selector === ':disabled' ? Boolean(this.disabled) : true; },
        setSelectionRange(start, end) { this.selectionStart = start; this.selectionEnd = end; },
        setRangeText(text, start, end) { this.value = this.value.slice(0, start) + text + this.value.slice(end); this.setSelectionRange(start + text.length, start + text.length); },
        dispatchEvent(event) { assert.equal(event.type, 'input'); this.inputs++; },
        focus(options) { assert.equal(options.preventScroll, true); },
    };
}

test('inserts at cursor and replaces selection without losing surrounding text', () => {
    const input = field('awal akhir', 5, 10);
    assert.equal(editAksara(input, { text: '\u1B13' }), '');
    assert.equal(input.value, 'awal \u1B13');
    assert.equal(input.selectionStart, 6);
    assert.equal(input.inputs, 1);
});

test('pasangan adds one adeg-adeg and rejects an unrelated cursor context', () => {
    const input = field('\u1B13');
    editAksara(input, { text: '\u1B26', pasangan: true });
    assert.equal(input.value, '\u1B13\u1B44\u1B26');
    const manual = field('\u1B13\u1B44');
    editAksara(manual, { text: '\u1B26', pasangan: true });
    assert.equal(manual.value, input.value);
    const invalid = field('Latin ');
    assert.match(editAksara(invalid, { text: '\u1B26', pasangan: true }), /setelah aksara dasar/);
    assert.equal(invalid.value, 'Latin ');
    assert.equal(invalid.inputs, 0);
});

test('marks and backspace edit one Unicode character without corrupting surrogate pairs', () => {
    const input = field('\u1B13');
    editAksara(input, { text: '\u1B36' });
    assert.equal(input.value, '\u1B13\u1B36');
    editAksara(input, { action: 'backspace' });
    assert.equal(input.value, '\u1B13');
    const emoji = field('a😀b', 3);
    editAksara(emoji, { action: 'backspace' });
    assert.equal(emoji.value, 'ab');
    assert.equal(emoji.selectionStart, 1);
});

test('cursor controls traverse code points and backspace deletes selected text', () => {
    const input = field('a😀b', 1);
    editAksara(input, { action: 'right' });
    assert.equal(input.selectionStart, 3);
    editAksara(input, { action: 'left' });
    assert.equal(input.selectionStart, 1);
    input.setSelectionRange(1, 3);
    editAksara(input, { action: 'backspace' });
    assert.equal(input.value, 'ab');
});

test('respects maxlength and rejects newline in a single line input', () => {
    const input = field('a');
    input.maxLength = 1;
    assert.match(editAksara(input, { text: '\u1B13' }), /Batas panjang/);
    assert.equal(input.value, 'a');
    input.tagName = 'INPUT';
    assert.match(editAksara(input, { action: 'enter' }), /Baris baru/);
    input.maxLength = 2;
    editAksara(input, { action: 'space' });
    assert.equal(input.value, 'a ');
});
