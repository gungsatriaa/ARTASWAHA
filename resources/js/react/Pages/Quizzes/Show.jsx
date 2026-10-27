import { Link, usePage } from '@inertiajs/react';
import { PageMeta, buttonClass } from '../../Components/ui';
import { Card, Back, Action, secondaryClass } from '../../Components/forms';
import AudioPlayer from '../../Components/AudioPlayer';
export default function Quiz({ quiz, meta }) {
    const { navigation: nav } = usePage().props;
    return (
        <>
            <PageMeta {...meta} />
            <Back href={nav.quizzes}>Semua kuis</Back>
            <div className="grid items-start gap-7 lg:grid-cols-[20rem_minmax(0,1fr)]">
                <aside className="rounded-card bg-surface p-6 lg:sticky lg:top-28">
                    <p className="text-xs font-bold text-accent">Kuis buatanmu</p>
                    <h1 className="mt-5 break-words font-display text-3xl font-bold">{quiz.title}</h1>
                    <p className="mt-4 text-sm leading-7 text-foreground/55">
                        {quiz.description ||
                            'Ajak teman menguji pengetahuan bahasa Bali lewat pertanyaan yang kamu buat.'}
                    </p>
                    <div className="mt-5 flex flex-wrap gap-2 text-xs text-foreground/60">
                        <span className="rounded-full bg-background px-3 py-2">{quiz.questions.length} pertanyaan</span>
                        <span className="rounded-full bg-background px-3 py-2">
                            {quiz.show_answers_immediately ? 'Feedback langsung' : 'Hasil di akhir'}
                        </span>
                        <span className="rounded-full bg-background px-3 py-2">
                            {quiz.seconds_per_question
                                ? `${quiz.seconds_per_question} detik/soal`
                                : 'Tanpa timer bawaan'}
                        </span>
                        {quiz.time_limit_seconds && (
                            <span className="rounded-full bg-background px-3 py-2">
                                Total {quiz.time_limit_seconds / 60} menit
                            </span>
                        )}
                    </div>
                    <div className="mt-7 grid gap-3">
                        {!quiz.is_archived && (
                            <>
                                <Action url={quiz.roomUrl} className={`${buttonClass} w-full`}>
                                    Buat room kuis
                                </Action>
                                <Link href={quiz.editUrl} className={secondaryClass}>
                                    Edit kuis
                                </Link>
                                <Link href={`${quiz.editUrl}?preview=1`} className={secondaryClass}>
                                    Preview peserta
                                </Link>
                            </>
                        )}
                        <Action url={quiz.duplicateUrl}>Duplikasikan kuis</Action>
                        {!quiz.is_archived && (
                            <Action url={quiz.archiveUrl} confirm="Arsipkan kuis ini?">
                                Arsipkan
                            </Action>
                        )}
                    </div>
                </aside>
                <section>
                    <h2 className="font-display text-3xl font-bold">Daftar pertanyaan</h2>
                    <p className="mt-2 text-sm leading-7 text-foreground/55">
                        Kelola pilihan, jawaban benar, dan pengaturan tiap soal.
                    </p>
                    <ol className="mt-6 space-y-4">
                        {quiz.questions.map((question, index) => (
                            <li key={question.id}>
                                <Card>
                                    <div className="flex items-start gap-4">
                                        <span className="grid size-11 shrink-0 place-items-center rounded-2xl bg-accent/10 font-display text-lg font-bold text-accent">
                                            {index + 1}
                                        </span>
                                        <h3 className="min-w-0 break-words font-display text-xl font-bold">
                                            {question.prompt}
                                        </h3>
                                    </div>
                                    <ol className="mt-5 grid gap-2 sm:grid-cols-2">
                                        {question.options.map((option, choice) => (
                                            <li
                                                key={choice}
                                                className={`rounded-2xl bg-background p-4 text-sm ${choice === question.correct_option ? 'ring-1 ring-success/40 text-success' : 'text-foreground/60'}`}
                                            >
                                                <span className="mr-2 font-bold">
                                                    {String.fromCharCode(65 + choice)}.
                                                </span>
                                                {option}
                                                {choice === question.correct_option && (
                                                    <span className="ml-2 text-xs font-bold">✓ Jawaban benar</span>
                                                )}
                                            </li>
                                        ))}
                                    </ol>
                                    {question.explanation && (
                                        <p className="mt-4 text-sm leading-7 text-foreground/55">
                                            {question.explanation}
                                        </p>
                                    )}
                                    <p className="mt-4 text-xs text-foreground/45">
                                        {question.time_limit_seconds
                                            ? `${question.time_limit_seconds} detik khusus soal ini`
                                            : 'Mengikuti waktu bawaan'}{' '}
                                        · Keyboard {question.keyboard_auto_open ? 'otomatis' : 'manual'}
                                    </p>
                                    <AudioPlayer audio={question.audio} label="Preview audio soal" />
                                    {question.audioEditUrl && (
                                        <Link href={question.audioEditUrl} className={`${secondaryClass} mt-3`}>
                                            Kelola audio soal
                                        </Link>
                                    )}
                                </Card>
                            </li>
                        ))}
                    </ol>
                </section>
            </div>
        </>
    );
}
