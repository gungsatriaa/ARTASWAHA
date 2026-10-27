import { useRef } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Button, PageMeta, buttonClass } from '../../Components/ui';
import { Card, Back, Heading, Field, Check, Errors, secondaryClass } from '../../Components/forms';
import Keyboard from '../../Components/LazyKeyboard';
export default function LessonForm({ lesson, paths, meta }) {
    const { links } = usePage().props;
    const target = useRef(null);
    const form = useForm({
        learning_path_id: lesson.learning_path_id ?? paths[0]?.id ?? '',
        title: lesson.title ?? '',
        slug: lesson.slug ?? '',
        summary: lesson.summary ?? '',
        content: lesson.content ?? '',
        sort_order: lesson.sort_order ?? 0,
        keyboard_auto_open: lesson.keyboard_auto_open ?? false,
        has_exercise: Boolean(lesson.exercise_prompt),
        exercise_prompt: lesson.exercise_prompt ?? '',
        exercise_options: lesson.exercise_options ?? ['', '', '', ''],
        correct_option: lesson.correct_option ?? '',
        exercise_explanation: lesson.exercise_explanation ?? '',
    });
    return (
        <div className="mx-auto max-w-5xl">
            <PageMeta {...meta} />
            <Back href={links['admin.lessons.index']}>Kembali ke modul</Back>
            <Heading title={lesson.id ? 'Ubah modul' : 'Modul baru'}>
                Simpan sebagai draf, periksa preview, lalu terbitkan untuk pelajar.
            </Heading>
            {!paths.length ? (
                <Card>
                    <h2 className="font-display text-xl font-bold">Buat jalur belajar terlebih dahulu.</h2>
                    <Link className={`${buttonClass} mt-5`} href={links['admin.learning-paths.create']}>
                        Buat jalur belajar
                    </Link>
                </Card>
            ) : (
                <Card>
                    {lesson.is_published && (
                        <p className="mb-5 rounded-2xl bg-accent/10 p-4 text-sm leading-7 text-accent">
                            Menyimpan perubahan menarik modul dari situs sampai ditinjau dan diterbitkan kembali.
                        </p>
                    )}
                    <form
                        onFocusCapture={(e) => {
                            if (e.target.matches('input:not([type=number]),textarea')) target.current = e.target;
                        }}
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (!form.processing) form[lesson.id ? 'put' : 'post'](lesson.action);
                        }}
                        className="space-y-5"
                    >
                        <Errors errors={form.errors} />
                        <Field
                            label="Jalur belajar"
                            as="select"
                            required
                            value={form.data.learning_path_id}
                            onChange={(e) => form.setData('learning_path_id', e.target.value)}
                            error={form.errors.learning_path_id}
                        >
                            {paths.map((path) => (
                                <option key={path.id} value={path.id}>
                                    {path.title}
                                </option>
                            ))}
                        </Field>
                        {[
                            ['title', 'Judul', 120],
                            ['slug', 'Slug URL (opsional)', 150],
                            ['summary', 'Ringkasan', 255],
                        ].map(([key, label, max]) => (
                            <Field
                                key={key}
                                label={label}
                                value={form.data[key]}
                                onChange={(e) => form.setData(key, e.target.value)}
                                required={key !== 'slug'}
                                maxLength={max}
                                error={form.errors[key]}
                            />
                        ))}
                        <Field
                            label="Isi materi"
                            as="textarea"
                            rows={12}
                            required
                            maxLength={50000}
                            value={form.data.content}
                            onChange={(e) => form.setData('content', e.target.value)}
                            error={form.errors.content}
                            hint="Gunakan teks biasa. Materi harus ditinjau sebelum dapat dilihat pengunjung."
                        />
                        <Field
                            label="Urutan dalam jalur"
                            type="number"
                            min={0}
                            max={65535}
                            required
                            value={form.data.sort_order}
                            onChange={(e) => form.setData('sort_order', e.target.value)}
                            error={form.errors.sort_order}
                        />

                        <Check
                            checked={form.data.has_exercise}
                            onChange={(e) => form.setData('has_exercise', e.target.checked)}
                        >
                            Tambahkan latihan pilihan ganda
                        </Check>
                        {form.data.has_exercise && (
                            <fieldset className="space-y-4">
                                <legend className="mb-3 font-display text-xl font-bold">Latihan setelah materi</legend>
                                <Field
                                    label="Pertanyaan latihan"
                                    as="textarea"
                                    rows={2}
                                    required
                                    maxLength={500}
                                    value={form.data.exercise_prompt}
                                    onChange={(e) => form.setData('exercise_prompt', e.target.value)}
                                    error={form.errors.exercise_prompt}
                                />
                                {form.data.exercise_options.map((option, index) => (
                                    <div key={index} className="flex items-end gap-3">
                                        <label className="mb-4">
                                            <span className="sr-only">Jawaban benar pilihan {index + 1}</span>
                                            <input
                                                type="radio"
                                                name="correct_option"
                                                required
                                                checked={form.data.correct_option === index}
                                                onChange={() => form.setData('correct_option', index)}
                                                className="size-5 accent-[var(--color-accent)]"
                                            />
                                        </label>
                                        <Field
                                            className="flex-1"
                                            label={`Pilihan ${index + 1}`}
                                            required
                                            maxLength={140}
                                            value={option}
                                            onChange={(e) =>
                                                form.setData(
                                                    'exercise_options',
                                                    form.data.exercise_options.map((value, position) =>
                                                        position === index ? e.target.value : value,
                                                    ),
                                                )
                                            }
                                            error={form.errors[`exercise_options.${index}`]}
                                        />
                                    </div>
                                ))}
                                <Field
                                    label="Penjelasan jawaban (opsional)"
                                    as="textarea"
                                    maxLength={500}
                                    value={form.data.exercise_explanation}
                                    onChange={(e) => form.setData('exercise_explanation', e.target.value)}
                                    error={form.errors.exercise_explanation}
                                />
                            </fieldset>
                        )}
                        <Keyboard targetRef={target} />
                        <Button type="submit" disabled={form.processing}>
                            Simpan sebagai draf
                        </Button>
                    </form>
                    {lesson.id && (
                        <div className="mt-5 flex flex-wrap gap-3">
                            <Link className={secondaryClass} href={lesson.previewUrl}>
                                Preview modul tersimpan
                            </Link>
                            <Link className={secondaryClass} href={lesson.audioUrl}>
                                Siapkan audio
                            </Link>
                        </div>
                    )}
                </Card>
            )}
        </div>
    );
}
