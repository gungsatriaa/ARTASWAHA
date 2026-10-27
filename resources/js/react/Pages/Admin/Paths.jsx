import { Link, usePage } from '@inertiajs/react';
import { PageMeta, Pagination, buttonClass, EmptyState } from '../../Components/ui';
import { Card, Heading, secondaryClass } from '../../Components/forms';
export default function Paths({ paths, meta }) {
    const { links } = usePage().props;
    const entries = Array.isArray(paths) ? paths : paths.data;
    return (
        <>
            <PageMeta {...meta} />
            <Heading
                title="Jalur belajar"
                action={
                    <Link href={links['admin.learning-paths.create']} className={buttonClass}>
                        ＋ Buat jalur
                    </Link>
                }
            >
                Susun kategori, urutan, dan ketersediaan materi pembelajaran.
            </Heading>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {entries.map((path) => (
                    <Card key={path.id}>
                        <p className="text-xs font-semibold text-accent">
                            {path.group === 'aksara' ? 'Aksara Bali' : 'Paribasa Bali'}
                        </p>
                        <h2 className="mt-3 break-words font-display text-xl font-bold">{path.title}</h2>
                        <p className="mt-3 text-sm leading-7 text-foreground/55">{path.description}</p>
                        <p className="mt-4 text-xs text-foreground/45">
                            {path.count} modul · Urutan {path.sort_order} · {path.is_published ? 'Tampil' : 'Draf'}
                        </p>
                        <div className="mt-5 flex flex-wrap gap-2">
                            <Link className={secondaryClass} href={path.editUrl}>
                                Ubah jalur
                            </Link>
                            <Link
                                className={secondaryClass}
                                href={`${links['admin.lessons.index']}?learning_path_id=${path.id}`}
                            >
                                Lihat modul
                            </Link>
                        </div>
                    </Card>
                ))}
            </div>
            {!entries.length && (
                <EmptyState title="Belum ada jalur belajar">Buat jalur pertama untuk mengelompokkan modul.</EmptyState>
            )}
            {!Array.isArray(paths) && <Pagination {...paths} />}
        </>
    );
}
