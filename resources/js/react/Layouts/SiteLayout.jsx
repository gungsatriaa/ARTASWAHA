import Footer from '../Components/Footer';
import { ProfileDropdown, SubjectDropdown, SubjectLinks } from '../Components/NavigationMenus';
import PageEffects from '../Components/PageEffects';
import NavigationLink from '../Components/NavigationLink';
import { useEffect, useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import Dialog from '../Components/Dialog';
import Icon from '../Components/Icon';
import { Action, secondaryClass } from '../Components/forms';
import { buttonClass } from '../Components/ui';

const navigationPages = {
    home: 'Home', book: 'Learning/Catalog', quiz: 'Quizzes/Index',
    user: 'Profile/Show', info: 'Profile/About', chart: 'Admin/Dashboard',
};

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
    const appMobile = viewer && component !== 'Learning/Interview' && !component.startsWith('Auth/');
    const playing = component === 'Rooms/Show' && (props.room?.status === 'active' || props.room?.feedback);
    const active =
        component === 'Home'
            ? 'home'
            : component.startsWith('Profile/')
              ? 'user'
              : /Quizzes|Rooms/.test(component)
                ? 'quiz'
                : 'book';
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
                    <NavigationLink
                        page="Home"
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
                    </NavigationLink>
                    <nav aria-label="Navigasi utama" className="hidden items-center gap-1 md:flex">
                        <NavigationLink
                            page="Home"
                            href={nav.home}
                            aria-current={active === 'home' ? 'page' : undefined}
                            className={`${secondaryClass} !px-3 ${active === 'home' ? '!bg-primary/20 !text-accent' : '!bg-transparent'}`}
                        >
                            <Icon name="home" />
                            Beranda
                        </NavigationLink>
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
                        <NavigationLink
                            page="Quizzes/Index"
                            href={nav.quizzes}
                            aria-current={active === 'quiz' ? 'page' : undefined}
                            className={`${secondaryClass} !px-3 ${active === 'quiz' ? '!bg-primary/20 !text-accent' : '!bg-transparent'}`}
                        >
                            <Icon name="quiz" />
                            Kuis
                        </NavigationLink>
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
                            type="button"
                            aria-label="Buka menu"
                            aria-expanded={menu}
                            aria-haspopup="dialog"
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
                        <NavigationLink page={navigationPages[icon]}
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
                        </NavigationLink>
                    ))}
                </nav>
            )}
            {(component === 'Home' ||
                component === 'Privacy' ||
                (!viewer &&
                    !component.startsWith('Auth/') &&
                    !['Learning/Interview', 'Learning/Lesson'].includes(component))) && (
                <Footer className={viewer && component === 'Home' ? 'hidden md:block' : ''} />
            )}
            <Dialog
                open={menu}
                onClose={() => setMenu(false)}
                title={viewer?.name ?? 'Menu ARTASWAHA'}
                id="site-menu-title"
                className="site-drawer !fixed !inset-y-0 !left-auto !right-0 !m-0 !h-dvh !max-h-dvh !w-[min(22rem,calc(100%-1rem))] !rounded-r-none !p-5"
            >
                <div className="flex min-h-[calc(100dvh-7rem)] flex-col gap-6 pb-[env(safe-area-inset-bottom)]">
                    {viewer?.mode === 'anonymous' && (
                        <p className="text-sm font-semibold text-accent">Akun anonim · di perangkat ini</p>
                    )}
                    <nav className="space-y-5" aria-label="Menu halaman">
                        <div className="space-y-1">
                            {[
                                ['Beranda', nav.home, 'home', active === 'home'],
                                ['Semua materi', nav.learning, 'book', active === 'book'],
                                ['Kuis', nav.quizzes, 'quiz', active === 'quiz'],
                            ].map(([label, url, icon, selected]) => (
                                <NavigationLink page={navigationPages[icon]}
                                    onClick={() => setMenu(false)}
                                    key={label}
                                    href={url}
                                    aria-current={selected ? 'page' : undefined}
                                    className={`flex min-h-12 items-center gap-3 rounded-2xl px-4 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-accent ${selected ? 'bg-primary/20 text-accent' : 'text-foreground/65 hover:bg-background hover:text-foreground'}`}
                                >
                                    <Icon name={icon} />
                                    <span>{label}</span>
                                </NavigationLink>
                            ))}
                        </div>
                        <div className="space-y-2">
                            <p className="px-4 text-xs font-semibold text-foreground/40">Materi belajar</p>
                            {[
                                ['paribasa', 'Paribasa Bali', 'chat'],
                                ['aksara', 'Aksara Bali', 'aksara'],
                            ].map(([group, title, icon]) => (
                                <details key={group} className="group rounded-2xl bg-background p-2">
                                    <summary className="flex min-h-12 cursor-pointer list-none items-center gap-3 rounded-xl px-2 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                                        <Icon name={icon} className="size-5 text-accent" />
                                        <span className="flex-1">{title}</span>
                                        <Icon
                                            name="chevron"
                                            className="size-4 text-foreground/40 transition-transform group-open:rotate-180 motion-reduce:transition-none"
                                        />
                                    </summary>
                                    <SubjectLinks group={group} items={materials[group]} close={() => setMenu(false)} />
                                </details>
                            ))}
                        </div>
                        {viewer && (
                            <div className="space-y-1">
                                {[
                                    ['Profil', nav.profile, 'user'],
                                    ['Tentang aplikasi ini', links['profile.about'], 'info'],
                                    ...(viewer.isAdmin ? [['Admin', nav.admin, 'chart']] : []),
                                ].map(([label, url, icon]) => (
                                    <NavigationLink page={navigationPages[icon]}
                                        onClick={() => setMenu(false)}
                                        key={label}
                                        href={url}
                                        className="flex min-h-12 items-center gap-3 rounded-2xl px-4 text-sm font-semibold text-foreground/65 hover:bg-background hover:text-accent"
                                    >
                                        <Icon name={icon} />
                                        {label}
                                    </NavigationLink>
                                ))}
                            </div>
                        )}
                    </nav>
                    <div className="mt-auto space-y-3 pt-4">
                        {!viewer && (
                            <>
                                <p className="text-sm leading-6 text-foreground/50">
                                    Simpan progres dan lanjutkan belajar dari perangkat lain dengan akunmu.
                                </p>
                                <div className="grid grid-cols-2 gap-2">
                                    <Link
                                        onClick={() => setMenu(false)}
                                        href={nav.login}
                                        className={`${secondaryClass} !px-3`}
                                    >
                                        <Icon name="login" className="size-4" />
                                        Masuk
                                    </Link>
                                    <Link
                                        onClick={() => setMenu(false)}
                                        href={nav.register}
                                        className={`${buttonClass} !px-3`}
                                    >
                                        Daftar akun
                                    </Link>
                                </div>
                            </>
                        )}
                        {viewer?.mode === 'anonymous' && (
                            <Link
                                onClick={() => setMenu(false)}
                                href={nav.login}
                                className={`${secondaryClass} w-full`}
                            >
                                Masuk dengan akun
                            </Link>
                        )}
                        {viewer?.mode === 'account' && (
                            <Action url={nav.logout} className={`${secondaryClass} w-full`}>
                                Keluar
                            </Action>
                        )}
                        <Link
                            onClick={() => setMenu(false)}
                            href={nav.privacy}
                            className="flex min-h-11 items-center justify-center text-xs text-foreground/45 hover:text-accent"
                        >
                            Kebijakan privasi
                        </Link>
                    </div>
                </div>
            </Dialog>
        </>
    );
}
