import { ProfileDropdown, SubjectDropdown, SubjectLinks } from '../Components/NavigationMenus';
import PageEffects from '../Components/PageEffects';
import { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import Dialog from '../Components/Dialog';
import Icon from '../Components/Icon';
import { Action, secondaryClass } from '../Components/forms';
import { buttonClass } from '../Components/ui';

export function usePresence(url) {
    useEffect(() => {
        if (!url) return;
        let controller;
        const ping = () => {
            if (document.visibilityState !== 'visible' || controller) return;
            controller = new AbortController();
            const current = controller;
            const timeout = setTimeout(() => current.abort(), 10000);
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                signal: current.signal,
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            })
                .catch(() => {})
                .finally(() => {
                    clearTimeout(timeout);
                    if (controller === current) controller = undefined;
                });
        };
        ping();
        const timer = window.setInterval(ping, 30000);
        document.addEventListener('visibilitychange', ping);
        return () => {
            window.clearInterval(timer);
            document.removeEventListener('visibilitychange', ping);
            controller?.abort();
        };
    }, [url]);
}
function FlashNotice({ flash }) {
    const [visible, setVisible] = useState(Boolean(flash?.message));
    useEffect(() => {
        setVisible(Boolean(flash?.message));
        if (!flash?.message) return;
        const timer = setTimeout(() => setVisible(false), 5000);
        return () => clearTimeout(timer);
    }, [flash]);
    if (!visible || !flash?.message) return null;
    return (
        <div
            role="status"
            className={`fixed left-1/2 top-[max(1rem,env(safe-area-inset-top))] z-[70] flex w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 items-center gap-3 rounded-full px-5 py-3 text-sm font-semibold text-white shadow-lg ${flash.tone === 'info' ? 'bg-primary' : 'bg-[#15803d]'}`}
        >
            <Icon name="check" className="size-6 shrink-0" />
            <span className="min-w-0 flex-1">{flash.message}</span>
            <button
                type="button"
                aria-label="Tutup notifikasi"
                onClick={() => setVisible(false)}
                className="grid size-9 shrink-0 place-items-center rounded-full focus-visible:outline-2 focus-visible:outline-white"
            >
                <Icon name="close" />
            </button>
        </div>
    );
}
export { FlashNotice };
export default function SiteLayout({ children }) {
    const { props, component } = usePage();
    const { viewer, navigation: nav, brand, flash, csrfToken, links, materials = {} } = props;
    const [menu, setMenu] = useState(false);
    const appMobile =
        viewer && !['Learning/Interview', 'Learning/Lesson'].includes(component) && !component.startsWith('Auth/');
    const playing = component === 'Rooms/Show' && (props.room?.status === 'active' || props.room?.feedback);
    const active =
        component === 'Home'
            ? 'home'
            : component.startsWith('Profile/')
              ? 'user'
              : /Quizzes|Rooms/.test(component)
                ? 'quiz'
                : 'book';
    usePresence(nav.presence);
    useEffect(() => {
        setMenu(false);
    }, [component]);
    return (
        <>
            <Head>
                <meta name="csrf-token" content={csrfToken} head-key="csrf-token" />
            </Head>
            <a
                href="#main-content"
                className="sr-only fixed left-4 top-4 z-[80] rounded-full bg-accent px-5 py-3 text-background focus:not-sr-only"
            >
                Lewati ke konten
            </a>
            <header
                className={`sticky top-0 z-40 bg-background/95 backdrop-blur-sm ${playing ? 'hidden md:block' : ''}`}
            >
                <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-10 lg:py-5">
                    <Link
                        href={nav.home}
                        aria-label="ARTASWAHA beranda"
                        className="flex shrink-0 items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-accent"
                    >
                        <img
                            src={brand.logo}
                            alt=""
                            width="44"
                            height="44"
                            className="size-10 object-contain sm:size-11"
                        />
                        <span className="font-display text-lg font-bold tracking-tight sm:text-2xl md:hidden lg:inline">
                            ARTASWAHA
                        </span>
                    </Link>
                    <nav aria-label="Navigasi utama" className="hidden items-center gap-1 md:flex">
                        <Link
                            href={nav.home}
                            aria-current={active === 'home' ? 'page' : undefined}
                            className={`${secondaryClass} !px-3 ${active === 'home' ? '!bg-primary/20 !text-accent' : '!bg-transparent'}`}
                        >
                            <Icon name="home" />
                            Beranda
                        </Link>
                        <SubjectDropdown
                            group="paribasa"
                            items={materials.paribasa}
                            active={
                                active === 'book' && (props.path?.group ?? props.lesson?.path?.group) === 'paribasa'
                            }
                        />
                        <SubjectDropdown
                            group="aksara"
                            items={materials.aksara}
                            active={active === 'book' && (props.path?.group ?? props.lesson?.path?.group) === 'aksara'}
                        />
                        <Link
                            href={nav.quizzes}
                            aria-current={active === 'quiz' ? 'page' : undefined}
                            className={`${secondaryClass} !px-3 ${active === 'quiz' ? '!bg-primary/20 !text-accent' : '!bg-transparent'}`}
                        >
                            <Icon name="quiz" />
                            Kuis
                        </Link>
                        {viewer ? (
                            <ProfileDropdown />
                        ) : (
                            <>
                                <Link href={nav.login} className={secondaryClass}>
                                    Masuk
                                </Link>
                                <Link href={nav.register} className={buttonClass}>
                                    Daftar
                                </Link>
                            </>
                        )}
                    </nav>
                    {!appMobile && (
                        <button
                            aria-label="Buka menu"
                            onClick={() => setMenu(true)}
                            className="grid size-11 place-items-center rounded-full bg-surface md:hidden"
                        >
                            <Icon name="menu" />
                        </button>
                    )}
                </div>
            </header>
            <FlashNotice flash={flash} />
            <main
                id="main-content"
                tabIndex="-1"
                className={`mx-auto w-full max-w-7xl px-4 pt-5 outline-none sm:px-6 sm:pt-8 lg:px-10 ${appMobile && !playing ? 'pb-32 md:pb-16' : 'pb-16'}`}
            >
                <PageEffects>{children}</PageEffects>
            </main>
            {appMobile && !playing && (
                <nav
                    aria-label="Navigasi utama mobile"
                    className="fixed inset-x-3 bottom-[max(.75rem,env(safe-area-inset-bottom))] z-40 mx-auto grid max-w-lg grid-cols-4 rounded-full bg-surface/95 p-1.5 shadow-xl shadow-black/40 backdrop-blur-lg md:hidden"
                >
                    {[
                        ['home', 'Beranda', nav.home],
                        ['book', 'Belajar', nav.learning],
                        ['quiz', 'Kuis', nav.quizzes],
                        ['user', 'Profil', nav.profile],
                    ].map(([icon, label, url]) => (
                        <Link
                            key={icon}
                            href={url}
                            aria-current={active === icon ? 'page' : undefined}
                            className={`flex min-h-16 flex-col items-center justify-center gap-1 rounded-full text-[11px] font-semibold focus-visible:outline-2 focus-visible:outline-accent ${active === icon ? 'text-accent' : 'text-foreground/55'}`}
                        >
                            <span
                                className={`grid size-9 place-items-center rounded-full ${active === icon ? 'bg-accent text-background' : ''}`}
                            >
                                <Icon name={icon} />
                            </span>
                            {label}
                        </Link>
                    ))}
                </nav>
            )}
            {(!viewer || component === 'Privacy') &&
                !component.startsWith('Auth/') &&
                !['Learning/Interview', 'Learning/Lesson'].includes(component) && (
                    <footer className="mx-auto mt-8 grid max-w-7xl gap-8 px-5 py-10 text-sm text-foreground/55 sm:grid-cols-3 lg:px-10">
                        <div>
                            <p className="font-display text-xl font-bold text-foreground">ARTASWAHA</p>
                            <p className="mt-3 max-w-xs leading-7">
                                Belajar bahasa, sastra, dan aksara Bali lewat cerita, latihan, dan permainan.
                            </p>
                        </div>
                        <div className="grid content-start gap-3">
                            <Link href={nav.learning}>Jelajahi materi</Link>
                            <Link href={nav.quizzes}>Arena kuis</Link>
                            <Link href={nav.privacy}>Kebijakan privasi</Link>
                        </div>
                        <div>
                            <p className="font-bold text-foreground">Hubungi kami</p>
                            <a href="mailto:kerthajayasoftware@gmail.com" className="mt-3 block break-all text-accent">
                                kerthajayasoftware@gmail.com
                            </a>
                            <p className="mt-4 text-xs">© {new Date().getFullYear()} ARTASWAHA</p>
                        </div>
                    </footer>
                )}
            <Dialog
                open={menu}
                onClose={() => setMenu(false)}
                title={viewer?.name ?? 'Mau ke mana?'}
                id="site-menu-title"
            >
                {viewer?.mode === 'anonymous' && (
                    <p className="mb-4 text-sm font-semibold text-accent">Akun anonim · di perangkat ini</p>
                )}
                <nav className="grid gap-2" aria-label="Menu halaman">
                    {[
                        ['paribasa', 'Paribasa Bali'],
                        ['aksara', 'Aksara Bali'],
                    ].map(([group, title]) => (
                        <details key={group} className="rounded-2xl bg-background p-3">
                            <summary className="min-h-11 cursor-pointer content-center font-bold">{title}</summary>
                            <SubjectLinks group={group} items={materials[group]} close={() => setMenu(false)} />
                        </details>
                    ))}
                    {[
                        ['Beranda', nav.home],
                        ['Semua materi', nav.learning],
                        ['Kuis', nav.quizzes],
                        ...(viewer
                            ? [
                                  ['Profil', nav.profile],
                                  ['Tentang aplikasi ini', links['profile.about']],
                              ]
                            : [
                                  ['Masuk', nav.login],
                                  ['Daftar akun', nav.register],
                              ]),
                        ...(viewer?.isAdmin ? [['Admin', nav.admin]] : []),
                        ...(viewer?.mode === 'anonymous' ? [['Masuk dengan akun', nav.login]] : []),
                    ].map(([label, url]) => (
                        <Link onClick={() => setMenu(false)} key={label} href={url} className={secondaryClass}>
                            {label}
                        </Link>
                    ))}
                </nav>
                {viewer?.mode === 'account' && (
                    <div className="mt-5">
                        <Action url={nav.logout}>Keluar</Action>
                    </div>
                )}
            </Dialog>
        </>
    );
}
