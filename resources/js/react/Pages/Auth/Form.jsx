import { useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { PageMeta, Button } from '../../Components/ui';
import { Field, Errors, secondaryClass } from '../../Components/forms';
export default function AuthForm({ mode, token, email, meta }) {
    const { navigation: nav, links, brand } = usePage().props;
    const register = mode === 'register',
        reset = mode === 'reset-password',
        forgot = mode === 'forgot-password';
    const [show, setShow] = useState(false);
    const form = useForm({
        name: '',
        email: email ?? '',
        password: '',
        password_confirmation: '',
        remember: false,
        token,
    });
    const generalErrors = Object.fromEntries(
        Object.entries(form.errors).filter(
            ([field]) => !['name', 'email', 'password', 'password_confirmation'].includes(field),
        ),
    );
    const submit = (event) => {
        event.preventDefault();
        if (form.processing) return;
        form.post(
            links[register ? 'register.store' : reset ? 'password.update' : forgot ? 'password.email' : 'login.store'],
            { onFinish: () => form.reset('password', 'password_confirmation') },
        );
    };
    return (
        <div className="grid items-center gap-10 lg:min-h-[calc(100svh-7rem)] lg:grid-cols-[1fr_1fr] lg:gap-16">
            <PageMeta {...meta} />
            <aside className="hidden px-5 lg:block">
                <img
                    src={brand.mascot}
                    width="280"
                    height="320"
                    alt=""
                    className="mx-auto h-56 w-auto object-contain xl:h-64"
                />
                <h2 className="mt-6 font-display text-3xl font-bold leading-tight xl:text-4xl">
                    Langkah kecilmu, cerita Bali yang lebih dekat.
                </h2>
                <p className="mt-4 text-sm leading-8 text-foreground/55">
                    Simpan progres, lanjutkan dari perangkat lain, dan buat kuis untuk bermain bersama.
                </p>
            </aside>
            <section
                aria-labelledby="auth-title"
                className="mx-auto w-full max-w-md sm:rounded-card sm:bg-surface sm:p-6"
            >
                <h1
                    id="auth-title"
                    className="font-display text-3xl font-bold leading-tight tracking-tight lg:text-2xl"
                >
                    {meta.title.replace(' — ARTASWAHA', '')}
                </h1>
                <p className="mt-2 text-sm leading-6 text-foreground/55">
                    {forgot
                        ? 'Masukkan email untuk menerima tautan pemulihan.'
                        : reset
                          ? 'Gunakan minimal delapan karakter.'
                          : 'Satu akun untuk perjalanan belajarmu.'}
                </p>
                <Errors errors={generalErrors} />
                {!forgot && !reset && (
                    <>
                        <a
                            href={links['auth.google.redirect']}
                            className={`${secondaryClass} mt-5 min-h-11 w-full !bg-white !text-[#1f1f1f] hover:!bg-white/90`}
                        >
                            <svg aria-hidden="true" viewBox="0 0 48 48" className="size-5 shrink-0">
                                <path
                                    fill="#4285F4"
                                    d="M43.61 24.46c0-1.36-.12-2.66-.35-3.92H24v7.43h11a9.4 9.4 0 0 1-4.08 6.17v5h6.59c3.85-3.55 6.1-8.79 6.1-14.68Z"
                                />
                                <path
                                    fill="#34A853"
                                    d="M24 44c5.5 0 10.1-1.82 13.47-4.93l-6.59-5c-1.82 1.22-4.15 1.95-6.88 1.95-5.3 0-9.8-3.59-11.41-8.42H5.8v5.17A20 20 0 0 0 24 44Z"
                                />
                                <path
                                    fill="#FBBC05"
                                    d="M12.59 27.6A12 12 0 0 1 12 24c0-1.25.21-2.46.59-3.6v-5.17H5.8A20 20 0 0 0 4 24c0 3.23.77 6.29 1.8 8.77l6.79-5.17Z"
                                />
                                <path
                                    fill="#EA4335"
                                    d="M24 11.98c3 0 5.69 1.03 7.81 3.05l5.86-5.86A19.6 19.6 0 0 0 24 4 20 20 0 0 0 5.8 15.23l6.79 5.17C14.2 15.57 18.7 11.98 24 11.98Z"
                                />
                            </svg>
                            Lanjutkan dengan Google
                        </a>
                        <div className="mt-4 flex items-center gap-3 text-xs text-foreground/40">
                            <span className="h-px flex-1 bg-white/10" />
                            atau dengan email
                            <span className="h-px flex-1 bg-white/10" />
                        </div>
                    </>
                )}
                <form onSubmit={submit} className="mt-4 space-y-3">
                    {register && (
                        <Field
                            label="Nama panggilan"
                            name="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            required
                            maxLength={60}
                            autoComplete="nickname"
                            error={form.errors.name}
                        />
                    )}
                    <Field
                        label="Email"
                        name="email"
                        type="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        required
                        autoComplete="username"
                        error={form.errors.email}
                    />
                    {!forgot && (
                        <>
                            <div className="relative [&_input]:pr-28">
                                <Field
                                    label="Kata sandi"
                                    name="password"
                                    type={show ? 'text' : 'password'}
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    required
                                    minLength={register || reset ? 8 : undefined}
                                    autoComplete={register || reset ? 'new-password' : 'current-password'}
                                    error={form.errors.password}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShow(!show)}
                                    aria-pressed={show}
                                    aria-label={`${show ? 'Sembunyikan' : 'Tampilkan'} kata sandi`}
                                    className="absolute top-7 right-3 min-h-11 rounded-full px-1 text-xs font-semibold text-accent focus-visible:outline-2 focus-visible:outline-accent"
                                >
                                    {show ? 'Sembunyikan' : 'Lihat'}
                                </button>
                            </div>
                            {(register || reset) && (
                                <Field
                                    label="Konfirmasi kata sandi"
                                    name="password_confirmation"
                                    type={show ? 'text' : 'password'}
                                    value={form.data.password_confirmation}
                                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                    required
                                    autoComplete="new-password"
                                    error={form.errors.password_confirmation}
                                />
                            )}
                            {!register && !reset && (
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <label className="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm text-foreground/65">
                                        <input
                                            type="checkbox"
                                            checked={form.data.remember}
                                            onChange={(e) => form.setData('remember', e.target.checked)}
                                            className="size-4 accent-[var(--color-accent)]"
                                        />
                                        Ingat saya
                                    </label>
                                    <Link
                                        className="min-h-11 content-center text-sm font-bold text-accent"
                                        href={links['password.request']}
                                    >
                                        Lupa kata sandi?
                                    </Link>
                                </div>
                            )}
                        </>
                    )}
                    <Button type="submit" disabled={form.processing} className="w-full">
                        {form.processing
                            ? 'Memproses…'
                            : register
                              ? 'Daftar akun'
                              : forgot
                                ? 'Kirim tautan pemulihan'
                                : reset
                                  ? 'Simpan kata sandi'
                                  : 'Masuk'}
                    </Button>
                </form>
                {!forgot && !reset && (
                    <>
                        <p className="mt-3 text-center text-sm text-foreground/55">
                            {register ? 'Sudah punya akun?' : 'Belum punya akun?'}{' '}
                            <Link
                                href={register ? nav.login : nav.register}
                                className="inline-flex min-h-11 items-center font-bold text-accent"
                            >
                                {register ? 'Masuk' : 'Ayo daftar'}
                            </Link>
                        </p>
                    </>
                )}
                {(forgot || reset) && (
                    <Link
                        href={nav.login}
                        className="mt-4 inline-flex min-h-11 items-center text-sm font-bold text-accent"
                    >
                        Kembali ke login
                    </Link>
                )}
            </section>
        </div>
    );
}
