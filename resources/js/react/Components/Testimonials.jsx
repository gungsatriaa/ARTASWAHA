import { useRef } from 'react';
import { usePage } from '@inertiajs/react';
import { secondaryClass } from './forms';

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
        <section id="testimoni" className="py-12 sm:py-20">
            <h2 className="font-display text-3xl font-bold">Belajar yang terasa dekat dan menyenangkan</h2>
            <p className="mt-4 text-sm leading-7 text-foreground/55">
                Kutipan berikut adalah contoh rancangan, bukan testimoni pengguna nyata.
            </p>
            <div
                ref={track}
                role="region"
                aria-label="Daftar contoh testimoni"
                tabIndex="0"
                className="mt-7 flex snap-x snap-mandatory gap-4 overflow-x-auto overscroll-x-contain pb-3 md:grid md:grid-cols-3 md:overflow-visible"
            >
                {examples.map(([quote, role, avatar]) => (
                    <figure
                        key={role}
                        className="flex min-h-60 w-[88%] shrink-0 snap-start flex-col rounded-card bg-surface p-6 md:w-auto"
                    >
                        <blockquote className="flex-1 text-lg leading-8">{quote}</blockquote>
                        <figcaption className="mt-6 flex items-center gap-3">
                            <img
                                src={`${brand.avatarBase}/${avatar}.webp`}
                                alt=""
                                loading="lazy"
                                width="48"
                                height="48"
                                className="size-12 rounded-full object-cover"
                            />
                            <span>
                                <span className="block text-sm font-bold">{role}</span>
                                <span className="mt-1 block text-xs text-foreground/45">Ilustrasi · contoh suara</span>
                            </span>
                        </figcaption>
                    </figure>
                ))}
            </div>
            <div className="mt-3 flex justify-between gap-3 md:hidden">
                <button
                    type="button"
                    aria-label="Testimoni sebelumnya"
                    className={secondaryClass}
                    onClick={() => scroll(-1)}
                >
                    ←
                </button>
                <button
                    type="button"
                    aria-label="Testimoni berikutnya"
                    className={secondaryClass}
                    onClick={() => scroll(1)}
                >
                    →
                </button>
            </div>
        </section>
    );
}
