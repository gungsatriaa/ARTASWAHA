import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { renderToString } from 'react-dom/server';
import ErrorBoundary from './Components/ErrorBoundary';
import MotionProvider from './Components/MotionProvider';
import { resolve, title } from './resolve';
createServer(
    (page) =>
        createInertiaApp({
            page,
            title,
            resolve,
            render: renderToString,
            setup: ({ App, props }) => (
                <ErrorBoundary>
                    <MotionProvider>
                        <App {...props} />
                    </MotionProvider>
                </ErrorBoundary>
            ),
        }),
    { host: '127.0.0.1' },
);
