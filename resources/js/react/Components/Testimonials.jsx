import { useRef } from 'react';
import { usePage } from '@inertiajs/react';
import Icon from './Icon';

const examples = [
    ['Tebak-teki Bali bikin aku ingin tahu jawaban yang lain juga.', 'Murid', 'kupu_kupu'],
    ['Materinya singkat, jadi bisa kami pelajari bersama di rumah.', 'Keluarga', 'gajah'],
    ['Kuis bersama memberi cara baru untuk mengenalkan paribasa.', 'Pendidik', 'jalak_bali'],
];

export default function Testimonials() {
    const track = useRef(null);
    const { brand } = usePage().props;
    const scroll = (direction) =>
        track.current?.scrollBy({
            left: direction * track.current.clientWidth,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
        });
    return (
        <section id="testimoni" aria-labelledby="testimonials-title" className="py-12 sm:py-20">
            <div className="flex flex-wrap items-end justify-between gap-5">
                <div className="max-w-2xl">
                    <p className="mb-3 text-sm font-semibold text-accent">Gambaran pengalaman belajar</p>
                    <h2 id="testimonials-title" className="font-display text-3xl font-bold leading-tight sm:text-4xl">
                        Belajar yang terasa dekat dan menyenangkan.
                    </h2>
                </div>
                <p className="max-w-sm text-xs leading-6 text-foreground/40">
                    Kutipan berikut adalah contoh rancangan, bukan testimoni pengguna nyata.
                </p>
            </div>
            <div
                ref={track}
                role="region"
                aria-label="Daftar contoh testimoni"
                tabIndex="0"
                className="mt-7 flex gap-4 snap-x snap-mandatory overflow-x-auto overscroll-x-contain rounded-card pb-3 outline-none focus-visible:ring-2 focus-visible:ring-accent md:grid md:grid-cols-3 md:overflow-visible"
            >
                {examples.map(([quote, role, avatar], index) => (
                    <figure
                        key={role}
                        className={`flex min-h-72 w-[88%] shrink-0 snap-start flex-col rounded-card p-6 sm:p-7 md:w-auto ${index === 1 ? 'bg-primary/10' : 'bg-surface'}`}
                    >
                        <span aria-hidden="true" className="h-12 font-display text-6xl leading-none text-accent/55">
                            “
                        </span>
                        <blockquote className="mt-3 flex-1 font-display text-xl font-medium leading-8 text-foreground/85">
                            {quote}
                        </blockquote>
                        <figcaption className="mt-7 flex items-center gap-3">
                            <img
                                src={`${brand.avatarBase}/${avatar}.webp`}
                                alt=""
                                loading="lazy"
                                width="48"
                                height="48"
                                className="size-12 rounded-full object-cover ring-4 ring-background/50"
                            />
                            <span>
                                <span className="block text-sm font-bold">{role}</span>
                                <span className="mt-1 block text-xs text-foreground/45">Contoh pengalaman</span>
                            </span>
                        </figcaption>
                    </figure>
                ))}
            </div>
            <div className="mt-3 flex items-center justify-between gap-4 md:hidden">
                <p className="text-xs leading-6 text-foreground/40">Geser untuk melihat cerita lainnya</p>
                <div className="flex shrink-0 gap-2">
                    {[
                        [-1, 'Testimoni sebelumnya'],
                        [1, 'Testimoni berikutnya'],
                    ].map(([direction, label]) => (
                        <button
                            key={label}
                            type="button"
                            aria-label={label}
                            className="grid size-11 place-items-center rounded-full bg-surface text-accent transition hover:bg-primary/20 focus-visible:outline-2 focus-visible:outline-accent"
                            onClick={() => scroll(direction)}
                        >
                            <Icon name="arrow" className={`size-5 ${direction === -1 ? 'rotate-180' : ''}`} />
                        </button>
                    ))}
                </div>
            </div>
        </section>
    );
}
