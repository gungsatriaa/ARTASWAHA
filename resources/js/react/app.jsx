import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import SiteLayout from './Layouts/SiteLayout';
import ErrorBoundary from './Components/ErrorBoundary';
import MotionProvider from './Components/MotionProvider';

const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    title: title => title.includes('ARTASWAHA') ? title : `${title} — ARTASWAHA`,
    resolve: async name => {
        const load = pages[`./Pages/${name}.jsx`];
        if (!load) throw new Error('Halaman belum tersedia.');
        const page = await load();
        page.default.layout ??= page => <SiteLayout>{page}</SiteLayout>;
        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <StrictMode>
                <ErrorBoundary>
                    <MotionProvider><App {...props} /></MotionProvider>
                </ErrorBoundary>
            </StrictMode>,
        );
    },
    progress: { color: '#ffc700', delay: 180, showSpinner: false },
}).catch(() => {
    const fallback = document.createElement('p');
    fallback.className = 'mx-auto max-w-xl p-6 text-center';
    fallback.textContent = 'Aplikasi belum dapat dimuat. Periksa koneksimu lalu muat ulang halaman.';
    document.getElementById('app')?.replaceChildren(fallback);
});
