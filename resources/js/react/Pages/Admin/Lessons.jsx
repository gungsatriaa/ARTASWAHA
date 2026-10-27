import { Link, router, useForm, usePage } from '@inertiajs/react';
import { PageMeta, Pagination, buttonClass, EmptyState } from '../../Components/ui';
import { Card, Heading, Field, Action, Status, secondaryClass } from '../../Components/forms';
export default function Lessons({ lessons, paths, filters, meta }) {
    const { links } = usePage().props;
    const form = useForm({ learning_path_id: filters.learning_path_id ?? '', status: filters.status ?? '' });
    return (
        <>
            <PageMeta {...meta} />
            <Heading
                title="Modul pembelajaran"
                action={
                    <Link href={links['admin.lessons.create']} className={buttonClass}>
                        ＋ Tambah modul
                    </Link>
                }
            >
                Tulis, tinjau, dan terbitkan materi yang siap dipelajari.
            </Heading>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    router.get(links['admin.lessons.index'], form.data, { preserveState: true, replace: true });
                }}
                className="mb-6 grid items-end gap-3 sm:grid-cols-[1fr_1fr_auto]"
            >
                <Field
                    as="select"
                    label="Jalur belajar"
                    value={form.data.learning_path_id}
                    onChange={(e) => form.setData('learning_path_id', e.target.value)}
                >
                    <option value="">Semua jalur</option>
                    {paths.map((path) => (
                        <option key={path.id} value={path.id}>
                            {path.title}
                        </option>
                    ))}
                </Field>
                <Field
                    as="select"
                    label="Status"
                    value={form.data.status}
                    onChange={(e) => form.setData('status', e.target.value)}
                >
                    <option value="">Semua status</option>
                    <option value="draft">Draf</option>
                    <option value="published">Terbit</option>
                    <option value="archived">Arsip</option>
                </Field>
                <button className={secondaryClass}>Terapkan</button>
            </form>
            <div className="space-y-4">
                {lessons.data.map((lesson) => (
                    <Card key={lesson.id}>
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div className="min-w-0 max-w-2xl">
                                <h2 className="break-words font-display text-xl font-bold">{lesson.title}</h2>
                                <p className="mt-3 text-sm leading-7 text-foreground/55">{lesson.summary}</p>
                            </div>
                            <Status
                                value={
                                    lesson.is_published && lesson.reviewed_at
                                        ? 'published'
                                        : lesson.reviewed_at
                                          ? 'archived'
                                          : 'draft'
                                }
                            />
                        </div>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <Link className={secondaryClass} href={lesson.editUrl}>
                                Ubah
                            </Link>
                            <Link className={secondaryClass} href={lesson.previewUrl}>
                                Preview & tinjau
                            </Link>
                            <Link className={secondaryClass} href={lesson.audioUrl}>
                                Siapkan audio
                            </Link>
                            {lesson.is_published && (
                                <Action url={lesson.archiveUrl} confirm="Arsipkan modul ini?">
                                    Arsipkan
                                </Action>
                            )}
                        </div>
                    </Card>
                ))}
            </div>
            {!lessons.data.length && (
                <EmptyState title="Belum ada modul yang sesuai">
                    Tambahkan modul atau ubah filter untuk melihat materi.
                </EmptyState>
            )}
            <Pagination {...lessons} />
        </>
    );
}
