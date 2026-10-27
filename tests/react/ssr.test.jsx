// @vitest-environment node
import { expect, it } from 'vitest';
import { createInertiaApp } from '@inertiajs/react';
import { renderToString } from 'react-dom/server';
import { resolve, title } from '../../resources/js/react/resolve';
import MotionProvider from '../../resources/js/react/Components/MotionProvider';
import Keyboard from '../../resources/js/react/Components/Keyboard';

const shared = {
    viewer: null,
    navigation: {
        home: '/',
        learning: '/belajar',
        quizzes: '/kuis',
        privacy: '/kebijakan-privasi',
        login: '/masuk',
        register: '/daftar',
    },
    brand: { logo: '/logo.webp', mascot: '/maskot.webp', avatarBase: '/avatar' },
    links: {},
    flash: {},
    csrfToken: 'test',
    meta: {
        title: 'Belajar Bali — ARTASWAHA',
        description: 'Materi Bali.',
        canonical: 'https://example.test/',
        robots: 'index, follow',
        schema: { '@context': 'https://schema.org', '@type': 'WebSite', name: '<Bali>' },
    },
};
const list = { data: [], current_page: 1, last_page: 1, total: 0 };
const fixtures = {
    Home: { paths: [], selectedIds: [], hasInterview: false },
    'Auth/Form': { mode: 'login', email: '' },
    'Learning/Catalog': { paths: list, filters: {} },
    'Learning/Path': { path: { title: 'Cecimpedan', group: 'paribasa' }, lessons: list },
    'Learning/Interview': { paths: [], answers: {}, step: 1, roles: {} },
    'Learning/Lesson': {
        lesson: {
            title: 'Cecimpedan',
            content: 'Teka-teki Bali',
            path: { title: 'Paribasa', url: '/belajar' },
            exercise_prompt: null,
        },
    },
    'Quizzes/Index': { quizzes: list, filters: {} },
    Privacy: {},
};
it.each(Object.entries(fixtures))(
    'server-renders %s content and metadata without browser globals',
    async (component, props) => {
        const response = await createInertiaApp({
            page: { component, url: '/', props: { ...shared, ...props }, version: 'test' },
            resolve,
            title,
            render: renderToString,
            setup: ({ App, props }) => (
                <MotionProvider>
                    <App {...props} />
                </MotionProvider>
            ),
        });
        expect(response.body).toContain('<h1');
        if (component === 'Home') expect(response.body).toContain('aria-label="Footer ARTASWAHA"');
        expect(response.body).not.toContain('document is not defined');
        expect(response.head.join('')).toContain('name="description"');
        expect(response.head.join('')).toContain('rel="canonical"');
        const schema = response.head
            .join('')
            .match(/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/)?.[1];
        expect(JSON.parse(schema).name).toBe('<Bali>');
        expect(schema).not.toContain('<Bali>');
    },
);
it('renders an already-open Balinese keyboard without importing a DOM initializer', () => {
    expect(renderToString(<Keyboard autoOpen />)).toContain('Coretan latihan aksara');
});
