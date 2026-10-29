import { useForm, usePage } from '../../routing';
import { PageMeta, Button } from '../../Components/ui';
import { Back, Card, Heading, Field, Check, Errors } from '../../Components/forms';
export default function PathForm({ path, meta }) {
    const { links } = usePage().props;
    const form = useForm({
        group: path.group ?? 'paribasa',
        title: path.title ?? '',
        slug: path.slug ?? '',
        description: path.description ?? '',
        sort_order: path.sort_order ?? 0,
        is_published: path.is_published ?? false,
    });
    return (
        <div className="mx-auto max-w-4xl">
            <PageMeta {...meta} />
            <Back href={links['admin.learning-paths.index']}>Kembali ke jalur belajar</Back>
            <Heading title={path.id ? 'Ubah jalur' : 'Jalur belajar baru'}>
                Jalur mengelompokkan modul dan menentukan urutan belajar.
            </Heading>
            <Card>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        if (!form.processing) form[path.id ? 'put' : 'post'](path.action);
                    }}
                    className="space-y-5"
                >
                    <Errors errors={form.errors} />
                    <Field
                        as="select"
                        label="Kategori"
                        value={form.data.group}
                        onChange={(e) => form.setData('group', e.target.value)}
                        error={form.errors.group}
                    >
                        <option value="paribasa">Paribasa Bali</option>
                        <option value="aksara">Aksara Bali</option>
                    </Field>
                    {[
                        ['title', 'Nama jalur', 'text', 120],
                        ['slug', 'Slug URL (opsional)', 'text', 150],
                    ].map(([key, label, type, max]) => (
                        <Field
                            key={key}
                            label={label}
                            type={type}
                            required={key === 'title'}
                            maxLength={max}
                            value={form.data[key]}
                            onChange={(e) => form.setData(key, e.target.value)}
                            error={form.errors[key]}
                        />
                    ))}
                    <Field
                        as="textarea"
                        rows={4}
                        required
                        maxLength={1000}
                        label="Deskripsi"
                        value={form.data.description}
                        onChange={(e) => form.setData('description', e.target.value)}
                        error={form.errors.description}
                    />
                    <Field
                        type="number"
                        min={0}
                        max={65535}
                        required
                        label="Urutan"
                        value={form.data.sort_order}
                        onChange={(e) => form.setData('sort_order', e.target.value)}
                        error={form.errors.sort_order}
                    />
                    <Check
                        checked={form.data.is_published}
                        onChange={(e) => form.setData('is_published', e.target.checked)}
                    >
                        Tampilkan jalur di situs
                    </Check>
                    <Button type="submit" disabled={form.processing}>
                        Simpan jalur belajar
                    </Button>
                </form>
            </Card>
        </div>
    );
}
