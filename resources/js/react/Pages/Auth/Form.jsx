import { useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { PageMeta, Button } from '../../Components/ui';
import { Field, Check, Card, Errors, secondaryClass } from '../../Components/forms';
export default function AuthForm({ mode, token, email, meta }) {
    const { navigation: nav, links, brand } = usePage().props;
    const register = mode === 'register',
        reset = mode === 'reset-password',
        forgot = mode === 'forgot-password';
    const [show, setShow] = useState(false);
    const form = useForm({ name: '', email, password: '', password_confirmation: '', remember: false, token });
    const submit = (event) => {
        event.preventDefault();
        if (form.processing) return;
        form.post(
            links[register ? 'register.store' : reset ? 'password.update' : forgot ? 'password.email' : 'login.store'],
            { onFinish: () => form.reset('password', 'password_confirmation') },
        );
    };
    return (
        <div className="mx-auto grid max-w-5xl items-center gap-10 py-4 lg:grid-cols-2 lg:py-10">
            <PageMeta {...meta} />
            <aside className="hidden max-w-md lg:block">
                <img
                    src={brand.mascot}
                    width="280"
                    height="320"
                    alt=""
                    className="mx-auto h-72 w-auto object-contain"
                />
                <h2 className="mt-8 font-display text-4xl font-bold leading-tight">
                    Langkah kecilmu, cerita Bali yang lebih dekat.
                </h2>
                <p className="mt-4 text-sm leading-8 text-foreground/55">
                    Simpan progres, lanjutkan dari perangkat lain, dan buat kuis untuk bermain bersama.
                </p>
            </aside>
            <Card className="mx-auto w-full max-w-md sm:!p-9">
                <h1 className="font-display text-3xl font-bold">{meta.title.replace(' — ARTASWAHA', '')}</h1>
                <p className="mt-3 text-sm leading-7 text-foreground/55">
                    {forgot
                        ? 'Masukkan email untuk menerima tautan pemulihan.'
                        : reset
                          ? 'Gunakan minimal delapan karakter.'
                          : 'Satu akun untuk perjalanan belajarmu.'}
                </p>
                <Errors errors={form.errors} />
                <form onSubmit={submit} className="mt-6 space-y-4">
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
                            <button
                                type="button"
                                onClick={() => setShow(!show)}
                                aria-pressed={show}
                                className="min-h-11 text-xs font-semibold text-accent"
                            >
                                {show ? 'Sembunyikan' : 'Tampilkan'} kata sandi
                            </button>
                            {!register && !reset && (
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <Check
                                        checked={form.data.remember}
                                        onChange={(e) => form.setData('remember', e.target.checked)}
                                    >
                                        Ingat saya
                                    </Check>
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
                        <a href={links['auth.google.redirect']} className={`${secondaryClass} mt-4 w-full`}>
                            Lanjutkan dengan Google
                        </a>
                        <p className="mt-5 text-center text-sm text-foreground/55">
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
            </Card>
        </div>
    );
}
