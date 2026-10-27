import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import * as m from 'motion/react-m';
import { PageMeta, CategoryIcon, buttonClass } from '../Components/ui';
import { Card, secondaryClass } from '../Components/forms';
import Testimonials from '../Components/Testimonials';
import Faq from '../Components/Faq';
import Icon from '../Components/Icon';

export default function Home({ recent, meta }) {
    const { viewer, navigation: nav, brand, links } = usePage().props;
    const [hello, setHello] = useState(false);
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
                <section aria-label="Beranda belajarmu" className="mx-auto max-w-lg space-y-6 md:hidden">
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
                    <section className="overflow-hidden rounded-card bg-surface p-5">
                        <div className="grid grid-cols-[minmax(0,1fr)_6rem] items-center gap-3">
                            <div className="min-w-0">
                                <p className="text-xs font-semibold text-accent">
                                    {recent ? 'Lanjutkan langkahmu' : 'Sedikit waktu, cerita baru'}
                                </p>
                                <h2 className="mt-3 break-words font-display text-2xl font-bold leading-tight">
                                    {recent?.title ?? 'Yuk, kenali Bali lebih dekat.'}
                                </h2>
                            </div>
                            <m.button
                                type="button"
                                aria-label="Sapa maskot belajarmu"
                                onClick={() => setHello(!hello)}
                                whileTap={{ rotate: -5, scale: 0.96 }}
                                className="rounded-full focus-visible:outline-2 focus-visible:outline-accent"
                            >
                                <img
                                    src={brand.mascot}
                                    alt="Maskot ARTASWAHA"
                                    width="160"
                                    height="200"
                                    className="h-auto w-full object-contain"
                                />
                            </m.button>
                        </div>
                        {hello && (
                            <p role="status" className="mt-3 text-sm text-accent">
                                Rahajeng! Siap belajar bareng?
                            </p>
                        )}
                        <p className="mt-4 text-sm leading-7 text-foreground/60">
                            {recent
                                ? recent.pathTitle
                                : 'Mulai satu pelajaran singkat. Belajar sesuai waktu dan rasa ingin tahumu.'}
                        </p>
                        <Link href={recent?.url ?? nav.learning} className={`${buttonClass} mt-5 w-full`}>
                            {recent ? 'Buka pelajaran terakhir' : 'Mulai satu pelajaran'}
                            <Icon name="arrow" className="size-4" />
                        </Link>
                    </section>
                    <section aria-labelledby="home-greeting-title" className="rounded-card bg-primary/15 p-5 sm:p-6">
                        <div className="flex items-center gap-2 text-xs font-semibold text-accent">
                            <Icon name="chat" className="size-4" />
                            Kenali satu sapaan
                        </div>
                        <p lang="ban" className="mt-4 break-words font-balinese text-3xl leading-loose text-foreground">
                            ᬒᬁ ᬲ᭄ᬯᬲ᭄ᬢ᭄ᬬᬲ᭄ᬢᬸ᭟
                        </p>
                        <h2 id="home-greeting-title" className="mt-3 font-display text-xl font-bold">
                            Om Swastyastu
                        </h2>
                        <p className="mt-2 text-sm leading-7 text-foreground/60">
                            Salam pembuka dalam bahasa Bali. Satu sapaan untuk mengawali percakapan.
                        </p>
                    </section>
                    <Link
                        href={nav.quizzes}
                        className="group flex items-center justify-between gap-4 rounded-card bg-surface p-5 focus-visible:outline-2 focus-visible:outline-accent"
                    >
                        <div>
                            <h2 className="font-display text-xl font-bold">Main kuis bareng</h2>
                            <p className="mt-2 text-sm text-foreground/55">Masukkan kode untuk gabung kuis.</p>
                        </div>
                        <span className="grid size-11 shrink-0 place-items-center rounded-full bg-primary/20 text-accent group-hover:bg-primary/35">
                            <Icon name="arrow" className="size-5" />
                        </span>
                    </Link>
                </section>
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
                <section
                    id="tentang"
                    aria-labelledby="about-platform-title"
                    className="grid items-center gap-8 py-12 sm:py-20 lg:grid-cols-[1.15fr_.85fr] lg:gap-14"
                >
                    <div className="max-w-2xl">
                        <p className="mb-4 text-sm font-semibold text-accent">Tentang ARTASWAHA</p>
                        <h2
                            id="about-platform-title"
                            className="font-display text-3xl font-bold leading-tight sm:text-4xl"
                        >
                            Ruang belajar untuk mengenal bahasa dan budaya Bali.
                        </h2>
                        <p className="mt-5 text-sm leading-8 text-foreground/65 sm:text-base">
                            ARTASWAHA adalah platform edukasi bahasa, sastra, dan aksara Bali. Kami menghadirkan
                            paribasa dan aksara lewat penjelasan, contoh sehari-hari, latihan singkat, serta kuis yang
                            dapat dimainkan bersama.
                        </p>
                        <p className="mt-4 text-sm leading-8 text-foreground/55 sm:text-base">
                            Untuk murid, guru, pendatang, maupun siapa saja yang ingin mengenal Bali lebih dekat. Mulai
                            dari rasa ingin tahu, pelajari sesuai waktumu, dan bangun pemahaman sedikit demi sedikit.
                        </p>
                        <Link
                            href={nav.learning}
                            className="mt-5 inline-flex min-h-11 items-center gap-2 rounded-full font-semibold text-accent focus-visible:outline-2 focus-visible:outline-accent"
                        >
                            Kenali materi pembelajaran
                            <Icon name="arrow" className="size-4" />
                        </Link>
                    </div>
                    <Card as="aside" className="!bg-primary/10 sm:!p-8" aria-label="Tujuan ARTASWAHA">
                        <span
                            lang="ban"
                            aria-hidden="true"
                            className="block font-balinese text-6xl leading-relaxed text-accent"
                        >
                            ᬅ
                        </span>
                        <h3 className="mt-4 max-w-sm font-display text-2xl font-bold leading-snug sm:text-3xl">
                            Bahasa yang tetap dekat dengan keseharian.
                        </h3>
                        <p className="mt-4 text-sm leading-8 text-foreground/55">
                            Kami ingin membuat proses mengenal warisan bahasa Bali terasa lebih mudah dimulai, relevan,
                            dan menyenangkan untuk dipelajari bersama.
                        </p>
                        <div className="mt-6 flex flex-wrap gap-2">
                            {['Bahasa', 'Sastra', 'Aksara'].map((label) => (
                                <span
                                    key={label}
                                    className="rounded-full bg-background px-4 py-2 text-xs font-semibold text-foreground/65"
                                >
                                    {label} Bali
                                </span>
                            ))}
                        </div>
                    </Card>
                </section>
                <section id="keunggulan" aria-labelledby="platform-benefits-title" className="pb-12 pt-3 sm:pb-20">
                    <header className="mb-7 flex flex-wrap items-end justify-between gap-4 sm:mb-9">
                        <div className="max-w-2xl">
                            <p className="mb-3 text-sm font-semibold text-accent">Kelebihan platform</p>
                            <h2
                                id="platform-benefits-title"
                                className="font-display text-3xl font-bold leading-tight sm:text-4xl"
                            >
                                Lebih mudah memulai, lebih banyak cara belajar.
                            </h2>
                        </div>
                        <p className="max-w-sm text-sm leading-7 text-foreground/50">
                            Dari belajar mandiri sampai bermain bersama, pilih cara yang sesuai dengan kebutuhanmu.
                        </p>
                    </header>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {[
                            [
                                'user',
                                'Mulai tanpa membuat akun',
                                'Cukup gunakan profil anonim untuk mulai belajar. Kamu bisa membuat akun ketika ingin melanjutkan di perangkat lain.',
                            ],
                            [
                                'book',
                                'Materi dan latihan singkat',
                                'Pahami penjelasan lewat contoh, lalu coba latihan untuk mengingat kembali hal yang baru dipelajari.',
                            ],
                            [
                                'aksara',
                                'Keyboard Aksara Bali',
                                'Coba menulis aksara dasar, pasangan, dan tanda melalui keyboard yang tersedia pada pembelajaran.',
                            ],
                            [
                                'quiz',
                                'Kuis bersama teman',
                                'Gabung dengan kode room, atau buat kuis melalui akunmu untuk belajar bersama teman dan murid.',
                            ],
                            [
                                'chart',
                                'Progres belajar tercatat',
                                'Lihat pelajaran yang sudah diselesaikan dan riwayat aktivitasmu untuk menentukan langkah berikutnya.',
                            ],
                            [
                                'spark',
                                'Saran belajar berbantuan AI',
                                'Dapatkan arahan belajar berdasarkan pilihan materi dan aktivitas yang tercatat, sebagai pendamping perjalanan belajarmu.',
                            ],
                        ].map(([icon, title, text]) => (
                            <Card key={title}>
                                <span className="grid size-11 place-items-center rounded-2xl bg-primary/15 text-accent">
                                    <Icon name={icon} className="size-5" />
                                </span>
                                <h3 className="mt-5 font-display text-xl font-bold">{title}</h3>
                                <p className="mt-3 text-sm leading-7 text-foreground/55">{text}</p>
                            </Card>
                        ))}
                    </div>
                </section>
                <Testimonials />
                <Faq />
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
