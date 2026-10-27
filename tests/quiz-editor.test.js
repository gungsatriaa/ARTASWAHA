import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../resources/js/quiz-editor.js', import.meta.url), 'utf8');
const api = new Function('document', source.replaceAll('export function', 'function') + '\nreturn { readQuestion, previewAudio, moveQuestion, cloneQuestion, renumberQuestionNames };')({ querySelectorAll: () => [] });

test('reads only participant fields and keeps automatic keyboard setting', () => {
    const question = {
        querySelector: selector => selector.includes('prompt') ? { value: 'Aksara ka?' } : selector.includes('time_limit_seconds') ? { value: '45' } : { checked: true },
        querySelectorAll: () => [{ value: 'Ka' }, { value: 'Na' }],
    };
    assert.deepEqual(api.readQuestion(question), { prompt: 'Aksara ka?', options: ['Ka', 'Na'], keyboard: true, seconds: 45 });
});

test('preview accepts approved audio only while prompt and options still match', () => {
    const question = { dataset: { sourcePrompt: 'Ka?', sourceOptions: '["Ka","Na"]', audioUrl: '/audio/1' } };
    assert.equal(api.previewAudio(question, { prompt: 'Ka?', options: ['Ka', 'Na'] }), '/audio/1');
    assert.equal(api.previewAudio(question, { prompt: 'Ga?', options: ['Ka', 'Na'] }), '');
    assert.equal(api.previewAudio(question, { prompt: 'Ka?', options: ['Na', 'Ka'] }), '');
    question.dataset.sourceOptions = 'invalid';
    assert.equal(api.previewAudio(question, { prompt: 'Ka?', options: ['Ka', 'Na'] }), '');
});

test('renumbering reordered questions preserves checked answers in separate radio groups', () => {
    const radios = [];
    const makeRadio = index => {
        let name = `questions[${index}][correct_option]`;
        const radio = { checked: true, get name() { return name; }, set name(value) {
            name = value;
            if (this.checked) radios.filter(other => other !== this && other.name === value).forEach(other => { other.checked = false; });
        } };
        radios.push(radio);
        return radio;
    };
    const first = makeRadio(0);
    const second = makeRadio(1);
    api.renumberQuestionNames([{ querySelectorAll: () => [second] }, { querySelectorAll: () => [first] }]);
    assert.equal(second.name, 'questions[0][correct_option]');
    assert.equal(first.name, 'questions[1][correct_option]');
    assert.equal(first.checked, true);
    assert.equal(second.checked, true);
});

test('move controls reorder only one sibling and stop at either boundary', () => {
    const list = [];
    const node = name => ({ name,
        get previousElementSibling() { return list[list.indexOf(this) - 1]; },
        get nextElementSibling() { return list[list.indexOf(this) + 1]; },
        before(item) { list.splice(list.indexOf(item), 1); list.splice(list.indexOf(this), 0, item); },
        after(item) { list.splice(list.indexOf(item), 1); list.splice(list.indexOf(this) + 1, 0, item); },
    });
    const a = node('a'), b = node('b'), c = node('c');
    list.push(a, b, c);
    assert.equal(api.moveQuestion(a, 'up'), false);
    assert.equal(api.moveQuestion(b, 'up'), true);
    assert.deepEqual(list.map(item => item.name), ['b', 'a', 'c']);
    assert.equal(api.moveQuestion(c, 'down'), false);
    assert.equal(api.moveQuestion(a, 'down'), true);
    assert.deepEqual(list.map(item => item.name), ['b', 'c', 'a']);
});

test('duplicate drops saved ID and audio references while retaining entered text and correct choice', () => {
    const original = [
        { name: 'questions[0][id]', value: '9', type: 'hidden' },
        { name: 'questions[0][prompt]', value: 'Ka?', type: 'textarea' },
        { name: 'questions[0][correct_option]', value: '1', type: 'radio', checked: true },
        { name: 'questions[0][keyboard_auto_open]', value: '1', type: 'checkbox', checked: true },
    ];
    const copied = original.map(field => ({ ...field, value: '', checked: false, remove() { this.removed = true; }, removeAttribute() {} }));
    const audio = { textContent: '' };
    const clone = { dataset: { audioUrl: '/audio/9' }, querySelectorAll: selector => selector === 'input, textarea' ? copied : [], querySelector: () => audio };
    const result = api.cloneQuestion({ cloneNode: () => clone, querySelectorAll: () => original });
    assert.equal(copied[0].removed, true);
    assert.equal(copied[1].value, 'Ka?');
    assert.equal(copied[2].checked, true);
    assert.equal(copied[2].name, 'questions[__INDEX__][correct_option]');
    assert.equal(copied[3].checked, true);
    assert.equal(result.dataset.audioUrl, '');
    assert.equal(result.open, true);
    assert.match(audio.textContent, /belum memiliki audio/);
    assert.equal(original[0].value, '9');
});
