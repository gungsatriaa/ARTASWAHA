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
            <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.65fr)] lg:gap-10">
                <div className="min-w-0 lg:sticky lg:top-28">
                    <header className="rounded-card bg-surface p-6 sm:p-8">
                        <div className="flex items-start gap-4 lg:flex-col lg:gap-6">
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
                        <div className="mt-6 flex items-center gap-3 rounded-2xl bg-background p-4">
                            <Icon name="book" className="size-5 shrink-0 text-accent" />
                            <p className="text-sm font-semibold text-foreground/75">
                                {lessons.total} pelajaran tersedia
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
                <section aria-labelledby="lesson-list-title" className="min-w-0 lg:pt-2">
                    <h2 id="lesson-list-title" className="font-display text-2xl font-bold">
                        Daftar modul
                    </h2>
                    <p className="mt-2 text-sm leading-6 text-foreground/55">
                        Pilih pelajaran untuk membaca materi dan mencoba latihan singkatnya.
                    </p>
                    {lessons.data.length ? (
                        <ol className="mt-5 grid gap-3 sm:gap-4">
                            {lessons.data.map((lesson, index) => (
                                <li key={lesson.id}>
                                    <Link
                                        href={lesson.url}
                                        className="group flex h-full min-h-24 items-center gap-3 rounded-card bg-surface p-4 transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:gap-5 sm:p-6"
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
                                Belum ada pelajaran untuk {path.title}. Sementara itu, kamu bisa menjelajahi materi
                                lain.
                            </EmptyState>
                        </div>
                    )}
                    <Pagination {...lessons} />
                </section>
            </div>
        </>
    );
}
