import { Check, Field } from './forms';

export default function QuestionFields({ question, onChange, errors = {} }) {
    const type = question.question_type ?? 'single';
    return (
        <>
            <Field
                as="select"
                label="Tipe soal"
                value={type}
                error={errors.question_type}
                onChange={(event) => onChange('question_type', event.target.value)}
            >
                <option value="single">Pilihan ganda · satu jawaban</option>
                <option value="multiple">Pilihan banyak · checkbox</option>
                <option value="essay">Esai · jawaban teks</option>
            </Field>
            {type === 'essay' && (
                <>
                    <Field
                        as="textarea"
                        label="Jawaban acuan"
                        rows={3}
                        required
                        maxLength={20009}
                        value={(question.accepted_answers ?? []).join('\n')}
                        onChange={(event) => onChange('accepted_answers', event.target.value.split('\n'))}
                        error={errors.accepted_answers}
                        hint="Satu alternatif per baris, maksimal 10. Penilaian mencocokkan teks; kapital dan spasi berlebih diabaikan. Jawaban panjang yang maknanya sama belum tentu cocok."
                    />
                    <Check
                        checked={question.keyboard_auto_open ?? false}
                        onChange={(event) => onChange('keyboard_auto_open', event.target.checked)}
                    >
                        Buka keyboard aksara otomatis untuk jawaban esai
                    </Check>
                </>
            )}
        </>
    );
}
