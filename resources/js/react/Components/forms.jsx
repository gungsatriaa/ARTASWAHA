import { useId, useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import Dialog from './Dialog';
import { Button, buttonClass } from './ui';

export const inputClass =
    'mt-2 min-h-12 w-full min-w-0 rounded-control bg-background px-4 py-3 text-base font-normal text-foreground outline-none ring-1 ring-white/10 transition focus:ring-2 focus:ring-accent disabled:opacity-50';
export const secondaryClass =
    'inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-white/5 px-5 py-3 text-sm font-bold text-foreground/75 transition hover:bg-primary/20 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent disabled:opacity-50';
export function Card({ as: Tag = 'section', className = '', children, ...props }) {
    return (
        <Tag
            data-reveal
            data-ui="card"
            className={`min-w-0 rounded-card bg-surface p-5 sm:p-7 ${className}`}
            {...props}
        >
            {children}
        </Tag>
    );
}
export function Field({ label, error, as: Tag = 'input', hint, className = '', ...props }) {
    const id = useId();
    return (
        <div className={`min-w-0 ${className}`}>
            <label htmlFor={id} className="block text-sm font-semibold">
                {label}
            </label>
            <Tag
                id={id}
                className={inputClass}
                aria-invalid={Boolean(error)}
                aria-describedby={error ? `${id}-error` : undefined}
                {...props}
            />
            {hint && <p className="mt-2 text-xs font-normal leading-6 text-foreground/50">{hint}</p>}
            {error && (
                <p id={`${id}-error`} role="alert" className="mt-2 text-sm text-danger">
                    {error}
                </p>
            )}
        </div>
    );
}
export function Check({ children, checked, onChange, ...props }) {
    return (
        <label className="flex min-h-12 cursor-pointer items-start gap-3 rounded-2xl bg-background p-4 text-sm leading-6">
            <input
                type="checkbox"
                checked={checked}
                onChange={onChange}
                className="mt-1 size-5 shrink-0 accent-[var(--color-accent)]"
                {...props}
            />
            <span>{children}</span>
        </label>
    );
}
export function Errors({ errors }) {
    const messages = [...new Set(Object.values(errors ?? {}))];
    return messages.length ? (
        <div role="alert" className="my-5 rounded-2xl bg-danger/10 p-4 text-sm leading-6 text-danger">
            <p className="font-bold">Periksa isian berikut.</p>
            <ul className="mt-2 list-disc pl-5">
                {messages.map((message, index) => (
                    <li key={index}>{message}</li>
                ))}
            </ul>
        </div>
    ) : null;
}
export function Action({ url, method = 'post', data = {}, confirm, children, className = secondaryClass, onSuccess }) {
    const form = useForm(data);
    const [open, setOpen] = useState(false);
    const id = useId();
    const submit = () =>
        form[method](url, {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                onSuccess?.();
            },
        });
    return (
        <>
            <button
                type="button"
                className={className}
                disabled={form.processing}
                onClick={() => (confirm ? setOpen(true) : submit())}
            >
                {children}
            </button>
            {confirm && (
                <Dialog
                    open={open}
                    onClose={() => setOpen(false)}
                    title={confirm}
                    id={id}
                    variant={Object.keys(form.errors).length ? 'error' : 'info'}
                >
                    <p className="text-sm leading-7 text-foreground/60">Pastikan pilihanmu sebelum melanjutkan.</p>
                    <Errors errors={form.errors} />
                    <div className="mt-6 flex flex-wrap justify-end gap-3">
                        <button className={secondaryClass} onClick={() => setOpen(false)}>
                            Batal
                        </button>
                        <Button onClick={submit} disabled={form.processing}>
                            Ya, lanjutkan
                        </Button>
                    </div>
                </Dialog>
            )}
        </>
    );
}
export function Heading({ title, children, action }) {
    return (
        <header data-ui="page-heading" className="mb-7 flex flex-wrap items-start justify-between gap-4">
            <div className="min-w-0 max-w-2xl">
                <h1 className="break-words font-display text-3xl font-bold leading-tight tracking-tight sm:text-4xl">
                    {title}
                </h1>
                {children && <p className="mt-3 text-sm leading-7 text-foreground/60">{children}</p>}
            </div>
            {action}
        </header>
    );
}
export function Back({ href, children = 'Kembali' }) {
    return (
        <Link
            href={href}
            className="mb-5 inline-flex min-h-11 items-center gap-2 rounded-full text-sm font-semibold text-foreground/60 hover:text-accent"
        >
            ← {children}
        </Link>
    );
}
export function Stat({ label, value, note }) {
    return (
        <div data-ui="stat" className="min-w-0 rounded-card bg-surface p-4 sm:p-6">
            <p className="text-xs leading-5 text-foreground/55 sm:text-sm">{label}</p>
            <p className="mt-3 admin-number font-sans text-3xl font-bold tabular-nums sm:text-4xl">{value ?? '—'}</p>
            {note && <p className="mt-2 text-xs leading-5 text-foreground/45">{note}</p>}
        </div>
    );
}
export function Status({ value }) {
    const labels = {
        queued: 'Mengantre',
        delayed: 'Antrean tertunda',
        processing: 'Diproses',
        completed: 'Selesai',
        failed: 'Gagal',
        unavailable: 'Belum tersedia',
        review: 'Perlu tinjauan',
        ready: 'Siap digunakan',
        stale: 'Perlu diperbarui',
        draft: 'Draf',
        published: 'Terbit',
        archived: 'Diarsipkan',
    };
    return (
        <span
            className={`inline-flex rounded-full px-3 py-1.5 text-xs font-bold ${['ready', 'completed', 'published'].includes(value) ? 'bg-success/10 text-success' : value === 'failed' ? 'bg-danger/10 text-danger' : 'bg-accent/10 text-accent'}`}
        >
            {labels[value] ?? 'Belum diminta'}
        </span>
    );
}
