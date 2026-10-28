import { Field, secondaryClass } from './forms';
import QuestionFields from './QuestionFields';
import { Button } from './ui';

export default function LessonExerciseFields({ exercises, onChange, errors }) {
    const change = (index, key, value) =>
        onChange(
            exercises.map((exercise, position) => (position === index ? { ...exercise, [key]: value } : exercise)),
        );
    return (
        <section className="my-6 space-y-4">
            <h2 className="font-display text-xl font-bold">Rangkaian latihan modul ({exercises.length} soal)</h2>
            <p className="text-sm leading-7 text-foreground/60">
                Tulis judul bagian materi dengan awalan ##. Nomor bagian menentukan kapan soal muncul, mulai dari 1.
                Esai memakai jawaban acuan yang dicocokkan otomatis.
            </p>
            {exercises.map((exercise, index) => (
                <details key={index} className="rounded-2xl bg-background p-4" open={exercises.length === 1}>
                    <summary className="cursor-pointer font-semibold">
                        Soal {index + 1}: {exercise.exercise_prompt || 'Pertanyaan baru'}
                    </summary>
                    <div className="mt-5 space-y-4">
                        <Field
                            label="Bagian materi"
                            type="number"
                            min={1}
                            max={21}
                            value={exercise.section + 1}
                            onChange={(event) => change(index, 'section', Number(event.target.value) - 1)}
                        />
                        <Field
                            label="Pertanyaan"
                            required
                            maxLength={500}
                            as="textarea"
                            value={exercise.exercise_prompt}
                            onChange={(event) => change(index, 'exercise_prompt', event.target.value)}
                        />
                        <QuestionFields
                            question={exercise}
                            onChange={(key, value) => change(index, key, value)}
                            errors={Object.fromEntries(
                                Object.entries(errors)
                                    .filter(([key]) => key.startsWith(`exercises.${index}.`))
                                    .map(([key, value]) => [key.replace(`exercises.${index}.`, ''), value]),
                            )}
                        />
                        {exercise.question_type !== 'essay' &&
                            (exercise.exercise_options ?? ['', '', '', '']).map((option, optionIndex) => (
                                <div className="flex items-center gap-3" key={optionIndex}>
                                    <input
                                        type={exercise.question_type === 'multiple' ? 'checkbox' : 'radio'}
                                        aria-label={`Jawaban benar soal ${index + 1} pilihan ${optionIndex + 1}`}
                                        name={`correct-${index}`}
                                        className="size-5 accent-[var(--color-accent)]"
                                        checked={
                                            exercise.question_type === 'multiple'
                                                ? (exercise.correct_options ?? []).includes(optionIndex)
                                                : exercise.correct_option === optionIndex
                                        }
                                        onChange={() =>
                                            change(
                                                index,
                                                exercise.question_type === 'multiple'
                                                    ? 'correct_options'
                                                    : 'correct_option',
                                                exercise.question_type === 'multiple'
                                                    ? (exercise.correct_options ?? []).includes(optionIndex)
                                                        ? exercise.correct_options.filter(
                                                              (value) => value !== optionIndex,
                                                          )
                                                        : [...(exercise.correct_options ?? []), optionIndex]
                                                    : optionIndex,
                                            )
                                        }
                                    />
                                    <Field
                                        label={`Pilihan ${optionIndex + 1}`}
                                        className="flex-1"
                                        required
                                        maxLength={140}
                                        value={option}
                                        onChange={(event) =>
                                            change(
                                                index,
                                                'exercise_options',
                                                exercise.exercise_options.map((value, position) =>
                                                    position === optionIndex ? event.target.value : value,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                            ))}
                        <Field
                            label="Penjelasan jawaban"
                            as="textarea"
                            maxLength={1000}
                            value={exercise.exercise_explanation ?? ''}
                            onChange={(event) => change(index, 'exercise_explanation', event.target.value)}
                        />
                        <button
                            type="button"
                            className={`${secondaryClass} text-danger`}
                            onClick={() => onChange(exercises.filter((_, position) => position !== index))}
                        >
                            Hapus soal {index + 1}
                        </button>
                    </div>
                </details>
            ))}
            <Button
                type="button"
                disabled={exercises.length >= 50}
                onClick={() =>
                    onChange([
                        ...exercises,
                        {
                            section: exercises.at(-1)?.section ?? 0,
                            question_type: 'single',
                            exercise_prompt: '',
                            exercise_options: ['', '', '', ''],
                            correct_option: 0,
                            correct_options: [],
                            accepted_answers: [],
                            keyboard_auto_open: false,
                            exercise_explanation: '',
                        },
                    ])
                }
            >
                Tambah soal modul
            </Button>
        </section>
    );
}
