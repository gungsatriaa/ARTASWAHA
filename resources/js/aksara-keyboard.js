import { editAksara } from './aksara-edit.js';
export { editAksara, pasanganText } from './aksara-edit.js';

const consonants = [
    ['Ha', 0x1B33], ['Na', 0x1B26], ['Ca', 0x1B18], ['Ra', 0x1B2D], ['Ka', 0x1B13], ['Da', 0x1B24],
    ['Ta', 0x1B22], ['Sa', 0x1B32], ['Wa', 0x1B2F], ['La', 0x1B2E], ['Ma', 0x1B2B], ['Ga', 0x1B15],
    ['Ba', 0x1B29], ['Nga', 0x1B17], ['Pa', 0x1B27], ['Ja', 0x1B1A], ['Ya', 0x1B2C], ['Nya', 0x1B1C],
];
const marks = [
    ['Ulu', 0x1B36], ['Suku', 0x1B38], ['Taling', 0x1B3E], ['Tedung', 0x1B35], ['Taling tedung', 0x1B40],
    ['Pepet', 0x1B42], ['Cecek', 0x1B02], ['Surang', 0x1B03], ['Bisah', 0x1B04], ['Adeg-adeg', 0x1B44],
];

export function initAksaraKeyboard(keyboard) {
    const scope = keyboard.closest('[data-aksara-scope]');
    if (!scope) return;
    const keys = keyboard.querySelector('[data-aksara-keys]');
    const status = keyboard.querySelector('[data-aksara-status]');
    const targetLabel = keyboard.querySelector('[data-aksara-target]');
    let target = null;
    let originalInputMode = null;
    let rendered = false;
    const restoreInputMode = () => {
        if (!target) return;
        if (originalInputMode === null) target.removeAttribute('inputmode');
        else target.setAttribute('inputmode', originalInputMode);
    };
    const selectTarget = field => {
        if (field === target) return;
        restoreInputMode();
        target = field;
        originalInputMode = field.getAttribute('inputmode');
        if (keyboard.open) field.setAttribute('inputmode', 'none');
        targetLabel.textContent = field.dataset.aksaraLabel || field.getAttribute('aria-label') || 'Kolom teks terpilih';
    };
    const renderKeys = mode => {
        const fragment = document.createDocumentFragment();
        for (const [name, code] of mode === 'pangangge' ? marks : consonants) {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.aksara = String.fromCodePoint(code);
            if (mode === 'pasangan') button.dataset.pasangan = '1';
            button.setAttribute('aria-label', mode === 'pasangan' ? `Pasangan ${name}` : name);
            const glyph = document.createElement('span');
            glyph.className = 'aksara-glyph';
            glyph.setAttribute('aria-hidden', 'true');
            glyph.textContent = (mode === 'pasangan' ? '\u25CC\u1B44' : mode === 'pangangge' ? '\u25CC' : '') + button.dataset.aksara;
            const label = document.createElement('span');
            label.className = 'aksara-key-name';
            label.textContent = name;
            button.append(glyph, label);
            fragment.append(button);
        }
        keys.replaceChildren(fragment);
        keys.setAttribute('aria-label', mode === 'pasangan' ? 'Gantungan dan gempelan' : mode === 'pangangge' ? 'Pangangge' : 'Tombol aksara dasar');
        keyboard.querySelectorAll('[data-aksara-mode]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.aksaraMode === mode)));
        rendered = true;
    };
    const syncOpen = () => {
        if (keyboard.open) {
            if (!rendered) renderKeys('dasar');
            if (target) target.setAttribute('inputmode', 'none');
        } else restoreInputMode();
    };
    const trackTarget = event => {
        if (event.target.matches('[data-aksara-input]') && event.target.closest('[data-aksara-scope]') === scope && !event.target.matches(':disabled') && !event.target.readOnly) selectTarget(event.target);
    };
    scope.addEventListener('pointerdown', trackTarget);
    scope.addEventListener('focusin', trackTarget);
    keyboard.addEventListener('toggle', syncOpen);
    keyboard.addEventListener('mousedown', event => {
        if (event.target.closest('button')) event.preventDefault();
    });
    keyboard.addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button) return;
        if (button.dataset.aksaraMode) {
            renderKeys(button.dataset.aksaraMode);
            return;
        }
        if (!target || !target.isConnected || !scope.contains(target) || target.matches(':disabled') || target.readOnly) {
            restoreInputMode();
            target = null;
            status.textContent = 'Pilih kolom yang ingin diisi terlebih dahulu.';
            return;
        }
        status.textContent = editAksara(target, { text: button.dataset.aksara, pasangan: Boolean(button.dataset.pasangan), action: button.dataset.aksaraAction });
        target.focus({ preventScroll: true });
    });
    keyboard.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            keyboard.open = false;
            restoreInputMode();
            keyboard.querySelector('summary').focus({ preventScroll: true });
        }
    });
    const scratch = scope.querySelector('textarea[data-aksara-input]:not([name])');
    if (scratch && scratch.closest('[data-aksara-scope]') === scope) selectTarget(scratch);
    syncOpen();
}

document.querySelectorAll('[data-aksara-keyboard]').forEach(initAksaraKeyboard);
