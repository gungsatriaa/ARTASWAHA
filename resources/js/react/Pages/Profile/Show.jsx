import Skeleton from '../../Components/Skeleton';
import { lazy, Suspense, useEffect, useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { PageMeta, Button, buttonClass } from '../../Components/ui';
import { Card, Action, Field, Errors, Stat, secondaryClass } from '../../Components/forms';
import Dialog from '../../Components/Dialog';
import Analysis from '../../Components/Analysis';

const AvatarCrop = lazy(() => import('../../Components/AvatarCrop'));

export default function Profile({
    about,
    paths,
    interests,
    completedLessons,
    totalLessons,
    activity,
    analysis,
    avatars,
    email,
    version,
    meta,
}) {
    const { viewer, navigation: nav, links } = usePage().props;
    const [avatarOpen, setAvatarOpen] = useState(false),
        [preview, setPreview] = useState(null),
        [candidate, setCandidate] = useState(null);
    const form = useForm({ avatar: avatars[0]?.value ?? '', avatar_upload: null, _method: 'put' });
    useEffect(() => {
        if (!form.data.avatar_upload) {
            setPreview(null);
            return;
        }
        const url = URL.createObjectURL(form.data.avatar_upload);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [form.data.avatar_upload]);
    return (
        <>
            <PageMeta {...meta} />
            <div className="grid items-start gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
                <aside className="rounded-card bg-surface p-5 lg:sticky lg:top-28 lg:p-7">
                    <div className="flex items-center gap-4 lg:block">
                        <button
                            onClick={() => setAvatarOpen(true)}
                            aria-label="Ganti avatar profil"
                            className="rounded-full focus-visible:outline-2 focus-visible:outline-accent"
                        >
                            <img
                                src={viewer.avatar}
                                alt=""
                                width="88"
                                height="88"
                                className="size-20 rounded-full object-cover ring-4 ring-primary/20"
                            />
                        </button>
                        <div className="min-w-0">
                            <h1 className="break-words font-display text-2xl font-bold lg:mt-5">{viewer.name}</h1>
                            <p className="mt-2 text-xs font-semibold text-accent">
                                {viewer.mode === 'anonymous' ? 'Akun anonim' : 'Akun terdaftar'}
                            </p>
                            {email && <p className="mt-2 break-all text-xs text-foreground/50">{email}</p>}
                        </div>
                    </div>
                    <button onClick={() => setAvatarOpen(true)} className={`${secondaryClass} mt-4`}>
                        Ganti avatar
                    </button>
                    <nav aria-label="Menu profil" className="mt-6 grid gap-2">
                        <Link
                            href={nav.profile}
                            className={`${secondaryClass} !justify-start ${!about ? '!bg-primary/20 !text-accent' : ''}`}
                        >
                            Progres belajar
                        </Link>
                        <Link
                            href={links['profile.about']}
                            className={`${secondaryClass} !justify-start ${about ? '!bg-primary/20 !text-accent' : ''}`}
                        >
                            Tentang aplikasi ini
                        </Link>
                        {viewer.mode === 'anonymous' ? (
                            <Link href={nav.login} className={`${secondaryClass} !justify-start`}>
                                Masuk
                            </Link>
                        ) : (
                            <Action url={nav.logout} className={`${secondaryClass} !justify-start`}>
                                Keluar
                            </Action>
                        )}
                    </nav>
                </aside>
                <div className="min-w-0 space-y-6">
                    {about ? (
                        <>
                            <Card>
                                <h2 className="font-display text-3xl font-bold">Tentang ARTASWAHA</h2>
                                <p className="mt-4 text-sm leading-8 text-foreground/65">
                                    ARTASWAHA adalah platform edukasi Bahasa, Sastra, dan Aksara Bali. Pelajari materi
                                    mandiri dan ikuti kuis bersama dalam room.
                                </p>
                                <div className="mt-7 grid gap-4 sm:grid-cols-2">
                                    <div className="rounded-2xl bg-background p-5">
                                        <h3 className="font-bold">Paribasa Bali</h3>
                                        <p className="mt-3 text-sm leading-7 text-foreground/55">
                                            Cecimpedan, peparikan, dan sesonggakan.
                                        </p>
                                    </div>
                                    <div className="rounded-2xl bg-background p-5">
                                        <h3 className="font-bold">Aksara Bali</h3>
                                        <p className="mt-3 text-sm leading-7 text-foreground/55">
                                            Aksara dasar, gantungan, tanda, dan angka.
                                        </p>
                                    </div>
                                </div>
                                <Link href={nav.learning} className={`${buttonClass} mt-6`}>
                                    Jelajahi materi
                                </Link>
                            </Card>
                            <Card>
                                <h2 className="font-display text-xl font-bold">Privasi dan bantuan</h2>
                                {version && <p className="mt-3 text-xs text-foreground/50">Versi {version}</p>}
                                <p className="mt-3 text-sm leading-7 text-foreground/55">
                                    Pelajari bagaimana data digunakan dan hubungi pengelola bila membutuhkan bantuan.
                                </p>
                                <Link
                                    href={nav.privacy}
                                    className="mt-3 inline-flex min-h-11 items-center font-bold text-accent"
                                >
                                    Kebijakan privasi →
                                </Link>
                                <a
                                    href="mailto:kerthajayasoftware@gmail.com"
                                    className="mt-2 block break-all text-sm text-accent"
                                >
                                    kerthajayasoftware@gmail.com
                                </a>
                            </Card>
                        </>
                    ) : (
                        <>
                            <section aria-labelledby="overview-title">
                                <h2 id="overview-title" className="mb-4 font-display text-xl font-bold">
                                    Ringkasan belajar
                                </h2>
                                <div className="grid grid-cols-3 gap-2 sm:gap-4">
                                    <Stat label="Pelajaran selesai" value={completedLessons} />
                                    <Stat label="Materi dipelajari" value={paths.length} />
                                    <Stat label="Hari aktif minggu ini" value={`${activity?.active_days_7 ?? 0}/7`} />
                                </div>
                            </section>
                            <div className="grid items-start gap-6 xl:grid-cols-2">
                                <div className="space-y-6">
                                    {activity && (
                                        <Card>
                                            <h2 className="font-display text-xl font-bold">Sedikit setiap hari</h2>
                                            <p className="mt-2 text-xs leading-6 text-foreground/50">
                                                Aktivitas belajar dalam tujuh hari terakhir.
                                            </p>
                                            <ol
                                                className="mt-5 grid grid-cols-7 gap-2"
                                                aria-label="Aktivitas belajar tujuh hari terakhir"
                                            >
                                                {activity.daily.slice(-7).map((day) => {
                                                    const active = day.lessons_completed + day.quiz_answers > 0;
                                                    const date = new Date(`${day.date}T00:00:00+08:00`);
                                                    return (
                                                        <li
                                                            key={day.date}
                                                            aria-label={`${day.date}: ${day.lessons_completed} pelajaran selesai, ${day.quiz_answers} jawaban kuis`}
                                                            className="text-center"
                                                        >
                                                            <span className="text-[10px] text-foreground/45">
                                                                {date.toLocaleDateString('id-ID', {
                                                                    weekday: 'short',
                                                                    timeZone: 'Asia/Makassar',
                                                                })}
                                                            </span>
                                                            <span
                                                                className={`mx-auto mt-2 grid aspect-square max-w-10 place-items-center rounded-full text-sm font-bold ${active ? 'bg-accent text-background' : 'bg-background text-foreground/25'}`}
                                                            >
                                                                {active ? '✓' : '·'}
                                                            </span>
                                                            <time
                                                                dateTime={day.date}
                                                                className="mt-2 block text-[10px] text-foreground/40"
                                                            >
                                                                {day.date.slice(-2)}
                                                            </time>
                                                        </li>
                                                    );
                                                })}
                                            </ol>
                                            <p className="mt-4 text-xs leading-6 text-foreground/45">
                                                Selesaikan pelajaran atau jawab kuis untuk mencatat hari aktif.
                                            </p>
                                            {activity.quiz_answers_14 > 0 && (
                                                <p className="mt-3 text-xs text-foreground/60">
                                                    Kuis 14 hari: {activity.quiz_correct_14} dari{' '}
                                                    {activity.quiz_answers_14} jawaban benar.
                                                </p>
                                            )}
                                        </Card>
                                    )}
                                    <Card id="progres">
                                        <div className="flex flex-wrap justify-between gap-3">
                                            <h2 className="font-display text-xl font-bold">Progres belajar</h2>
                                            <Link className="text-sm font-bold text-accent" href={nav.learning}>
                                                Lihat materi
                                            </Link>
                                        </div>
                                        {paths.length ? (
                                            <>
                                                <p className="mt-3 text-sm text-foreground/55">
                                                    {completedLessons} dari {totalLessons} pelajaran selesai
                                                </p>
                                                <ul className="mt-5 space-y-3">
                                                    {paths.map((path) => (
                                                        <li key={path.id}>
                                                            <Link
                                                                href={path.url}
                                                                className="block rounded-2xl bg-background p-4"
                                                            >
                                                                <div className="flex items-start justify-between gap-3">
                                                                    <h3 className="min-w-0 break-words text-sm font-bold">
                                                                        {path.title}
                                                                    </h3>
                                                                    <span className="shrink-0 text-xs font-bold text-accent">
                                                                        {Math.round(
                                                                            (path.completed /
                                                                                Math.max(1, path.lessonsCount)) *
                                                                                100,
                                                                        )}
                                                                        %
                                                                    </span>
                                                                </div>
                                                                <progress
                                                                    aria-label={`Progres ${path.title}`}
                                                                    value={path.completed}
                                                                    max={Math.max(1, path.lessonsCount)}
                                                                    className="mt-3 h-2 w-full overflow-hidden rounded-full accent-[var(--color-primary)]"
                                                                />
                                                                <p className="mt-2 text-xs text-foreground/45">
                                                                    {path.completed} dari {path.lessonsCount} pelajaran
                                                                </p>
                                                            </Link>
                                                        </li>
                                                    ))}
                                                </ul>
                                            </>
                                        ) : (
                                            <div className="py-8 text-center">
                                                <h3 className="font-display text-xl font-bold">
                                                    Belum ada progres belajar
                                                </h3>
                                                <p className="mt-3 text-sm leading-7 text-foreground/55">
                                                    Mulai dari satu pelajaran. Setiap langkahmu akan tercatat di sini.
                                                </p>
                                                <Link className={`${buttonClass} mt-5`} href={nav.learning}>
                                                    Pilih materi
                                                </Link>
                                            </div>
                                        )}
                                    </Card>
                                </div>
                                <div className="space-y-6">
                                    <Analysis key={`${analysis?.status}-${analysis?.updated_at}`} analysis={analysis} />
                                    {interests.length > 0 && (
                                        <Card>
                                            <h2 className="font-display text-xl font-bold">Minat belajarmu</h2>
                                            <p className="mt-2 text-xs text-foreground/50">
                                                {analysis?.role} · Pilihan dari perkenalan awalmu.
                                            </p>
                                            <div className="mt-4 flex flex-wrap gap-2">
                                                {interests.map((path) => (
                                                    <Link href={path.url} key={path.id} className={secondaryClass}>
                                                        {path.title}
                                                    </Link>
                                                ))}
                                            </div>
                                        </Card>
                                    )}
                                    {viewer.mode === 'anonymous' && (
                                        <Card className="!bg-primary/10">
                                            <p className="text-xs font-bold text-accent">
                                                Akun anonim · hanya di perangkat ini
                                            </p>
                                            <h2 className="mt-3 font-display text-xl font-bold">
                                                Belajar sebagai {viewer.name}
                                            </h2>
                                            <p className="mt-3 text-sm leading-7 text-foreground/55">
                                                Progres tersimpan di browser ini. Hubungkan ke akun agar bisa
                                                melanjutkan di perangkat lain.
                                            </p>
                                            <Link href={nav.register} className={`${buttonClass} mt-5`}>
                                                Daftar akun
                                            </Link>
                                        </Card>
                                    )}
                                </div>
                            </div>
                        </>
                    )}
                </div>
            </div>
            <Dialog
                open={avatarOpen}
                onClose={() => setAvatarOpen(false)}
                title="Pilih avatar belajarmu"
                id="profile-avatar"
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(links['profile.avatar.update'], {
                            forceFormData: true,
                            preserveScroll: true,
                            onSuccess: () => setAvatarOpen(false),
                        });
                    }}
                >
                    <Errors errors={form.errors} />
                    <div className="grid grid-cols-4 gap-3">
                        {avatars.map((avatar) => (
                            <label
                                key={avatar.value}
                                className={`cursor-pointer rounded-full p-1 ring-2 ${form.data.avatar === avatar.value && !preview ? 'ring-accent' : 'ring-transparent'}`}
                            >
                                <input
                                    type="radio"
                                    name="avatar"
                                    className="sr-only"
                                    checked={form.data.avatar === avatar.value && !preview}
                                    onChange={() => {
                                        form.setData({ avatar: avatar.value, avatar_upload: null, _method: 'put' });
                                    }}
                                />
                                <img
                                    src={avatar.url}
                                    alt={avatar.name}
                                    width="64"
                                    height="64"
                                    className="aspect-square w-full rounded-full object-cover"
                                />
                            </label>
                        ))}
                    </div>
                    {preview && (
                        <img
                            src={preview}
                            alt="Preview avatar unggahan"
                            width="88"
                            height="88"
                            className="mx-auto mt-5 size-22 rounded-full object-cover"
                        />
                    )}
                    <div className="mt-5">
                        <Field
                            label="Atau unggah avatarmu"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(e) => {
                                setCandidate(e.target.files[0] ?? null);
                                e.target.value = '';
                            }}
                            hint="JPG, PNG, WEBP; maksimal 2 MB dan 2048 × 2048 piksel."
                            error={form.errors.avatar_upload}
                        />
                    </div>
                    <Suspense fallback={<Skeleton label="Menyiapkan pengaturan gambar…" compact />}>
                        {candidate && (
                            <AvatarCrop
                                key={`${candidate.name}-${candidate.lastModified}`}
                                file={candidate}
                                onCancel={() => setCandidate(null)}
                                onApply={(file) => {
                                    form.setData('avatar_upload', file);
                                    setCandidate(null);
                                }}
                            />
                        )}
                    </Suspense>
                    <Button type="submit" className="mt-5 w-full" disabled={form.processing || Boolean(candidate)}>
                        Simpan avatar
                    </Button>
                </form>
            </Dialog>
        </>
    );
}
