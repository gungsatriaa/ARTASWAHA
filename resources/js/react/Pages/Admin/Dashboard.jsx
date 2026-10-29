import { lazy, Suspense, useMemo } from 'react';
import OnlineVisitors from '../../Components/OnlineVisitors';
import AdminWeekly from '../../Components/AdminWeekly';
import Skeleton from '../../Components/Skeleton';
import { Link, usePage } from '../../routing';
import { PageMeta, buttonClass } from '../../Components/ui';
import { Card, Heading, Stat, Status, secondaryClass } from '../../Components/forms';
const Chart = lazy(() => import('../../Components/Chart'));
export default function Dashboard({ counts, tasks, lessons, overview, meta }) {
    const { links } = usePage().props;
    const series = useMemo(
        () => [
            { label: 'Modul selesai', data: overview?.chart.completions ?? [] },
            { label: 'Jawaban kuis', data: overview?.chart.answers ?? [] },
        ],
        [overview],
    );
    const publication =
        Number(counts.total) > 0 ? Math.round((Number(counts.published) / Number(counts.total)) * 100) : 0;
    return (
        <>
            <PageMeta {...meta} />
            <Heading
                title="Dashboard pengelola"
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
                Pantau aktivitas pelajar, kesiapan materi, dan pekerjaan yang perlu ditindaklanjuti.
            </Heading>
            <div className="mb-6 grid items-stretch gap-5 xl:grid-cols-[1.25fr_1fr]">
                <OnlineVisitors />
                <Card>
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="font-display text-lg font-bold">Kesiapan modul</h2>
                        <span className="admin-number font-sans text-2xl font-bold text-accent">{publication}%</span>
                    </div>
                    <progress
                        aria-label="Persentase modul terbit"
                        value={publication}
                        max={100}
                        className="mt-5 block h-2 w-full overflow-hidden rounded-full accent-[var(--color-primary)]"
                    />
                    <p className="mt-3 text-xs leading-6 text-foreground/50">
                        {counts.published ?? 0} dari {counts.total ?? 0} modul sudah ditinjau dan diterbitkan.
                    </p>
                    <Link
                        href={`${links['admin.lessons.index']}?status=draft`}
                        className="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-accent"
                    >
                        Tinjau modul berikutnya →
                    </Link>
                </Card>
            </div>
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
            {overview && (
                <section className="mt-6" aria-labelledby="dashboard-weekly-title">
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h2 id="dashboard-weekly-title" className="font-display text-xl font-bold">
                            Aktivitas tujuh hari terakhir
                        </h2>
                        <Link href={links['admin.analytics.index']} className="text-sm font-bold text-accent">
                            Analisis lengkap →
                        </Link>
                    </div>
                    <AdminWeekly weekly={overview.weekly} />
                    <Card className="mt-5">
                        <h2 className="font-display text-xl font-bold">Tren pembelajaran</h2>
                        <p className="mt-2 text-xs text-foreground/50">
                            Penyelesaian modul dan jawaban kuis per hari · 14 hari terakhir, WITA
                        </p>
                        <Suspense fallback={<Skeleton label="Menyiapkan grafik aktivitas…" />}>
                            <Chart label="Tren pembelajaran platform" labels={overview.chart.labels} series={series} />
                        </Suspense>
                    </Card>
                </section>
            )}
            <section aria-label="Tindak lanjut AI dan audio" className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {[
                    [
                        'Analisis belajar gagal',
                        tasks.failed_interviews,
                        `${links['admin.ai.index']}?tab=interviews&status=failed`,
                    ],
                    [
                        'Antrean AI > 10 menit',
                        overview?.metrics.delayedAnalysis ?? 0,
                        `${links['admin.ai.index']}?tab=interviews&status=queued`,
                    ],
                    ['Audio menunggu tinjauan', tasks.audio_review, `${links['admin.audio.index']}?status=review`],
                    ['Audio gagal/kedaluwarsa', tasks.audio_failed, links['admin.audio.index']],
                ].map(([label, value, url]) => (
                    <Link key={label} href={url}>
                        <Stat label={label} value={value} />
                    </Link>
                ))}
            </section>
            <div className="mt-6">
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
            </div>
        </>
    );
}
