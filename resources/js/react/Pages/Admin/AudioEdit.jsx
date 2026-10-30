import { useEffect } from 'react';
import { Link, useForm } from '../../routing';
import { PageMeta, Button } from '../../Components/ui';
import { Card, Heading, Field, Back, Errors, secondaryClass } from '../../Components/forms';
import AudioStatus from '../../Components/AudioStatus';
export default function AudioEdit(props) {
    return <AudioEditor key={props.action} {...props} />;
}

function AudioEditor({ title, text, action, configured, audio, backUrl, meta, targets = [] }) {
    const form = useForm({
        spoken_text: text,
        language: audio?.language ?? 'id',
        speed: audio?.speed ?? 150,
        version: audio?.version ?? '',
        audio_upload: null,
        action: 'save',
    });
    useEffect(() => {
        form.setData('version', audio?.version ?? '');
    }, [audio?.version]);
    return (
        <div className="mx-auto max-w-5xl">
            <PageMeta {...meta} />
            <Back href={backUrl}>Kembali ke materi</Back>
            <Heading title="Siapkan audio">{title}</Heading>
            {targets.length > 1 && (
                <details className="mb-5 rounded-2xl bg-surface p-4">
                    <summary className="cursor-pointer font-semibold">Pilih audio materi atau soal</summary>
                    <nav aria-label="Audio modul" className="mt-4 flex max-h-64 flex-wrap gap-2 overflow-y-auto">
                        {targets.map((target) => (
                            <Link key={target.url} href={target.url} className={secondaryClass} aria-current={target.active ? 'page' : undefined}>
                                {target.title}
                            </Link>
                        ))}
                    </nav>
                </details>
            )}
            {audio && <AudioStatus key={audio.version} audio={audio} />}
            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        const chosen = e.nativeEvent.submitter?.value ?? 'save';
                        if (form.processing) return;
                        form.transform((data) => ({ ...data, action: chosen }));
                        form.post(action, {
                            forceFormData: true,
                            preserveScroll: true,
                        });
                    }}
                    className="space-y-5"
                >
                    <Errors errors={form.errors} />
                    <p className="text-sm leading-6 text-foreground/65">
                        Tombol Generate audio otomatis menyimpan narasi. Setelah selesai, dengarkan dan setujui hasilnya.
                        Simpan narasi tanpa perubahan tetap mempertahankan audio; perubahan teks, bahasa, atau tempo memerlukan audio baru.
                    </p>
                    <Field
                        as="textarea"
                        label="Teks yang dibacakan"
                        rows={10}
                        required
                        maxLength={16000}
                        value={form.data.spoken_text}
                        onChange={(e) => form.setData('spoken_text', e.target.value)}
                        error={form.errors.spoken_text}
                        hint="Maksimal 16.000 karakter. Narasi panjang diproses bertahap; semua hasil perlu didengarkan dan diperiksa."
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Field
                            as="select"
                            label="Bahasa"
                            value={form.data.language}
                            onChange={(e) => form.setData('language', e.target.value)}
                        >
                            <option value="id">Indonesia</option>
                            <option value="en">Inggris</option>
                            <option value="ban">Bali · unggah rekaman</option>
                        </Field>
                        <Field
                            label="Kecepatan (150 = normal)"
                            type="number"
                            min={100}
                            max={220}
                            required
                            value={form.data.speed}
                            onChange={(e) => form.setData('speed', e.target.value)}
                            error={form.errors.speed}
                        />
                    </div>
                    <Field
                        label="Unggah rekaman pengganti"
                        type="file"
                        accept="audio/wav,audio/mpeg,audio/ogg"
                        onChange={(e) => form.setData('audio_upload', e.target.files[0] ?? null)}
                        error={form.errors.audio_upload}
                        hint="WAV, MP3, OGG; maksimal 10 MB. Gunakan rekaman dengan persetujuan pemilik suara."
                    />
                    <div className="flex flex-wrap gap-3">
                        <button
                            type="submit"
                            name="action"
                            value="save"
                            disabled={form.processing}
                            className={secondaryClass}
                        >
                            Simpan narasi
                        </button>
                        <Button type="submit" name="action" value="generate" disabled={form.processing}>
                            Generate audio
                        </Button>
                        <button
                            type="submit"
                            name="action"
                            value="upload"
                            disabled={form.processing}
                            className={secondaryClass}
                        >
                            Gunakan rekaman
                        </button>
                    </div>
                    {!configured && (
                        <p className="text-xs leading-6 text-foreground/50">
                            Mesin TTS belum dikonfigurasi. Unggah rekaman tetap tersedia.
                        </p>
                    )}
                </form>
            </Card>
        </div>
    );
}
