import { useEffect, useId, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import Icon from './Icon';

export default function Dropdown({ label, trigger, children, active = false, align = 'left' }) {
    const [open, setOpen] = useState(false);
    const root = useRef(null),
        button = useRef(null),
        panel = useRef(null),
        keyboard = useRef(false);
    const id = useId();
    const { url } = usePage();
    useEffect(() => setOpen(false), [url]);
    useEffect(() => {
        if (!open) return;
        if (keyboard.current) {
            panel.current?.querySelector('a, button')?.focus();
            keyboard.current = false;
        }
        const outside = (event) => {
            if (!root.current?.contains(event.target)) setOpen(false);
        };
        const escape = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                setOpen(false);
                button.current?.focus();
            }
        };
        document.addEventListener('pointerdown', outside);
        document.addEventListener('focusin', outside);
        document.addEventListener('keydown', escape);
        return () => {
            document.removeEventListener('pointerdown', outside);
            document.removeEventListener('focusin', outside);
            document.removeEventListener('keydown', escape);
        };
    }, [open]);
    return (
        <div ref={root} className="relative">
            <button
                ref={button}
                type="button"
                aria-label={label}
                aria-expanded={open}
                aria-controls={id}
                onClick={() => setOpen(!open)}
                onKeyDown={(event) => {
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        if (open) panel.current?.querySelector('a, button')?.focus();
                        else {
                            keyboard.current = true;
                            setOpen(true);
                        }
                    }
                }}
                className={`flex min-h-11 items-center gap-2 rounded-full px-3 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-accent ${open || active ? 'bg-primary/20 text-accent' : 'text-foreground/65 hover:bg-white/5 hover:text-foreground'}`}
            >
                {trigger}
                <Icon name="chevron" className={`size-4 shrink-0 transition-transform ${open ? 'rotate-180' : ''}`} />
            </button>
            {open && (
                <div
                    ref={panel}
                    id={id}
                    aria-label={label}
                    onClick={(event) => {
                        if (event.target.closest('a')) setOpen(false);
                    }}
                    onSubmitCapture={() => setOpen(false)}
                    className={`absolute top-[calc(100%+.75rem)] z-50 w-80 max-w-[calc(100vw-2rem)] rounded-card bg-surface p-3 react-page-enter shadow-2xl shadow-black/50 ${align === 'right' ? 'right-0' : 'left-0'}`}
                >
                    {children}
                </div>
            )}
        </div>
    );
}
