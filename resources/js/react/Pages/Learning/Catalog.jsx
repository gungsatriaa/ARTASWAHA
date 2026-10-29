import { useEffect, useState } from 'react';
import { router, usePage } from '../../routing';
import * as m from 'motion/react-m';
import Icon from '../../Components/Icon';
import NavigationLink from '../../Components/NavigationLink';
import { Button, CategoryIcon, EmptyState, PageMeta, Pagination } from '../../Components/ui';

const groups = [
    { value: '', label: 'Semua materi' },
    { value: 'paribasa', label: 'Paribasa Bali' },
    { value: 'aksara', label: 'Aksara Bali' },
];

export default function Catalog({ paths, filters, meta }) {
    const { navigation, viewer, brand } = usePage().props;
    const [search, setSearch] = useState(filters.q);
    const [loading, setLoading] = useState(false);
    useEffect(() => setSearch(filters.q), [filters.q]);
    const visit = (group) =>
        router.get(
            navigation.learning,
            { ...(search.trim() ? { q: search.trim() } : {}), ...(group ? { group } : {}) },
            {
                only: ['paths', 'filters'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => setLoading(true),
                onFinish: () => setLoading(false),
            },
        );
    return (
        <>
            <PageMeta {...meta} />
            <header className="relative mb-7 flex items-center justify-between gap-4 overflow-hidden rounded-card bg-surface p-6 sm:p-8">
                <div className="relative z-10 max-w-2xl">
                    <p className="text-sm font-semibold text-accent">
                        {viewer ? `Rahajeng, ${viewer.name}` : 'Petualangan belajarmu'}
                    </p>
                    <h1 className="mt-2 font-display text-3xl font-bold leading-tight tracking-tight sm:text-4xl">
                        Mau belajar apa hari ini?
                    </h1>
                    <p className="mt-3 max-w-lg text-sm leading-7 text-foreground/60 sm:text-base">
                        Temukan cerita, bahasa, dan aksara Bali. Mulai dari hal yang membuatmu penasaran.
                    </p>
                </div>
                <img
                    src={brand.mascot}
                    alt=""
                    width="160"
                    height="160"
                    className="hidden size-36 shrink-0 object-contain lg:block"
                />
            </header>
            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    visit(filters.group);
                }}
                className="flex gap-2 sm:gap-3"
                role="search"
                aria-label="Cari materi belajar"
            >
                <div className="relative min-w-0 flex-1">
                    <span className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-foreground/40">
                        <Icon name="search" />
                    </span>
                    <label className="sr-only" htmlFor="learning-search">
                        Nama materi
                    </label>
                    <input
                        id="learning-search"
                        type="search"
                        value={search}
                        maxLength={100}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cari materi yang ingin kamu pelajari"
                        className="min-h-12 w-full rounded-full bg-surface py-3 pl-12 pr-4 text-base text-foreground placeholder:text-foreground/35 focus:outline-2 focus:outline-accent"
                    />
                </div>
                <Button type="submit" disabled={loading}>
                    Cari
                </Button>
            </form>
            <div className="mt-5 flex flex-wrap gap-2" role="group" aria-label="Jenis materi">
                {groups.map((group) => (
                    <button
                        key={group.value}
                        type="button"
                        aria-pressed={filters.group === group.value}
                        disabled={loading}
                        onClick={() => visit(group.value)}
                        className={`min-h-11 rounded-full px-4 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-accent disabled:opacity-60 ${filters.group === group.value ? 'bg-primary text-white' : 'bg-surface text-foreground/60 hover:text-accent'}`}
                    >
                        {group.label}
                    </button>
                ))}
            </div>
            <div className="mt-7 flex items-center justify-between gap-4 text-xs text-foreground/45">
                <p role="status">{paths.total} materi ditemukan</p>
                <p>Pelajari sesuai waktumu</p>
            </div>
            <div aria-busy={loading} className="mt-5 space-y-8">
                {paths.data.length === 0 ? (
                    <EmptyState
                        title="Materi belum ditemukan"
                        action={
                            <button
                                type="button"
                                onClick={() => {
                                    setSearch('');
                                    router.get(navigation.learning);
                                }}
                                className="min-h-11 rounded-full px-5 font-semibold text-accent"
                            >
                                Lihat semua materi
                            </button>
                        }
                    >
                        Coba nama yang lebih singkat atau jelajahi kategori lainnya.
                    </EmptyState>
                ) : (
                    groups
                        .filter((group) => group.value)
                        .map((group) => {
                            const entries = paths.data.filter((path) => path.group === group.value);
                            if (!entries.length) return null;
                            return (
                                <section key={group.value} aria-labelledby={`group-${group.value}`}>
                                    <h2 id={`group-${group.value}`} className="mb-3 font-display text-xl font-bold">
                                        {group.label}
                                    </h2>
                                    <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        {entries.map((path) => (
                                            <li key={path.id}>
                                                <m.div initial={false} whileHover={{ y: -3 }} className="h-full">
                                                    <NavigationLink
                                                        page="Learning/Path"
                                                        href={path.url}
                                                        className="group flex h-full flex-col rounded-card bg-surface p-5 transition-colors hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent sm:p-6"
                                                    >
                                                        <div className="flex items-center justify-between gap-4">
                                                            <CategoryIcon group={path.group} title={path.title} />
                                                            <Icon
                                                                name="arrow"
                                                                className="size-5 text-foreground/30 transition-colors group-hover:text-accent"
                                                            />
                                                        </div>
                                                        <h3 className="mt-5 break-words font-display text-lg font-bold leading-snug group-hover:text-accent">
                                                            {path.title}
                                                        </h3>
                                                        {path.description && (
                                                            <p className="mt-2 line-clamp-3 text-sm leading-6 text-foreground/55">
                                                                {path.description}
                                                            </p>
                                                        )}
                                                        <p className="mt-auto pt-5 text-xs font-semibold text-accent">
                                                            {path.lessonsCount > 0
                                                                ? `${path.lessonsCount} pelajaran`
                                                                : 'Pelajaran sedang disiapkan'}
                                                        </p>
                                                    </NavigationLink>
                                                </m.div>
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            );
                        })
                )}
            </div>
            <Pagination {...paths} only={['paths', 'filters']} />
        </>
    );
}
