import { useEffect, useState } from 'react';
import { router, usePage } from '../../routing';
import { PageMeta } from '../../Components/ui';
import { Card, Heading, Back, Status, Action } from '../../Components/forms';
import Analysis from '../../Components/Analysis';
import AiStatistics from '../../Components/AiStatistics';
export default function AiDetail({ kind, record, meta }) {
    const { links } = usePage().props;
    const [paused, setPaused] = useState(false);
    useEffect(() => {
        setPaused(false);
        if (!['queued', 'processing'].includes(record.status)) return;
        let busy = false,
            attempts = 0;
        const timer = setInterval(() => {
            if (document.visibilityState === 'visible' && !busy && attempts < 120) {
                attempts++;
                if (attempts >= 120) {
                    clearInterval(timer);
                    setPaused(true);
                }
                busy = true;
                router.reload({
                    only: ['record'],
                    onFinish: () => {
                        busy = false;
                    },
                });
            }
        }, 5000);
        return () => clearInterval(timer);
    }, [record.id, record.status]);
    return (
        <>
            <PageMeta {...meta} />
            <Back
                href={`${links['admin.ai.index']}?tab=${kind === 'profile' ? 'interviews' : kind === 'draft' ? 'drafts' : 'overview'}`}
            >
                Kembali ke Pusat AI
            </Back>
            <Heading
                title={
                    kind === 'profile'
                        ? `Pelajar #${record.id}`
                        : kind === 'draft'
                          ? `Draf kuis #${record.id}`
                          : 'Interpretasi statistik AI'
                }
            >
                Hasil AI perlu ditinjau. Interpretasi tidak menetapkan kemampuan individu atau membuktikan sebab akibat.
            </Heading>
            {paused && (
                <p role="alert" className="mb-4 text-sm text-accent">
                    Pemeriksaan otomatis dijeda. Muat ulang nanti untuk melihat pembaruan pekerjaan.
                </p>
            )}
            <div className="mb-6">
                <Status value={record.status} />
            </div>
            {kind === 'profile' ? (
                <>
                    <Card className="mb-5">
                        <h2 className="font-display text-xl font-bold">Dasar rekomendasi</h2>
                        <dl className="mt-5 grid gap-5 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-foreground/45">Peran</dt>
                                <dd className="mt-2 font-bold">{record.analysis.role}</dd>
                            </div>
                            <div>
                                <dt className="text-foreground/45">Jenis profil</dt>
                                <dd className="mt-2 font-bold">{record.mode === 'account' ? 'Akun' : 'Anonim'}</dd>
                            </div>
                            <div>
                                <dt className="text-foreground/45">Materi pilihan</dt>
                                <dd className="mt-2 leading-7">
                                    {record.paths.map((path) => path.title).join(', ') || 'Materi sudah tidak tersedia'}
                                </dd>
                            </div>
                        </dl>
                        <div className="mt-5">
                            <Action url={record.retryUrl}>Tes ulang analisis</Action>
                        </div>
                    </Card>
                    <Analysis analysis={record.analysis} poll={false} />
                    <Card className="mt-5">
                        <h2 className="font-display text-xl font-bold">Riwayat proses & hasil AI</h2>
                        <p className="mt-3 text-xs leading-6 text-foreground/50">
                            20 kejadian terakhir. Respons mentah dan API key tidak dicatat; identitas tidak disertakan
                            dalam input AI. Waktu WITA. Permintaan terakhir:{' '}
                            {record.requestedAt
                                ? new Date(record.requestedAt).toLocaleString('id-ID', { timeZone: 'Asia/Makassar' })
                                : 'Belum diminta'}
                            .
                        </p>
                        <ol className="mt-5 space-y-3">
                            {(record.events ?? []).map((event, index) => (
                                <li key={`${event.at}-${index}`} className="rounded-2xl bg-background p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <Status value={event.status} />
                                        <time className="text-xs text-foreground/50" dateTime={event.at}>
                                            {new Date(event.at).toLocaleString('id-ID', { timeZone: 'Asia/Makassar' })}
                                        </time>
                                    </div>
                                    <p className="mt-3 text-sm leading-7">{event.message}</p>
                                    {(event.exception_type || event.http_status || event.duration_ms != null) && (
                                        <p className="mt-2 break-all text-xs leading-6 text-foreground/50">
                                            {event.exception_type}{' '}
                                            {event.http_status ? `HTTP ${event.http_status}` : ''}{' '}
                                            {event.duration_ms != null ? `${event.duration_ms} ms` : ''}
                                        </p>
                                    )}
                                    {event.result && (
                                        <details className="mt-3">
                                            <summary className="min-h-11 cursor-pointer content-center text-sm font-bold text-accent">
                                                Lihat hasil yang disimpan
                                            </summary>
                                            <p className="mt-2 whitespace-pre-line text-sm leading-7 text-foreground/65">
                                                {event.result.summary}
                                            </p>
                                            <ul className="mt-3 space-y-2 text-sm leading-7 text-foreground/55">
                                                {event.result.recommendations.map((item) => (
                                                    <li key={item.learning_path_id}>
                                                        Materi #{item.learning_path_id}: {item.reason}
                                                    </li>
                                                ))}
                                            </ul>
                                        </details>
                                    )}
                                </li>
                            ))}
                        </ol>
                        {!record.events?.length && (
                            <p className="mt-4 text-sm text-foreground/55">
                                Belum ada riwayat. Tes ulang untuk merekam proses pertama.
                            </p>
                        )}
                    </Card>
                </>
            ) : kind === 'draft' ? (
                <div className="space-y-4">
                    {(record.questions ?? []).map((question, index) => (
                        <Card key={index}>
                            <h2 className="font-display text-xl font-bold">
                                {index + 1}. {question.prompt}
                            </h2>
                            <ol className="mt-4 space-y-2">
                                {question.options.map((option, choice) => (
                                    <li
                                        className={`rounded-2xl bg-background p-3 text-sm ${choice === question.correct_option ? 'text-success' : 'text-foreground/65'}`}
                                        key={choice}
                                    >
                                        {String.fromCharCode(65 + choice)}. {option}
                                        {choice === question.correct_option ? ' ✓ kunci jawaban' : ''}
                                    </li>
                                ))}
                            </ol>
                            <p className="mt-4 text-sm leading-7 text-foreground/55">{question.explanation}</p>
                        </Card>
                    ))}
                    {!record.questions?.length && (
                        <Card>
                            <p className="text-sm text-foreground/55">Belum ada soal yang berhasil dihasilkan.</p>
                        </Card>
                    )}
                </div>
            ) : (
                <>
                    <h2 className="mb-4 font-display text-xl font-bold">Data faktual yang digunakan</h2>
                    <AiStatistics snapshot={record.snapshot} />
                    <Card className="mt-6">
                        <h2 className="font-display text-xl font-bold">Interpretasi AI · perlu ditinjau</h2>
                        {record.status === 'completed' && record.result ? (
                            <>
                                <p className="mt-4 whitespace-pre-line text-sm leading-8 text-foreground/75">
                                    {record.result.summary}
                                </p>
                                {[
                                    ['observations', 'Observasi berdasarkan data'],
                                    ['suggestions', 'Saran untuk dipertimbangkan'],
                                ].map(([key, label]) => (
                                    <section key={key} className="mt-5">
                                        <h3 className="font-bold text-accent">{label}</h3>
                                        <ul className="mt-3 list-disc space-y-2 pl-5 text-sm leading-7 text-foreground/60">
                                            {(record.result[key] ?? []).map((item, index) => (
                                                <li key={index}>{item}</li>
                                            ))}
                                        </ul>
                                    </section>
                                ))}
                            </>
                        ) : (
                            <p className="mt-4 text-sm leading-7 text-foreground/55">
                                {['queued', 'processing'].includes(record.status)
                                    ? 'Laporan diproses di background. Statistik tetap bisa dibaca selama menunggu.'
                                    : 'Interpretasi belum tersedia. Periksa layanan atau minta ulang laporan dari Statistik AI.'}
                            </p>
                        )}
                    </Card>
                </>
            )}
        </>
    );
}
