import { useEffect, useState } from 'react';
import usePolling from '../Hooks/usePolling';
import { Card, Status, Action, secondaryClass } from './forms';
import AudioPlayer from './AudioPlayer';
export default function AudioStatus({ audio: initial }) {
    const [stopped, setStopped] = useState(false);
    const { data, error, exhausted } = usePolling(initial?.statusUrl, {
        active: !stopped && ['queued', 'processing'].includes(initial?.status),
        interval: 3000,
        limit: 720,
    });
    const changed = data?.version && data.version !== initial?.version;
    useEffect(() => {
        if (changed || (data && !['queued', 'processing'].includes(data.status))) setStopped(true);
    }, [changed, data]);
    if (!initial) return null;
    const audio = !changed && data?.audio ? data.audio : initial,
        progress = audio.progress ?? {};
    const pending = ['queued', 'processing'].includes(audio.status);
    const stages = {
        loading_model: 'Memuat model suara',
        preparing_voice: 'Menyiapkan referensi suara',
        generating: 'Sedang membuat audio',
        finalizing: 'Memeriksa file audio',
    };
    return (
        <Card className="mb-5">
            <Status value={audio.status} />
            {exhausted && pending && (
                <p role="alert" className="mt-3 text-sm text-accent">
                    Pemeriksaan otomatis dijeda. Muat ulang untuk memperbarui status audio; proses di server tetap
                    berjalan.
                </p>
            )}
            {changed ? (
                <p role="alert" className="mt-4 text-sm leading-7 text-accent">
                    Versi narasi berubah. Muat ulang halaman sebelum menyimpan atau menyetujui audio.
                </p>
            ) : (
                <>
                    {pending && !exhausted && (
                        <>
                            <p role="status" className="mt-4 text-sm font-bold">
                                {stages[progress.stage] ??
                                    (audio.status === 'queued'
                                        ? 'Menunggu giliran pembuatan audio'
                                        : 'Sedang membuat audio')}
                            </p>
                            <progress
                                aria-label="Pembuatan audio"
                                max={100}
                                value={progress.percent ?? undefined}
                                className="mt-4 h-2 w-full accent-[var(--color-accent)]"
                            />
                            <p className="mt-3 text-xs leading-6 text-foreground/55">
                                {progress.total_parts > 0
                                    ? `Bagian ${progress.completed_parts} dari ${progress.total_parts} selesai`
                                    : 'Menunggu informasi bagian narasi.'}
                            </p>
                        </>
                    )}
                    <div className="mt-4 grid gap-3 text-xs text-foreground/55 sm:grid-cols-3">
                        <p>
                            Mulai diproses
                            <span className="mt-2 block font-bold text-foreground">
                                {progress.started_at
                                    ? new Date(progress.started_at).toLocaleTimeString('id-ID', {
                                          timeZone: 'Asia/Makassar',
                                      })
                                    : 'Menunggu worker'}
                            </span>
                        </p>
                        <p>
                            Durasi proses
                            <span className="mt-2 block font-bold text-foreground">
                                {progress.elapsed_seconds !== null && progress.elapsed_seconds !== undefined
                                    ? `${Math.floor(progress.elapsed_seconds / 60)} menit ${progress.elapsed_seconds % 60} detik`
                                    : '—'}
                            </span>
                        </p>
                        {pending && (
                            <p>
                                Perkiraan sisa
                                <span className="mt-2 block font-bold text-foreground">
                                    {progress.estimated_remaining_seconds !== null &&
                                    progress.estimated_remaining_seconds !== undefined
                                        ? `Sekitar ${Math.ceil(progress.estimated_remaining_seconds / 60)} menit`
                                        : 'Menunggu bagian pertama selesai'}
                                </span>
                            </p>
                        )}
                    </div>
                    {audio.status === 'failed' && (
                        <p className="mt-4 text-sm text-danger">Pembuatan audio gagal. Coba buat audio kembali.</p>
                    )}
                    {audio.status === 'unavailable' && (
                        <p className="mt-4 text-sm text-foreground/55">
                            Mesin TTS belum tersedia. Rekaman dapat diunggah.
                        </p>
                    )}
                    {audio.status === 'stale' && (
                        <p className="mt-4 text-sm text-accent">
                            Materi sudah berubah. Simpan narasi dan buat ulang audio.
                        </p>
                    )}
                    <AudioPlayer audio={audio} label="Dengarkan sebelum menyetujui" />
                    {audio.status === 'review' && (
                        <Action url={audio.approveUrl} data={{ version: audio.version }}>
                            Sudah diperiksa, setujui audio
                        </Action>
                    )}
                </>
            )}
            {error && (
                <p role="alert" className="mt-3 text-sm text-danger">
                    {error}
                </p>
            )}
        </Card>
    );
}
