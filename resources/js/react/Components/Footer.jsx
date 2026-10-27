import { Link, usePage } from '@inertiajs/react';
import Icon from './Icon';

export default function Footer({ className = '' }) {
    const { navigation, brand, materials = {} } = usePage().props;
    const linkClass =
        'inline-flex min-h-10 items-center rounded-lg text-sm leading-6 text-foreground/55 transition hover:text-accent focus-visible:outline-2 focus-visible:outline-accent';
    return (
        <footer className={`mt-12 bg-surface ${className}`} aria-label="Footer ARTASWAHA">
            <div className="mx-auto max-w-7xl px-5 pb-6 pt-10 sm:px-6 sm:pt-14 lg:px-10">
                <div className="grid gap-x-8 gap-y-9 sm:grid-cols-2 lg:grid-cols-[1.2fr_1fr_1fr_1.2fr]">
                    <div className="min-w-0">
                        <Link href={navigation.home} className="inline-flex items-center gap-3 rounded-full">
                            <img src={brand.logo} alt="" width="44" height="44" className="size-11 object-contain" />
                            <span className="font-display text-xl font-bold">ARTASWAHA</span>
                        </Link>
                        <p className="mt-4 max-w-xs text-sm leading-7 text-foreground/50">
                            Bahasa, cerita, dan aksara Bali. Dekat dengan budaya, tumbuh bersama setiap pelajaran.
                        </p>
                        <p className="mt-4 text-xs leading-6 text-foreground/35">
                            Belajar mandiri dan bermain kuis bersama.
                        </p>
                    </div>
                    {[
                        ['paribasa', 'Paribasa Bali'],
                        ['aksara', 'Aksara Bali'],
                    ].map(([group, title]) => (
                        <nav key={group} aria-label={`Materi ${title}`} className="min-w-0">
                            <h2 className="mb-3 flex items-center gap-2 font-display text-base font-bold">
                                <Icon name={group === 'aksara' ? 'aksara' : 'chat'} className="size-4 text-accent" />
                                {title}
                            </h2>
                            <ul>
                                {(materials[group] ?? []).slice(0, 4).map((item) => (
                                    <li key={item.id}>
                                        <Link className={linkClass} href={item.url}>
                                            {item.title}
                                        </Link>
                                    </li>
                                ))}
                                <li>
                                    <Link
                                        href={`${navigation.learning}?group=${group}`}
                                        className={`${linkClass} !text-accent`}
                                    >
                                        Semua materi
                                        <Icon name="arrow" className="ml-2 size-4" />
                                    </Link>
                                </li>
                            </ul>
                        </nav>
                    ))}
                    <div className="min-w-0">
                        <h2 className="mb-3 font-display text-base font-bold">Tetap terhubung</h2>
                        <a href="mailto:kerthajayasoftware@gmail.com" className={`${linkClass} gap-2 break-all`}>
                            <Icon name="mail" className="size-4 shrink-0 text-primary" />
                            <span>kerthajayasoftware@gmail.com</span>
                        </a>
                        <a href="tel:+6281337253221" className={`${linkClass} flex gap-2`}>
                            <Icon name="phone" className="size-4 shrink-0 text-primary" />
                            0813 3725 3221
                        </a>
                        <Link href={navigation.quizzes} className={`${linkClass} mt-3 gap-2`}>
                            <Icon name="quiz" className="size-4" />
                            Main kuis bareng
                        </Link>
                    </div>
                </div>
                <div className="mt-9 flex flex-wrap items-center justify-between gap-x-6 gap-y-3 pt-6 text-xs text-foreground/35">
                    <p>© {new Date().getFullYear()} ARTASWAHA</p>
                    <nav aria-label="Informasi layanan" className="flex flex-wrap gap-5">
                        <Link
                            href={`${navigation.home}#tentang`}
                            className="inline-flex min-h-10 items-center hover:text-accent"
                        >
                            Tentang ARTASWAHA
                        </Link>
                        <Link href={navigation.privacy} className="inline-flex min-h-10 items-center hover:text-accent">
                            Kebijakan privasi
                        </Link>
                    </nav>
                </div>
            </div>
        </footer>
    );
}
