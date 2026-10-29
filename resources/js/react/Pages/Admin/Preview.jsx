import LessonMaterial, { lessonSections } from '../../Components/LessonMaterial';
import { Link, usePage } from '../../routing';
import { PageMeta, buttonClass } from '../../Components/ui';
import { Card, Back, Heading, Action, Status, secondaryClass } from '../../Components/forms';
import AudioPlayer from '../../Components/AudioPlayer';
export default function Preview({ lesson, audio, meta }) {
    const { links } = usePage().props;
    return (
        <div className="mx-auto max-w-4xl">
            <PageMeta {...meta} />
            <Back href={links['admin.lessons.index']}>Kembali ke modul</Back>
            <Heading title="Preview & tinjau modul">
                Periksa materi, pilihan jawaban, dan audio sebelum menerbitkan.
            </Heading>
            <Card>
                <Status
                    value={
                        lesson.is_published && lesson.reviewed_at
                            ? 'published'
                            : lesson.reviewed_at
                              ? 'archived'
                              : 'draft'
                    }
                />
                <h2 className="mt-5 break-words font-display text-3xl font-bold">{lesson.title}</h2>
                <p className="mt-3 text-sm leading-7 text-foreground/55">{lesson.summary}</p>
                <div className="mt-6 space-y-8">
                    {lessonSections(lesson.content).map((section, index) => (
                        <section key={index}>
                            <h2 className="mb-5 font-display text-xl font-bold">{section.title}</h2>
                            <LessonMaterial content={section.body} />
                        </section>
                    ))}
                </div>
                <AudioPlayer audio={audio} label="Preview audio materi" />
                {(lesson.exercises ?? []).map((exercise, index) => (
                    <section key={index} className="my-5 rounded-2xl bg-background p-5">
                        <h2 className="font-bold">
                            Soal {index + 1} · bagian {exercise.section + 1} · {exercise.question_type}
                        </h2>
                        <p className="mt-3 leading-8">{exercise.exercise_prompt}</p>
                        <p className="mt-3 text-success">
                            Jawaban:{' '}
                            {exercise.question_type === 'essay'
                                ? exercise.accepted_answers?.join(' / ')
                                : exercise.question_type === 'multiple'
                                  ? exercise.correct_options
                                        ?.map((value) => exercise.exercise_options[value])
                                        .join(', ')
                                  : exercise.exercise_options[exercise.correct_option]}
                        </p>
                        <p className="mt-3 text-sm leading-7">{exercise.exercise_explanation}</p>
                    </section>
                ))}
                {lesson.exercise_prompt && (
                    <section className="mt-7 rounded-2xl bg-background p-5">
                        <h3 className="font-display text-xl font-bold">{lesson.exercise_prompt}</h3>
                        {lesson.question_type === 'essay' && (
                            <p className="mt-4 whitespace-pre-wrap break-words text-sm text-success">
                                Jawaban acuan: {(lesson.accepted_answers ?? []).join(' / ')}
                            </p>
                        )}
                        <ol className="mt-4 space-y-2">
                            {lesson.exercise_options.map((option, index) => (
                                <li
                                    key={index}
                                    className={`rounded-xl p-3 text-sm ${index === lesson.correct_option ? 'bg-success/10 text-success' : 'text-foreground/60'}`}
                                >
                                    {String.fromCharCode(65 + index)}. {option}
                                    {(
                                        lesson.question_type === 'multiple'
                                            ? (lesson.correct_options ?? []).includes(index)
                                            : index === lesson.correct_option
                                    )
                                        ? ' ✓ Jawaban benar'
                                        : ''}
                                </li>
                            ))}
                        </ol>
                        <p className="mt-4 text-sm leading-7 text-foreground/55">{lesson.exercise_explanation}</p>
                    </section>
                )}
                <div className="mt-6 flex flex-wrap gap-3">
                    <Link className={secondaryClass} href={lesson.editUrl}>
                        Ubah modul
                    </Link>
                    <Link className={secondaryClass} href={lesson.audioUrl}>
                        Siapkan audio
                    </Link>
                    <Action
                        url={lesson.publishUrl}
                        className={buttonClass}
                        confirm="Modul sudah ditinjau dan siap diterbitkan?"
                    >
                        Terbitkan modul
                    </Action>
                    {lesson.is_published && (
                        <Action url={lesson.archiveUrl} confirm="Arsipkan modul ini?">
                            Arsipkan
                        </Action>
                    )}
                </div>
            </Card>
        </div>
    );
}
