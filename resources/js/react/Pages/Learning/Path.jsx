import { Link, usePage } from '@inertiajs/react';
import Icon from '../../Components/Icon';
import { CategoryIcon, EmptyState, PageMeta, Pagination } from '../../Components/ui';

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
            <header className="rounded-card bg-surface p-6 sm:p-8">
                <div className="flex items-start gap-4 sm:gap-5">
                    <CategoryIcon group={path.group} className="size-14" />
                    <div className="min-w-0">
                        <p className="text-sm font-semibold text-accent">
                            {path.group === 'aksara' ? 'Aksara Bali' : 'Paribasa Bali'}
                        </p>
                        <h1 className="mt-2 break-words font-display text-3xl font-bold leading-tight tracking-tight sm:text-4xl">
                            {path.title}
                        </h1>
                    </div>
                </div>
                {path.description && (
                    <p className="mt-5 max-w-2xl text-sm leading-7 text-foreground/60 sm:text-base">
                        {path.description}
                    </p>
                )}
                <p className="mt-5 text-xs font-semibold text-foreground/45">{lessons.total} pelajaran tersedia</p>
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
            <section aria-labelledby="lesson-list-title" className="mt-8">
                <h2 id="lesson-list-title" className="font-display text-2xl font-bold">
                    Pilih pelajaran
                </h2>
                <p className="mt-2 text-sm leading-6 text-foreground/55">
                    Pelajari materi, lalu coba latihan singkatnya.
                </p>
                {lessons.data.length ? (
                    <ol className="mt-5 grid gap-3 md:grid-cols-2">
                        {lessons.data.map((lesson, index) => (
                            <li key={lesson.id}>
                                <Link
                                    href={lesson.url}
                                    className="group flex h-full min-h-24 items-start gap-4 rounded-card bg-surface p-5 transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
                                >
                                    <span
                                        aria-hidden="true"
                                        className="grid size-10 shrink-0 place-items-center rounded-xl bg-accent/10 font-display text-sm font-bold text-accent"
                                    >
                                        {String(lessons.from + index).padStart(2, '0')}
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block break-words font-display text-lg font-bold leading-snug group-hover:text-accent">
                                            {lesson.title}
                                        </span>
                                        {lesson.summary && (
                                            <span className="mt-2 block text-sm leading-6 text-foreground/55">
                                                {lesson.summary}
                                            </span>
                                        )}
                                    </span>
                                    <Icon name="arrow" className="mt-1 size-4 shrink-0 text-foreground/35" />
                                </Link>
                            </li>
                        ))}
                    </ol>
                ) : (
                    <div className="mt-5">
                        <EmptyState
                            title="Pelajaran sedang disiapkan"
                            action={
                                <Link
                                    href={navigation.learning}
                                    className="inline-flex min-h-11 items-center rounded-full bg-accent px-5 py-3 text-sm font-bold text-background"
                                >
                                    Pilih materi lain
                                </Link>
                            }
                        >
                            Belum ada pelajaran untuk {path.title}. Sementara itu, kamu bisa menjelajahi materi lain.
                        </EmptyState>
                    </div>
                )}
                <Pagination {...lessons} />
            </section>
        </>
    );
}
