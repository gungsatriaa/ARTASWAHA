import PageEffects from '../Components/PageEffects';
import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import Dialog from '../Components/Dialog';
import Icon from '../Components/Icon';
import { Action, secondaryClass } from '../Components/forms';
import { FlashNotice, usePresence } from './SiteLayout';

export default function AdminLayout({ children }) {
    const { props, component } = usePage();
    const { viewer, brand, links, navigation, flash, csrfToken } = props;
    const [open, setOpen] = useState(false);
    usePresence(navigation.presence);
    const items = [
        ['Admin/Dashboard', 'Dashboard', 'admin.index', 'home'],
        ['Admin/Analytics', 'Statistik', 'admin.analytics.index', 'chart'],
        ['Admin/Ai', 'Pusat AI', 'admin.ai.index', 'spark'],
        ['Admin/Audio', 'Audio pembelajaran', 'admin.audio.index', 'audio'],
        ['Admin/Path', 'Jalur belajar', 'admin.learning-paths.index', 'route'],
        ['Admin/Lesson', 'Modul', 'admin.lessons.index', 'book'],
    ];
    const nav = (
        <nav aria-label="Menu pengelolaan" className="grid gap-2">
            {items.map(([prefix, label, name, icon]) => (
                <Link
                    key={name}
                    href={links[name]}
                    onClick={() => setOpen(false)}
                    aria-current={component.startsWith(prefix) ? 'page' : undefined}
                    className={`flex min-h-12 items-center gap-3 rounded-2xl px-4 text-sm font-bold ${component.startsWith(prefix) ? 'bg-primary/20 text-accent' : 'text-foreground/60 hover:bg-background'}`}
                >
                    <Icon name={icon} />
                    {label}
                </Link>
            ))}
            <Link href={navigation.quizzes} className={secondaryClass}>
                <Icon name="quiz" />
                Kelola kuis
            </Link>
            <Link href={navigation.home} className={secondaryClass}>
                Lihat situs
            </Link>
        </nav>
    );
    return (
        <div className="min-h-screen lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]">
            <Head>
                <meta name="csrf-token" content={csrfToken} head-key="csrf-token" />
            </Head>
            <a href="#admin-content" className="sr-only focus:not-sr-only">
                Lewati ke konten
            </a>
            <aside className="sticky top-0 hidden h-dvh flex-col bg-surface p-5 lg:flex">
                <Link href={links['admin.index']} className="mb-9 flex items-center gap-3">
                    <img src={brand.logo} alt="" width="40" height="40" />
                    <span className="font-display text-lg font-bold">
                        ARTASWAHA<span className="block text-xs font-normal text-accent">Ruang pengelola</span>
                    </span>
                </Link>
                {nav}
                <div className="mt-auto pt-7">
                    <Action url={navigation.logout}>Keluar</Action>
                </div>
            </aside>
            <div className="min-w-0">
                <header className="sticky top-0 z-30 flex min-h-20 items-center justify-between gap-4 bg-background/95 px-4 backdrop-blur sm:px-8">
                    <button
                        aria-label="Buka menu admin"
                        className={`${secondaryClass} lg:hidden`}
                        onClick={() => setOpen(true)}
                    >
                        <Icon name="menu" />
                    </button>
                    <p className="text-sm font-semibold text-foreground/55">Ruang kendali ARTASWAHA</p>
                    <Link href={navigation.profile} className="flex min-w-0 items-center gap-3">
                        <span className="hidden text-sm sm:block">{viewer?.name}</span>
                        <img
                            alt=""
                            src={viewer?.avatar}
                            width="40"
                            height="40"
                            className="size-10 shrink-0 rounded-full object-cover"
                        />
                    </Link>
                </header>
                <FlashNotice flash={flash} />
                <main tabIndex="-1" id="admin-content" className="mx-auto max-w-[100rem] p-4 pb-12 sm:p-8 lg:p-10">
                    <PageEffects>{children}</PageEffects>
                </main>
            </div>
            <Dialog open={open} onClose={() => setOpen(false)} title="Menu admin" id="admin-menu-title">
                {nav}
            </Dialog>
        </div>
    );
}
