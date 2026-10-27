import { useEffect, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Button, PageMeta, Pagination, buttonClass, EmptyState } from '../../Components/ui';
import { Heading, Card, Field, Action, Errors, secondaryClass } from '../../Components/forms';
import Dialog from '../../Components/Dialog';
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
            <Heading
                title="Belajar lebih seru kalau dimainkan."
                action={
                    viewer?.mode === 'account' ? (
                        <Link href={links['quizzes.create']} className={buttonClass}>
                            ＋ Buat kuis
                        </Link>
                    ) : (
                        <Button onClick={() => setLogin(true)}>＋ Buat kuis</Button>
                    )
                }
            >
                Pilih kuis, tantang teman, dan kenali bahasa Bali sambil bermain.
            </Heading>
            <Card className="mb-9">
                <p className="text-sm font-bold text-accent">Sudah ada yang mengajak?</p>
                <h2 className="mt-2 font-display text-2xl font-bold sm:text-3xl">Masukkan kode room</h2>
                <p className="mt-3 text-sm leading-7 text-foreground/55">
                    Gabung ke permainan temanmu. Kode baru terdiri dari 4 huruf atau angka.
                </p>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        if (!form.processing) form.post(links['rooms.join']);
                    }}
                    className="mt-5 flex flex-col items-stretch gap-3 sm:flex-row sm:items-end"
                >
                    <div className="min-w-0 flex-1">
                        <Field
                            label="Kode room"
                            name="code"
                            placeholder="AB12"
                            value={form.data.code}
                            onChange={(e) => form.setData('code', e.target.value.toUpperCase())}
                            required
                            minLength={4}
                            maxLength={10}
                            pattern="[A-Za-z0-9]{4}([A-Za-z0-9]{6})?"
                            autoComplete="off"
                            autoCapitalize="characters"
                            error={form.errors.code}
                        />
                    </div>
                    <Button type="submit" disabled={form.processing} className="min-h-12">
                        Gabung room →
                    </Button>
                </form>
            </Card>
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
                            <Card key={quiz.id} as="article" className="flex flex-col">
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
