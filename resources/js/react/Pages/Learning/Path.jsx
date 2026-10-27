import { Link, usePage } from '@inertiajs/react';
import Icon from '../../Components/Icon';
import { CategoryIcon, PageMeta, Pagination } from '../../Components/ui';

export default function Path({ path, lessons, recommendation, meta }) {
    const { navigation } = usePage().props;
    return (
        <>
            <PageMeta {...meta} />
            <Link
                href={navigation.learning}
                className="mb-5 inline-flex min-h-11 items-center gap-2 rounded-full text-sm font-semibold text-foreground/60 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
            >
                <Icon name="back" />
                Semua materi
            </Link>
            <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.7fr)] lg:gap-6">
                <div className="min-w-0 lg:sticky lg:top-28">
                    <header className="rounded-card bg-surface p-6 sm:p-8">
                        <div className="flex items-center gap-3">
                            <CategoryIcon group={path.group} className="size-12 shrink-0" />
                            <p className="text-sm font-semibold text-accent">
                                {path.group === 'aksara' ? 'Aksara Bali' : 'Paribasa Bali'}
                            </p>
                        </div>
                        <h1 className="mt-6 break-words font-display text-3xl font-bold leading-tight tracking-tight sm:text-4xl">
                            {path.title}
                        </h1>
                        {path.description && (
                            <p className="mt-5 max-w-2xl text-sm leading-7 text-foreground/60 sm:text-base">
                                {path.description}
                            </p>
                        )}
                        <div className="mt-7 flex items-center gap-3 text-foreground/60">
                            <Icon name="book" className="size-5 shrink-0 text-accent" />
                            <p className="text-sm font-medium">{lessons.total} pelajaran tersedia</p>
                        </div>
                        <div className="mt-6">
                            <div className="flex items-center justify-between gap-3 text-sm">
                                <span className="font-semibold">Progres materi</span>
                                <span className="font-bold tabular-nums text-accent">{path.progress ?? 0}%</span>
                            </div>
                            <progress
                                aria-label={`Progres materi ${path.title}`}
                                value={path.progress ?? 0}
                                max={100}
                                className="mt-3 block h-2 w-full overflow-hidden rounded-full accent-[var(--color-primary)]"
                            />
                            <p className="mt-3 text-xs leading-6 text-foreground/50">
                                {path.completedLessons ?? 0} dari {lessons.total} modul selesai
                            </p>
                        </div>
                    </header>
                    {recommendation && (
                        <aside aria-label="Alasan rekomendasi" className="mt-5 rounded-card bg-primary/10 p-5">
                            <p className="text-sm font-bold text-accent">Direkomendasikan untuk pilihan belajarmu</p>
                            <p className="mt-2 text-sm leading-7 text-foreground/65">{recommendation}</p>
                            <a
                                href={`${navigation.profile}#analisis`}
                                className="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-accent"
                            >
                                Lihat peta belajarmu <Icon name="arrow" className="ml-2 size-4" />
                            </a>
                        </aside>
                    )}
                </div>
                <section aria-labelledby="lesson-list-title" className="min-w-0 rounded-card bg-surface p-5 sm:p-8">
                    <h2 id="lesson-list-title" className="font-display text-2xl font-bold">
                        Daftar modul
                    </h2>
                    <p className="mt-2 text-sm leading-6 text-foreground/55">
                        Materi dan latihan singkat, satu pelajaran dalam satu waktu.
                    </p>
                    {lessons.data.length ? (
                        <ol className="mt-5 grid gap-3 sm:gap-4">
                            {lessons.data.map((lesson, index) => (
                                <li key={lesson.id}>
                                    <Link
                                        href={lesson.url}
                                        className="group flex h-full min-h-24 items-center gap-3 rounded-3xl bg-background p-4 transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:gap-5 sm:p-6"
                                    >
                                        <span
                                            aria-hidden="true"
                                            className="grid size-10 shrink-0 place-items-center rounded-2xl bg-primary/15 font-display text-sm font-bold text-accent"
                                        >
                                            {String(lessons.from + index).padStart(2, '0')}
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block break-words font-display text-base font-bold leading-snug group-hover:text-accent sm:text-xl">
                                                {lesson.title}
                                            </span>
                                            {lesson.summary && (
                                                <span className="mt-2 block text-sm leading-6 text-foreground/55">
                                                    {lesson.summary}
                                                </span>
                                            )}
                                            <span className="mt-4 flex items-center justify-between gap-3 text-xs">
                                                <span className="text-foreground/50">
                                                    {lesson.progress === 100 ? 'Selesai' : 'Belum selesai'}
                                                </span>
                                                <span className="font-bold tabular-nums text-accent">
                                                    {lesson.progress ?? 0}%
                                                </span>
                                            </span>
                                            <progress
                                                aria-label={`Progres modul ${lesson.title}`}
                                                value={lesson.progress ?? 0}
                                                max={100}
                                                className="mt-2 block h-1.5 w-full overflow-hidden rounded-full accent-[var(--color-primary)]"
                                            />
                                        </span>
                                        <Icon
                                            name="arrow"
                                            className="size-5 shrink-0 text-foreground/35 group-hover:text-accent"
                                        />
                                    </Link>
                                </li>
                            ))}
                        </ol>
                    ) : (
                        <div className="mt-7 rounded-3xl bg-background p-5 sm:p-7">
                            <span className="grid size-12 place-items-center rounded-full bg-primary/15 text-accent">
                                <Icon name="book" className="size-6" />
                            </span>
                            <h3 className="mt-5 font-display text-xl font-bold">Pelajaran sedang disiapkan</h3>
                            <p className="mt-2 max-w-lg text-sm leading-7 text-foreground/60">
                                Belum ada pelajaran untuk {path.title}. Kamu bisa belajar dari materi lain yang
                                tersedia.
                            </p>
                            <Link
                                href={navigation.learning}
                                className="mt-6 inline-flex min-h-11 items-center gap-2 rounded-full bg-accent px-5 py-3 text-sm font-bold text-background transition-colors hover:bg-accent/90 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
                            >
                                Jelajahi materi lain <Icon name="arrow" className="size-4" />
                            </Link>
                        </div>
                    )}
                    <Pagination {...lessons} />
                </section>
            </div>
        </>
    );
}
