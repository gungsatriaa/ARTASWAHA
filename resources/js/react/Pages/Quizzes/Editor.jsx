import { useEffect, useId, useRef, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { PageMeta, Button } from '../../Components/ui';
import { Card, Heading, Back, Field, Check, Errors, secondaryClass } from '../../Components/forms';
import QuestionFields from '../../Components/QuestionFields';
import AnswerInput from '../../Components/AnswerInput';
import Keyboard from '../../Components/LazyKeyboard';
import AudioPlayer from '../../Components/AudioPlayer';
import usePolling from '../../Hooks/usePolling';
import Icon from '../../Components/Icon';

export const blankQuestion = (key) => ({
    _key: key,
    prompt: '',
    options: ['', '', '', ''],
    question_type: 'single',
    correct_options: [],
    accepted_answers: [],
    correct_option: '',
    explanation: '',
    keyboard_auto_open: false,
    time_limit_seconds: '',
});
export function withoutOption(question, index) {
    return {
        ...question,
        options: question.options.filter((_, position) => position !== index),
        correct_options: (question.correct_options ?? [])
            .filter((value) => value !== index)
            .map((value) => (value > index ? value - 1 : value)),
        correct_option:
            question.correct_option === index
                ? ''
                : question.correct_option > index
                  ? question.correct_option - 1
                  : question.correct_option,
    };
}
export function payloadQuestions(questions) {
    return questions.map((question) => ({
        ...Object.fromEntries(
            [
                'prompt',
                'options',
                'correct_option',
                'question_type',
                'correct_options',
                'accepted_answers',
                'explanation',
                'keyboard_auto_open',
                'time_limit_seconds',
            ].map((key) => [key, question[key] ?? null]),
        ),
        question_type: question.question_type ?? 'single',
        ...(question.id ? { id: question.id } : {}),
    }));
}
export default function Editor({ quiz, hasRooms, preview, meta }) {
    const { links, navigation: nav, csrfToken } = usePage().props;
    const [step, setStep] = useState(preview ? 2 : 0),
        [position, setPosition] = useState(0),
        [expanded, setExpanded] = useState(0),
        [chosen, setChosen] = useState(null),
        [file, setFile] = useState(null),
        [draftUrl, setDraftUrl] = useState(null),
        [importing, setImporting] = useState(false),
        [uploading, setUploading] = useState(false),
        [source, setSource] = useState(''),
        [uploadError, setUploadError] = useState('');
    const uploadId = useId();
    const nextKey = useRef(quiz?.questions.length ?? 1),
        target = useRef(null),
        upload = useRef(null);
    const form = useForm({
        title: quiz?.title ?? '',
        description: quiz?.description ?? '',
        seconds_per_question: quiz?.seconds_per_question ?? '',
        time_limit_minutes: quiz?.time_limit_seconds ? quiz.time_limit_seconds / 60 : '',
        show_answers_immediately: quiz?.show_answers_immediately ?? true,
        questions: quiz?.questions.map((q, index) => ({
            ...q,
            options: q.options?.length ? q.options : ['', '', '', ''],
            question_type: q.question_type ?? 'single',
            correct_options: q.correct_options ?? [],
            accepted_answers: q.accepted_answers ?? [],
            _key: index,
            time_limit_seconds: q.time_limit_seconds ?? '',
        })) ?? [blankQuestion(0)],
    });
    const {
        data: draft,
        error: pollError,
        exhausted,
    } = usePolling(draftUrl, { active: importing, interval: 3000, limit: 200 });
    useEffect(() => {
        if (exhausted && importing) {
            setImporting(false);
            setUploadError(
                'Pemeriksaan draf dijeda. Proses server mungkin masih berjalan. Soal yang sudah kamu tulis tetap tersimpan di editor.',
            );
        }
    }, [exhausted, importing]);
    useEffect(() => {
        if (!draft) return;
        if (draft.status === 'completed') {
            form.setData(
                'questions',
                (draft.questions ?? []).map((question) => ({
                    ...question,
                    _key: nextKey.current++,
                    keyboard_auto_open: false,
                    time_limit_seconds: '',
                })),
            );
            setExpanded(nextKey.current - (draft.questions ?? []).length);
            setPosition(0);
            setChosen(null);
            setSource(draft.source_text ?? '');
            setImporting(false);
            setStep(1);
        } else if (['failed', 'unavailable'].includes(draft.status)) {
            setImporting(false);
            setUploadError(draft.error || 'Draf belum berhasil dibuat. Coba unggah ulang dokumen.');
        }
    }, [draft]);
    useEffect(() => () => upload.current?.abort(), []);
    useEffect(() => {
        const leave = (event) => {
            if (form.isDirty && !form.processing) {
                event.preventDefault();
                event.returnValue = '';
            }
        };
        window.addEventListener('beforeunload', leave);
        const off = router.on('before', (event) => {
            if (
                event.detail.visit.method === 'get' &&
                form.isDirty &&
                !form.processing &&
                !window.confirm('Perubahan belum disimpan. Tinggalkan editor?')
            )
                event.preventDefault();
        });
        return () => {
            window.removeEventListener('beforeunload', leave);
            off();
        };
    }, [form.isDirty, form.processing]);
    const change = (index, key, value) =>
        form.setData(
            'questions',
            form.data.questions.map((question, position) =>
                position === index ? { ...question, [key]: value } : question,
            ),
        );
    const move = (index, offset) => {
        const questions = [...form.data.questions],
            next = index + offset;
        if (next < 0 || next >= questions.length) return;
        [questions[index], questions[next]] = [questions[next], questions[index]];
        form.setData('questions', questions);
    };
    const generate = async () => {
        if (!file || uploading || importing) return;
        if (
            form.data.questions.some((question) => question.prompt.trim()) &&
            !window.confirm('Hasil draf akan mengganti soal dalam editor. Lanjutkan?')
        )
            return;
        setUploadError('');
        setUploading(true);
        upload.current = new AbortController();
        const body = new FormData();
        body.append('file', file);
        try {
            const response = await fetch(links['quizzes.drafts.store'], {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                signal: upload.current.signal,
            });
            const json = await response.json();
            if (!response.ok)
                throw new Error(
                    Object.values(json.errors ?? {})
                        .flat()
                        .join(' ') ||
                        json.message ||
                        'Unggahan gagal.',
                );
            setDraftUrl(json.status_url);
            setImporting(true);
        } catch (error) {
            if (error.name !== 'AbortError') setUploadError(error.message);
        } finally {
            setUploading(false);
        }
    };
    const submit = (e) => {
        e.preventDefault();
        if (form.processing) return;
        form.transform((data) => ({ ...data, questions: payloadQuestions(data.questions) }));
        form[quiz ? 'put' : 'post'](quiz?.action ?? links['quizzes.store'], {
            onError: (errors) => {
                const invalid = Object.keys(errors).find((key) => key.startsWith('questions'));
                setStep(invalid ? 1 : 0);
                if (invalid) setExpanded(form.data.questions[Number(invalid.split('.')[1]) || 0]?._key);
            },
        });
    };
    const addQuestion = () => {
        const question = blankQuestion(nextKey.current++);
        form.setData('questions', [...form.data.questions, question]);
        setExpanded(question._key);
    };
    const duplicate = (index) => {
        const copy = { ...form.data.questions[index], id: undefined, audio: null, audioEditUrl: null, _key: nextKey.current++ };
        form.setData('questions', [...form.data.questions.slice(0, index + 1), copy, ...form.data.questions.slice(index + 1)]);
        setExpanded(copy._key);
    };
    const remove = (index) => {
        const questions = form.data.questions.filter((_, position) => position !== index);
        form.setData('questions', questions);
        setExpanded(questions[Math.min(index, questions.length - 1)]?._key);
    };
    const question = form.data.questions[Math.min(position, form.data.questions.length - 1)];
    const original = quiz?.questions.find((item) => item.id === question?.id);
    const previewAudio =
        original &&
        original.prompt === question.prompt &&
        JSON.stringify(original.options) === JSON.stringify(question.options)
            ? original.audio
            : null;
    return (
        <div className="quiz-editor mx-auto max-w-6xl">
            <PageMeta {...meta} />
            <Back href={quiz?.url ?? nav.quizzes}>Kembali ke kuis</Back>
            <Heading title={quiz ? 'Edit kuis' : 'Buat kuis'}>
                Atur permainan, tulis pertanyaan, lalu tinjau sebelum menyimpan.
            </Heading>
            {hasRooms && (
                <p className="mb-6 rounded-2xl bg-accent/10 p-4 text-sm leading-7 text-accent">
                    Kuis ini sudah memiliki room. Perubahan disimpan sebagai versi baru agar room dan jawaban lama tetap
                    aman.
                </p>
            )}
            <div className="grid items-start gap-5 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-8">
                <aside className="min-w-0 lg:sticky lg:top-28">
                    <nav aria-label="Tahap membuat kuis" className="grid grid-cols-3 gap-2 rounded-card bg-surface p-2 lg:grid-cols-1 lg:p-3">
                        {[
                            ['Pengaturan', 'Judul dan cara bermain'],
                            ['Kelola soal', 'Pertanyaan dan jawaban'],
                            ['Preview & simpan', 'Periksa sebelum dibagikan'],
                        ].map(([label, description], index) => (
                            <button type="button" key={label} aria-current={index === step ? 'step' : undefined} onClick={() => setStep(index)}
                                className={`flex min-h-20 flex-col items-center gap-2 rounded-2xl px-2 py-3 text-center transition-colors focus-visible:outline-2 focus-visible:outline-accent lg:min-h-0 lg:flex-row lg:gap-3 lg:px-3 lg:py-4 lg:text-left ${index === step ? 'bg-primary/20 text-accent' : 'text-foreground/55 hover:bg-white/5 hover:text-foreground'}`}>
                                <span className={`grid size-8 shrink-0 place-items-center rounded-full text-sm font-bold ${index === step ? 'bg-accent text-background' : 'bg-background'}`}>{index + 1}</span>
                                <span className="min-w-0 text-xs font-bold leading-5 sm:text-sm">{label}<span className="mt-1 hidden text-xs font-normal text-foreground/45 lg:block">{description}</span></span>
                            </button>
                        ))}
                    </nav>
                    <div className="mt-5 hidden rounded-card bg-surface p-5 lg:block">
                        <p className="text-xs text-foreground/50">Kuis yang sedang disusun</p>
                        <p className="mt-2 break-words font-display font-bold">{form.data.title.trim() || 'Kuis barumu'}</p>
                        <p className="mt-4 text-sm text-foreground/65">{form.data.questions.length} soal · maksimal 20</p>
                        <p className="mt-2 text-xs leading-6 text-foreground/45">Perubahan tersimpan setelah kamu memilih Simpan kuis.</p>
                    </div>
                </aside>
                <div className="min-w-0 space-y-5">
            {!quiz && step === 1 && (
                <details className="group rounded-card bg-surface p-5 sm:p-6" open={Boolean(importing || uploadError || source)}>
                    <summary className="flex min-h-11 cursor-pointer list-none items-center gap-3 focus-visible:outline-2 focus-visible:outline-accent">
                        <span className="grid size-11 shrink-0 place-items-center rounded-2xl bg-primary/15 text-accent"><Icon name="spark" /></span>
                        <span className="min-w-0 flex-1"><span className="block font-display text-lg font-bold">Bantu susun soal dengan AI</span><span className="mt-1 block text-xs text-foreground/55">Opsional · mulai dari dokumen atau gambar</span></span>
                        <Icon name="chevron" className="size-5 shrink-0 text-foreground/50 transition-transform group-open:rotate-180" />
                    </summary>
                    <div className="mt-5 space-y-4">
                    <p className="mt-2 text-sm leading-7 text-foreground/55">
                        Unggah JPG, PNG, WEBP, PDF, DOC, atau DOCX; maksimal 10 MB. Periksa soal dan teks hasil
                        ekstraksi sebelum menyimpan.
                    </p>
                    <label htmlFor={uploadId} className="flex min-h-24 cursor-pointer items-center gap-4 rounded-2xl bg-background p-4 transition-colors hover:bg-primary/15 focus-within:ring-2 focus-within:ring-accent">
                        <span className="grid size-10 shrink-0 place-items-center rounded-full bg-primary/15 text-accent"><Icon name="book" /></span>
                        <span className="min-w-0"><span className="block break-all text-sm font-semibold">{file?.name ?? 'Pilih dokumen sumber'}</span><span className="mt-1 block text-xs text-foreground/50">Klik untuk memilih file · maksimal 10 MB</span></span>
                        <input id={uploadId} aria-label="Dokumen sumber" className="sr-only" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" onChange={(e) => setFile(e.target.files[0] ?? null)} />
                    </label>
                    <Button
                        type="button"
                        onClick={generate}
                        disabled={!file || uploading || importing}
                        className="mt-4"
                    >
                        {uploading ? 'Mengunggah…' : importing ? 'Menyusun soal…' : 'Buat draf soal'}
                    </Button>
                    {importing && (
                        <p role="status" className="mt-3 text-sm text-foreground/55">
                            Draf diproses. Kamu dapat tetap menyiapkan pengaturan kuis.
                        </p>
                    )}
                    {(uploadError || pollError) && (
                        <p role="alert" className="mt-3 text-sm text-danger">
                            {uploadError || pollError}
                        </p>
                    )}
                    {source && (
                        <details className="mt-4">
                            <summary className="min-h-11 cursor-pointer font-bold">Tinjau teks OCR</summary>
                            <pre className="max-h-52 overflow-auto whitespace-pre-wrap break-words rounded-2xl bg-background p-4 text-sm leading-7">
                                {source}
                            </pre>
                        </details>
                    )}
                    </div>
                </details>
            )}
            <form
                onSubmit={submit}
                onFocusCapture={(e) => {
                    if (e.target.matches('input[type=text],input:not([type]),textarea')) target.current = e.target;
                }}
            >
                <Errors errors={form.errors} />
                {step === 0 && (
                    <Card className="space-y-5">
                        <div><h2 className="font-display text-xl font-bold">Pengaturan kuis</h2><p className="mt-2 text-sm leading-7 text-foreground/55">Beri judul yang mudah dikenali. Waktu dan cara menampilkan hasil bisa kamu sesuaikan.</p></div>
                        <Field
                            label="Judul kuis"
                            required
                            maxLength={120}
                            placeholder="Misalnya, Tebak-tebakan bahasa Bali"
                            value={form.data.title}
                            onChange={(e) => form.setData('title', e.target.value)}
                            error={form.errors.title}
                        />
                        <Field
                            label="Deskripsi (opsional)"
                            as="textarea"
                            rows={2}
                            placeholder="Ceritakan singkat apa yang akan dipelajari peserta."
                            maxLength={500}
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            error={form.errors.description}
                        />
                        <div className="rounded-2xl bg-background p-4 sm:p-5"><p className="mb-4 flex items-center gap-2 text-sm font-semibold text-accent"><Icon name="calendar" className="size-4" /> Atur waktu bermain</p><div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                label="Waktu bawaan per soal (detik)"
                                type="number"
                                min={5}
                                max={300}
                                step={1}
                                value={form.data.seconds_per_question}
                                onChange={(e) => form.setData('seconds_per_question', e.target.value)}
                                hint="Kosongkan untuk tanpa batas waktu per soal."
                                error={form.errors.seconds_per_question}
                            />
                            <Field
                                label="Batas waktu total (menit)"
                                type="number"
                                min={1}
                                max={180}
                                step={1}
                                value={form.data.time_limit_minutes}
                                onChange={(e) => form.setData('time_limit_minutes', e.target.value)}
                                hint="Berlaku sejak host memulai kuis; opsional."
                                error={form.errors.time_limit_minutes}
                            />
                        </div></div>
                        <Check
                            checked={form.data.show_answers_immediately}
                            onChange={(e) => form.setData('show_answers_immediately', e.target.checked)}
                        >
                            Tampilkan jawaban benar setelah menjawab. Jika dimatikan, hasil benar atau salah baru
                            terlihat setelah kuis selesai.
                        </Check>
                        <Keyboard targetRef={target} />
                    </Card>
                )}
                {step === 1 && (
                    <section className="space-y-4">
                        <div className="flex flex-wrap justify-between gap-3">
                            <div><h2 className="font-display text-2xl font-bold">Pertanyaan</h2><p className="mt-2 text-sm text-foreground/55">{form.data.questions.length} dari 20 soal · buka satu soal untuk mengedit.</p></div>
                            <button
                                type="button"
                                disabled={form.data.questions.length >= 20}
                                className={secondaryClass}
                                onClick={addQuestion}
                            >
                                ＋ Tambah pertanyaan
                            </button>
                        </div>
                        {form.data.questions.map((q, index) => (
                            <Card key={q._key} className="!p-4 sm:!p-6">
                                <details open={expanded === q._key}>
                                    <summary onClick={(event) => { event.preventDefault(); setExpanded(expanded === q._key ? null : q._key); }} className="flex min-h-12 cursor-pointer list-none items-center gap-3 focus-visible:outline-2 focus-visible:outline-accent">
                                        <span className="grid size-10 shrink-0 place-items-center rounded-2xl bg-primary/15 font-bold text-accent">{index + 1}</span>
                                        <span className="min-w-0 flex-1"><span className="block font-display font-bold">Pertanyaan {index + 1}</span><span className="mt-1 block truncate text-sm text-foreground/55">{q.prompt || 'Tulis pertanyaan dan tentukan jawaban benarnya'}</span></span>
                                        <Icon name="chevron" className={`size-5 shrink-0 text-foreground/50 transition-transform ${expanded === q._key ? 'rotate-180' : ''}`} />
                                    </summary>
                                    <div className="mt-4 space-y-4">
                                        <Field
                                            as="textarea"
                                            label="Teks pertanyaan"
                                            required
                                            rows={2}
                                            maxLength={500}
                                            value={q.prompt}
                                            onChange={(e) => change(index, 'prompt', e.target.value)}
                                            error={form.errors[`questions.${index}.prompt`]}
                                        />
                                        <QuestionFields
                                            question={q}
                                            onChange={(key, value) => change(index, key, value)}
                                            errors={Object.fromEntries(
                                                Object.entries(form.errors)
                                                    .filter(([key]) => key.startsWith(`questions.${index}.`))
                                                    .map(([key, value]) => [
                                                        key.slice(`questions.${index}.`.length),
                                                        value,
                                                    ]),
                                            )}
                                        />
                                        {q.question_type !== 'essay' && (
                                            <fieldset className="space-y-3">
                                                <legend className="mb-2 text-sm font-semibold">
                                                    {q.question_type === 'multiple'
                                                        ? 'Pilihan jawaban · tandai semua yang benar'
                                                        : 'Pilihan jawaban · tandai satu yang benar'}
                                                </legend>
                                                {q.options.map((option, choice) => (
                                                    <div
                                                        key={choice}
                                                        className="flex items-start gap-3 rounded-2xl bg-background p-3"
                                                    >
                                                        <label className="mt-4 flex min-h-10 items-center">
                                                            <span className="sr-only">
                                                                Jawaban benar pilihan {choice + 1} untuk soal{' '}
                                                                {index + 1}
                                                            </span>
                                                            <input
                                                                type={
                                                                    q.question_type === 'multiple'
                                                                        ? 'checkbox'
                                                                        : 'radio'
                                                                }
                                                                name={`correct-${q._key}`}
                                                                checked={
                                                                    q.question_type === 'multiple'
                                                                        ? (q.correct_options ?? []).includes(choice)
                                                                        : Number(q.correct_option) === choice &&
                                                                          q.correct_option !== '' &&
                                                                          q.correct_option !== null
                                                                }
                                                                onChange={() =>
                                                                    q.question_type === 'multiple'
                                                                        ? change(
                                                                              index,
                                                                              'correct_options',
                                                                              (q.correct_options ?? []).includes(choice)
                                                                                  ? q.correct_options.filter(
                                                                                        (value) => value !== choice,
                                                                                    )
                                                                                  : [
                                                                                        ...(q.correct_options ?? []),
                                                                                        choice,
                                                                                    ],
                                                                          )
                                                                        : change(index, 'correct_option', choice)
                                                                }
                                                                className="size-5 accent-[var(--color-accent)]"
                                                            />
                                                        </label>
                                                        <Field
                                                            className="flex-1"
                                                            label={`Pilihan ${String.fromCharCode(65 + choice)}`}
                                                            value={option}
                                                            required
                                                            maxLength={140}
                                                            onChange={(e) =>
                                                                change(
                                                                    index,
                                                                    'options',
                                                                    q.options.map((text, position) =>
                                                                        position === choice ? e.target.value : text,
                                                                    ),
                                                                )
                                                            }
                                                            error={form.errors[`questions.${index}.options.${choice}`]}
                                                        />
                                                        <button
                                                            type="button"
                                                            aria-label={`Hapus pilihan ${choice + 1}`}
                                                            disabled={q.options.length <= 2}
                                                            className={`${secondaryClass} mt-7 !px-3`}
                                                            onClick={() =>
                                                                form.setData(
                                                                    'questions',
                                                                    form.data.questions.map((item, position) =>
                                                                        position === index
                                                                            ? withoutOption(item, choice)
                                                                            : item,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            ×
                                                        </button>
                                                    </div>
                                                ))}
                                                {q.options.length < 5 && (
                                                    <button
                                                        type="button"
                                                        className={secondaryClass}
                                                        onClick={() => change(index, 'options', [...q.options, ''])}
                                                    >
                                                        Tambah pilihan
                                                    </button>
                                                )}
                                            </fieldset>
                                        )}
                                        <Field
                                            as="textarea"
                                            label="Penjelasan jawaban (opsional)"
                                            rows={2}
                                            maxLength={500}
                                            value={q.explanation ?? ''}
                                            onChange={(e) => change(index, 'explanation', e.target.value)}
                                        />
                                        <Field
                                            label="Waktu khusus soal (detik, opsional)"
                                            type="number"
                                            min={5}
                                            max={300}
                                            step={1}
                                            value={q.time_limit_seconds}
                                            onChange={(e) => change(index, 'time_limit_seconds', e.target.value)}
                                            error={form.errors[`questions.${index}.time_limit_seconds`]}
                                        />

                                        {q.audioEditUrl && (
                                            <Link href={q.audioEditUrl} className={secondaryClass}>
                                                Kelola audio soal
                                            </Link>
                                        )}
                                        <div className="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                className={secondaryClass}
                                                disabled={index === 0}
                                                onClick={() => move(index, -1)}
                                            >
                                                Naik
                                            </button>
                                            <button
                                                type="button"
                                                className={secondaryClass}
                                                disabled={index === form.data.questions.length - 1}
                                                onClick={() => move(index, 1)}
                                            >
                                                Turun
                                            </button>
                                            <button
                                                type="button"
                                                className={secondaryClass}
                                                disabled={form.data.questions.length >= 20}
                                                onClick={() => duplicate(index)}
                                            >
                                                Duplikasikan soal
                                            </button>
                                            <button
                                                type="button"
                                                className={`${secondaryClass} text-danger`}
                                                disabled={form.data.questions.length <= 1}
                                                onClick={() => remove(index)}
                                            >
                                                Hapus soal
                                            </button>
                                        </div>
                                    </div>
                                </details>
                            </Card>
                        ))}
                        <Keyboard targetRef={target} />
                    </section>
                )}
                {step === 2 && (
                    <Card>
                        <p className="mb-2 text-sm font-semibold text-accent">{form.data.title || 'Kuis tanpa judul'}</p>
                        <h2 className="font-display text-2xl font-bold">Preview sebagai peserta</h2>
                        <p className="mt-2 text-sm leading-7 text-foreground/55">
                            Preview tidak mengirim jawaban atau menjalankan timer.
                        </p>
                        <p className="mt-5 text-sm font-bold text-accent">
                            Soal {Math.min(position + 1, form.data.questions.length)} dari {form.data.questions.length}
                        </p>
                        <h3 className="mt-4 whitespace-pre-wrap break-words font-display text-2xl font-bold">
                            {question?.prompt || 'Teks pertanyaan belum diisi'}
                        </h3>
                        <p className="mt-3 text-xs text-foreground/55">
                            Waktu soal:{' '}
                            {question?.time_limit_seconds || form.data.seconds_per_question || 'tanpa batas'}
                            {question?.time_limit_seconds || form.data.seconds_per_question ? ' detik' : ''}
                            {form.data.time_limit_minutes ? ` · Batas total ${form.data.time_limit_minutes} menit` : ''}
                        </p>
                        <AudioPlayer audio={previewAudio} label="Preview audio soal" />
                        {original && !previewAudio && (
                            <p className="my-3 text-xs text-foreground/50">
                                Audio lama tidak diputar karena teks soal atau pilihan berubah.
                            </p>
                        )}
                        {question && (
                            <AnswerInput
                                key={question._key}
                                question={question}
                                required={false}
                                answer={chosen ?? {}}
                                onChange={(key, value) =>
                                    setChosen((current) => ({ ...(current ?? {}), [key]: value }))
                                }
                            />
                        )}
                        <div className="mt-6 flex flex-wrap justify-between gap-3">
                            <button
                                type="button"
                                className={secondaryClass}
                                disabled={position <= 0}
                                onClick={() => {
                                    setPosition(position - 1);
                                    setChosen(null);
                                }}
                            >
                                Soal sebelumnya
                            </button>
                            <button
                                type="button"
                                className={secondaryClass}
                                disabled={position >= form.data.questions.length - 1}
                                onClick={() => {
                                    setPosition(position + 1);
                                    setChosen(null);
                                }}
                            >
                                Soal berikutnya
                            </button>
                        </div>
                    </Card>
                )}
                <div className="sticky bottom-24 z-20 mt-6 flex items-center justify-between gap-3 rounded-3xl bg-surface p-4 shadow-xl shadow-black/30 md:bottom-4">
                    <span className="hidden text-xs text-foreground/55 sm:block">Langkah {step + 1} dari 3</span>
                    <div className="flex flex-1 items-center justify-end gap-3">
                    {step > 0 && (
                        <button type="button" className={secondaryClass} onClick={() => setStep(step - 1)}>
                            Kembali
                        </button>
                    )}
                    {step < 2 ? (
                        <Button type="button" onClick={() => setStep(step + 1)}>
                            Lanjutkan →
                        </Button>
                    ) : (
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : quiz ? 'Simpan perubahan' : 'Simpan kuis'}
                        </Button>
                    )}
                    </div>
                </div>
            </form>
                </div>
            </div>
        </div>
    );
}
