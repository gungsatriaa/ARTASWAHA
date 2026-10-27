import { useRef, useState } from 'react';
import { editAksara } from '../../aksara-edit';
import { secondaryClass } from './forms';

const bases = [
    ['Ha', 0x1b33],
    ['Na', 0x1b26],
    ['Ca', 0x1b18],
    ['Ra', 0x1b2d],
    ['Ka', 0x1b13],
    ['Da', 0x1b24],
    ['Ta', 0x1b22],
    ['Sa', 0x1b32],
    ['Wa', 0x1b2f],
    ['La', 0x1b2e],
    ['Ma', 0x1b2b],
    ['Ga', 0x1b15],
    ['Ba', 0x1b29],
    ['Nga', 0x1b17],
    ['Pa', 0x1b27],
    ['Ja', 0x1b1a],
    ['Ya', 0x1b2c],
    ['Nya', 0x1b1c],
];
const marks = [
    ['Ulu', 0x1b36],
    ['Suku', 0x1b38],
    ['Taling', 0x1b3e],
    ['Tedung', 0x1b35],
    ['Taling tedung', 0x1b40],
    ['Pepet', 0x1b42],
    ['Cecek', 0x1b02],
    ['Surang', 0x1b03],
    ['Bisah', 0x1b04],
    ['Adeg-adeg', 0x1b44],
];
export default function Keyboard({ autoOpen = false, targetRef }) {
    const scratch = useRef(null);
    const [open, setOpen] = useState(autoOpen),
        [mode, setMode] = useState('dasar'),
        [text, setText] = useState(''),
        [message, setMessage] = useState('');
    const edit = (command) => {
        const target = targetRef?.current ?? scratch.current;
        if (!target || target.disabled || !target.isConnected) {
            setMessage('Pilih kolom teks terlebih dahulu.');
            return;
        }
        setMessage(editAksara(target, command) || '');
        if (target === scratch.current) setText(target.value);
    };
    return (
        <details
            open={open}
            onToggle={(event) => setOpen(event.currentTarget.open)}
            className="my-5 rounded-2xl bg-primary/10 p-4"
        >
            <summary className="min-h-11 cursor-pointer content-center text-sm font-bold text-accent">
                Keyboard aksara Bali
            </summary>
            {open && (
                <div className="mt-3">
                    <div className="flex flex-wrap gap-2">
                        {['dasar', 'pangangge', 'pasangan'].map((value) => (
                            <button
                                type="button"
                                key={value}
                                onClick={() => setMode(value)}
                                aria-pressed={mode === value}
                                className={`${secondaryClass} ${mode === value ? '!bg-accent !text-background' : ''}`}
                            >
                                {value}
                            </button>
                        ))}
                    </div>
                    {!targetRef && (
                        <textarea
                            ref={scratch}
                            aria-label="Coretan latihan aksara"
                            value={text}
                            onChange={(event) => setText(event.target.value)}
                            rows={2}
                            className="my-3 w-full rounded-xl bg-background p-4 font-balinese text-2xl"
                        />
                    )}
                    <div className="mt-3 grid grid-cols-5 gap-2 sm:grid-cols-9">
                        {(mode === 'pangangge' ? marks : bases).map(([name, code]) => (
                            <button
                                type="button"
                                key={name}
                                aria-label={mode === 'pasangan' ? `Pasangan ${name}` : name}
                                onPointerDown={(event) => event.preventDefault()}
                                onClick={() =>
                                    edit({ text: String.fromCodePoint(code), pasangan: mode === 'pasangan' })
                                }
                                className="flex min-h-16 min-w-0 flex-col items-center justify-center rounded-xl bg-background p-1 hover:bg-primary/20"
                            >
                                <span lang="ban" className="font-balinese text-2xl leading-relaxed">
                                    {mode === 'pasangan' ? '◌᭄' : mode === 'pangangge' ? '◌' : ''}
                                    {String.fromCodePoint(code)}
                                </span>
                                <span className="mt-1 text-[10px] text-foreground/55">{name}</span>
                            </button>
                        ))}
                    </div>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {[
                            ['left', '←'],
                            ['right', '→'],
                            ['backspace', 'Hapus'],
                            ['space', 'Spasi'],
                            ['enter', 'Baris baru'],
                        ].map(([action, label]) => (
                            <button
                                key={action}
                                type="button"
                                className={secondaryClass}
                                onPointerDown={(event) => event.preventDefault()}
                                onClick={() => edit({ action })}
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                    <p role="status" className="mt-2 text-xs text-accent">
                        {message}
                    </p>
                    <p className="mt-2 text-xs leading-5 text-foreground/45">Coretan tidak disimpan atau dinilai.</p>
                </div>
            )}
        </details>
    );
}
