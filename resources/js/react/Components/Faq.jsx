import Icon from './Icon';

const questions = [
    [
        'Apa itu ARTASWAHA?',
        'ARTASWAHA adalah platform edukasi bahasa, sastra, dan aksara Bali. Kamu bisa mempelajari materi secara mandiri, mencoba latihan singkat, dan mengikuti kuis bersama.',
    ],
    [
        'Apa saja yang bisa dipelajari?',
        'Jelajahi Paribasa Bali, seperti cecimpedan, peparikan, dan sesonggakan, serta Aksara Bali melalui modul yang telah ditinjau.',
    ],
    [
        'Apakah harus membuat akun?',
        'Tidak. Kamu bisa mulai belajar dan mengikuti kuis dengan profil anonim. Akun diperlukan untuk membuat kuis sendiri dan melanjutkan progres di perangkat lain.',
    ],
    [
        'Bagaimana progres belajarku disimpan?',
        'Progres terhubung dengan profil anonim pada browser yang kamu gunakan. Jika data browser atau cookie dihapus, akses ke profil anonim bisa hilang. Masuk dengan akun untuk menghubungkan progresmu ke akun tersebut.',
    ],
    [
        'Apakah bisa belajar dari HP?',
        'Bisa. Materi, latihan, keyboard Aksara Bali, dan kuis dapat dibuka melalui browser di HP maupun komputer.',
    ],
];

export default function Faq() {
    return (
        <section
            id="faq"
            aria-labelledby="faq-title"
            className="grid items-start gap-7 py-12 sm:gap-10 sm:py-20 lg:grid-cols-[.75fr_1.25fr] lg:gap-16"
        >
            <div className="lg:sticky lg:top-28">
                <span className="mb-5 grid size-12 place-items-center rounded-2xl bg-primary/15 text-accent">
                    <Icon name="chat" className="size-6" />
                </span>
                <p className="mb-3 text-sm font-semibold text-accent">Sebelum mulai belajar</p>
                <h2 id="faq-title" className="max-w-sm font-display text-3xl font-bold leading-tight sm:text-4xl">
                    Ada yang ingin kamu tanyakan?
                </h2>
                <p className="mt-4 max-w-sm text-sm leading-8 text-foreground/55">
                    Temukan jawaban tentang materi, akun, dan perjalanan belajarmu di ARTASWAHA.
                </p>
                <a
                    href="mailto:kerthajayasoftware@gmail.com"
                    className="mt-6 inline-flex min-h-11 items-center gap-2 rounded-full text-sm font-bold text-accent focus-visible:outline-2 focus-visible:outline-accent"
                >
                    Hubungi kami
                    <Icon name="arrow" className="size-4" />
                </a>
            </div>
            <div className="min-w-0 space-y-3">
                {questions.map(([question, answer], index) => (
                    <details
                        key={question}
                        name="landing-faq"
                        open={index === 0}
                        className="group rounded-card bg-surface p-1 transition-colors open:bg-primary/10"
                    >
                        <summary className="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 rounded-card px-4 py-4 font-display text-base font-bold focus-visible:outline-2 focus-visible:outline-accent sm:px-6 sm:text-lg [&::-webkit-details-marker]:hidden">
                            <span>{question}</span>
                            <span className="grid size-8 shrink-0 place-items-center rounded-full bg-background text-foreground/40 transition group-open:bg-primary/20 group-open:text-accent">
                                <Icon name="chevron" className="size-4 transition-transform group-open:rotate-180" />
                            </span>
                        </summary>
                        <p className="px-4 pb-5 pr-8 text-sm leading-8 text-foreground/60 sm:px-6 sm:pb-6 sm:pr-16">
                            {answer}
                        </p>
                    </details>
                ))}
            </div>
        </section>
    );
}
