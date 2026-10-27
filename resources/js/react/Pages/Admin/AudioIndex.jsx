import { Link, router, useForm, usePage } from '@inertiajs/react';
import { PageMeta, Pagination, EmptyState, Button } from '../../Components/ui';
import { Card, Heading, Field, Status, secondaryClass } from '../../Components/forms';
export default function AudioIndex({ items, type, configured, filters, meta }) {
    const { links } = usePage().props;
    const form = useForm({ type, q: filters.q ?? '', status: filters.status ?? '' });
    return (
        <>
            <PageMeta {...meta} />
            <Heading title="Audio pembelajaran">
                Siapkan narasi, dengarkan hasilnya, lalu setujui sebelum digunakan pelajar. Rekaman pengajar tetap dapat
                dipakai tanpa mesin TTS.
            </Heading>
            <p className="mb-5 text-xs text-foreground/50">
                {configured
                    ? 'Konfigurasi TTS offline siap.'
                    : 'TTS belum dikonfigurasi. Unggah rekaman tetap tersedia.'}
            </p>
            <nav aria-label="Sumber audio" className="mb-5 flex flex-wrap gap-2">
                {[
                    ['records', 'Audio tersimpan'],
                    ['modules', 'Pilih modul'],
                    ['questions', 'Pilih soal kuis'],
                ].map(([value, label]) => (
                    <Link
                        href={`${links['admin.audio.index']}?type=${value}`}
                        key={value}
                        className={`${secondaryClass} ${type === value ? '!bg-primary/20 !text-accent' : ''}`}
                    >
                        {label}
                    </Link>
                ))}
            </nav>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    router.get(links['admin.audio.index'], form.data, { preserveState: true, replace: true });
                }}
                className="mb-6 flex flex-wrap items-end gap-3"
            >
                {type === 'records' ? (
                    <Field
                        as="select"
                        label="Status"
                        value={form.data.status}
                        onChange={(e) => form.setData('status', e.target.value)}
                    >
                        <option value="">Semua</option>
                        {['draft', 'queued', 'processing', 'review', 'ready', 'failed', 'stale', 'unavailable'].map(
                            (status) => (
                                <option key={status} value={status}>
                                    {status}
                                </option>
                            ),
                        )}
                    </Field>
                ) : (
                    <Field
                        label="Cari judul/pertanyaan"
                        type="search"
                        maxLength={100}
                        value={form.data.q}
                        onChange={(e) => form.setData('q', e.target.value)}
                    />
                )}
                <Button type="submit">Terapkan</Button>
            </form>
            <div className="space-y-3">
                {items.data.map((item) => (
                    <Card key={item.id}>
                        <div className="flex flex-wrap items-center justify-between gap-4">
                            <div className="min-w-0 max-w-2xl">
                                <h2 className="break-words font-display text-lg font-bold">{item.title}</h2>
                                {item.status && (
                                    <div className="mt-3">
                                        <Status value={item.status} />
                                    </div>
                                )}
                            </div>
                            {item.url && (
                                <Link href={item.url} className={secondaryClass}>
                                    Kelola audio
                                </Link>
                            )}
                        </div>
                    </Card>
                ))}
            </div>
            {!items.data.length && (
                <EmptyState
                    title="Belum ada data pada bagian ini"
                    action={
                        <Link href={`${links['admin.audio.index']}?type=modules`} className={secondaryClass}>
                            Pilih modul untuk menyiapkan audio
                        </Link>
                    }
                >
                    Pilih materi lalu siapkan narasinya.
                </EmptyState>
            )}
            <Pagination {...items} />
        </>
    );
}
