import Skeleton from '../../Components/Skeleton';
import { lazy, Suspense, useMemo } from 'react';
import { PageMeta } from '../../Components/ui';
import { Card, Heading, Stat } from '../../Components/forms';
import { usePage } from '@inertiajs/react';
import OnlineVisitors from '../../Components/OnlineVisitors';
import AdminWeekly from '../../Components/AdminWeekly';
import { Link } from '@inertiajs/react';
const Chart = lazy(() => import('../../Components/Chart'));
export default function Analytics({
    chart,
    metrics,
    topPaths,
    weekly = {},
    roomStatuses = {},
    analysisStatuses = {},
    roles = [],
    meta,
}) {
    const { links } = usePage().props;
    const activitySeries = useMemo(
        () => [
            { label: 'Modul selesai', data: chart.completions },
            { label: 'Jawaban kuis', data: chart.answers },
        ],
        [chart],
    );
    const userSeries = useMemo(() => [{ label: 'Akun baru', data: chart.users }], [chart]);
    const accuracySeries = useMemo(
        () => [{ label: 'Jawaban benar (%)', data: chart.accuracy ?? chart.labels.map(() => null) }],
        [chart],
    );
    return (
        <div className="admin-analytics min-w-0">
            <PageMeta {...meta} />
            <Heading title="Analitik ARTASWAHA">
                Aktivitas belajar dan kuis dari data tersimpan. Grafik harian memakai waktu Bali (WITA).
            </Heading>
            <OnlineVisitors />
            <section className="mt-6" aria-labelledby="weekly-title">
                <h2 id="weekly-title" className="mb-4 font-display text-xl font-bold">
                    Tujuh hari terakhir
                </h2>
                <AdminWeekly weekly={weekly} />
            </section>
            <div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
                {[
                    ['Akun terdaftar', 'users'],
                    ['Profil anonim', 'anonymousProfiles'],
                    ['Pelajar menyelesaikan modul', 'learners'],
                    ['Modul selesai', 'completedLessons'],
                    ['Kuis dibuat', 'quizzes'],
                ].map(([label, key]) => (
                    <Stat key={key} label={label} value={metrics[key]} />
                ))}
            </div>
            <div className="mt-5 grid items-stretch gap-5 xl:grid-cols-2">
                <Card>
                    <h2 className="font-display text-xl font-bold">Aktivitas pembelajaran</h2>
                    <p className="mt-2 text-xs leading-6 text-foreground/50">
                        Penyelesaian modul dan jawaban kuis · 14 hari terakhir
                    </p>
                    <Suspense fallback={<Skeleton label="Menyiapkan grafik…" />}>
                        <Chart
                            label="Grafik aktivitas pembelajaran 14 hari"
                            labels={chart.labels}
                            series={activitySeries}
                        />
                    </Suspense>
                </Card>
                <Card>
                    <h2 className="font-display text-xl font-bold">Akun baru</h2>
                    <p className="mt-2 text-xs leading-6 text-foreground/50">
                        Pendaftaran akun per hari · 14 hari terakhir
                    </p>
                    <Suspense fallback={<Skeleton label="Menyiapkan grafik…" />}>
                        <Chart label="Pendaftaran akun per hari" type="bar" labels={chart.labels} series={userSeries} />
                    </Suspense>
                </Card>
            </div>
            <div className="mt-5 grid gap-5 lg:grid-cols-2">
                <Card>
                    <h2 className="font-display text-xl font-bold">Aktivitas kuis</h2>
                    <div className="mt-5 grid grid-cols-2 gap-3">
                        <Stat label="Room tersimpan" value={metrics.rooms} />
                        <Stat label="Jawaban tercatat" value={metrics.answers} />
                    </div>
                    <p className="mt-5 text-sm text-foreground/55">
                        <strong className="mr-3 font-sans text-3xl text-accent">
                            {metrics.accuracy === null ? '—' : `${metrics.accuracy}%`}
                        </strong>
                        jawaban benar
                    </p>
                </Card>
                <Card>
                    <h2 className="font-display text-xl font-bold">Jalur paling banyak diselesaikan</h2>
                    <ol className="mt-5 space-y-3">
                        {topPaths.map((path) => (
                            <li
                                key={path.title}
                                className="flex justify-between gap-3 rounded-2xl bg-background p-4 text-sm"
                            >
                                <span className="break-words">{path.title}</span>
                                <strong className="text-accent">{path.completions}</strong>
                            </li>
                        ))}
                        {!topPaths.length && (
                            <li className="text-sm text-foreground/55">Belum ada modul yang diselesaikan.</li>
                        )}
                    </ol>
                </Card>
            </div>
            <div className="mt-5 grid items-start gap-5 xl:grid-cols-2">
                <div className="min-w-0 space-y-5">
                    <Card>
                        <h2 className="font-display text-xl font-bold">Ketepatan jawaban per hari</h2>
                        <p className="mt-2 text-xs leading-6 text-foreground/50">
                            Persentase jawaban benar. Hari tanpa jawaban tidak diberi nilai 0%.
                        </p>
                        <Suspense fallback={<Skeleton label="Menyiapkan grafik ketepatan…" />}>
                            <Chart
                                percentage
                                label="Ketepatan jawaban kuis 14 hari"
                                labels={chart.labels}
                                series={accuracySeries}
                            />
                        </Suspense>
                    </Card>
                    <Card>
                        <h2 className="font-display text-xl font-bold">Status room</h2>
                        <div className="mt-5 grid grid-cols-2 gap-3">
                            {Object.entries({
                                waiting: 'Menunggu',
                                active: 'Berlangsung',
                                finished: 'Selesai',
                                inactive: 'Nonaktif',
                            }).map(([key, label]) => (
                                <div key={key} className="rounded-2xl bg-background p-4">
                                    <p className="min-h-10 text-xs leading-5 text-foreground/50">{label}</p>
                                    <p className="mt-2 font-sans text-2xl font-bold">{roomStatuses[key] ?? 0}</p>
                                </div>
                            ))}
                        </div>
                    </Card>
                </div>
                <div className="min-w-0 space-y-5">
                    <Card>
                        <h2 className="font-display text-xl font-bold">Peran pelajar</h2>
                        <p className="mt-2 text-xs leading-6 text-foreground/50">
                            Pilihan peran dari profil perkenalan yang tersimpan.
                        </p>
                        <ul className="mt-5 space-y-4">
                            {roles.map((item) => {
                                const total = roles.reduce((sum, entry) => sum + Number(entry.total), 0);
                                return (
                                    <li key={item.role}>
                                        <div className="mb-2 flex justify-between gap-3 text-sm">
                                            <span>
                                                {{
                                                    murid: 'Murid',
                                                    guru: 'Guru',
                                                    pendatang: 'Pendatang',
                                                    masyarakat: 'Masyarakat umum',
                                                }[item.role] ?? 'Lainnya'}
                                            </span>
                                            <strong className="text-accent">{item.total}</strong>
                                        </div>
                                        <progress
                                            aria-label={`Proporsi peran ${item.role}`}
                                            value={Number(item.total)}
                                            max={Math.max(1, total)}
                                            className="block h-2 w-full overflow-hidden rounded-full accent-[var(--color-primary)]"
                                        />
                                    </li>
                                );
                            })}
                            {!roles.length && (
                                <li className="rounded-2xl bg-background p-5 text-sm text-foreground/50">
                                    Belum ada profil perkenalan.
                                </li>
                            )}
                        </ul>
                    </Card>
                    <Card>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h2 className="font-display text-xl font-bold">Kesehatan analisis AI</h2>
                            <Link className="text-sm font-bold text-accent" href={links['admin.ai.index']}>
                                Pusat AI
                            </Link>
                        </div>
                        <div className="mt-5 grid grid-cols-2 gap-3">
                            {[
                                ['Selesai', analysisStatuses.completed ?? 0],
                                ['Mengantre / diproses', metrics.pendingAnalysis ?? 0],
                                ['Gagal', metrics.failedAnalysis ?? 0],
                                ['Tertunda > 10 menit', metrics.delayedAnalysis ?? 0],
                                ['Audio perlu tinjauan', metrics.audioReview ?? 0],
                                ['Audio gagal / kedaluwarsa', metrics.audioFailed ?? 0],
                            ].map(([label, value]) => (
                                <div key={label} className="rounded-2xl bg-background p-4">
                                    <p className="min-h-10 text-xs leading-5 text-foreground/50">{label}</p>
                                    <p className="mt-2 font-sans text-2xl font-bold">{value}</p>
                                </div>
                            ))}
                        </div>
                        <p className="mt-4 text-xs leading-6 text-foreground/50">
                            Antrean tertunda termasuk dalam total mengantre / diproses; tidak selalu berarti gagal.
                        </p>
                    </Card>
                </div>
            </div>
            <p className="mt-6 text-xs leading-6 text-foreground/40">
                Online menghitung akun aktif satu kali serta sesi tanpa akun. Statistik total bersifat kumulatif,
                sementara grafik dan perbandingan mingguan memakai 14 hari terakhir. Pelajar tercatat menghitung akun
                unik atau profil perangkat tanpa akun yang menyelesaikan modul. Aktivitas membaca dan durasi belajar
                belum tercatat. Online bukan jumlah koneksi WebSocket. Browser yang menutup halaman masih bisa dihitung
                hingga dua menit. Room dan jawaban yang dihapus tidak masuk statistik.
            </p>
        </div>
    );
}
