import { usePage } from '../routing';
import { buttonClass } from './ui';

export default function PageLoadError() {
    const {
        url,
        props: { navigation },
    } = usePage();
    return (
        <section className="mx-auto max-w-xl rounded-card bg-surface p-6 sm:p-9" role="alert">
            <h1 className="font-display text-2xl font-bold">Halaman belum dapat dimuat</h1>
            <p className="mt-4 text-sm leading-8 text-foreground/60">
                Periksa koneksimu lalu coba lagi. Bila aplikasi baru diperbarui, muat ulang untuk mengambil versi
                terbaru. Data yang sudah tersimpan tetap aman.
            </p>
            <div className="mt-6 flex flex-wrap gap-4">
                <a className={buttonClass} href={url}>
                    Muat ulang halaman
                </a>
                <a href={navigation.home} className="inline-flex min-h-11 items-center font-bold text-accent">
                    Kembali ke beranda
                </a>
            </div>
        </section>
    );
}
