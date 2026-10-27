import { useEffect, useRef } from 'react';
import Icon from './Icon';

export default function Dialog({ open, onClose, title, id, children, className = '', variant = 'info' }) {
    const dialog = useRef(null);
    useEffect(() => {
        if (!open || !dialog.current) return;
        const node = dialog.current;
        const previous = document.activeElement;
        const overflow = document.body.style.overflow;
        node.showModal();
        document.body.style.overflow = 'hidden';
        return () => {
            node.close();
            document.body.style.overflow = overflow;
            if (previous?.isConnected) previous.focus({ preventScroll: true });
        };
    }, [open]);

    if (!open) return null;
    return (
        <dialog
            ref={dialog}
            aria-labelledby={id}
            data-variant={variant}
            onCancel={(event) => {
                event.preventDefault();
                onClose();
            }}
            onClick={(event) => {
                if (event.target !== event.currentTarget) return;
                const box = event.currentTarget.getBoundingClientRect();
                if (
                    event.clientX < box.left ||
                    event.clientX > box.right ||
                    event.clientY < box.top ||
                    event.clientY > box.bottom
                )
                    onClose();
            }}
            className={`m-auto max-h-[calc(100dvh-2rem)] w-[min(28rem,calc(100%-2rem))] overflow-y-auto rounded-card bg-surface p-6 text-foreground shadow-2xl backdrop:bg-black/75 sm:p-8 ${className}`}
        >
            <header className="mb-5 flex items-center justify-between gap-4">
                <div className="flex min-w-0 items-center gap-3">
                    {variant !== 'info' && (
                        <span
                            aria-hidden="true"
                            className={`grid size-11 shrink-0 place-items-center rounded-full ${variant === 'success' ? 'bg-success/15 text-success' : 'bg-danger/15 text-danger'}`}
                        >
                            <Icon name={variant === 'success' ? 'check' : 'close'} className="size-6" />
                        </span>
                    )}
                    <h2
                        id={id}
                        className={`min-w-0 break-words font-display text-xl font-bold ${variant === 'success' ? 'text-success' : variant === 'error' ? 'text-danger' : ''}`}
                    >
                        {title}
                    </h2>
                </div>
                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Tutup"
                    className="grid size-11 shrink-0 place-items-center rounded-full bg-background text-foreground/65 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                >
                    <Icon name="close" />
                </button>
            </header>
            {children}
        </dialog>
    );
}
