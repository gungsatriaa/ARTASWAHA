import { StrictMode, useEffect } from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { createBrowserRouter, RouterProvider } from 'react-router';
import ErrorBoundary from './Components/ErrorBoundary';
import usePresence from './Hooks/usePresence';
import MotionProvider from './Components/MotionProvider';
import { resolve } from './resolve';
import { PageView, RouteError, configureNavigation, pageLoader } from './routing';
import { startBootLoader, markBootReady, failBootLoader } from './boot-loader';
startBootLoader();
function BootReady({ presenceUrl }) {
    usePresence(presenceUrl);
    useEffect(() => { markBootReady(); }, []);
    return null;
}
async function boot() {
    const bootstrap = document.getElementById('react-page');
    const page = JSON.parse(bootstrap.textContent);
    bootstrap.remove();
    const module = await resolve(page.component);
    const initialPage = { ...page, Component: module.default, stateKey: 0 };
    const navigation = createBrowserRouter([{ id: 'page', path: '*', loader: pageLoader, element: <PageView />, errorElement: <RouteError /> }], { hydrationData: { loaderData: { page: initialPage } } });
    configureNavigation(navigation, initialPage, resolve);
    const content = <StrictMode><ErrorBoundary><MotionProvider><RouterProvider router={navigation} /><BootReady presenceUrl={page.props.navigation.presence} /></MotionProvider></ErrorBoundary></StrictMode>;
    const el = document.getElementById('app');
    if (el.hasChildNodes()) hydrateRoot(el, content);
    else createRoot(el).render(content);
}
boot().catch(() => {
    failBootLoader();
    const message = document.createElement('p');
    message.className = 'p-8 text-center';
    message.textContent = 'Aplikasi belum dapat dimuat. Periksa koneksi lalu muat ulang halaman.';
    document.getElementById('app')?.replaceChildren(message);
});
