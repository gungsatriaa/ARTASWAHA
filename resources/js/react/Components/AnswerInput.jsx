import { useId, useRef } from 'react';
import Keyboard from './LazyKeyboard';

export function hasAnswer(question, answer) {
    if (question.question_type === 'essay') return Boolean(answer.answer_text?.trim());
    if (question.question_type === 'multiple') return Boolean(answer.answer_indexes?.length);
    return answer.answer_index !== '' && answer.answer_index !== undefined && answer.answer_index !== null;
}

export default function AnswerInput({ question, answer, onChange, required = true }) {
    const id = useId();
    const target = useRef(null);
    if (question.question_type === 'essay') {
        return (
            <div className="mt-4">
                <label htmlFor={id} className="text-sm font-semibold">
                    Jawaban esai
                </label>
                <textarea
                    id={id}
                    ref={target}
                    required={required}
                    maxLength={2000}
                    rows={4}
                    value={answer.answer_text ?? ''}
                    onChange={(event) => onChange('answer_text', event.target.value)}
                    placeholder="Tulis jawabanmu di sini…"
                    className="mt-2 w-full rounded-2xl bg-background p-4 text-base leading-8 outline-none ring-1 ring-white/10 focus:ring-2 focus:ring-accent"
                />
                <Keyboard targetRef={target} autoOpen={question.keyboard_auto_open} />
            </div>
        );
    }
    const multiple = question.question_type === 'multiple';
    return (
        <fieldset className="mt-4 grid gap-3 sm:grid-cols-2">
            <legend className="mb-3 text-sm font-semibold text-foreground/65">
                {multiple ? 'Pilih semua jawaban yang benar' : 'Pilih satu jawaban'}
            </legend>
            {(question.options ?? []).map((option, index) => {
                const checked = multiple
                    ? (answer.answer_indexes ?? []).includes(index)
                    : answer.answer_index === index;
                return (
                    <label
                        key={index}
                        className={`flex min-h-16 cursor-pointer items-center gap-3 rounded-2xl bg-background p-4 ring-1 ${checked ? 'ring-accent bg-accent/10' : 'ring-white/10'}`}
                    >
                        <input
                            type={multiple ? 'checkbox' : 'radio'}
                            name={id}
                            required={required && !multiple}
                            checked={checked}
                            onChange={() =>
                                onChange(
                                    multiple ? 'answer_indexes' : 'answer_index',
                                    multiple
                                        ? checked
                                            ? answer.answer_indexes.filter((value) => value !== index)
                                            : [...(answer.answer_indexes ?? []), index]
                                        : index,
                                )
                            }
                            className="size-5 shrink-0 accent-[var(--color-accent)]"
                        />
                        <span className="min-w-0 break-words text-sm font-semibold">{option}</span>
                    </label>
                );
            })}
        </fieldset>
    );
}
