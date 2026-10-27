import { Link, router, useForm, usePage } from '@inertiajs/react';
import { PageMeta, Pagination, Button } from '../../Components/ui';
import { Card, Heading, Field, Action, Status, Stat, secondaryClass } from '../../Components/forms';
import AiStatistics from '../../Components/AiStatistics';
export default function Ai({
    tab,
    start,
    end,
    snapshot,
    statuses,
    profiles,
    drafts,
    reports,
    paths,
    aiConfigured,
    ttsConfigured,
    pendingJobs,
    audioCounts,
    filters,
    meta,
}) {
    const { links } = usePage().props;
    const form = useForm({
        tab,
        start,
        end,
        role: filters.role ?? '',
        status: filters.status ?? '',
        mode: filters.mode ?? '',
        learning_path_id: filters.learning_path_id ?? '',
    });
    const entries = tab === 'interviews' ? profiles : tab === 'drafts' ? drafts : reports;
    return (
        <>
            <PageMeta {...meta} />
            <Heading
                title="Pusat AI"
                action={
                    <Link href={links['admin.audio.index']} className={secondaryClass}>
                        Audio pembelajaran →
                    </Link>
                }
            >
                Kelola analisis interview, interpretasi statistik, dan draf kuis. Membuka halaman ini tidak menjalankan
                AI.
            </Heading>
            <nav aria-label="Bagian Pusat AI" className="mb-6 flex flex-wrap gap-2">
                {[
                    ['overview', 'Statistik AI'],
                    ['interviews', 'Analisis interview'],
                    ['drafts', 'Draf kuis AI'],
                    ['services', 'Status layanan'],
                ].map(([value, label]) => (
                    <Link
                        key={value}
                        href={`${links['admin.ai.index']}?tab=${value}&start=${start}&end=${end}`}
                        className={`${secondaryClass} ${tab === value ? '!bg-primary/20 !text-accent' : ''}`}
                    >
                        {label}
                    </Link>
                ))}
            </nav>
            {['overview', 'interviews'].includes(tab) && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        router.get(links['admin.ai.index'], form.data, { preserveState: true, replace: true });
                    }}
                    className="mb-6 grid items-end gap-3 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <Field
                        label="Mulai"
                        type="date"
                        required
                        value={form.data.start}
                        onChange={(e) => form.setData('start', e.target.value)}
                    />
                    <Field
                        label="Sampai"
                        type="date"
                        required
                        value={form.data.end}
                        onChange={(e) => form.setData('end', e.target.value)}
                    />
                    {tab === 'interviews' && (
                        <>
                            <Field
                                as="select"
                                label="Peran"
                                value={form.data.role}
                                onChange={(e) => form.setData('role', e.target.value)}
                            >
                                <option value="">Semua</option>
                                {[
                                    ['murid', 'Murid'],
                                    ['guru', 'Guru'],
                                    ['pendatang', 'Pendatang'],
                                    ['masyarakat', 'Masyarakat umum'],
                                ].map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </Field>
                            <Field
                                as="select"
                                label="Status"
                                value={form.data.status}
                                onChange={(e) => form.setData('status', e.target.value)}
                            >
                                <option value="">Semua</option>
                                {['queued', 'processing', 'completed', 'failed', 'unavailable', 'not_requested'].map(
                                    (value) => (
                                        <option key={value} value={value}>
                                            {value}
                                        </option>
                                    ),
                                )}
                            </Field>
                            <Field
                                as="select"
                                label="Profil"
                                value={form.data.mode}
                                onChange={(e) => form.setData('mode', e.target.value)}
                            >
                                <option value="">Semua</option>
                                <option value="account">Akun</option>
                                <option value="anonymous">Anonim</option>
                            </Field>
                            <Field
                                as="select"
                                label="Materi utama"
                                value={form.data.learning_path_id}
                                onChange={(e) => form.setData('learning_path_id', e.target.value)}
                            >
                                <option value="">Semua</option>
                                {paths.map((path) => (
                                    <option value={path.id} key={path.id}>
                                        {path.title}
                                    </option>
                                ))}
                            </Field>
                        </>
                    )}
                    <Button type="submit">Terapkan</Button>
                </form>
            )}
            {tab === 'overview' && (
                <>
                    <AiStatistics snapshot={snapshot} />
                    <div className="my-6 grid gap-5 xl:grid-cols-2">
                        <Card>
                            <h2 className="font-display text-xl font-bold">Status analisis interview</h2>
                            <ul className="mt-5 space-y-3">
                                {statuses.map((item, index) => (
                                    <li key={index} className="flex justify-between gap-3">
                                        <Status value={item.analysis_status} />
                                        <strong>{item.total}</strong>
                                    </li>
                                ))}
                            </ul>
                            {!statuses.length && (
                                <p className="mt-4 text-sm text-foreground/55">Belum ada interview.</p>
                            )}
                        </Card>
                        <Card>
                            <h2 className="font-display text-xl font-bold">Materi utama dipilih</h2>
                            <p className="mt-2 text-xs leading-6 text-foreground/50">
                                Materi pertama dari pilihan interview, bukan penilaian kemampuan.
                            </p>
                            <ul className="mt-4 space-y-3">
                                {(snapshot.primary_materials ?? []).map((item, index) => (
                                    <li key={index} className="flex justify-between gap-3 text-sm">
                                        <span>{item.title}</span>
                                        <strong>{item.total}</strong>
                                    </li>
                                ))}
                            </ul>
                        </Card>
                    </div>
                    <Card>
                        <h2 className="font-display text-xl font-bold">Interpretasi statistik oleh AI</h2>
                        <p className="mt-3 text-sm leading-7 text-foreground/55">
                            Ringkasan dan saran berdasarkan snapshot agregat. Identitas dan jawaban pribadi tidak
                            dikirim untuk laporan ini.
                        </p>
                        <div className="mt-4">
                            <Action url={links['admin.ai.reports.store']} data={{ start, end }}>
                                Buat ringkasan AI
                            </Action>
                        </div>
                        {!aiConfigured && (
                            <p className="mt-3 text-xs text-foreground/50">
                                AI belum dikonfigurasi. Statistik faktual tetap tersedia.
                            </p>
                        )}
                    </Card>
                </>
            )}
            {tab === 'services' ? (
                <div className="grid gap-5 xl:grid-cols-2">
                    <Card>
                        <h2 className="font-display text-xl font-bold">Analisis & ringkasan AI</h2>
                        <p className="mt-4 text-sm">{aiConfigured ? 'Konfigurasi siap' : 'Belum dikonfigurasi'}</p>
                        <p className="mt-3 text-xs leading-7 text-foreground/45">
                            Koneksi model belum diuji oleh halaman ini. Lihat status pekerjaan untuk mengetahui hasil
                            pemrosesan terakhir.
                        </p>
                    </Card>
                    <Card>
                        <h2 className="font-display text-xl font-bold">TTS offline</h2>
                        <p className="mt-4 text-sm">
                            {ttsConfigured ? 'Konfigurasi TTS offline siap' : 'Mesin belum dikonfigurasi'}
                        </p>
                        <p className="mt-3 text-xs leading-7 text-foreground/50">
                            Unggah rekaman tetap tersedia. Untuk Bahasa Bali, gunakan rekaman yang diperiksa pengajar.
                        </p>
                    </Card>
                    <Stat
                        label="Pekerjaan di antrean lokal"
                        value={pendingJobs}
                        note="Jumlah antrean bukan bukti worker sedang aktif."
                    />
                    <Card>
                        <h2 className="font-display text-xl font-bold">Audio perlu tindakan</h2>
                        <p className="mt-4 text-sm leading-8 text-foreground/60">
                            Perlu ditinjau: {audioCounts.review ?? 0} · gagal: {audioCounts.failed ?? 0} · kedaluwarsa:{' '}
                            {audioCounts.stale ?? 0}
                        </p>
                    </Card>
                </div>
            ) : (
                entries && (
                    <section className="mt-6">
                        <h2 className="mb-4 font-display text-xl font-bold">
                            {tab === 'interviews'
                                ? 'Interview tersimpan'
                                : tab === 'drafts'
                                  ? 'Draf kuis AI'
                                  : 'Laporan AI'}
                        </h2>
                        <div className="space-y-3">
                            {entries.data.map((record) => (
                                <Link
                                    href={record.url}
                                    key={record.id}
                                    className="flex flex-wrap items-center justify-between gap-4 rounded-card bg-surface p-5"
                                >
                                    <span className="min-w-0">
                                        <span className="block font-bold">
                                            {tab === 'interviews'
                                                ? `Pelajar #${record.id} · ${record.mode === 'account' ? 'Akun' : 'Anonim'}`
                                                : tab === 'drafts'
                                                  ? `Draf #${record.id}`
                                                  : `Laporan #${record.id}`}
                                        </span>
                                        <span className="mt-2 block text-xs text-foreground/45">
                                            {tab === 'interviews'
                                                ? record.role
                                                : record.period_start
                                                  ? `${record.period_start.slice(0, 10)} – ${record.period_end.slice(0, 10)}`
                                                  : record.created_at?.slice(0, 10)}
                                        </span>
                                    </span>
                                    <Status value={record.analysis_status ?? record.status} />
                                </Link>
                            ))}
                        </div>
                        {!entries.data.length && (
                            <p className="rounded-card bg-surface p-6 text-sm text-foreground/55">
                                Belum ada data yang sesuai filter.
                            </p>
                        )}
                        <Pagination {...entries} />
                    </section>
                )
            )}
        </>
    );
}
