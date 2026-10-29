import { StrictMode, useEffect } from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import ErrorBoundary from './Components/ErrorBoundary';
import usePresence from './Hooks/usePresence';
import MotionProvider from './Components/MotionProvider';
import { resolve, title } from './resolve';
import { startBootLoader, markBootReady, failBootLoader } from './boot-loader';
startBootLoader();
function BootReady({ presenceUrl }) {
    usePresence(presenceUrl);
    useEffect(() => {
        markBootReady();
    }, []);
    return null;
}
createInertiaApp({
    title,
    resolve,
    setup({ el, App, props }) {
        const content = (
            <StrictMode>
                <ErrorBoundary>
                    <MotionProvider>
                        <App {...props} />
                        <BootReady presenceUrl={props.initialPage.props.navigation.presence} />
                    </MotionProvider>
                </ErrorBoundary>
            </StrictMode>
        );
        if (el.hasChildNodes()) hydrateRoot(el, content);
        else createRoot(el).render(content);
    },
    progress: false,
}).catch(() => {
    failBootLoader();
    const message = document.createElement('p');
    message.className = 'p-8 text-center';
    message.textContent = 'Aplikasi belum dapat dimuat. Periksa koneksi lalu muat ulang halaman.';
    document.getElementById('app')?.replaceChildren(message);
});
