import { Head, Link, usePage } from '@inertiajs/react';
import PageEffects from '../Components/PageEffects';
import Icon from '../Components/Icon';
import { FlashNotice, usePresence } from './SiteLayout';

export default function AuthLayout({ children, onboarding = false }) {
    const { brand, navigation, flash, csrfToken } = usePage().props;
    usePresence(navigation.presence);
    return (
        <div className="min-h-svh">
            <Head>
                <meta name="csrf-token" content={csrfToken} head-key="csrf-token" />
            </Head>
            <a
                href="#auth-content"
                className="sr-only fixed left-4 top-4 z-[80] rounded-full bg-accent px-5 py-3 text-background focus:not-sr-only"
            >
                Lewati ke formulir
            </a>
            <header className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-5 sm:px-8 sm:py-4">
                <Link
                    href={navigation.home}
                    className="inline-flex min-h-11 items-center gap-3 rounded-full focus-visible:outline-2 focus-visible:outline-accent"
                >
                    <img src={brand.logo} alt="" width="40" height="48" className="h-10 w-9 object-contain" />
                    <span className="font-display text-lg font-bold tracking-tight">ARTASWAHA</span>
                </Link>
                {!onboarding && (
                    <Link
                        href={navigation.home}
                        aria-label="Kembali ke beranda"
                        className="grid size-11 shrink-0 place-items-center rounded-full bg-surface text-foreground/60 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                    >
                        <Icon name="close" className="size-5" />
                    </Link>
                )}
            </header>
            <main
                id="auth-content"
                tabIndex={-1}
                className="mx-auto w-full max-w-6xl px-5 pb-6 pt-4 outline-none sm:px-8 lg:pt-0"
            >
                <PageEffects>{children}</PageEffects>
            </main>
            <FlashNotice flash={flash} />
        </div>
    );
}
