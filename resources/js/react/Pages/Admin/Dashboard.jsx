import { Link, usePage } from '@inertiajs/react';
import { PageMeta, buttonClass } from '../../Components/ui';
import { Card, Heading, Stat, Status, secondaryClass } from '../../Components/forms';
export default function Dashboard({ counts, tasks, paths, lessons, meta }) {
    const { links } = usePage().props;
    return (
        <>
            <PageMeta {...meta} />
            <Heading
                title="Ringkasan konten"
                action={
                    <div className="flex flex-wrap gap-2">
                        <Link className={secondaryClass} href={links['admin.learning-paths.create']}>
                            Buat jalur
                        </Link>
                        <Link className={buttonClass} href={links['admin.lessons.create']}>
                            ＋ Tambah modul
                        </Link>
                    </div>
                }
            >
                Pantau materi yang sudah tersedia dan selesaikan draf yang menunggu peninjauan.
            </Heading>
            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {[
                    ['Semua modul', 'total'],
                    ['Modul terbit', 'published'],
                    ['Perlu ditinjau', 'drafts'],
                    ['Diarsipkan', 'archived'],
                ].map(([label, key]) => (
                    <Stat label={label} value={counts[key] ?? 0} key={key} />
                ))}
            </div>
            <section aria-label="Tindak lanjut AI dan audio" className="mt-5 grid gap-3 sm:grid-cols-3">
                {[
                    [
                        'Analisis interview gagal',
                        tasks.failed_interviews,
                        `${links['admin.ai.index']}?tab=interviews&status=failed`,
                    ],
                    ['Audio menunggu tinjauan', tasks.audio_review, `${links['admin.audio.index']}?status=review`],
                    ['Audio gagal/kedaluwarsa', tasks.audio_failed, links['admin.audio.index']],
                ].map(([label, value, url]) => (
                    <Link key={label} href={url}>
                        <Stat label={label} value={value} />
                    </Link>
                ))}
            </section>
            <div className="mt-6 grid items-start gap-5 xl:grid-cols-[1.5fr_1fr]">
                <Card>
                    <div className="mb-5 flex flex-wrap justify-between gap-3">
                        <h2 className="font-display text-xl font-bold">Modul terakhir diubah</h2>
                        <Link href={links['admin.lessons.index']} className="text-sm font-bold text-accent">
                            Lihat semua
                        </Link>
                    </div>
                    <div className="space-y-3">
                        {lessons.map((lesson) => (
                            <Link
                                key={lesson.id}
                                href={lesson.editUrl}
                                className="flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-background p-4"
                            >
                                <h3 className="min-w-0 break-words text-sm font-bold">{lesson.title}</h3>
                                <Status
                                    value={
                                        lesson.is_published && lesson.reviewed_at
                                            ? 'published'
                                            : lesson.reviewed_at
                                              ? 'archived'
                                              : 'draft'
                                    }
                                />
                            </Link>
                        ))}
                        {!lessons.length && (
                            <p className="py-5 text-sm text-foreground/55">Belum ada modul pembelajaran.</p>
                        )}
                    </div>
                </Card>
                <Card>
                    <div className="mb-5 flex justify-between gap-3">
                        <h2 className="font-display text-xl font-bold">Jalur belajar</h2>
                        <Link href={links['admin.learning-paths.index']} className="text-sm font-bold text-accent">
                            Kelola
                        </Link>
                    </div>
                    <div className="space-y-3">
                        {paths.map((path) => (
                            <Link
                                href={path.editUrl}
                                key={path.id}
                                className="flex items-center justify-between gap-3 rounded-2xl bg-background p-4"
                            >
                                <div className="min-w-0">
                                    <h3 className="break-words text-sm font-bold">{path.title}</h3>
                                    <p className="mt-2 text-xs text-foreground/45">{path.count} modul</p>
                                </div>
                                <span
                                    className={`text-xs font-bold ${path.is_published ? 'text-success' : 'text-foreground/40'}`}
                                >
                                    {path.is_published ? 'Tampil' : 'Draf'}
                                </span>
                            </Link>
                        ))}
                        {!paths.length && <p className="text-sm text-foreground/55">Belum ada jalur belajar.</p>}
                    </div>
                </Card>
            </div>
        </>
    );
}
