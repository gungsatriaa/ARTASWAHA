import { useEffect, useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Button, PageMeta } from '../../Components/ui';
import Icon from '../../Components/Icon';
import { Card, Field, Errors } from '../../Components/forms';
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
        if (form.processing) return;
        form.transform((data) => data);
        form.post(links['learning.interview.store'], {
            forceFormData: step === 4,
            preserveScroll: false,
        });
    };
    const mode = (value) => {
        if (form.processing) return;
        form.transform((data) => ({ ...data, mode: value, step: 1 }));
        form.post(links['learning.interview.store'], {
            preserveScroll: false,
            preserveState: false,
        });
    };
    const titles = [
        'Bagaimana kamu ingin lanjut?',
        'Budaya Bali apa yang ingin kamu pelajari?',
        'Siapakah kamu?',
        'Apa nama panggilanmu?',
    ];
    return (
        <>
            <PageMeta {...meta} />
            <section
                key={step}
                aria-label="Perkenalan awal"
                className="onboarding-step mx-auto max-w-5xl pb-8 pt-2 sm:pt-6"
            >
                <div className="mb-4 flex items-center justify-between gap-4">
                    <Link
                        href={step > 1 ? `${links['learning.interview.show']}?step=${step - 1}` : navigation.login}
                        className="min-h-11 content-center text-sm font-semibold text-foreground/60"
                    >
                        {step > 1 ? '← Kembali' : 'Sudah punya akun? Masuk'}
                    </Link>
                    <span className="rounded-full bg-surface px-4 py-2 text-sm font-bold text-accent">{step} / 4</span>
                </div>
                <ol aria-label={`Langkah ${step} dari 4`} className="mb-6 grid grid-cols-4 gap-2 sm:gap-3">
                    {['Akun', 'Materi', 'Tentangmu', 'Profil'].map((label, index) => (
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
                        {step === 2
                            ? 'Boleh memilih beberapa materi Paribasa dan Aksara sekaligus. Pilihanmu membantu AI memberikan saran belajar.'
                            : step === 3
                              ? 'Kami akan menyesuaikan cara menyapa dan rekomendasi belajar untukmu.'
                              : step === 1
                                ? 'Kamu bebas belajar tanpa akun. Akun memudahkan progres di perangkat lain.'
                                : 'Lengkapi profil anonim untuk mulai belajar.'}
                    </p>
                    <Errors errors={form.errors} />
                    <form onSubmit={submit} className="mt-6">
                        {step === 2 && (
                            <div className="space-y-6">
                                <p role="status" className="text-sm font-semibold text-accent">
                                    {form.data.learning_path_ids.length} materi dipilih · boleh lebih dari satu
                                </p>
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
                                                            name="learning_path_ids[]"
                                                            value={path.id}
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
                        {step === 3 && (
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
                        {step === 1 && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                {!viewer && (
                                    <section className="flex min-w-0 flex-col rounded-card bg-surface p-5 sm:p-7">
                                        <span className="grid size-12 place-items-center rounded-2xl bg-primary/20 text-accent">
                                            <Icon name="user" className="size-6" />
                                        </span>
                                        <h2 className="mt-5 font-display text-xl font-bold">Lanjut anonim</h2>
                                        <p className="mt-3 flex-1 text-sm leading-7 text-foreground/55">
                                            Isi nama panggilan dan avatar. Profilmu dikenali melalui browser ini.
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
                                <section className="flex min-w-0 flex-col rounded-card bg-surface p-5 sm:p-7">
                                    <span className="grid size-12 place-items-center rounded-2xl bg-primary/20 text-accent">
                                        <Icon name="login" className="size-6" />
                                    </span>
                                    <h2 className="mt-5 font-display text-xl font-bold">
                                        {viewer ? 'Lanjut dengan akunmu' : 'Masuk atau daftar'}
                                    </h2>
                                    <p className="mt-3 flex-1 text-sm leading-7 text-foreground/55">
                                        Gunakan akunmu untuk melanjutkan belajar di perangkat lain. Belum punya akun?
                                        Kamu bisa mendaftar dari halaman masuk.
                                    </p>
                                    <Link
                                        href={navigation.login}
                                        className="mt-6 inline-flex min-h-11 w-full items-center justify-center rounded-full bg-primary px-5 py-3 text-sm font-bold text-white hover:bg-primary/85"
                                    >
                                        Masuk / daftar
                                    </Link>
                                </section>
                            </div>
                        )}
                        {step === 4 && (
                            <div className="grid gap-7 rounded-card bg-surface p-5 sm:p-8 md:grid-cols-[15rem_minmax(0,1fr)] md:gap-10">
                                <div className="flex min-w-0 flex-col items-center rounded-3xl bg-primary/10 px-5 py-6 text-center">
                                    <img
                                        src={preview ?? avatar}
                                        width="112"
                                        height="112"
                                        alt="Preview avatar"
                                        className="size-28 rounded-full object-cover ring-4 ring-primary/20"
                                    />
                                    <p className="mt-5 font-display text-lg font-bold">
                                        {preview ? 'Avatar pilihanmu' : 'Avatar acak untukmu'}
                                    </p>
                                    <p className="mt-2 text-sm leading-6 text-foreground/55">
                                        Pakai karakter ini atau pilih foto yang kamu suka.
                                    </p>
                                    <label className="relative mt-5 inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-full bg-primary/20 px-5 py-3 text-sm font-bold text-accent transition hover:bg-primary/30 focus-within:outline-2 focus-within:outline-offset-4 focus-within:outline-accent">
                                        <Icon name="edit" className="size-4" />
                                        {preview ? 'Ganti foto' : 'Unggah foto'}
                                        <input
                                            type="file"
                                            name="avatar_upload"
                                            accept="image/jpeg,image/png,image/webp"
                                            aria-label="Unggah avatar sendiri (opsional)"
                                            aria-invalid={Boolean(form.errors.avatar_upload)}
                                            aria-describedby="avatar-upload-help"
                                            className="sr-only"
                                            onChange={(e) => form.setData('avatar_upload', e.target.files[0] ?? null)}
                                        />
                                    </label>
                                    {preview && (
                                        <button
                                            type="button"
                                            className="mt-2 min-h-11 text-sm text-foreground/65 hover:text-accent focus-visible:outline-2 focus-visible:outline-accent"
                                            onClick={() => form.setData('avatar_upload', null)}
                                        >
                                            Gunakan avatar acak
                                        </button>
                                    )}
                                    <p id="avatar-upload-help" className="mt-4 text-xs leading-5 text-foreground/45">
                                        Opsional · JPG, PNG, WEBP
                                        <br />
                                        Maksimal 2 MB · 2048 × 2048 piksel
                                    </p>
                                    {form.errors.avatar_upload && (
                                        <p role="alert" className="mt-3 text-sm text-danger">
                                            {form.errors.avatar_upload}
                                        </p>
                                    )}
                                </div>
                                <div className="flex min-w-0 flex-col justify-center py-1 md:py-4">
                                    <h2 className="font-display text-2xl font-bold">Buat profil belajarmu</h2>
                                    <p className="mb-6 mt-2 max-w-md text-sm leading-7 text-foreground/55">
                                        Nama ini akan tampil di profil dan saat kamu bergabung dalam kuis.
                                    </p>
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
                                        hint="Gunakan nama yang ingin kamu pakai saat belajar."
                                    />
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                        className="mt-7 w-full sm:self-start sm:w-auto"
                                    >
                                        {form.processing ? 'Menyimpan…' : 'Mulai belajar →'}
                                    </Button>
                                </div>
                            </div>
                        )}
                        {step !== 1 && step !== 4 && (
                            <div className="mt-6 flex justify-end">
                                <Button type="submit" disabled={form.processing} className="w-full sm:w-auto">
                                    {form.processing ? 'Menyimpan…' : 'Lanjutkan →'}
                                </Button>
                            </div>
                        )}
                    </form>
                    <p className="mt-5 text-xs leading-6 text-foreground/45">
                        {step === 4
                            ? 'Profil tersimpan di server dan dikenali melalui cookie browser ini. Menghapus cookie dapat memutus aksesnya.'
                            : 'Pilihanmu dipakai untuk menyesuaikan pengalaman belajar.'}{' '}
                        <Link href={navigation.privacy} className="text-accent underline underline-offset-4">
                            Lihat penggunaan datamu
                        </Link>
                        .
                    </p>
                </Card>
            </section>
        </>
    );
}
