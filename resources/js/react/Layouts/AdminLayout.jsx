import PageEffects from '../Components/PageEffects';
import NavigationLink from '../Components/NavigationLink';
import { useEffect, useState } from 'react';
import { Head, Link, usePage } from '../routing';
import Dialog from '../Components/Dialog';
import Icon from '../Components/Icon';
import { Action, secondaryClass } from '../Components/forms';
import { FlashNotice } from './SiteLayout';

const groups = [
    [
        'Ringkasan',
        [
            ['Admin/Dashboard', 'Dashboard', 'admin.index', 'home'],
            ['Admin/Analytics', 'Statistik & aktivitas', 'admin.analytics.index', 'chart'],
        ],
    ],
    [
        'Pembelajaran',
        [
            ['Admin/Path', 'Jalur belajar', 'admin.learning-paths.index', 'route'],
            ['Admin/Lesson', 'Modul pembelajaran', 'admin.lessons.index', 'book'],
        ],
    ],
    [
        'Layanan & AI',
        [
            ['Admin/Ai', 'Pusat AI', 'admin.ai.index', 'spark'],
            ['Admin/Audio', 'Audio pembelajaran', 'admin.audio.index', 'audio'],
        ],
    ],
];
export default function AdminLayout({ children }) {
    const { props, component, url } = usePage();
    const { viewer, brand, links, navigation, flash, csrfToken } = props;
    const [open, setOpen] = useState(false);
    useEffect(() => setOpen(false), [url]);
    const active = (prefix) =>
        component.startsWith(prefix) || (prefix === 'Admin/Lesson' && component === 'Admin/Preview');
    const current = groups.flatMap(([, items]) => items).find(([prefix]) => active(prefix));
    const nav = (
        <nav aria-label="Menu pengelolaan" className="space-y-7">
            {groups.map(([label, items]) => (
                <section key={label}>
                    <h2 className="mb-2 px-3 text-xs font-medium text-foreground/35">{label}</h2>
                    <div className="space-y-1">
                        {items.map(([prefix, title, name, icon]) => (
                            <NavigationLink
                                page={{ "Admin/Path": "Admin/Paths", "Admin/Lesson": "Admin/Lessons", "Admin/Audio": "Admin/AudioIndex" }[prefix] ?? prefix}
                                key={name}
                                href={links[name]}
                                onClick={() => setOpen(false)}
                                aria-current={active(prefix) ? 'page' : undefined}
                                className={`group flex min-h-12 items-center gap-3 rounded-2xl px-3 py-3 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-accent ${active(prefix) ? 'bg-primary/20 text-accent' : 'text-foreground/55 hover:bg-white/5 hover:text-foreground'}`}
                            >
                                <Icon name={icon} className="size-5 shrink-0" />
                                <span className="min-w-0 flex-1">{title}</span>
                                {active(prefix) && <span className="size-1.5 shrink-0 rounded-full bg-accent" />}
                            </NavigationLink>
                        ))}
                    </div>
                </section>
            ))}
            <section>
                <h2 className="mb-2 px-3 text-xs font-medium text-foreground/35">Akses situs</h2>
                <Link
                    href={navigation.quizzes}
                    className="flex min-h-11 items-center gap-3 rounded-2xl px-3 text-sm font-semibold text-foreground/55 hover:bg-white/5 hover:text-accent"
                >
                    <Icon name="quiz" /> Kelola kuis <Icon name="arrow" className="ml-auto size-4" />
                </Link>
                <Link
                    href={navigation.home}
                    className="flex min-h-11 items-center gap-3 rounded-2xl px-3 text-sm font-semibold text-foreground/55 hover:bg-white/5 hover:text-accent"
                >
                    <Icon name="home" /> Lihat situs <Icon name="arrow" className="ml-auto size-4" />
                </Link>
            </section>
        </nav>
    );
    return (
        <div className="admin-shell min-h-screen font-sans lg:grid lg:grid-cols-[16.5rem_minmax(0,1fr)]">
            <Head>
                <meta name="csrf-token" content={csrfToken} head-key="csrf-token" />
            </Head>
            <a
                href="#admin-content"
                className="sr-only fixed left-4 top-4 z-[80] rounded-full bg-accent px-5 py-3 text-background focus:not-sr-only"
            >
                Lewati ke konten
            </a>
            <aside className="sticky top-0 hidden h-dvh min-h-0 flex-col bg-surface px-4 py-6 lg:flex">
                <Link href={links['admin.index']} className="mb-8 flex items-center gap-3 px-2">
                    <img src={brand.logo} alt="" width="38" height="46" className="h-11 w-9 object-contain" />
                    <span className="font-display text-lg font-bold">
                        ARTASWAHA
                        <span className="mt-0.5 block font-sans text-[11px] font-medium text-accent">
                            Ruang pengelola
                        </span>
                    </span>
                </Link>
                <div className="min-h-0 flex-1 overflow-y-auto pr-1">{nav}</div>
                <div className="mt-5 flex items-center gap-3 rounded-2xl bg-background p-3">
                    <img
                        alt=""
                        src={viewer?.avatar}
                        width="36"
                        height="36"
                        className="size-9 shrink-0 rounded-full object-cover"
                    />
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-xs font-bold">{viewer?.name}</p>
                        <p className="mt-1 text-[11px] text-foreground/40">Administrator</p>
                    </div>
                    <Action
                        url={navigation.logout}
                        className="grid size-11 shrink-0 place-items-center rounded-full text-foreground/50 hover:bg-danger/10 hover:text-danger"
                    >
                        <Icon name="login" />
                        <span className="sr-only">Keluar</span>
                    </Action>
                </div>
            </aside>
            <div className="min-w-0">
                <header className="sticky top-0 z-30 flex min-h-18 items-center justify-between gap-3 bg-background/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <div className="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            aria-label="Buka menu admin"
                            className="grid size-11 shrink-0 place-items-center rounded-full bg-surface text-foreground/65 lg:hidden"
                            onClick={() => setOpen(true)}
                        >
                            <Icon name="menu" />
                        </button>
                        <div className="min-w-0">
                            <p className="text-[11px] text-foreground/35">Pengelolaan platform</p>
                            <p className="truncate text-sm font-semibold">{current?.[1] ?? 'Ruang kendali'}</p>
                        </div>
                    </div>
                    <Link
                        href={navigation.profile}
                        aria-label="Profil administrator"
                        className="flex shrink-0 items-center gap-3 rounded-full bg-surface py-1.5 pl-3 pr-1.5"
                    >
                        <span className="hidden text-xs text-foreground/60 sm:block">{viewer?.name}</span>
                        <img
                            alt=""
                            src={viewer?.avatar}
                            width="32"
                            height="32"
                            className="size-8 rounded-full object-cover"
                        />
                    </Link>
                </header>
                <FlashNotice flash={flash} />
                <main
                    tabIndex={-1}
                    id="admin-content"
                    className="admin-workspace mx-auto max-w-[100rem] px-4 pb-12 pt-5 outline-none sm:px-6 lg:px-8 lg:pt-6"
                >
                    <PageEffects>{children}</PageEffects>
                </main>
            </div>
            <Dialog
                open={open}
                onClose={() => setOpen(false)}
                title="Menu admin"
                id="admin-menu-title"
                className="!ml-0 !mr-auto !h-dvh !max-h-dvh !w-[min(21rem,calc(100%-2rem))] !rounded-l-none"
            >
                {nav}
                <Action url={navigation.logout} className={`${secondaryClass} mt-6 w-full`}>
                    Keluar
                </Action>
            </Dialog>
        </div>
    );
}
