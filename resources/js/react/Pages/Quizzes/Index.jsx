import { useEffect, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Button, PageMeta, Pagination, buttonClass, EmptyState } from '../../Components/ui';
import { Heading, Card, Field, Action, inputClass, secondaryClass } from '../../Components/forms';
import Dialog from '../../Components/Dialog';
import Icon from '../../Components/Icon';
export default function Quizzes({ quizzes, filters, code, meta }) {
    const { viewer, navigation: nav, links } = usePage().props;
    const [login, setLogin] = useState(false),
        [blocked, setBlocked] = useState(null);
    const form = useForm({ code: code ?? '' }),
        search = useForm({ q: filters.q ?? '', status: filters.status ?? 'active' });
    useEffect(() => {
        const key = ['room_started', 'room_finished', 'room_inactive'].find((key) => form.errors[key]);
        if (key) setBlocked(form.errors[key]);
    }, [form.errors]);
    return (
        <>
            <PageMeta {...meta} />
            <header className="mb-7 flex flex-col items-start justify-between gap-5 sm:mb-9 sm:flex-row sm:items-end">
                <div className="max-w-2xl">
                    <p className="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-accent">
                        <Icon name="quiz" className="size-5" /> Arena kuis Bali
                    </p>
                    <h1 className="font-display text-3xl font-bold leading-tight tracking-tight sm:text-4xl">
                        Belajar lebih seru kalau dimainkan.
                    </h1>
                    <p className="mt-3 text-sm leading-7 text-foreground/60">
                        Ajak teman, uji pengetahuanmu, dan temukan hal baru tentang Bali.
                    </p>
                </div>
                {viewer?.mode === 'account' ? (
                    <Link href={links['quizzes.create']} className={`${secondaryClass} w-full shrink-0 sm:w-auto`}>
                        <Icon name="edit" className="size-4" /> Buat kuis
                    </Link>
                ) : (
                    <button
                        type="button"
                        onClick={() => setLogin(true)}
                        className={`${secondaryClass} w-full shrink-0 sm:w-auto`}
                    >
                        <Icon name="edit" className="size-4" /> Buat kuis
                    </button>
                )}
            </header>
            <div className="mb-9 grid items-stretch gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,0.85fr)] lg:gap-6">
                <Card className="sm:!p-8">
                    <span className="mb-5 grid size-12 place-items-center rounded-2xl bg-primary/20 text-accent">
                        <Icon name="quiz" className="size-6" />
                    </span>
                    <h2 className="font-display text-2xl font-bold sm:text-3xl">Punya kode room?</h2>
                    <p className="mt-3 max-w-md text-sm leading-7 text-foreground/55">
                        Masukkan kode dari host untuk bergabung sebelum permainan dimulai.
                    </p>
                    <form
                        aria-label="Gabung room kuis"
                        aria-busy={form.processing}
                        onSubmit={(e) => {
                            e.preventDefault();
                            if (!form.processing) form.post(links['rooms.join']);
                        }}
                        className="mt-6"
                    >
                        <label htmlFor="room-code" className="block text-sm font-semibold">
                            Kode room
                        </label>
                        <input
                            id="room-code"
                            className={`${inputClass} !min-h-16 !rounded-3xl text-center !text-xl !font-bold tracking-[0.16em] placeholder:!text-sm placeholder:font-normal placeholder:tracking-normal placeholder:text-foreground/40 sm:!text-2xl`}
                            name="code"
                            placeholder="Contoh: 123456"
                            inputMode="numeric"
                            value={form.data.code}
                            onChange={(e) => form.setData('code', e.target.value.toUpperCase())}
                            required
                            minLength={4}
                            maxLength={10}
                            pattern="([0-9]{6}|[A-Za-z0-9]{4}|[A-Za-z0-9]{10})"
                            autoComplete="off"
                            autoCapitalize="characters"
                            spellCheck={false}
                            aria-invalid={Boolean(form.errors.code)}
                            aria-describedby={form.errors.code ? 'room-code-error room-code-hint' : 'room-code-hint'}
                        />
                        <p id="room-code-hint" className="mt-3 text-xs leading-6 text-foreground/45">
                            Masukkan 6 digit angka dari host. Kode undangan lama tetap bisa digunakan.
                        </p>
                        {form.errors.code && (
                            <p id="room-code-error" role="alert" className="mt-2 text-sm text-danger">
                                {form.errors.code}
                            </p>
                        )}
                        <Button type="submit" disabled={form.processing} className="mt-5 min-h-12 w-full">
                            {form.processing ? 'Menghubungkan…' : 'Gabung room'}
                            <Icon name="arrow" className="size-4" />
                        </Button>
                    </form>
                </Card>
                <aside
                    aria-labelledby="quiz-guide-title"
                    className="flex flex-col rounded-card bg-primary/10 p-5 sm:p-8"
                >
                    <h2 id="quiz-guide-title" className="font-display text-xl font-bold">
                        Dari kode, jadi keseruan.
                    </h2>
                    <p className="mt-3 text-sm leading-7 text-foreground/55">
                        Tiga langkah untuk mulai bermain bersama.
                    </p>
                    <ol className="my-6 space-y-5">
                        {[
                            ['Masuk ke room', 'Minta kode undangan dari teman atau gurumu.'],
                            ['Tunggu host memulai', 'Pastikan namamu sudah masuk dalam daftar pemain.'],
                            ['Pilih jawabanmu', 'Jawab pertanyaan dan lihat hasilnya setelah bermain.'],
                        ].map(([title, description], index) => (
                            <li key={title} className="flex gap-3">
                                <span
                                    aria-hidden="true"
                                    className="grid size-8 shrink-0 place-items-center rounded-full bg-primary/20 text-xs font-bold text-accent"
                                >
                                    {index + 1}
                                </span>
                                <div className="min-w-0">
                                    <h3 className="text-sm font-bold">{title}</h3>
                                    <p className="mt-1 text-xs leading-6 text-foreground/55">{description}</p>
                                </div>
                            </li>
                        ))}
                    </ol>
                    <p className="mt-auto flex items-start gap-2 text-xs leading-6 text-foreground/55">
                        <Icon name="info" className="mt-1 size-4 shrink-0 text-accent" />
                        Profil anonim juga bisa bergabung. Masuk dengan akun untuk membuat kuis sendiri.
                    </p>
                </aside>
            </div>
            {viewer?.mode === 'account' && (
                <section>
                    <Heading title="Kuis buatanmu">Koleksi pribadi untuk dimainkan sendiri atau bersama teman.</Heading>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            router.get(nav.quizzes, search.data, { preserveState: true, replace: true });
                        }}
                        className="mb-6 grid items-end gap-3 sm:grid-cols-[minmax(0,1fr)_12rem_auto]"
                    >
                        <Field
                            label="Cari kuis"
                            type="search"
                            value={search.data.q}
                            onChange={(e) => search.setData('q', e.target.value)}
                            maxLength={120}
                        />
                        <Field
                            as="select"
                            label="Status"
                            value={search.data.status}
                            onChange={(e) => search.setData('status', e.target.value)}
                        >
                            <option value="active">Aktif</option>
                            <option value="archived">Arsip</option>
                        </Field>
                        <Button type="submit">Terapkan</Button>
                    </form>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {quizzes.data.map((quiz) => (
                            <Card
                                key={quiz.id}
                                as="article"
                                className="flex flex-col transition-colors hover:bg-primary/10"
                            >
                                <p className="text-xs font-semibold text-accent">
                                    {quiz.is_archived ? 'Arsip' : 'Kuis pribadi'}
                                </p>
                                <h2 className="mt-4 break-words font-display text-xl font-bold">
                                    <Link href={quiz.url}>{quiz.title}</Link>
                                </h2>
                                <p className="mt-3 flex-1 text-sm leading-7 text-foreground/55">
                                    {quiz.description || 'Siap dimainkan sendiri atau bersama teman.'}
                                </p>
                                <p className="mt-5 text-xs text-foreground/50">
                                    {quiz.questionsCount} soal · {quiz.roomsCount} room tersimpan
                                </p>
                                <Link
                                    href={quiz.url}
                                    className="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-accent"
                                >
                                    Buka kuis →
                                </Link>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {!quiz.is_archived && (
                                        <Link className={secondaryClass} href={quiz.editUrl}>
                                            Edit
                                        </Link>
                                    )}
                                    <Action url={quiz.duplicateUrl}>Duplikasikan</Action>
                                    {!quiz.is_archived && (
                                        <Action url={quiz.archiveUrl} confirm="Arsipkan kuis ini?">
                                            Arsipkan
                                        </Action>
                                    )}
                                </div>
                            </Card>
                        ))}
                    </div>
                    {!quizzes.data.length && (
                        <EmptyState
                            title="Koleksimu masih kosong"
                            action={
                                <Link href={links['quizzes.create']} className={buttonClass}>
                                    Buat kuis pertama
                                </Link>
                            }
                        >
                            Buat kuis, tambahkan soal, lalu ajak teman bermain.
                        </EmptyState>
                    )}
                    <Pagination {...quizzes} />
                </section>
            )}
            <Dialog open={login} onClose={() => setLogin(false)} title="Masuk untuk membuat kuis" id="quiz-login-title">
                <p className="text-sm leading-7 text-foreground/65">
                    Anda harus login untuk membuat kuis. Profil anonim tetap bisa bergabung dan menjawab kuis.
                </p>
                <div className="mt-6 flex flex-wrap gap-3">
                    <Link href={nav.login} className={buttonClass}>
                        Masuk
                    </Link>
                    <Link href={nav.register} className={secondaryClass}>
                        Daftar akun
                    </Link>
                </div>
            </Dialog>
            <Dialog
                open={Boolean(blocked)}
                onClose={() => setBlocked(null)}
                title={form.errors.room_started ? 'Kuis sudah dimulai' : 'Room tidak tersedia'}
                id="room-blocked-title"
            >
                <p className="text-sm leading-7 text-foreground/65">{blocked}</p>
                <button onClick={() => setBlocked(null)} className={`${secondaryClass} mt-5`}>
                    Kembali
                </button>
            </Dialog>
        </>
    );
}
