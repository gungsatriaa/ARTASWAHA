import { useEffect, useRef, useState } from 'react';
import { useForm, Link } from '@inertiajs/react';
import { PageMeta, Button, buttonClass } from '../../Components/ui';
import { Back, Card, Errors, Action, secondaryClass } from '../../Components/forms';
import AnswerInput, { hasAnswer } from '../../Components/AnswerInput';
import AudioPlayer from '../../Components/AudioPlayer';
import Icon from '../../Components/Icon';
import LessonMaterial, { lessonSections, AksaraText } from '../../Components/LessonMaterial';

function Exercise({ question, index, attemptId, url }) {
    const form = useForm({
        answer_index: '',
        answer_indexes: [],
        answer_text: '',
        question_index: index,
        attempt_id: attemptId ?? null,
    });
    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                if (!form.processing) {
                    form.post(url, { preserveScroll: true });
                }
            }}
        >
            <h2 className="font-display text-xl font-bold leading-relaxed sm:text-2xl">
                <AksaraText>{question.exercise_prompt}</AksaraText>
            </h2>
            <Errors errors={form.errors} />
            <AnswerInput
                question={{ ...question, options: question.exercise_options }}
                answer={form.data}
                onChange={(key, value) => form.setData(key, value)}
            />
            <div className="mt-6 flex justify-end">
                <Button
                    type="submit"
                    className="w-full sm:w-auto"
                    disabled={form.processing || !hasAnswer(question, form.data)}
                >
                    Kirim jawaban
                </Button>
            </div>
        </form>
    );
}

