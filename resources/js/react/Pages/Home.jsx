import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import * as m from 'motion/react-m';
import { PageMeta, CategoryIcon, buttonClass, EmptyState } from '../Components/ui';
import { Card, secondaryClass } from '../Components/forms';
import Analysis from '../Components/Analysis';
import Testimonials from '../Components/Testimonials';
import Icon from '../Components/Icon';

export default function Home({ paths, selectedIds, recent, analysis, meta }) {
    const { viewer, navigation: nav, brand, links } = usePage().props;
    const [hello, setHello] = useState(false);
    const selected = selectedIds.length ? paths.filter((path) => selectedIds.includes(path.id)) : paths;
    const start = viewer ? (
        <Link className={buttonClass} href={nav.learning}>
            Lanjut belajar
        </Link>
    ) : (
        <Link className={buttonClass} href={links['learning.interview.show']}>
            Mulai belajar gratis
        </Link>
    );
    return (
        <>
            <PageMeta {...meta} />
            {viewer && (
                <div className="mx-auto max-w-lg space-y-7 md:hidden">
                    <header className="flex items-center justify-between gap-4">
                        <div className="min-w-0">
                            <p className="text-sm text-foreground/55">Selamat datang kembali,</p>
                            <h1 className="mt-1 break-words font-display text-2xl font-bold">{viewer.name}</h1>
                            <p className="mt-2 text-xs font-semibold text-accent">
                                {viewer.mode === 'anonymous'
                                    ? 'Akun anonim · di perangkat ini'
                                    : 'Siap untuk langkah berikutnya?'}
                            </p>
                        </div>
                        <Link href={nav.profile}>
                            <img
                                src={viewer.avatar}
                                width="56"
                                height="56"
                                alt="Profil"
                                className="size-14 rounded-full object-cover"
                            />
                        </Link>
                    </header>
                    <section className="rounded-card bg-accent p-6 text-background">
                        <p className="text-sm font-semibold">Riwayat belajar terakhir</p>
                        <h2 className="mt-3 font-display text-2xl font-bold leading-tight">
                            {recent?.title ?? 'Belum ada riwayat belajar'}
                        </h2>
                        <p className="mt-3 text-sm leading-7 text-background/65">
                            {recent
                                ? `${recent.pathTitle} · ${recent.points} poin`
                                : 'Selesaikan pelajaran pertamamu untuk melihat riwayat belajar di sini.'}
                        </p>
                        <Link
                            href={recent?.url ?? nav.learning}
                            className="mt-6 inline-flex min-h-12 items-center rounded-full bg-background px-6 text-sm font-bold text-accent"
                        >
                            {recent ? 'Buka pelajaran' : 'Pilih materi'} →
                        </Link>
                    </section>
                    <section>
                        <header className="mb-4 flex justify-between gap-3">
                            <h2 className="font-display text-xl font-bold">Materi pilihanmu</h2>
                            <Link href={nav.learning} className="min-h-11 text-sm font-bold text-accent">
                                Lihat semua
                            </Link>
                        </header>
                        <div className="space-y-3">
                            {selected.map((path) => (
                                <Link
                                    key={path.id}
                                    href={path.url}
                                    className="flex items-center gap-4 rounded-card bg-surface p-4"
                                >
                                    <CategoryIcon group={path.group} />
                                    <div className="min-w-0 flex-1">
                                        <h3 className="break-words font-display text-base font-bold">{path.title}</h3>
                                        <p className="mt-1 text-xs text-foreground/50">
                                            {path.lessonsCount
                                                ? `${path.completed} dari ${path.lessonsCount} pelajaran selesai`
                                                : 'Pelajaran sedang disiapkan'}
                                        </p>
                                    </div>
                                    <span className="text-foreground/40">›</span>
                                </Link>
                            ))}
                            {!selected.length && (
                                <EmptyState title="Materi pilihanmu belum tersedia">
                                    Pilih materi lain melalui Lihat semua untuk melanjutkan belajar.
                                </EmptyState>
                            )}
                        </div>
                    </section>
                    <Analysis key={`${analysis?.status}-${analysis?.updated_at}`} analysis={analysis} compact />
                    <Link
                        href={nav.quizzes}
                        className="flex items-center justify-between gap-4 rounded-card bg-primary/15 p-6"
                    >
                        <div>
                            <h2 className="font-display text-xl font-bold">Main kuis bareng</h2>
                            <p className="mt-2 text-sm text-foreground/55">Masukkan kode untuk gabung kuis.</p>
                        </div>
                        <span className="grid size-12 shrink-0 place-items-center rounded-full bg-primary text-2xl">
                            →
                        </span>
                    </Link>
                </div>
            )}
            <div className={viewer ? 'hidden md:block' : ''}>
                <section className="grid items-center gap-10 pb-12 pt-5 sm:gap-14 sm:py-12 lg:grid-cols-[1.08fr_.92fr] lg:gap-12 lg:py-16">
                    <div className="max-w-2xl">
                        <p className="mb-5 inline-flex items-center gap-2 text-xs font-semibold text-accent sm:text-sm">
                            <span className="size-2 rounded-full bg-primary" />
                            Bahasa, aksara, dan cerita Bali
                        </p>
                        <h1 className="font-display text-[2.65rem] font-extrabold leading-[1.08] tracking-tight sm:text-6xl lg:text-[4.5rem]">
                            Bahasa Bali jadi <span className="text-accent">seru</span> dipelajari.
                        </h1>
                        <p className="mt-6 max-w-xl text-base leading-8 text-foreground/60 sm:text-lg">
                            Dari teka-teki sehari-hari sampai aksara pertama. Kenali budaya Bali lewat cerita, latihan
                            singkat, dan permainan yang bisa langsung kamu coba.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            {start}
                            <Link href={nav.quizzes} className={secondaryClass}>
                                Gabung kuis
                            </Link>
                        </div>
                        <p className="mt-4 text-xs leading-6 text-foreground/45">
                            Mulai tanpa akun. Progres belajar tersimpan di perangkatmu.
                        </p>
                        <Link
                            href={nav.learning}
                            className="mt-2 inline-flex min-h-11 items-center text-sm font-bold text-accent"
                        >
                            Atau lihat semua materi
                        </Link>
                    </div>
                    <Card className="mx-auto w-full max-w-lg !p-5 sm:!p-7">
                        <div className="flex items-center gap-3">
                            <CategoryIcon group="aksara" className="size-10" />
                            <p className="text-sm font-bold text-accent">Kenalan dengan Aksara Bali</p>
                        </div>
                        <div className="mt-5 grid grid-cols-[minmax(0,1fr)_7rem] items-center gap-3 sm:grid-cols-[minmax(0,1fr)_10rem]">
                            <div className="min-w-0">
                                <p className="mb-2 text-xs text-foreground/40">Sapaan yang mengawali cerita</p>
                                <h2 className="font-display text-2xl font-bold leading-tight sm:text-3xl">
                                    Om Swastyastu
                                </h2>
                                <p className="mt-3 text-sm leading-7 text-foreground/55">
                                    Salam pembuka dalam bahasa Bali. Mari mulai dari yang dekat dengan keseharian.
                                </p>
                            </div>
                            <m.button
                                type="button"
                                aria-label="Sapa maskot ARTASWAHA"
                                onClick={() => setHello(!hello)}
                                whileTap={{ rotate: -5, scale: 0.96 }}
                                className="relative rounded-full focus-visible:outline-2 focus-visible:outline-accent"
                            >
                                <img
                                    src={brand.mascot}
                                    width="320"
                                    height="400"
                                    alt="Maskot ARTASWAHA membaca lontar"
                                    className="h-auto w-full object-contain"
                                />
                                {hello && (
                                    <span
                                        role="status"
                                        className="absolute right-0 top-0 rounded-2xl bg-accent px-3 py-2 text-xs font-bold text-background"
                                    >
                                        Rahajeng! Yuk, belajar.
                                    </span>
                                )}
                            </m.button>
                        </div>
                        <div className="mt-5 rounded-2xl bg-background p-4 sm:p-5">
                            <p lang="ban" className="break-words font-balinese text-3xl leading-loose sm:text-4xl">
                                ᬒᬁ ᬲ᭄ᬯᬲ᭄ᬢ᭄ᬬᬲ᭄ᬢᬸ᭟
                            </p>
                            <p className="mt-3 text-xs leading-6 text-foreground/40">
                                Kenali bentuk aksaranya, lalu coba latihan singkatnya.
                            </p>
                        </div>
                    </Card>
                </section>
                <section id="tentang" className="py-12 sm:py-20">
                    <h2 className="max-w-2xl font-display text-3xl font-bold sm:text-4xl">
                        Belajar budaya Bali, dekat dengan keseharian.
                    </h2>
                    <p className="mt-4 max-w-2xl text-sm leading-8 text-foreground/60">
                        ARTASWAHA mengajakmu belajar lewat contoh, cerita, dan latihan singkat. Pilih materi yang kamu
                        sukai, lalu lanjutkan sesuai waktumu.
                    </p>
                    <div className="mt-8 grid gap-4 md:grid-cols-2">
                        {[
                            ['Paribasa Bali', 'Temukan makna di balik teka-teki, bunyi, dan ungkapan.', 'paribasa'],
                            ['Aksara Bali', 'Kenali bentuk huruf, tanda, gantungan, dan angka.', 'aksara'],
                        ].map(([title, text, group]) => (
                            <Card key={group} className="flex flex-col">
                                <div className="flex items-start gap-4">
                                    <CategoryIcon group={group} />
                                    <div className="min-w-0">
                                        <h3 className="font-display text-2xl font-bold">{title}</h3>
                                        <p className="mt-2 text-sm leading-7 text-foreground/55">{text}</p>
                                    </div>
                                </div>
                                <ul className="my-5 flex-1 space-y-2">
                                    {paths
                                        .filter((path) => path.group === group)
                                        .slice(0, 3)
                                        .map((path) => (
                                            <li key={path.id}>
                                                <Link
                                                    href={path.url}
                                                    className="flex min-h-12 items-center justify-between gap-3 rounded-2xl bg-background px-4 py-3 text-sm font-semibold hover:text-accent"
                                                >
                                                    <span className="min-w-0 break-words">{path.title}</span>
                                                    <Icon name="arrow" className="size-4 shrink-0 text-foreground/35" />
                                                </Link>
                                            </li>
                                        ))}
                                </ul>
                                <Link
                                    href={`${nav.learning}?group=${group}`}
                                    className="inline-flex min-h-11 items-center gap-2 self-start text-sm font-bold text-accent"
                                >
                                    Jelajahi {title}
                                    <Icon name="arrow" className="size-4" />
                                </Link>
                            </Card>
                        ))}
                    </div>
                </section>
                <section id="keunggulan" className="py-12">
                    <h2 className="font-display text-3xl font-bold">Langkah kecil, pemahaman baru.</h2>
                    <div className="mt-7 grid gap-4 sm:grid-cols-3">
                        {[
                            ['Sesuai waktumu', 'Materi ringkas yang bisa dipelajari sedikit demi sedikit.'],
                            ['Langsung berlatih', 'Coba latihan singkat dan keyboard Aksara Bali.'],
                            ['Progres tercatat', 'Lanjutkan perjalanan belajarmu dari perangkat ini.'],
                        ].map(([title, text]) => (
                            <Card key={title}>
                                <h3 className="font-display text-xl font-bold">{title}</h3>
                                <p className="mt-3 text-sm leading-7 text-foreground/55">{text}</p>
                            </Card>
                        ))}
                    </div>
                </section>
                <Testimonials />
                <section id="faq" className="mx-auto max-w-3xl py-12">
                    <h2 className="mb-6 font-display text-3xl font-bold">Yang sering ditanyakan</h2>
                    {[
                        [
                            'Apa itu ARTASWAHA?',
                            'Platform edukasi bahasa, sastra, dan aksara Bali dengan materi mandiri serta kuis bersama.',
                        ],
                        [
                            'Apa saja yang bisa dipelajari?',
                            'Paribasa Bali dan Aksara Bali, melalui modul yang telah ditinjau.',
                        ],
                        [
                            'Apakah harus membuat akun?',
                            'Kamu bisa belajar dan mengikuti kuis dengan profil anonim. Akun diperlukan untuk membuat kuis.',
                        ],
                    ].map(([question, answer]) => (
                        <details key={question} className="my-3 rounded-card bg-surface p-5">
                            <summary className="min-h-11 cursor-pointer content-center font-bold">{question}</summary>
                            <p className="mt-3 text-sm leading-7 text-foreground/60">{answer}</p>
                        </details>
                    ))}
                </section>
                <section className="mt-6 flex flex-col items-start justify-between gap-6 rounded-card bg-primary/15 p-6 sm:p-9 lg:flex-row lg:items-center">
                    <div>
                        <h2 className="max-w-xl font-display text-2xl font-bold sm:text-3xl">
                            Satu pelajaran kecil, satu cerita baru.
                        </h2>
                        <p className="mt-3 max-w-xl text-sm leading-7 text-foreground/55">
                            Pilih hal yang membuatmu penasaran. Belajar sesuai waktumu, lanjutkan kapan pun kamu siap.
                        </p>
                    </div>
                    <Link
                        href={viewer ? nav.learning : links['learning.interview.show']}
                        className={`${buttonClass} shrink-0`}
                    >
                        {viewer ? 'Jelajahi materi' : 'Mulai perjalananmu'}
                        <Icon name="arrow" className="size-4" />
                    </Link>
                </section>
            </div>
        </>
    );
}
