import { useEffect, useRef, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Button, PageMeta, buttonClass } from '../../Components/ui';
import { Action, Card, Errors, secondaryClass } from '../../Components/forms';
import Dialog from '../../Components/Dialog';
import AudioPlayer from '../../Components/AudioPlayer';
import AnswerFeedback from '../../Components/AnswerFeedback';
import QuizTransition from '../../Components/QuizTransition';
import QuizResults from '../../Components/QuizResults';
import AnswerInput, { hasAnswer } from '../../Components/AnswerInput';
import useRoom from '../../Hooks/useRoom';
import Icon from '../../Components/Icon';
import { AksaraText } from '../../Components/LessonMaterial';

export function Countdown({ deadline, serverNow, seconds, onExpire, label }) {
    const initial = Math.max(0, Math.ceil((Date.parse(deadline) - Date.parse(serverNow)) / 1000));
    const [remaining, setRemaining] = useState(initial),
        callback = useRef(onExpire);
    callback.current = onExpire;
    useEffect(() => {
        const offset = Date.parse(serverNow) - Date.now();
        let interval,
            last = -Infinity;
        const tick = () => {
            const value = Math.max(0, Math.ceil((Date.parse(deadline) - Date.now() - offset) / 1000));
            setRemaining(value);
            if (value === 0 && Date.now() - last >= 3000) {
                last = Date.now();
                callback.current();
            }
        };
        const start = () => {
            clearInterval(interval);
            if (document.visibilityState === 'visible') {
                tick();
                interval = setInterval(tick, 1000);
            }
        };
        start();
        document.addEventListener('visibilitychange', start);
        return () => {
            clearInterval(interval);
            document.removeEventListener('visibilitychange', start);
        };
    }, [deadline, serverNow]);
    return (
        <div className="flex items-center gap-3 rounded-2xl bg-background p-3 text-sm sm:px-4">
            <span className="shrink-0 text-foreground/55">{label}</span>
            {seconds && (
                <progress
                    aria-label={label}
                    max={seconds}
                    value={Math.min(seconds, remaining)}
                    className={`h-2 min-w-0 flex-1 ${remaining <= 5 ? 'accent-[var(--color-danger)]' : 'accent-[var(--color-primary)]'}`}
                />
            )}
            <span
                role="timer"
                aria-live="off"
                className={`ml-auto font-sans text-lg font-bold tabular-nums ${remaining <= 5 ? 'text-danger' : 'text-accent'}`}
            >
                {Math.floor(remaining / 60)}:{String(remaining % 60).padStart(2, '0')}
            </span>
        </div>
    );
}
function Question({ room, onExpire }) {
    const form = useForm({ question_id: room.question.id, answer_index: '', answer_indexes: [], answer_text: '' });
    const answered = room.position - (room.feedback ? 0 : 1);
    return (
        <Card className="!p-5 sm:!p-8">
            <div className="flex items-center justify-between gap-4">
                <div className="min-w-0">
                    <p className="truncate text-xs text-foreground/50">{room.title}</p>
                    <h2 className="mt-1 font-display text-lg font-bold">
                        Soal {room.position} <span className="text-foreground/40">dari {room.count}</span>
                    </h2>
                </div>
                <span className="grid size-11 shrink-0 place-items-center rounded-full bg-accent/10 font-sans text-xs font-bold text-accent">
                    {room.position}/{room.count}
                </span>
            </div>
            <progress
                aria-label="Progres kuis"
                max={room.count}
                value={answered}
                className="mt-5 h-2 w-full accent-[var(--color-accent)]"
            />
            <div className="py-6 sm:py-8">
                <h3 className="break-words font-display text-xl font-bold leading-relaxed sm:text-2xl sm:leading-relaxed">
                    <AksaraText>{room.question.prompt}</AksaraText>
                </h3>
            </div>
            {room.deadline && !room.feedback ? (
                <div className="mt-4">
                    <Countdown
                        deadline={room.deadline}
                        serverNow={room.serverNow}
                        seconds={room.seconds}
                        label="Sisa waktu"
                        onExpire={onExpire}
                    />
                </div>
            ) : (
                !room.feedback && (
                    <p className="flex items-center gap-2 text-xs leading-6 text-foreground/50">
                        <Icon name="info" className="size-4 shrink-0" /> Tanpa batas waktu soal. Kamu langsung lanjut setelah menjawab.
                    </p>
                )
            )}
            <AudioPlayer
                audio={room.audio}
                label="Dengarkan soal"
                deadline={room.deadline ?? room.endsAt}
                serverNow={room.serverNow}
            />
            {room.feedback ? (
                <>
                    <div className="my-4"><AnswerFeedback correct={Boolean(room.answer?.is_correct)} /></div>
                    {room.question.question_type === 'essay' && (
                        <div className="space-y-3 rounded-2xl bg-background p-4 text-sm leading-7">
                            <p className="whitespace-pre-wrap break-words">
                                <span className="text-foreground/50">Jawabanmu: </span>
                                {room.answer?.answer_text}
                            </p>
                            <p className="whitespace-pre-wrap break-words text-success">
                                Jawaban acuan: {room.correctText}
                            </p>
                        </div>
                    )}
                    <ol className="mt-4 grid gap-3 sm:grid-cols-2">
                        {room.question.options.map((option, index) => (
                            <li
                                key={index}
                                className={`flex min-h-20 items-center gap-3 rounded-2xl p-4 ring-1 ${(room.question.question_type === 'multiple' ? (room.correctOptions ?? []).includes(index) : index === room.correctOption) ? 'bg-success/10 text-success ring-success/50' : (room.question.question_type === 'multiple' ? (room.answer?.answer_indexes ?? []).includes(index) : index === room.answer?.answer_index) ? 'bg-danger/10 text-danger ring-danger/50' : 'bg-background ring-white/5'}`}
                            >
                                <span aria-hidden="true" className="grid size-9 shrink-0 place-items-center rounded-xl bg-background/50 text-sm font-bold">{String.fromCharCode(65 + index)}</span>
                                <span className="min-w-0 flex-1 break-words text-sm font-semibold leading-7"><AksaraText>{option}</AksaraText></span>
                                {(
                                    room.question.question_type === 'multiple'
                                        ? (room.correctOptions ?? []).includes(index)
                                        : index === room.correctOption
                                ) ? (
                                    <span className="max-w-16 text-right text-xs font-bold">Jawaban benar</span>
                                ) : (
                                      room.question.question_type === 'multiple'
                                          ? (room.answer?.answer_indexes ?? []).includes(index)
                                          : index === room.answer?.answer_index
                                  ) ? (
                                    <span className="max-w-16 text-right text-xs font-bold">Jawabanmu</span>
                                ) : null}
                            </li>
                        ))}
                    </ol>
                    <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                        <p className="text-xs text-foreground/50">Soal berikutnya muncul sebentar lagi.</p>
                        <Link href={room.urls.show} replace className={secondaryClass}>Lanjut <Icon name="arrow" className="size-4" /></Link>
                    </div>
                </>
            ) : (
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (!form.processing) form.post(room.urls.answer, { preserveScroll: true });
                    }}
                >
                    <Errors errors={form.errors} />
                    <AnswerInput
                        question={room.question}
                        answer={form.data}
                        onChange={(key, value) => form.setData(key, value)}
                    />
                    <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="flex items-center gap-2 text-xs leading-6 text-foreground/50"><Icon name="lock" className="size-4 shrink-0" />Jawaban tidak dapat diubah setelah dikirim.</p>
                        <Button
                            type="submit"
                            disabled={form.processing || !hasAnswer(room.question, form.data)}
                            className="w-full shrink-0 sm:w-auto"
                        >
                            {form.processing ? 'Mengirim…' : 'Kirim jawaban'}
                        </Button>
                    </div>
                </form>
            )}
        </Card>
    );
}
export default function Room({ room, meta }) {
    const { viewer, navigation: nav, csrfToken } = usePage().props;
    const { reload, connection, unavailable } = useRoom(room, viewer, csrfToken);
    const [copied, setCopied] = useState('');
    return (
        <div className={`mx-auto ${room.status === 'active' || room.feedback ? 'max-w-3xl' : 'max-w-6xl'}`}>
            <PageMeta {...meta} />
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                {['waiting', 'active'].includes(room.status) ? (
                    <Action url={room.urls.leave}>
                        {room.isHost ? '← Keluar & nonaktifkan room' : '← Keluar dari room'}
                    </Action>
                ) : (
                    <Link href={nav.quizzes} className={secondaryClass}>
                        ← Kembali ke kuis
                    </Link>
                )}
                {room.isHost && (
                    <Action
                        url={room.urls.destroy}
                        method="delete"
                        confirm={`Hapus room ${room.code}? Peserta dan jawaban akan dihapus.`}
                        className={`${secondaryClass} !text-danger`}
                    >
                        Hapus room
                    </Action>
                )}
            </div>
            {room.totalExpired && (
                <p className="mb-4 rounded-2xl bg-accent/10 p-4 text-sm font-bold text-accent">
                    Waktu total kuis sudah habis. Jawaban yang sudah dikirim tetap tersimpan.
                </p>
            )}
            {room.status === 'waiting' ? (
                <>
                    <section className="grid overflow-hidden rounded-card bg-surface lg:grid-cols-[minmax(0,1fr)_21rem]">
                        <div className="p-6 sm:p-9">
                            <span className="inline-flex rounded-full bg-primary/20 px-4 py-2 text-sm font-bold text-accent">
                                Menunggu pemain
                            </span>
                            <p className="mt-6 text-sm font-semibold text-foreground/45">{room.title}</p>
                            <h1 className="mt-3 font-display text-3xl font-bold sm:text-4xl">Siap belajar bareng?</h1>
                            <p className="mt-4 max-w-lg text-sm leading-8 text-foreground/55">
                                Bagikan kode ke temanmu. Host bisa memulai kapan saja setelah semua siap.
                            </p>
                            <div className="mt-5 flex flex-wrap gap-2 text-xs font-semibold text-foreground/65">
                                <span className="inline-flex items-center gap-2 rounded-full bg-background px-3 py-2"><Icon name="quiz" className="size-4 text-accent" />{room.count} soal</span>
                                <span className="inline-flex items-center gap-2 rounded-full bg-background px-3 py-2"><Icon name="user" className="size-4 text-accent" />{room.participants.length} pemain</span>
                            </div>
                            <p className="mt-4 text-xs text-foreground/40" role="status">
                                {connection}
                            </p>
                        </div>
                        <div className="flex flex-col justify-center bg-primary/10 p-6 sm:p-8">
                            <p className="text-sm font-semibold text-foreground/60">Kode room</p>
                            <p className="mt-3 break-all font-sans text-4xl font-bold tracking-[.1em] text-accent sm:text-5xl">
                                {room.code}
                            </p>
                            <p className="mt-4 text-sm leading-7 text-foreground/60">
                                Temanmu dapat memasukkan kode ini di halaman kuis.
                            </p>
                            <button
                                type="button"
                                className={`${secondaryClass} mt-6 w-full`}
                                onClick={async () => {
                                    try {
                                        await navigator.clipboard.writeText(room.invite);
                                        setCopied('Tautan undangan disalin.');
                                    } catch {
                                        setCopied('Salin kode room di atas untuk mengundang teman.');
                                    }
                                }}
                            >
                                Salin tautan undangan
                            </button>
                            <p role="status" className="mt-2 text-xs font-semibold text-accent">
                                {copied}
                            </p>
                        </div>
                    </section>
                    {room.totalSeconds && (
                        <p className="mt-4 text-sm text-foreground/55">
                            Batas total {room.totalSeconds / 60} menit dimulai saat host memulai kuis.
                        </p>
                    )}
                    <Card className="mt-5">
                        <header className="flex flex-wrap justify-between gap-3">
                            <h2 className="font-display text-2xl font-bold">Pemain bergabung</h2>
                            <p className="text-sm font-bold text-accent">{room.participants.length} pemain</p>
                        </header>
                        <ul className="mt-5 grid gap-3 sm:grid-cols-2">
                            {room.participants.map((member) => (
                                <li key={member.id} className="flex items-center gap-3 rounded-2xl bg-background p-4 transition-colors hover:bg-primary/10">
                                    <img
                                        src={member.avatar}
                                        alt=""
                                        width="44"
                                        height="44"
                                        className="size-11 rounded-full object-cover"
                                    />
                                    <span className="min-w-0 flex-1 truncate text-sm font-bold">{member.name}</span>
                                    {member.host && (
                                        <span className="rounded-full bg-accent/10 px-3 py-1 text-xs font-bold text-accent">
                                            Host
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                        {room.isHost ? (
                            <div className="mt-6">
                                <Action url={room.urls.start} className={`${buttonClass} w-full sm:w-auto`}>
                                    Mulai kuis <Icon name="arrow" className="size-4" />
                                </Action>
                            </div>
                        ) : (
                            <p className="mt-5 text-sm text-foreground/50">
                                Host akan memulai kuis saat semua pemain siap.
                            </p>
                        )}
                    </Card>
                </>
            ) : room.status === 'inactive' ? (
                <Card>
                    <h1 className="font-display text-3xl font-bold">Room dinonaktifkan</h1>
                    <p className="mt-4 text-sm leading-7 text-foreground/60">
                        Host telah keluar. Jawaban yang sudah tersimpan tetap aman.
                    </p>
                    <Link className={`${buttonClass} mt-6`} href={nav.quizzes}>
                        Kembali ke kuis
                    </Link>
                </Card>
            ) : (
                <>
                    <h1 className="sr-only">{room.title}</h1>
                    {room.endsAt && room.status === 'active' && (
                        <div className="mb-4 rounded-2xl bg-accent/10 p-4">
                            <Countdown
                                deadline={room.endsAt}
                                serverNow={room.serverNow}
                                label="Sisa waktu total kuis"
                                onExpire={reload}
                            />
                        </div>
                    )}
                    <QuizTransition stateKey={`${room.question?.id}-${room.feedback}-${room.status}`}>
                    {room.question && (room.status === 'active' || room.feedback) ? (
                        <Question key={`${room.question.id}-${room.feedback}`} room={room} onExpire={reload} />
                    ) : (
                        <Card>
                            <div className="flex flex-col items-center py-4 text-center sm:py-6">
                                <span className={`grid size-16 place-items-center rounded-full ${room.status === 'finished' ? 'bg-success/15 text-success' : 'bg-primary/15 text-accent'}`}>
                                    <Icon name={room.status === 'finished' ? 'check' : 'user'} className="size-8" />
                                </span>
                                <p className="mt-4 text-sm font-bold text-accent">
                                    {room.status === 'finished' ? 'Kuis selesai' : 'Jawaban terkirim'}
                                </p>
                                <h2 className="mt-4 font-display text-3xl font-bold">
                                    {room.status === 'finished' ? 'Kuis selesai!' : 'Kamu sudah menjawab semua soal!'}
                                </h2>
                                <p className="mt-4 text-sm leading-7 text-foreground/60">
                                    {room.status === 'finished'
                                        ? 'Terima kasih sudah bermain. Siap mencoba kuis lainnya?'
                                        : 'Pemain lain masih menjawab. Tunggu sampai kuis selesai.'}
                                </p>
                                <p className="mt-4 rounded-full bg-accent/10 px-5 py-2 font-sans font-bold text-accent">{room.score} poin terkumpul</p>
                                <Link href={nav.quizzes} className={`${buttonClass} mt-6`}>
                                    Lihat kuis lain
                                </Link>
                            </div>
                            {room.status === 'finished' && room.results?.length > 0 && (
                                <div className="mt-8"><QuizResults results={room.results} /></div>
                            )}
                        </Card>
                    )}
                    </QuizTransition>
                </>
            )}
            <Dialog
                open={Boolean(unavailable)}
                onClose={() => router.visit(nav.quizzes)}
                title={unavailable === 'inactive' ? 'Room dinonaktifkan' : 'Room dihapus oleh host'}
                id="room-unavailable-title"
                variant="error"
            >
                <p className="text-sm leading-7 text-foreground/60">
                    Sesi ini sudah tidak tersedia. Kamu dapat memilih kuis lainnya.
                </p>
                <Link href={nav.quizzes} className={`${buttonClass} mt-6`}>
                    Kembali ke kuis
                </Link>
            </Dialog>
        </div>
    );
}
