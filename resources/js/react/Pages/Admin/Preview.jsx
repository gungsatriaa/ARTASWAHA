import { Link, usePage } from '@inertiajs/react';
import { PageMeta, buttonClass } from '../../Components/ui';
import { Card, Back, Heading, Action, Status, secondaryClass } from '../../Components/forms';
import AudioPlayer from '../../Components/AudioPlayer';
import Keyboard from '../../Components/LazyKeyboard';
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
                <div className="mt-6 whitespace-pre-line break-words text-base leading-8 text-foreground/80">
                    {lesson.content}
                </div>
                <AudioPlayer audio={audio} label="Preview audio materi" />
                {lesson.exercise_prompt && (
                    <section className="mt-7 rounded-2xl bg-background p-5">
                        <h3 className="font-display text-xl font-bold">{lesson.exercise_prompt}</h3>
                        <ol className="mt-4 space-y-2">
                            {lesson.exercise_options.map((option, index) => (
                                <li
                                    key={index}
                                    className={`rounded-xl p-3 text-sm ${index === lesson.correct_option ? 'bg-success/10 text-success' : 'text-foreground/60'}`}
                                >
                                    {String.fromCharCode(65 + index)}. {option}
                                    {index === lesson.correct_option ? ' ✓ Jawaban benar' : ''}
                                </li>
                            ))}
                        </ol>
                        <p className="mt-4 text-sm leading-7 text-foreground/55">{lesson.exercise_explanation}</p>
                    </section>
                )}
                <Keyboard autoOpen={lesson.keyboard_auto_open} />
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
