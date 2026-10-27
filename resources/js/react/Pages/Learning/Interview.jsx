import Dialog from '../../Components/Dialog';
import { useEffect, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Button, CategoryIcon, PageMeta } from '../../Components/ui';
import { Card, Field, Errors, secondaryClass } from '../../Components/forms';
export default function Interview({ paths, answers, step, roles, avatar, meta }) {
    const { links, navigation, viewer } = usePage().props;
    const form = useForm({
        step,
        learning_path_ids: answers.learning_path_ids ?? [],
        role: answers.role ?? '',
        mode: answers.mode ?? '',
        name: '',
        avatar_upload: null,
    });
    const [preview, setPreview] = useState(null);
    useEffect(() => {
        form.setData('step', step);
    }, [step]);
    useEffect(() => {
        if (!form.data.avatar_upload) {
            setPreview(null);
            return;
        }
        const url = URL.createObjectURL(form.data.avatar_upload);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [form.data.avatar_upload]);
    const submit = (e) => {
        e.preventDefault();
        if (!form.processing)
            form.transform((data) => data).post(links['learning.interview.store'], {
                forceFormData: step === 4,
                preserveScroll: false,
            });
    };
    const mode = (value) => {
        form.transform((data) => ({ ...data, mode: value, step: 3 })).post(links['learning.interview.store'], {
            preserveScroll: false,
            preserveState: false,
        });
    };
    const titles = [
        'Budaya Bali apa yang ingin kamu pelajari?',
        'Siapakah kamu?',
        'Bagaimana kamu ingin lanjut?',
        'Apa nama panggilanmu?',
    ];
    return (
        <>
            <PageMeta {...meta} />
            <Dialog
                open
                onClose={() => router.visit(navigation.home)}
                title="Mulai perjalanan belajarmu"
                id="interview-modal-title"
                className="!w-[min(56rem,calc(100%-1.5rem))] !p-5 sm:!p-8"
            >
                <div className="mb-4 flex items-center justify-between gap-4">
                    <Link
                        href={step > 1 ? `${links['learning.interview.show']}?step=${step - 1}` : navigation.home}
                        className="min-h-11 content-center text-sm font-semibold text-foreground/60"
                    >
                        ← {step > 1 ? 'Kembali' : 'Beranda'}
                    </Link>
                    <span className="rounded-full bg-surface px-4 py-2 text-sm font-bold text-accent">{step} / 4</span>
                </div>
                <ol aria-label={`Langkah ${step} dari 4`} className="mb-6 grid grid-cols-4 gap-2 sm:gap-3">
                    {['Materi', 'Tentangmu', 'Akun', 'Profil'].map((label, index) => (
                        <li key={label} aria-current={index + 1 === step ? 'step' : undefined}>
                            <div className={`mb-2 h-1.5 rounded-full ${index < step ? 'bg-accent' : 'bg-white/10'}`} />
                            <p
                                className={`text-[11px] font-semibold sm:text-xs ${index + 1 === step ? 'text-accent' : 'text-foreground/40'}`}
                            >
                                {index + 1}. {label}
                            </p>
                        </li>
                    ))}
                </ol>
                <Card className="!bg-transparent !p-0">
                    <p className="text-sm font-semibold text-accent">Kenalan dulu, yuk</p>
                    <h1 className="mt-3 max-w-4xl font-display text-2xl font-bold leading-tight sm:text-4xl">
                        {titles[step - 1]}
                    </h1>
                    <p className="mt-3 text-sm leading-7 text-foreground/55">
                        {step === 1
                            ? 'Pilih satu atau beberapa materi yang ingin kamu pelajari.'
                            : step === 2
                              ? 'Kami akan menyesuaikan cara menyapa dan rekomendasi belajar untukmu.'
                              : step === 3
                                ? 'Kamu bebas belajar tanpa akun. Akun memudahkan progres di perangkat lain.'
                                : 'Lengkapi profil anonim untuk mulai belajar.'}
                    </p>
                    <Errors errors={form.errors} />
                    <form onSubmit={submit} className="mt-6">
                        {step === 1 && (
                            <div className="space-y-6">
                                {[
                                    ['paribasa', 'Bahasa & paribasa'],
                                    ['aksara', 'Aksara Bali'],
                                ].map(([group, label]) => (
                                    <fieldset key={group}>
                                        <legend className="mb-3 font-display text-lg font-bold">{label}</legend>
                                        <div
                                            className={`grid gap-3 sm:grid-cols-2 ${group === 'aksara' ? 'xl:grid-cols-5' : 'lg:grid-cols-3'}`}
                                        >
                                            {paths
                                                .filter((path) => path.group === group)
                                                .map((path) => (
                                                    <label
                                                        key={path.id}
                                                        className={`flex cursor-pointer gap-3 rounded-2xl bg-background p-4 ring-1 transition ${form.data.learning_path_ids.includes(path.id) ? 'ring-accent bg-accent/10' : 'ring-white/5 hover:ring-accent/40'}`}
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            className="mt-1 size-5 shrink-0 accent-[var(--color-accent)]"
                                                            checked={form.data.learning_path_ids.includes(path.id)}
                                                            onChange={() =>
                                                                form.setData(
                                                                    'learning_path_ids',
                                                                    form.data.learning_path_ids.includes(path.id)
                                                                        ? form.data.learning_path_ids.filter(
                                                                              (id) => id !== path.id,
                                                                          )
                                                                        : [...form.data.learning_path_ids, path.id],
                                                                )
                                                            }
                                                        />
                                                        <span className="min-w-0">
                                                            <span className="block text-sm font-bold leading-6">
                                                                {path.title}
                                                            </span>
                                                            <span className="mt-2 block text-xs leading-6 text-foreground/50">
                                                                {path.description}
                                                            </span>
                                                        </span>
                                                    </label>
                                                ))}
                                        </div>
                                    </fieldset>
                                ))}
                            </div>
                        )}
                        {step === 2 && (
                            <fieldset className="grid gap-3 sm:grid-cols-2">
                                <legend className="sr-only">Pilih peran</legend>
                                {Object.entries(roles).map(([value, label]) => (
                                    <label
                                        key={value}
                                        className={`flex min-h-20 cursor-pointer items-center gap-4 rounded-2xl bg-background p-5 ring-1 ${form.data.role === value ? 'ring-accent bg-accent/10' : 'ring-white/5'}`}
                                    >
                                        <input
                                            type="radio"
                                            name="role"
                                            required
                                            checked={form.data.role === value}
                                            onChange={() => form.setData('role', value)}
                                            className="size-5 accent-[var(--color-accent)]"
                                        />
                                        <span className="font-display text-lg font-bold">{label}</span>
                                    </label>
                                ))}
                            </fieldset>
                        )}
                        {step === 3 && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                {!viewer && (
                                    <section className="flex flex-col rounded-card bg-background p-5 sm:p-7">
                                        <CategoryIcon group="paribasa" />
                                        <h2 className="mt-5 font-display text-xl font-bold">Lanjut anonim</h2>
                                        <p className="mt-3 flex-1 text-sm leading-7 text-foreground/55">
                                            Isi nama dan avatar. Profil belajar tersimpan di perangkat ini.
                                        </p>
                                        <Button
                                            type="button"
                                            className="mt-6 w-full"
                                            disabled={form.processing}
                                            onClick={() => mode('anonymous')}
                                        >
                                            Pilih anonim
                                        </Button>
                                    </section>
                                )}
                                <section className="flex flex-col rounded-card bg-background p-5 sm:p-7">
                                    <CategoryIcon group="aksara" />
                                    <h2 className="mt-5 font-display text-xl font-bold">
                                        {viewer ? 'Lanjut dengan akunmu' : 'Masuk atau daftar'}
                                    </h2>
                                    <p className="mt-3 flex-1 text-sm leading-7 text-foreground/55">
                                        Pilihan belajar tetap tersimpan. Akun memudahkanmu melanjutkan di perangkat
                                        lain.
                                    </p>
                                    <Button
                                        type="button"
                                        disabled={form.processing}
                                        onClick={() => mode('account')}
                                        className="mt-6 w-full !bg-primary !text-white"
                                    >
                                        {viewer ? 'Lanjutkan' : 'Masuk / daftar'}
                                    </Button>
                                </section>
                            </div>
                        )}
                        {step === 4 && (
                            <div className="grid gap-5 lg:grid-cols-2">
                                <Field
                                    label="Nama panggilan"
                                    name="name"
                                    autoComplete="nickname"
                                    required
                                    minLength={2}
                                    maxLength={80}
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    placeholder="Misalnya, Komang"
                                    error={form.errors.name}
                                />
                                <div className="rounded-2xl bg-background p-5">
                                    <div className="mb-4 flex items-center gap-4">
                                        <img
                                            src={preview ?? avatar}
                                            width="64"
                                            height="64"
                                            alt="Preview avatar"
                                            className="size-16 rounded-full object-cover"
                                        />
                                        <div>
                                            <p className="font-bold">{preview ? 'Avatar pilihanmu' : 'Avatar acak'}</p>
                                            <p className="mt-1 text-xs leading-6 text-foreground/50">
                                                Pakai karakter acak, atau unggah fotomu.
                                            </p>
                                        </div>
                                    </div>
                                    <Field
                                        label="Unggah avatar sendiri (opsional)"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={(e) => form.setData('avatar_upload', e.target.files[0] ?? null)}
                                        error={form.errors.avatar_upload}
                                        hint="JPG, PNG, WEBP · maksimal 2 MB dan 2048 × 2048 piksel."
                                    />
                                    {preview && (
                                        <button
                                            type="button"
                                            className="mt-2 min-h-11 text-sm text-accent"
                                            onClick={() => form.setData('avatar_upload', null)}
                                        >
                                            Gunakan avatar acak
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}
                        {step !== 3 && (
                            <div className="mt-6 flex justify-end">
                                <Button type="submit" disabled={form.processing} className="w-full sm:w-auto">
                                    {form.processing ? 'Menyimpan…' : step === 4 ? 'Mulai belajar →' : 'Lanjutkan →'}
                                </Button>
                            </div>
                        )}
                    </form>
                    <p className="mt-5 text-xs leading-6 text-foreground/45">
                        {step === 4
                            ? 'Profil anonim tersimpan di perangkat ini dan bisa hilang jika cookie dihapus.'
                            : 'Pilihanmu dipakai untuk menyesuaikan pengalaman belajar.'}{' '}
                        <Link href={navigation.privacy} className="text-accent underline underline-offset-4">
                            Lihat penggunaan datamu
                        </Link>
                        .
                    </p>
                </Card>
            </Dialog>
        </>
    );
}
