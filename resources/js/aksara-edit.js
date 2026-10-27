export function pasanganText(value, start, character) {
    const before = value.slice(0, start);
    if (/[\u1B13-\u1B33]\u1B44$/.test(before)) return character;
    return /[\u1B13-\u1B33]$/.test(before) ? '\u1B44' + character : null;
}

function previousCharacter(value, position) {
    if (position === 0) return 0;
    const last = value.charCodeAt(position - 1);
    const previous = value.charCodeAt(position - 2);
    return position - (last >= 0xDC00 && last <= 0xDFFF && previous >= 0xD800 && previous <= 0xDBFF ? 2 : 1);
}

export function editAksara(field, { text, pasangan = false, action } = {}) {
    let start = field.selectionStart;
    let end = field.selectionEnd;
    if (action === 'left' || action === 'right') {
        const position = action === 'left'
            ? (start === end ? previousCharacter(field.value, start) : start)
            : (start === end ? Math.min(field.value.length, end + (field.value.codePointAt(end) > 0xFFFF ? 2 : 1)) : end);
        field.setSelectionRange(position, position);
        return '';
    }
    if (action === 'backspace') {
        if (start === end) start = previousCharacter(field.value, start);
        text = '';
    } else if (action === 'space') text = ' ';
    else if (action === 'enter') {
        if (field.tagName !== 'TEXTAREA') return 'Baris baru hanya tersedia di kolom teks panjang.';
        text = '\n';
    }
    if (pasangan) {
        text = pasanganText(field.value, start, text);
        if (text === null) return 'Letakkan kursor tepat setelah aksara dasar atau adeg-adeg yang mengikutinya.';
    }
    if (typeof text !== 'string') return '';
    if (field.maxLength >= 0 && field.value.length - (end - start) + text.length > field.maxLength) return 'Batas panjang kolom tercapai.';
    field.setRangeText(text, start, end, 'end');
    field.dispatchEvent(new Event('input', { bubbles: true }));
    return '';
}
