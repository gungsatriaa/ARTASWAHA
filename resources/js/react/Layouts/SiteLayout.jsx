import { useEffect, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Dialog from '../Components/Dialog';
import Icon from '../Components/Icon';

export function usePresence(url) {
    useEffect(() => {
        if (!url) return;
        let controller;
        const ping = () => {
            if (document.visibilityState !== 'visible' || controller) return;
            controller = new AbortController();
            const current = controller;
            fetch(url, { method: 'POST', credentials: 'same-origin', signal: current.signal, headers: {
                Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            } }).catch(() => {}).finally(() => { if (controller === current) controller = undefined; });
        };
        ping();
        const timer = window.setInterval(ping, 30000);
        document.addEventListener('visibilitychange', ping);
        return () => { window.clearInterval(timer); document.removeEventListener('visibilitychange', ping); controller?.abort(); };
    }, [url]);
}

function FlashNotice({ flash }) {
    const [visible, setVisible] = useState(Boolean(flash?.message));
    useEffect(() => {
        setVisible(Boolean(flash?.message));
        if (!flash?.message) return;
        const timer = window.setTimeout(() => setVisible(false), 5000);
        return () => window.clearTimeout(timer);
    }, [flash]);
    if (!visible || !flash?.message) return null;
    return <div role="status" className={`fixed left-1/2 top-4 z-[70] flex w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 items-center gap-3 rounded-full px-5 py-3 text-sm font-semibold text-white shadow-lg ${flash.tone === 'info' ? 'bg-primary' : 'bg-[#15803d]'}`}>
        <Icon name="check" className="size-6 shrink-0" /><span className="min-w-0 flex-1">{flash.message}</span>
        <button type="button" onClick={() => setVisible(false)} aria-label="Tutup notifikasi" className="grid size-9 shrink-0 place-items-center rounded-full focus-visible:outline-2 focus-visible:outline-white"><Icon name="close" /></button>
    </div>;
}

export default function SiteLayout({ children }) {
    const { props } = usePage();
    const { viewer, navigation, brand, flash, csrfToken } = props;
    const [menu, setMenu] = useState(false);
    usePresence(navigation.presence);
    const publicLinks = <>
        <a href={navigation.home}>Beranda</a>
        <Link href={navigation.learning}>Semua materi</Link>
        <a href={navigation.quizzes}>Kuis</a>
        {viewer ? <a href={navigation.profile}>Profil</a> : <><a href={navigation.login}>Masuk</a><a href={navigation.register}>Daftar akun</a></>}
        {viewer?.isAdmin && <a href={navigation.admin}>Admin</a>}
        {viewer?.mode === 'anonymous' && <a href={navigation.login}>Masuk dengan akun</a>}
    </>;
    return <>
        <a href="#main-content" className="sr-only fixed left-4 top-4 z-[80] rounded-full bg-accent px-5 py-3 text-background focus:not-sr-only">Lewati ke konten</a>
        <header className="sticky top-0 z-40 bg-background/95 backdrop-blur-sm">
            <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-10 lg:py-5">
                <a href={navigation.home} aria-label="ARTASWAHA beranda" className="flex shrink-0 items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-accent">
                    <img src={brand.logo} width="44" height="44" alt="" className="size-10 object-contain sm:size-11" />
                    <span className="font-display text-lg font-bold tracking-tight sm:text-2xl">ARTASWAHA</span>
                </a>
                <nav className="hidden items-center gap-1 md:flex [&>a]:rounded-full [&>a]:px-4 [&>a]:py-3 [&>a]:text-sm [&>a]:font-semibold [&>a]:text-foreground/65 [&>a]:transition-colors [&>a]:hover:bg-surface [&>a]:hover:text-accent [&>a]:focus-visible:outline-2 [&>a]:focus-visible:outline-accent" aria-label="Navigasi utama">
                    <a href={navigation.home}>Beranda</a>
                    <Link href={navigation.learning} className="!bg-primary/20 !text-accent" aria-current="page">Belajar</Link>
                    <a href={navigation.quizzes}>Kuis</a>
                    {viewer ? <button type="button" onClick={() => setMenu(true)} className="ml-2 rounded-full focus-visible:outline-2 focus-visible:outline-accent" aria-label={`Menu profil ${viewer.name}`}><img src={viewer.avatar} alt="" width="44" height="44" className="size-11 rounded-full object-cover ring-2 ring-primary/40" /></button> : <><a href={navigation.login}>Masuk</a><a href={navigation.register} className="!bg-accent !text-background">Daftar</a></>}
                </nav>
                {!viewer && <button type="button" onClick={() => setMenu(true)} aria-label="Buka menu" className="grid size-11 place-items-center rounded-full bg-surface text-foreground/75 md:hidden"><Icon name="menu" /></button>}
            </div>
        </header>
        <FlashNotice flash={flash} />
        <main id="main-content" tabIndex="-1" className={`mx-auto min-h-[calc(100dvh-5rem)] w-full max-w-6xl px-4 pb-12 pt-5 outline-none sm:px-6 sm:pt-8 lg:px-10 ${viewer ? 'pb-32 md:pb-12' : ''}`}>{children}</main>
        {viewer && <nav aria-label="Navigasi utama mobile" className="fixed inset-x-3 bottom-[max(.75rem,env(safe-area-inset-bottom))] z-40 mx-auto grid max-w-lg grid-cols-4 rounded-full bg-surface/95 p-1.5 shadow-xl shadow-black/40 backdrop-blur-lg md:hidden">
            {[['home', 'Beranda', navigation.home], ['book', 'Belajar', navigation.learning], ['quiz', 'Kuis', navigation.quizzes], ['user', 'Profil', navigation.profile]].map(([icon, label, href]) => {
                const active = icon === 'book';
                const content = <><span className={`grid size-9 place-items-center rounded-full transition-colors ${active ? 'bg-accent text-background' : 'text-foreground/65'}`}><Icon name={icon} /></span>{label}</>;
                const attributes = { className: `flex min-h-16 flex-col items-center justify-center gap-1 rounded-full text-[11px] font-semibold focus-visible:outline-2 focus-visible:outline-accent ${active ? 'text-accent' : 'text-foreground/55'}`, 'aria-current': active ? 'page' : undefined };
                return active ? <Link key={icon} href={href} {...attributes}>{content}</Link> : <a key={icon} href={href} {...attributes}>{content}</a>;
            })}
        </nav>}
        <Dialog open={menu} onClose={() => setMenu(false)} title={viewer ? viewer.name : 'Mau ke mana?'} id="site-menu-title">
            {viewer?.mode === 'anonymous' && <p className="mb-4 text-sm font-semibold text-accent">Akun anonim · di perangkat ini</p>}
            <nav onClick={event => { if (event.target.closest('a')) setMenu(false); }} aria-label="Menu halaman" className="grid gap-1 [&>a]:rounded-2xl [&>a]:px-4 [&>a]:py-3 [&>a]:text-sm [&>a]:font-semibold [&>a]:text-foreground/70 [&>a]:hover:bg-background [&>a]:focus-visible:outline-2 [&>a]:focus-visible:outline-accent">{publicLinks}</nav>
            {viewer?.mode === 'account' && <form action={navigation.logout} method="post" className="mt-4 border-t border-white/10 pt-3"><input type="hidden" name="_token" value={csrfToken} /><button className="min-h-11 rounded-full px-4 text-sm font-bold text-danger" type="submit">Keluar</button></form>}
        </Dialog>
    </>;
}
