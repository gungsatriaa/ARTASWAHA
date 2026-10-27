import { useRef, useState } from 'react';
import { editAksara } from '../../aksara-edit';
import { secondaryClass } from './forms';
import Icon from './Icon';

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
            className="my-5 min-w-0 overflow-hidden rounded-card bg-surface p-4 sm:p-6"
        >
            <summary className="flex min-h-12 cursor-pointer list-none items-center gap-3 rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                <span className="grid size-11 shrink-0 place-items-center rounded-2xl bg-primary/15 text-accent">
                    <Icon name="aksara" className="size-6" />
                </span>
                <span className="min-w-0 flex-1">
                    <span className="block font-display text-lg font-bold sm:text-xl">Keyboard aksara Bali</span>
                    <span className="mt-1 block text-xs font-normal leading-5 text-foreground/50">
                        Ketuk aksara untuk menyusun tulisan.
                    </span>
                </span>
                <span className="grid size-9 shrink-0 place-items-center rounded-full bg-white/5 text-foreground/60">
                    <Icon name="chevron" className={`size-4 transition-transform ${open ? 'rotate-180' : ''}`} />
                </span>
            </summary>
            {open && (
                <div className="mt-5">
                    <div
                        role="group"
                        aria-label="Kategori aksara"
                        className="grid grid-cols-3 gap-1 rounded-full bg-background p-1"
                    >
                        {['dasar', 'pangangge', 'pasangan'].map((value) => (
                            <button
                                type="button"
                                key={value}
                                onClick={() => setMode(value)}
                                aria-pressed={mode === value}
                                aria-label={value}
                                className={`min-h-11 min-w-0 rounded-full px-2 py-3 text-xs font-semibold capitalize transition-colors focus-visible:outline-2 focus-visible:outline-accent sm:text-sm ${mode === value ? 'bg-accent text-background' : 'text-foreground/60 hover:bg-white/5 hover:text-foreground'}`}
                            >
                                {value}
                            </button>
                        ))}
                    </div>
                    {!targetRef && (
                        <label className="mt-5 block">
                            <span className="text-xs font-semibold text-foreground/60">Area latihan</span>
                            <textarea
                                ref={scratch}
                                aria-label="Coretan latihan aksara"
                                value={text}
                                onChange={(event) => setText(event.target.value)}
                                rows={2}
                                placeholder="Tulis aksara di sini…"
                                spellCheck={false}
                                className="mt-2 min-h-28 w-full resize-y rounded-2xl bg-background px-4 py-5 font-balinese text-3xl leading-relaxed text-foreground outline-none placeholder:font-sans placeholder:text-sm placeholder:text-foreground/30 focus-visible:ring-2 focus-visible:ring-primary"
                            />
                        </label>
                    )}
                    <div className="mt-5 grid grid-cols-5 gap-1.5 sm:grid-cols-9 sm:gap-2">
                        {(mode === 'pangangge' ? marks : bases).map(([name, code]) => (
                            <button
                                type="button"
                                key={name}
                                aria-label={mode === 'pasangan' ? `Pasangan ${name}` : name}
                                onPointerDown={(event) => event.preventDefault()}
                                onClick={() =>
                                    edit({ text: String.fromCodePoint(code), pasangan: mode === 'pasangan' })
                                }
                                className="flex min-h-20 min-w-0 select-none flex-col items-center justify-center gap-1 rounded-2xl bg-background px-1 py-2 text-foreground transition-colors hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent active:bg-primary/30 sm:min-h-24"
                            >
                                <span
                                    lang="ban"
                                    className="grid min-h-10 place-items-center font-balinese text-3xl leading-relaxed"
                                >
                                    {mode === 'pasangan' ? '◌᭄' : mode === 'pangangge' ? '◌' : ''}
                                    {String.fromCodePoint(code)}
                                </span>
                                <span className="text-center font-sans text-[10px] font-medium leading-4 text-foreground/55 sm:text-xs">
                                    {name}
                                </span>
                            </button>
                        ))}
                    </div>
                    <div className="mt-4 flex flex-wrap gap-2 rounded-2xl bg-background/60 p-2">
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
                                className={`${secondaryClass} grow !px-3 !py-2 sm:grow-0 sm:!px-5`}
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
                    {!targetRef && (
                        <p className="mt-3 text-xs leading-5 text-foreground/45">
                            Coretan tidak disimpan atau dinilai.
                        </p>
                    )}
                </div>
            )}
        </details>
    );
}
