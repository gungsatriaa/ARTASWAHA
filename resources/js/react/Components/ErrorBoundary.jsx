import { Component } from 'react';
import { failBootLoader } from '../boot-loader';

export default class ErrorBoundary extends Component {
    state = { failed: false };

    static getDerivedStateFromError() {
        return { failed: true };
    }

    componentDidCatch() {
        failBootLoader();
    }

    render() {
        if (!this.state.failed) return this.props.children;
        return (
            <main className="mx-auto max-w-xl px-5 py-16">
                <h1 className="font-display text-2xl font-bold">Halaman belum dapat ditampilkan</h1>
                <p className="mt-3 text-foreground/65">
                    Periksa koneksimu, lalu buka halaman kembali. Data yang sudah tersimpan tetap aman.
                </p>
                <a
                    href=""
                    className="mt-6 inline-flex min-h-11 items-center rounded-full bg-accent px-6 font-bold text-background"
                >
                    Muat ulang
                </a>
                <a href="/" className="ml-4 inline-flex min-h-11 items-center text-accent">
                    Beranda
                </a>
            </main>
        );
    }
}
