import Skeleton from '../../Components/Skeleton';
import { lazy, Suspense, useMemo } from 'react';
import { PageMeta } from '../../Components/ui';
import { Card, Heading, Stat } from '../../Components/forms';
import { usePage } from '@inertiajs/react';
import usePolling from '../../Hooks/usePolling';
const Chart = lazy(() => import('../../Components/Chart'));
export default function Analytics({ chart, metrics, topPaths, meta }) {
    const { links } = usePage().props;
    const activitySeries = useMemo(
        () => [
            { label: 'Modul selesai', data: chart.completions },
            { label: 'Jawaban kuis', data: chart.answers },
        ],
        [chart],
    );
    const userSeries = useMemo(() => [{ label: 'Akun baru', data: chart.users }], [chart]);
    const { data: online, error } = usePolling(links['admin.analytics.online'], { interval: 15000, limit: 10000 });
    return (
        <>
            <PageMeta {...meta} />
            <Heading title="Analitik ARTASWAHA">
                Aktivitas belajar dan kuis dari data tersimpan. Grafik harian memakai waktu Bali (WITA).
            </Heading>
            <Card>
                <div className="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p className="text-xs font-bold text-success">● Aktivitas langsung</p>
                        <h2 className="mt-3 font-display text-2xl font-bold">Pengunjung aktif</h2>
                        <p className="mt-2 max-w-xl text-sm leading-7 text-foreground/55">
                            Browser yang mengirim aktivitas dalam 2 menit terakhir; diperbarui tiap 15 detik.
                        </p>
                    </div>
                    <p className="font-display text-6xl font-bold tabular-nums text-accent" aria-live="polite">
                        {online?.total ?? '—'}
                    </p>
                </div>
                <div className="mt-5 flex flex-wrap gap-4 text-sm text-foreground/60">
                    <p>
                        Akun <strong className="ml-2 text-foreground">{online?.accounts ?? '—'}</strong>
                    </p>
                    <p>
                        Tanpa akun <strong className="ml-2 text-foreground">{online?.anonymous ?? '—'}</strong>
                    </p>
                </div>
                {error && <p className="mt-3 text-xs text-danger">{error}</p>}
            </Card>
            <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {[
                    ['Akun terdaftar', 'users'],
                    ['Profil anonim', 'anonymousProfiles'],
                    ['Modul selesai', 'completedLessons'],
                    ['Kuis dibuat', 'quizzes'],
                ].map(([label, key]) => (
                    <Stat key={key} label={label} value={metrics[key]} />
                ))}
            </div>
            <div className="mt-5 grid gap-5 xl:grid-cols-[1.6fr_1fr]">
                <Card>
                    <h2 className="font-display text-xl font-bold">Aktivitas pembelajaran</h2>
                    <p className="mt-2 text-xs text-foreground/50">
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
                        <strong className="mr-3 font-display text-3xl text-accent">
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
            <p className="mt-6 text-xs leading-6 text-foreground/40">
                Online adalah browser aktif, bukan jumlah koneksi WebSocket. Browser yang menutup halaman masih bisa
                dihitung hingga dua menit. Room dan jawaban yang dihapus tidak masuk statistik.
            </p>
        </>
    );
}
