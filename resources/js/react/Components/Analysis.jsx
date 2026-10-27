import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Skeleton from './Skeleton';
import usePolling from '../Hooks/usePolling';
import { Card, Status } from './forms';

export default function Analysis({ analysis: initial, compact = false, poll = true }) {
    const { links, navigation } = usePage().props;
    const [finished, setFinished] = useState(false);
    const [slow, setSlow] = useState(false);
    const { data, error, exhausted } = usePolling(links['profile.analysis.show'], {
        active: poll && !finished && ['queued', 'processing'].includes(initial?.status),
        interval: 5000,
        limit: 24,
    });
    const analysis = data?.analysis ?? initial;
    useEffect(() => {
        if (data?.analysis && !['queued', 'processing'].includes(data.analysis.status)) setFinished(true);
    }, [data]);
    useEffect(() => {
        setSlow(false);
        if (analysis?.status !== 'processing') return;
        const timer = setTimeout(() => setSlow(true), 20000);
        return () => clearTimeout(timer);
    }, [analysis?.status]);
    if (!analysis) return null;
    const notes = {
        queued: 'Analisis menunggu antrean. Kamu bisa melanjutkan belajar sementara menunggu.',
        processing: 'Rekomendasi sedang disiapkan. Kamu tetap bisa mulai belajar dari materi pilihanmu.',
        failed: 'Analisis belum berhasil. Sistem akan mencoba lagi pada pembaruan berikutnya. Progresmu tetap tersimpan.',
        delayed: 'Antrean analisis lebih lama dari biasanya. Kamu bisa melanjutkan belajar dan melihat hasilnya nanti.',
        unavailable: 'Rekomendasi AI belum tersedia. Kamu tetap bisa belajar dari materi pilihanmu.',
    };
    return (
        <Card id="analisis">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h2 className="font-display text-xl font-bold">Analisis belajarmu</h2>
                <Status value={analysis.status} />
            </div>
            <p className="mt-4 whitespace-pre-line break-words text-sm leading-7 text-foreground/65" role="status">
                {analysis.summary || notes[analysis.status] || notes.unavailable}
            </p>
            {poll && analysis.status === 'processing' && !slow && !error && !exhausted && (
                <div className="mt-4">
                    <Skeleton label="Menyiapkan analisis belajar…" compact />
                </div>
            )}
            {slow && !error && !exhausted && analysis.status === 'processing' && (
                <p className="mt-3 text-xs leading-6 text-foreground/50">
                    AI membutuhkan waktu lebih lama. Hasil akan tampil jika proses selesai; kamu tetap bisa melanjutkan
                    belajar.
                </p>
            )}
            {exhausted && ['queued', 'processing'].includes(analysis.status) && (
                <p role="alert" className="mt-3 text-xs leading-6 text-accent">
                    Analisis masih diproses. Pemeriksaan otomatis dijeda; muat ulang nanti untuk melihat pembaruan.
                    Progresmu tetap tersimpan.
                </p>
            )}
            {error && (
                <p role="alert" className="mt-3 text-xs text-danger">
                    {error}
                </p>
            )}
            {analysis.updated_at && (
                <p className="mt-3 text-xs text-foreground/45">
                    Terakhir diperbarui{' '}
                    <time dateTime={analysis.updated_at}>
                        {new Date(analysis.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Makassar' })}
                    </time>
                </p>
            )}
            {compact ? (
                <Link
                    className="mt-4 inline-flex min-h-11 items-center text-sm font-bold text-accent"
                    href={`${navigation.profile}#analisis`}
                >
                    Lihat analisis dan peta belajarmu →
                </Link>
            ) : (
                <>
                    <ol className="mt-5 space-y-3">
                        {(analysis.recommendations ?? []).map((item, index) => (
                            <li key={item.url}>
                                <Link href={item.url} className="flex gap-3 rounded-2xl bg-background p-4">
                                    <span className="grid size-8 shrink-0 place-items-center rounded-full bg-primary/20 text-xs font-bold text-accent">
                                        {index + 1}
                                    </span>
                                    <span className="min-w-0">
                                        <span className="block font-bold">{item.title}</span>
                                        <span className="mt-2 block text-sm leading-6 text-foreground/55">
                                            {item.reason}
                                        </span>
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ol>
                    <details className="mt-5">
                        <summary className="min-h-11 cursor-pointer content-center text-xs font-semibold text-foreground/50">
                            Tentang analisis ini
                        </summary>
                        <p className="text-xs leading-6 text-foreground/45">
                            Diperbarui otomatis sekitar setiap 6 jam berdasarkan progres dan jawaban kuis yang tercatat.
                            Analisis ini bukan penilaian kemampuan. Aktivitas membaca dan durasi belajar belum tercatat.
                        </p>
                    </details>
                </>
            )}
        </Card>
    );
}
