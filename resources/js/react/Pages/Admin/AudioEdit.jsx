import { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { PageMeta, Button } from '../../Components/ui';
import { Card, Heading, Field, Back, Errors, secondaryClass } from '../../Components/forms';
import AudioStatus from '../../Components/AudioStatus';
export default function AudioEdit({ title, text, action, configured, audio, backUrl, meta }) {
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
            {audio && <AudioStatus key={audio.version} audio={audio} />}
            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        const chosen = e.nativeEvent.submitter?.value ?? 'save';
                        if (!form.processing)
                            form.transform((data) => ({ ...data, action: chosen })).post(action, {
                                forceFormData: true,
                                preserveScroll: true,
                            });
                    }}
                    className="space-y-5"
                >
                    <Errors errors={form.errors} />
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
                            Buat audio offline
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
