import { createServer } from 'node:http';
import { pathToFileURL } from 'node:url';
import { renderToStaticMarkup, renderToString } from 'react-dom/server';
import { createStaticRouter, matchRoutes, parsePath, StaticRouterProvider } from 'react-router';
import ErrorBoundary from './Components/ErrorBoundary';
import { HeadProvider } from './Components/Head';
import MotionProvider from './Components/MotionProvider';
import { PageView, RouteError } from './routing';
import { resolve } from './resolve';

export async function renderPage(page) {
    if (
        !page ||
        typeof page.component !== 'string' ||
        typeof page.url !== 'string' ||
        !page.props ||
        typeof page.props !== 'object' ||
        Array.isArray(page.props)
    ) {
        throw new TypeError('Invalid page');
    }

    const { default: Component } = await resolve(page.component);
    const routes = [{ id: 'page', path: '*', element: <PageView />, errorElement: <RouteError /> }];
    const location = { pathname: '/', search: '', hash: '', ...parsePath(page.url), state: null, key: 'default' };
    const context = {
        basename: '/',
        location,
        matches: matchRoutes(routes, location),
        loaderData: { page: { ...page, Component, stateKey: 0 } },
        actionData: null,
        errors: null,
        statusCode: 200,
        loaderHeaders: {},
        actionHeaders: {},
    };
    const router = createStaticRouter(routes, context);
    const registry = new Map();
    const body = renderToString(
        <HeadProvider registry={registry}>
            <ErrorBoundary>
                <MotionProvider>
                    <StaticRouterProvider router={router} context={context} hydrate={false} />
                </MotionProvider>
            </ErrorBoundary>
        </HeadProvider>,
    );

    return { head: Array.from(registry.values(), (element) => renderToStaticMarkup(element)), body };
}

export function createRenderServer() {
    return createServer((request, response) => {
        const send = (status, payload) => {
            if (response.writableEnded) return;
            response.writeHead(status, { 'Content-Type': 'application/json' });
            response.end(JSON.stringify(payload));
        };

        if (request.method === 'GET' && request.url === '/health') {
            send(200, { status: 'ok' });
            return;
        }
        if (request.method !== 'POST' || request.url !== '/render') {
            send(404, { error: 'Not found' });
            request.resume();
            return;
        }

        const chunks = [];
        let size = 0;
        let oversized = false;
        request.on('data', (chunk) => {
            size += chunk.length;
            if (size > 2 * 1024 * 1024) {
                if (!oversized) {
                    oversized = true;
                    chunks.length = 0;
                    send(413, { error: 'Request body too large' });
                }
                return;
            }
            chunks.push(chunk);
        });
        request.on('error', () => {
            if (!response.writableEnded) send(500, { error: 'Rendering failed' });
        });
        request.on('end', async () => {
            if (oversized || response.writableEnded) return;
            try {
                const page = JSON.parse(Buffer.concat(chunks).toString('utf8'));
                send(200, await renderPage(page));
            } catch {
                send(500, { error: 'Rendering failed' });
            }
        });
    });
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
    createRenderServer().listen(13714, '127.0.0.1');
}
