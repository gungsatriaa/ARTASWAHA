import Skeleton from '../../Components/Skeleton';
import { lazy, Suspense, useEffect, useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { PageMeta, Button, buttonClass } from '../../Components/ui';
import { Card, Action, Field, Errors, secondaryClass } from '../../Components/forms';
import Dialog from '../../Components/Dialog';
import Analysis from '../../Components/Analysis';
import Icon from '../../Components/Icon';

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
    const form = useForm({
        avatar: avatars.find((item) => item.url === viewer.avatar)?.value ?? '',
        avatar_upload: null,
        _method: 'put',
    });
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
            <div className="mx-auto max-w-6xl space-y-7 sm:space-y-8">
                <header className="overflow-hidden rounded-card bg-surface">
                    <div className="flex flex-wrap items-center gap-5 p-5 sm:gap-7 sm:p-8">
                        <button
                            type="button"
                            onClick={() => setAvatarOpen(true)}
                            aria-label="Ganti avatar profil"
                            className="relative shrink-0 rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
                        >
                            <img
                                src={viewer.avatar}
                                alt=""
                                width="112"
                                height="112"
                                className="size-20 rounded-full object-cover ring-4 ring-primary/25 sm:size-28"
                            />
                            <span className="absolute -bottom-1 -right-1 grid size-8 place-items-center rounded-full bg-accent text-background ring-4 ring-surface sm:size-10">
                                <Icon name="book" className="size-4 sm:size-5" />
                            </span>
                        </button>
                        <div className="min-w-0 flex-1">
                            <p className="text-xs text-foreground/45 sm:text-sm">Profil belajar</p>
                            <h1 className="mt-2 break-words font-display text-2xl font-bold sm:text-3xl">
                                {viewer.name}
                            </h1>
                            <p className="mt-2 text-xs font-semibold text-accent sm:text-sm">
                                {viewer.mode === 'anonymous'
                                    ? 'Akun anonim · hanya di perangkat ini'
                                    : 'Akun terdaftar'}
                            </p>
                            <p className="mt-2 max-w-lg break-words text-xs leading-6 text-foreground/50 sm:text-sm">
                                {viewer.mode === 'anonymous'
                                    ? 'Progres tersimpan di browser ini. Buat akun untuk melanjutkan belajar di perangkat lain.'
                                    : email}
                            </p>
                        </div>
                        <div
                            className={`grid w-full gap-2 sm:ml-auto sm:w-auto sm:shrink-0 ${viewer.mode === 'anonymous' ? 'grid-cols-2 sm:grid-cols-1 lg:grid-cols-2' : 'grid-cols-1'}`}
                        >
                            <button
                                type="button"
                                onClick={() => setAvatarOpen(true)}
                                className={`${secondaryClass} !bg-primary/15 !px-4 !text-accent`}
                            >
                                <Icon name="edit" />
                                Ganti avatar
                            </button>
                            {viewer.mode === 'anonymous' && (
                                <Link href={nav.register} className={`${buttonClass} !px-4`}>
                                    Daftar akun
                                </Link>
                            )}
                        </div>
                    </div>
                    <nav
                        aria-label="Menu profil"
                        className="flex flex-wrap items-center gap-2 bg-background/20 p-3 sm:px-5 sm:py-4"
                    >
                        <Link
                            href={nav.profile}
                            aria-current={!about ? 'page' : undefined}
                            className={`${secondaryClass} !px-3 !text-xs sm:!px-5 sm:!text-sm ${!about ? '!bg-primary/20 !text-accent' : '!bg-transparent'}`}
                        >
                            <Icon name="chart" />
                            Progres belajar
                        </Link>
                        <Link
                            href={links['profile.about']}
                            aria-label="Tentang aplikasi ini"
                            aria-current={about ? 'page' : undefined}
                            className={`${secondaryClass} !px-3 !text-xs sm:!px-5 sm:!text-sm ${about ? '!bg-primary/20 !text-accent' : '!bg-transparent'}`}
                        >
                            <Icon name="info" />
                            <span className="sm:hidden">Tentang</span>
                            <span className="hidden sm:inline">Tentang aplikasi ini</span>
                        </Link>
                        <div className="ml-auto">
                            {viewer.mode === 'anonymous' ? (
                                <Link href={nav.login} className={`${secondaryClass} !bg-transparent !px-3 sm:!px-4`}>
                                    <Icon name="login" />
                                    <span className="sr-only sm:not-sr-only">Masuk</span>
                                </Link>
                            ) : (
                                <Action url={nav.logout} className={`${secondaryClass} !bg-transparent !px-3 sm:!px-4`}>
                                    <Icon name="login" />
                                    <span className="sr-only sm:not-sr-only">Keluar</span>
                                </Action>
                            )}
                        </div>
                    </nav>
                </header>
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
                                    {[
                                        ['Pelajaran selesai', completedLessons, 'check'],
                                        ['Materi dipelajari', paths.length, 'book'],
                                        ['Hari aktif minggu ini', `${activity?.active_days_7 ?? 0}/7`, 'calendar'],
                                    ].map(([label, value, icon]) => (
                                        <Card key={label} className="flex flex-col !p-3 sm:!p-6">
                                            <Icon
                                                name={icon}
                                                className={`mb-4 size-5 sm:size-6 ${icon === 'book' ? 'text-primary' : 'text-accent'}`}
                                            />
                                            <p className="mb-3 flex-1 text-[11px] leading-5 text-foreground/50 sm:text-sm">
                                                {label}
                                            </p>
                                            <p className="font-display text-3xl font-bold tabular-nums sm:text-4xl">
                                                {value}
                                            </p>
                                        </Card>
                                    ))}
                                </div>
                            </section>
                            <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] sm:gap-6">
                                <div className="space-y-6">
                                    {activity && (
                                        <Card>
                                            <div className="flex flex-wrap items-center justify-between gap-3">
                                                <h2 className="font-display text-xl font-bold">Sedikit setiap hari</h2>
                                                <span className="rounded-full bg-primary/15 px-3 py-2 text-xs font-bold text-accent">
                                                    {activity.active_days_7 ?? 0} hari aktif
                                                </span>
                                            </div>
                                            <p className="mt-2 text-xs leading-6 text-foreground/50">
                                                Aktivitas belajar dalam tujuh hari terakhir.
                                            </p>
                                            <ol
                                                className="mt-7 grid grid-cols-7 gap-1 sm:gap-3"
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
                                                                className={`mx-auto mt-2 grid aspect-square max-w-12 place-items-center rounded-full text-sm font-bold ${active ? 'bg-accent text-background' : 'bg-background text-foreground/25'}`}
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
