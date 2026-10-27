import { useEffect, useRef, useState } from 'react';
import { Button } from './ui';

export default function AvatarCrop({ file, onApply, onCancel }) {
    const canvas = useRef(null);
    const mounted = useRef(false);
    useEffect(() => {
        mounted.current = true;
        return () => {
            mounted.current = false;
        };
    }, []);
    const [image, setImage] = useState(null);
    const [zoom, setZoom] = useState(1);
    const [position, setPosition] = useState({ x: 0, y: 0 });
    const [error, setError] = useState('');
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            setError('Gunakan JPG, PNG, atau WEBP berukuran maksimal 2 MB.');
            return;
        }
        let disposed = false;
        const url = URL.createObjectURL(file);
        const source = new Image();
        source.onload = () => {
            if (disposed) return;
            if (source.width > 2048 || source.height > 2048) setError('Ukuran gambar maksimal 2048 × 2048 piksel.');
            else setImage(source);
        };
        source.onerror = () => {
            if (!disposed) setError('Gambar tidak dapat dibaca. Pilih gambar lain.');
        };
        source.src = url;
        return () => {
            disposed = true;
            URL.revokeObjectURL(url);
        };
    }, [file]);

    useEffect(() => {
        if (!image || !canvas.current) return;
        const size = Math.min(image.width, image.height) / zoom;
        const x = ((image.width - size) * (position.x + 1)) / 2;
        const y = ((image.height - size) * (position.y + 1)) / 2;
        const context = canvas.current.getContext('2d');
        context.clearRect(0, 0, 512, 512);
        context.drawImage(image, x, y, size, size, 0, 0, 512, 512);
    }, [image, zoom, position]);

    const apply = () => {
        setSaving(true);
        canvas.current.toBlob(
            (blob) => {
                if (!mounted.current) return;
                setSaving(false);
                if (!blob) {
                    setError('Gambar belum dapat disiapkan. Coba gambar lain.');
                    return;
                }
                onApply(new File([blob], 'avatar.webp', { type: 'image/webp' }));
            },
            'image/webp',
            0.9,
        );
    };

    return (
        <section className="mt-5 rounded-2xl bg-background p-4" aria-label="Atur tampilan avatar">
            <h3 className="font-bold">Atur tampilan avatar</h3>
            <p className="mt-2 text-xs leading-6 text-foreground/55">Bagian di dalam lingkaran yang akan disimpan.</p>
            {error ? (
                <p role="alert" className="mt-3 text-sm text-danger">
                    {error}
                </p>
            ) : (
                <>
                    <canvas
                        ref={canvas}
                        width="512"
                        height="512"
                        aria-label="Preview potongan avatar"
                        className="mx-auto my-5 aspect-square w-40 max-w-full rounded-full bg-surface"
                    />
                    {[
                        ['Perbesar', zoom, 1, 3, setZoom],
                        ['Geser horizontal', position.x, -1, 1, (x) => setPosition((value) => ({ ...value, x }))],
                        ['Geser vertikal', position.y, -1, 1, (y) => setPosition((value) => ({ ...value, y }))],
                    ].map(([label, value, min, max, change]) => (
                        <label key={label} className="mt-3 block text-xs font-semibold">
                            {label}
                            <input
                                type="range"
                                min={min}
                                max={max}
                                step=".01"
                                value={value}
                                onChange={(event) => change(Number(event.target.value))}
                                className="mt-2 h-8 w-full accent-[var(--color-accent)]"
                            />
                        </label>
                    ))}
                    <Button type="button" className="mt-4 w-full" disabled={!image || saving} onClick={apply}>
                        Gunakan potongan ini
                    </Button>
                </>
            )}
            <button
                type="button"
                disabled={saving}
                className="mt-2 min-h-11 text-sm font-bold text-accent"
                onClick={onCancel}
            >
                Batalkan unggahan
            </button>
        </section>
    );
}
