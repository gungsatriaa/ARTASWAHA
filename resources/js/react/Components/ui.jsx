import { Head, Link } from '@inertiajs/react';
import * as m from 'motion/react-m';
import Icon from './Icon';
import BalineseIcon from './BalineseIcon';

export const buttonClass =
    'inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-accent px-5 py-3 text-sm font-bold text-background transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent';

export function Button({ children, ...props }) {
    return (
        <m.button whileTap={{ scale: 0.97 }} {...props} className={`${buttonClass} ${props.className ?? ''}`}>
            {children}
        </m.button>
    );
}

export function PageMeta({ title, description, canonical, robots = 'noindex, follow', schema }) {
    return (
        <Head title={title}>
            {description && <meta name="description" content={description} head-key="description" />}
            {canonical && <link rel="canonical" href={canonical} head-key="canonical" />}
            <meta name="robots" content={robots} head-key="robots" />
            <meta property="og:title" content={title} head-key="og:title" />
            <meta property="og:description" content={description ?? ''} head-key="og:description" />
            <meta property="og:locale" content="id_ID" head-key="og:locale" />
            <meta property="og:site_name" content="ARTASWAHA" head-key="og:site_name" />
            <meta property="og:type" content="website" head-key="og:type" />
            {canonical && <meta property="og:url" content={canonical} head-key="og:url" />}
            <meta name="twitter:card" content="summary" head-key="twitter:card" />
            {schema && (
                <script
                    type="application/ld+json"
                    head-key="structured-data"
                    dangerouslySetInnerHTML={{ __html: JSON.stringify(schema).replace(/</g, '\\u003c') }}
                />
            )}
        </Head>
    );
}

export function CategoryIcon({ group, title = '', className = 'size-12' }) {
    const subject = title.toLocaleLowerCase('id-ID');
    const glyph = subject.includes('wreastra')
        ? 'ᬳ'
        : subject.includes('angka')
          ? '᭑'
          : subject.includes('arda')
            ? '᭄ᬬ'
            : subject.includes('tengenan')
              ? 'ᬂ'
              : subject.includes('suara')
                ? 'ᬶ'
                : 'ᬳ';
    return (
        <span
            className={`grid shrink-0 place-items-center rounded-2xl bg-primary/20 text-accent ${className}`}
            aria-hidden="true"
        >
            {group === 'aksara' ? <BalineseIcon className="size-8">{glyph}</BalineseIcon> : <Icon name="chat" className="size-6" />}
        </span>
    );
}

export function Pagination({ current_page: current, last_page: last, prev_page_url: previous, next_page_url: next }) {
    if (last <= 1) return null;
    return (
        <nav className="mt-7 flex items-center justify-between gap-4" aria-label="Halaman hasil">
            {previous ? (
                <Link
                    href={previous}
                    aria-label="Sebelumnya"
                    preserveScroll
                    className="inline-flex min-h-11 items-center gap-2 rounded-full bg-surface px-4 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-accent"
                >
                    <Icon name="back" />
                    <span className="hidden sm:inline">Sebelumnya</span>
                </Link>
            ) : (
                <span />
            )}
            <span className="text-xs text-foreground/55">
                {current} / {last}
            </span>
            {next ? (
                <Link
                    href={next}
                    aria-label="Berikutnya"
                    preserveScroll
                    className="inline-flex min-h-11 items-center gap-2 rounded-full bg-surface px-4 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-accent"
                >
                    <span className="hidden sm:inline">Berikutnya</span>
                    <Icon name="arrow" />
                </Link>
            ) : (
                <span />
            )}
        </nav>
    );
}

export function EmptyState({ title, children, action }) {
    return (
        <div className="rounded-card bg-surface px-6 py-10 text-center">
            <span className="mx-auto grid size-14 place-items-center rounded-full bg-primary/15 text-accent">
                <Icon name="book" className="size-7" />
            </span>
            <h2 className="mt-5 font-display text-xl font-bold">{title}</h2>
            <p className="mx-auto mt-2 max-w-md text-sm leading-7 text-foreground/60">{children}</p>
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
