import { useForm } from '@inertiajs/react';
import { PageMeta, Button } from '../../Components/ui';
import { Back, Card, Errors, Heading, Action } from '../../Components/forms';
import AudioPlayer from '../../Components/AudioPlayer';
export default function Lesson({ lesson, progress, audio, meta }) {
    const form = useForm({ answer_index: '' });
    return (
        <div className="mx-auto max-w-4xl">
            <PageMeta {...meta} />
            <Back href={lesson.path.url}>Daftar pelajaran</Back>
            <Card>
                <p className="mb-3 text-sm font-bold text-accent">{lesson.path.title}</p>
                <Heading title={lesson.title}>{lesson.summary}</Heading>
            </Card>
            <AudioPlayer audio={audio} />
            <Card className="mt-5">
                <h2 className="mb-5 font-display text-xl font-bold">Materi pelajaran</h2>
                <div className="whitespace-pre-line break-words text-base leading-8 text-foreground/80 sm:text-lg">
                    {lesson.content}
                </div>
            </Card>
            {progress?.completed_at ? (
                <Card className="mt-5 !bg-success/10">
                    <h2 className="font-display text-xl font-bold text-success">
                        Pelajaran selesai · {progress.points} poin
                    </h2>
                    <p className="mt-3 whitespace-pre-line text-sm leading-7 text-foreground/65">
                        {lesson.explanation}
                    </p>
                </Card>
            ) : lesson.exercise_prompt ? (
                <Card className="mt-5">
                    <h2 className="font-display text-xl font-bold">{lesson.exercise_prompt}</h2>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (!form.processing) form.post(lesson.answerUrl, { preserveScroll: true });
                        }}
                    >
                        <Errors errors={form.errors} />
                        <fieldset className="mt-4 grid gap-3 sm:grid-cols-2">
                            <legend className="sr-only">Pilih satu jawaban</legend>
                            {lesson.exercise_options.map((option, index) => (
                                <label
                                    key={index}
                                    className={`flex min-h-16 cursor-pointer items-center gap-3 rounded-2xl bg-background p-4 ring-1 ${form.data.answer_index === index ? 'ring-accent bg-accent/10' : 'ring-white/10'}`}
                                >
                                    <input
                                        type="radio"
                                        required
                                        name="answer"
                                        checked={form.data.answer_index === index}
                                        onChange={() => form.setData('answer_index', index)}
                                        className="size-5 shrink-0 accent-[var(--color-accent)]"
                                    />
                                    <span className="break-words text-sm font-semibold">{option}</span>
                                </label>
                            ))}
                        </fieldset>
                        <div className="mt-5 flex justify-end">
                            <Button type="submit" disabled={form.processing}>
                                Periksa jawaban
                            </Button>
                        </div>
                    </form>
                </Card>
            ) : (
                <div className="mt-5 flex justify-end">
                    <Action url={lesson.completeUrl}>Tandai pelajaran selesai</Action>
                </div>
            )}
        </div>
    );
}
