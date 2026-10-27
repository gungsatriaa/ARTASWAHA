import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../resources/js/aksara-edit.js', import.meta.url), 'utf8') + readFileSync(new URL('../resources/js/aksara-keyboard.js', import.meta.url), 'utf8').replace(/^import .*;|^export \{.*;$/gm, '');
function setup(open = true) {
    const modes = ['dasar', 'pasangan', 'pangangge'].map(mode => ({ dataset: { aksaraMode: mode }, setAttribute() {} }));
    const keys = { children: [], replaceChildren(fragment) { this.children = fragment.children; }, setAttribute() {} };
    const status = { textContent: '' };
    const label = { textContent: '' };
    const scopeEvents = {};
    const keyboardEvents = {};
    const scope = { addEventListener: (name, handler) => { scopeEvents[name] = handler; }, querySelector: () => null, contains: field => field.inScope };
    const keyboard = {
        open, closest: () => scope, addEventListener: (name, handler) => { keyboardEvents[name] = handler; },
        querySelector: selector => ({ '[data-aksara-keys]': keys, '[data-aksara-status]': status, '[data-aksara-target]': label, summary: { focus() {} } })[selector],
        querySelectorAll: () => modes,
    };
    const node = () => ({ children: [], dataset: {}, setAttribute() {}, append(...children) { this.children.push(...children); } });
    const document = { querySelectorAll: () => [], createElement: node, createDocumentFragment: node };
    const api = new Function('document', source.replaceAll('export function', 'function') + '\nreturn { editAksara, initAksaraKeyboard, pasanganText };')(document);
    api.initAksaraKeyboard(keyboard);
    return {
        ...api, keyboard, keys, status, label,
        focus(field) { field.closest = () => scope; scopeEvents.focusin({ target: field }); },
        focusNested(field) { field.closest = () => ({}); scopeEvents.focusin({ target: field }); },
        click(dataset) { keyboardEvents.click({ target: { closest: () => ({ dataset }) } }); },
        toggle(value) { keyboard.open = value; keyboardEvents.toggle(); },
    };
}

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
    const { editAksara } = setup();
    const input = field('awal akhir', 5, 10);
    assert.equal(editAksara(input, { text: '\u1B13' }), '');
    assert.equal(input.value, 'awal \u1B13');
    assert.equal(input.selectionStart, 6);
    assert.equal(input.inputs, 1);
});

test('pasangan adds one adeg-adeg and rejects an unrelated cursor context', () => {
    const { editAksara } = setup();
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
    const { editAksara } = setup();
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
    const { editAksara } = setup();
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
    const { editAksara } = setup();
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

test('renders reference groups lazily and retains the group across close/open', () => {
    const ui = setup(false);
    assert.equal(ui.keys.children.length, 0);
    ui.toggle(true);
    assert.equal(ui.keys.children.length, 18);
    assert.equal(ui.keys.children[4].dataset.aksara, '\u1B13');
    ui.click({ aksaraMode: 'pangangge' });
    assert.equal(ui.keys.children.length, 10);
    ui.toggle(false);
    ui.toggle(true);
    assert.equal(ui.keys.children.length, 10);
});

test('targets newly added fields and restores each original device keyboard mode', () => {
    const ui = setup();
    const first = field('');
    const second = field('');
    second.attributes.inputmode = 'text';
    ui.focus(first);
    assert.equal(first.attributes.inputmode, 'none');
    ui.click({ aksara: '\u1B13' });
    ui.focus(second);
    assert.equal(first.attributes.inputmode, undefined);
    ui.click({ aksara: '\u1B26' });
    assert.equal(first.value, '\u1B13');
    assert.equal(second.value, '\u1B26');
    ui.toggle(false);
    assert.equal(second.attributes.inputmode, 'text');
});

test('editor keyboard ignores fields inside a separate preview keyboard scope', () => {
    const ui = setup();
    const editor = field('');
    const preview = field('');
    ui.focus(editor);
    ui.focusNested(preview);
    ui.click({ aksara: '\u1B13' });
    assert.equal(editor.value, '\u1B13');
    assert.equal(preview.value, '');
});

for (const state of ['removed', 'disabled', 'readonly', 'outside']) {
    test(`refuses editing a ${state} target`, () => {
        const ui = setup();
        const input = field('');
        ui.focus(input);
        if (state === 'removed') input.isConnected = false;
        if (state === 'disabled') input.disabled = true;
        if (state === 'readonly') input.readOnly = true;
        if (state === 'outside') input.inScope = false;
        ui.click({ aksara: '\u1B13' });
        assert.equal(input.value, '');
        assert.match(ui.status.textContent, /Pilih kolom/);
    });
}
