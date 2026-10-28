import { useId, useRef } from 'react';
import Keyboard from './LazyKeyboard';
import Icon from './Icon';
import { AksaraText } from './LessonMaterial';

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
                        className={`group relative flex min-h-20 cursor-pointer items-center gap-3 rounded-2xl p-4 ring-1 transition-[background-color,box-shadow,transform] duration-150 motion-safe:hover:-translate-y-0.5 focus-within:ring-2 focus-within:ring-accent motion-reduce:transition-none ${checked ? 'bg-accent/10 ring-accent' : 'bg-background ring-white/10 hover:bg-primary/10 hover:ring-primary/50'}`}
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
                            className="peer sr-only"
                        />
                        <span aria-hidden="true" className={`grid size-9 shrink-0 place-items-center rounded-xl text-sm font-bold ${checked ? 'bg-accent text-background' : 'bg-white/5 text-foreground/50'}`}>
                            {String.fromCharCode(65 + index)}
                        </span>
                        <span className="min-w-0 flex-1 break-words text-sm font-semibold leading-7"><AksaraText>{option}</AksaraText></span>
                        <span aria-hidden="true" className={`grid size-5 shrink-0 place-items-center ring-1 ${multiple ? 'rounded-md' : 'rounded-full'} ${checked ? 'bg-accent text-background ring-accent' : 'ring-white/25'}`}>
                            {checked && <Icon name="check" className="size-3.5" />}
                        </span>
                    </label>
                );
            })}
        </fieldset>
    );
}