export default function Lesson({ lesson, progress, audio, quiz, nextLesson, meta }) {
    const sections = lessonSections(lesson.content);
    const source = sections.find((section) => section.title === 'Sumber belajar');
    const material = sections.filter((section) => section !== source);
    const currentSection = Math.max(0, Math.min(material.length - 1, quiz?.question?.section ?? 0));
    const [selected, setSelected] = useState(currentSection);
    const [practice, setPractice] = useState(Boolean(quiz) && (material.length === 0 || quiz.completed));
    const completed = quiz ? Boolean(quiz.completed) : Boolean(progress?.completed_at);
    const previous = useRef({ id: quiz?.id, index: quiz?.index, section: currentSection });
    useEffect(() => {
        const before = previous.current;
        if (before.index !== quiz?.index || before.id !== quiz?.id) {
            setSelected(currentSection);
            const restarted = quiz?.index === 0 && before.index > 0;
            setPractice(
                material.length === 0 || Boolean(quiz?.completed) || (!restarted && before.section === currentSection),
            );
        }
        previous.current = { id: quiz?.id, index: quiz?.index, section: currentSection };
    }, [quiz?.id, quiz?.index, quiz?.completed, currentSection, material.length]);
    const answered = completed ? (quiz?.total ?? 0) : (quiz?.index ?? 0);
    const percent = quiz?.total ? Math.round((answered / quiz.total) * 100) : completed ? 100 : 0;
    const choose = (index) => {
        setSelected(index);
        setPractice(false);
    };
    return (
        <div className="mx-auto max-w-6xl space-y-5 sm:space-y-7">
            <PageMeta {...meta} />
            <Back href={lesson.path.url}>Daftar pelajaran</Back>
            <header className="flex flex-wrap items-start justify-between gap-5">
                <div className="max-w-2xl">
                    <Link href={lesson.path.url} className="text-sm font-semibold text-accent">
                        {lesson.path.title}
                    </Link>
                    <h1 className="mt-3 font-display text-2xl font-bold leading-tight sm:text-4xl">{lesson.title}</h1>
                    <p className="mt-3 max-w-[60ch] text-sm leading-7 text-foreground/65 sm:text-base">
                        {lesson.summary}
                    </p>
                </div>
                {quiz && (
                    <div className="w-full max-w-xs rounded-2xl bg-surface p-4" aria-label="Progres latihan modul">
                        <div className="flex justify-between gap-4 text-sm">
                            <span className="text-foreground/65">
                                Soal tuntas {answered}/{quiz.total}
                            </span>
                            <strong className="text-accent">{percent}%</strong>
                        </div>
                        <progress
                            value={answered}
                            max={quiz.total}
                            className="mt-3 h-2 w-full accent-[var(--color-accent)]"
                            aria-label="Soal tuntas"
                        />
                    </div>
                )}
            </header>
            <div
                className={
                    material.length
                        ? 'grid items-start gap-5 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-8'
                        : 'mx-auto w-full max-w-3xl'
                }
            >
                {material.length > 0 && (
                    <aside className="min-w-0 lg:sticky lg:top-28">
                        <nav aria-label="Bagian pelajaran" className="flex gap-2 overflow-x-auto pb-2 lg:flex-col">
                            {material.map((section, index) => (
                                <button
                                    key={index}
                                    type="button"
                                    onClick={() => choose(index)}
                                    aria-current={!practice && selected === index ? 'step' : undefined}
                                    className={`flex min-h-12 shrink-0 items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-accent lg:shrink ${!practice && selected === index ? 'bg-primary/20 text-accent' : 'bg-surface text-foreground/65 hover:text-foreground'}`}
                                >
                                    <span className="grid size-7 shrink-0 place-items-center rounded-full bg-background text-xs">
                                        {index + 1}
                                    </span>
                                    <span className="max-w-[12rem] lg:max-w-none">{section.title}</span>
                                </button>
                            ))}
                            {quiz && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setSelected(currentSection);
                                        setPractice(true);
                                    }}
                                    className={`${secondaryClass} shrink-0 lg:mt-3`}
                                >
                                    <Icon name="quiz" className="size-4" />
                                    {completed
                                        ? 'Hasil latihan'
                                        : `Latihan ${quiz.index + 1 > quiz.total ? quiz.total : quiz.index + 1}/${quiz.total}`}
                                </button>
                            )}
                        </nav>
                        <p className="mt-3 hidden text-xs leading-6 text-foreground/50 lg:block">
                            Baca satu bagian, amati contohnya, lalu coba latihan. Kamu bisa membuka kembali materi kapan
                            saja.
                        </p>
                    </aside>
                )}
                <div className="min-w-0 space-y-5">
                    {practice && quiz ? (
                        <Card key={`quiz-${quiz.index}`} className="sm:!p-8">
                            {completed ? (
                                <div className="py-5 text-center">
                                    <span className="mx-auto grid size-14 place-items-center rounded-full bg-success/15 text-success">
                                        <Icon name="check" className="size-7" />
                                    </span>
                                    <h2 className="mt-5 font-display text-2xl font-bold">Modul selesai!</h2>
                                    <p className="mt-3 text-sm leading-7 text-foreground/65">
                                        {quiz.results?.length
                                            ? `${quiz.correct} benar dari ${quiz.total} soal · ${Math.round((quiz.correct / quiz.total) * 100)}%`
                                            : 'Modul ini sudah selesai. Rincian jawaban lama belum tercatat.'}
                                    </p>
                                    <p className="mt-2 text-xs text-foreground/50">
                                        Poin penyelesaian diberikan sekali. Pengulangan tersimpan sebagai percobaan
                                        baru.
                                    </p>
                                    <div className="mt-6 flex flex-wrap justify-center gap-3">
                                        <Action
                                            url={lesson.retryUrl}
                                            data={{ attempt_id: quiz.id ?? null }}
                                            className={buttonClass}
                                        >
                                            Ulangi kuis
                                        </Action>
                                        <Link className={secondaryClass} href={lesson.path.url}>
                                            Daftar modul
                                        </Link>
                                    </div>
                                </div>
                            ) : quiz.question ? (
                                <>
                                    <div className="mb-5 flex flex-wrap items-center justify-between gap-3 text-sm">
                                        <span className="font-semibold text-accent">
                                            Soal {quiz.index + 1} dari {quiz.total}
                                        </span>
                                        {material.length > 0 && (
                                            <button
                                                type="button"
                                                className="text-foreground/65 underline underline-offset-4"
                                                onClick={() => choose(currentSection)}
                                            >
                                                Lihat materi
                                            </button>
                                        )}
                                    </div>
                                    <Exercise
                                        key={quiz.index}
                                        question={quiz.question}
                                        index={quiz.index}
                                        url={lesson.answerUrl}
                                        attemptId={quiz.id}
                                    />
                                </>
                            ) : null}
                        </Card>
                    ) : (
                        <Card as="article" className="sm:!p-8">
                            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                                <h2 className="font-display text-xl font-bold sm:text-2xl">
                                    {material[selected]?.title}
                                </h2>
                                <span className="text-xs text-foreground/50">
                                    Bagian {selected + 1} dari {material.length}
                                </span>
                            </div>
                            <LessonMaterial content={material[selected]?.body ?? ''} />
                            <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-between">
                                {selected > 0 && (
                                    <button
                                        type="button"
                                        className={secondaryClass}
                                        onClick={() => choose(selected - 1)}
                                    >
                                        Bagian sebelumnya
                                    </button>
                                )}
                                {quiz && !completed && selected === currentSection ? (
                                    <Button type="button" onClick={() => setPractice(true)}>
                                        Coba latihan bagian ini
                                    </Button>
                                ) : selected < material.length - 1 ? (
                                    <Button type="button" onClick={() => choose(selected + 1)}>
                                        Baca bagian berikutnya
                                    </Button>
                                ) : quiz ? (
                                    <Button
                                        type="button"
                                        onClick={() => {
                                            setSelected(currentSection);
                                            setPractice(true);
                                        }}
                                    >
                                        {completed ? 'Lihat hasil latihan' : 'Lanjutkan latihan'}
                                    </Button>
                                ) : null}
                            </div>
                        </Card>
                    )}
                    {completed && quiz?.results?.length > 0 && (
                        <section aria-label="Hasil jawaban" className="space-y-3">
                            <h2 className="font-display text-xl font-bold">Pembahasan jawaban</h2>
                            {quiz.results.map((result, index) => (
                                <details key={index} className="rounded-3xl bg-surface p-5">
                                    <summary className="cursor-pointer font-semibold leading-7">
                                        <span className={result.is_correct ? 'text-success' : 'text-danger'}>
                                            {index + 1}. {result.is_correct ? 'Benar' : 'Belum tepat'}
                                        </span>
                                        <span className="mt-2 block">
                                            <AksaraText>{result.prompt}</AksaraText>
                                        </span>
                                    </summary>
                                    <div className="mt-4 space-y-3 text-sm leading-8">
                                        <p className={result.is_correct ? 'text-success' : 'text-danger'}>
                                            Jawabanmu: <AksaraText>{result.selected}</AksaraText>
                                        </p>
                                        {!result.is_correct && (
                                            <p className="text-success">
                                                Jawaban benar: <AksaraText>{result.correct}</AksaraText>
                                            </p>
                                        )}
                                        <p className="text-foreground/70">
                                            <AksaraText>{result.explanation}</AksaraText>
                                        </p>
                                    </div>
                                </details>
                            ))}
                        </section>
                    )}
                    {completed && quiz?.history?.length > 0 && (
                        <Card>
                            <h2 className="font-display text-xl font-bold">Riwayat percobaan</h2>
                            <ul className="mt-4 space-y-3 text-sm">
                                {quiz.history.map((item) => (
                                    <li key={item.id} className="flex flex-wrap justify-between gap-2">
                                        <span className="text-foreground/60">
                                            {new Date(item.completedAt).toLocaleString('id-ID')}
                                        </span>
                                        <strong className="text-accent">
                                            {item.correct}/{item.total} benar
                                        </strong>
                                    </li>
                                ))}
                            </ul>
                        </Card>
                    )}
                    {!practice && <AudioPlayer audio={audio} />}
                    {!quiz &&
                        (completed ? (
                            <Card className="!bg-success/10">
                                <h2 className="font-display text-xl font-bold text-success">
                                    Pelajaran selesai · {progress.points} poin
                                </h2>
                                <p className="mt-3 text-sm leading-7">
                                    <AksaraText>{lesson.explanation}</AksaraText>
                                </p>
                            </Card>
                        ) : lesson.exercise_prompt ? (
                            <Card>
                                <Exercise key={lesson.id} question={lesson} url={lesson.answerUrl} />
                            </Card>
                        ) : (
                            <div className="flex justify-end">
                                <Action url={lesson.completeUrl}>Tandai pelajaran selesai</Action>
                            </div>
                        ))}
                    {completed && nextLesson && (
                        <nav
                            aria-label="Modul lanjutan"
                            className="flex flex-col gap-4 rounded-card bg-surface p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6"
                        >
                            <div className="min-w-0">
                                <p className="text-xs font-semibold text-accent">Lanjutkan belajarmu</p>
                                <p className="mt-2 break-words font-display text-lg font-bold">{nextLesson.title}</p>
                            </div>
                            <Link href={nextLesson.url} className={`${buttonClass} shrink-0 gap-2`}>
                                Modul berikutnya <Icon name="arrow" className="size-4" />
                            </Link>
                        </nav>
                    )}
                    {source && (
                        <details className="rounded-3xl bg-surface p-5 text-sm sm:p-6">
                            <summary className="cursor-pointer font-semibold text-foreground/65">
                                Sumber materi dan latihan
                            </summary>
                            <div className="mt-5">
                                <LessonMaterial content={source.body} />
                            </div>
                        </details>
                    )}
                </div>
            </div>
        </div>
    );
}
