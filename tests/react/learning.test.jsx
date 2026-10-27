import { StrictMode, useState } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import Catalog from '../../resources/js/react/Pages/Learning/Catalog';
import Path from '../../resources/js/react/Pages/Learning/Path';
import SiteLayout, { usePresence } from '../../resources/js/react/Layouts/SiteLayout';
import Dialog from '../../resources/js/react/Components/Dialog';

const { page, get } = vi.hoisted(() => ({ page: { props: {} }, get: vi.fn() }));
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ ...page, component: 'Learning/Catalog' }),
    Link: ({ children, preserveScroll, ...props }) => <a {...props}>{children}</a>,
    Head: () => null,
    router: { get, on: () => () => {} },
}));
vi.mock('motion/react-m', async () => {
    const { forwardRef } = await import('react');
    return Object.fromEntries(
        ['div', 'button'].map((tag) => [
            tag,
            forwardRef(({ initial, whileHover, whileTap, children, ...props }, ref) => {
                const Tag = tag;
                return (
                    <Tag ref={ref} {...props}>
                        {children}
                    </Tag>
                );
            }),
        ]),
    );
});

beforeEach(() => {
    page.props = {
        viewer: { name: 'Komang', avatar: '/avatar/penyu.webp', mode: 'anonymous', isAdmin: false },
        navigation: {
            home: '/',
            learning: '/belajar',
            profile: '/profil',
            quizzes: '/kuis',
            login: '/masuk',
            register: '/daftar',
            presence: null,
        },
        brand: { logo: '/image/maskot.webp', mascot: '/image/asset1.webp' },
        flash: { message: null },
        links: { 'profile.about': '/profil/tentang' },
    };
    get.mockReset();
    Object.defineProperty(document, 'visibilityState', { configurable: true, value: 'visible' });
    Object.defineProperty(HTMLDialogElement.prototype, 'showModal', {
        configurable: true,
        value: function () {
            this.setAttribute('open', '');
            this.querySelector('button')?.focus();
        },
    });
    Object.defineProperty(HTMLDialogElement.prototype, 'close', {
        configurable: true,
        value: function () {
            this.removeAttribute('open');
        },
    });
});
afterEach(() => {
    cleanup();
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

const paths = {
    data: [
        {
            id: 1,
            title: 'Cecimpedan',
            group: 'paribasa',
            description: 'Teka-teki Bali.',
            lessonsCount: 2,
            url: '/belajar/cecimpedan',
        },
    ],
    total: 1,
    last_page: 1,
};
const meta = { title: 'Materi belajar — ARTASWAHA' };

describe('learning pages', () => {
    it('submits search to Laravel and prevents duplicate submits while visiting', () => {
        get.mockImplementation((url, data, callbacks) => callbacks.onStart());
        render(<Catalog paths={paths} filters={{ q: '', group: '' }} meta={meta} />);
        fireEvent.change(screen.getByRole('searchbox'), { target: { value: '  teka-teki  ' } });
        fireEvent.submit(screen.getByRole('search'));
        expect(get).toHaveBeenCalledWith(
            '/belajar',
            { q: 'teka-teki' },
            expect.objectContaining({ preserveState: true, replace: true }),
        );
        expect(screen.getByRole('button', { name: 'Cari', exact: true }).disabled).toBe(true);
        act(() => get.mock.calls[0][2].onFinish());
        expect(screen.getByRole('button', { name: 'Cari', exact: true }).disabled).toBe(false);
    });

    it('keeps category state in server visits and supports an empty result', () => {
        render(<Catalog paths={{ ...paths, data: [], total: 0 }} filters={{ q: 'aksara', group: '' }} meta={meta} />);
        fireEvent.click(screen.getByRole('button', { name: 'Aksara Bali', exact: true }));
        expect(get.mock.calls[0][1]).toEqual({ q: 'aksara', group: 'aksara' });
        expect(screen.getByText('Materi belum ditemukan')).toBeTruthy();
        fireEvent.click(screen.getByRole('button', { name: 'Lihat semua materi' }));
        expect(get).toHaveBeenLastCalledWith('/belajar');
    });

    it('escapes lesson text and uses document links for legacy lesson pages', () => {
        const title = '<script>bad()</script>';
        render(
            <Path
                path={{ title: 'Cecimpedan', group: 'paribasa' }}
                lessons={{
                    data: [{ id: 4, title, url: '/pelajaran/contoh' }],
                    from: 13,
                    total: 13,
                    current_page: 2,
                    last_page: 2,
                    prev_page_url: '/belajar/cecimpedan?page=1',
                }}
                meta={meta}
            />,
        );
        expect(screen.getByText(title)).toBeTruthy();
        expect(document.querySelector('script')).toBeNull();
        expect(screen.getByRole('link', { name: /bad/ }).getAttribute('href')).toBe('/pelajaran/contoh');
        expect(screen.getByText('13')).toBeTruthy();
        expect(screen.getByRole('link', { name: /Sebelumnya/ }).getAttribute('href')).toContain('page=1');
    });
});

describe('shared shell', () => {
    it('shows one flash and removes it after its bounded lifetime', () => {
        vi.useFakeTimers();
        page.props.flash = { message: 'Progres tersimpan.', tone: 'success' };
        render(
            <SiteLayout>
                <h1>Materi</h1>
            </SiteLayout>,
        );
        expect(screen.getAllByRole('status')).toHaveLength(1);
        act(() => vi.advanceTimersByTime(5000));
        expect(screen.queryByText('Progres tersimpan.')).toBeNull();
    });

    it('keeps mobile app navigation without a hamburger for an anonymous identity', () => {
        render(
            <SiteLayout>
                <h1>Materi</h1>
            </SiteLayout>,
        );
        expect(screen.getByRole('navigation', { name: 'Navigasi utama mobile' })).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Buka menu' })).toBeNull();
        fireEvent.click(screen.getByRole('button', { name: 'Menu profil Komang' }));
        expect(screen.getByText('Akun anonim · di perangkat ini')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Masuk', exact: true }).getAttribute('href')).toBe('/masuk');
    });

    it('restores focus and body scrolling after cancelling a native dialog', () => {
        function Example() {
            const [open, setOpen] = useState(false);
            return (
                <>
                    <button onClick={() => setOpen(true)}>Buka dialog</button>
                    <Dialog open={open} onClose={() => setOpen(false)} title="Menu" id="test-dialog">
                        <p>Pilihan</p>
                    </Dialog>
                </>
            );
        }
        render(
            <StrictMode>
                <Example />
            </StrictMode>,
        );
        const trigger = screen.getByRole('button', { name: 'Buka dialog' });
        trigger.focus();
        fireEvent.click(trigger);
        expect(document.body.style.overflow).toBe('hidden');
        fireEvent(screen.getByRole('dialog'), new Event('cancel', { cancelable: true }));
        expect(document.activeElement).toBe(trigger);
        expect(document.body.style.overflow).toBe('');
    });

    it('cleans presence timers, listeners, and in-flight requests under Strict Mode', async () => {
        vi.useFakeTimers();
        const fetcher = vi.fn().mockResolvedValue({ ok: true });
        vi.stubGlobal('fetch', fetcher);
        function Presence() {
            usePresence('/presence');
            return null;
        }
        const mounted = render(
            <StrictMode>
                <Presence />
            </StrictMode>,
        );
        await act(async () => {});
        expect(fetcher.mock.calls[0][1].signal.aborted).toBe(true);
        expect(vi.getTimerCount()).toBe(1);
        Object.defineProperty(document, 'visibilityState', { configurable: true, value: 'hidden' });
        const calls = fetcher.mock.calls.length;
        await act(async () => vi.advanceTimersByTime(30000));
        expect(fetcher).toHaveBeenCalledTimes(calls);
        mounted.unmount();
        expect(vi.getTimerCount()).toBe(0);
    });
});

it('opens separate subject dropdowns and supports keyboard dismissal with restored focus', () => {
    page.props.materials = {
        paribasa: [{ id: 1, title: 'Cecimpedan', url: '/belajar/cecimpedan' }],
        aksara: [{ id: 2, title: 'Wreastra', url: '/belajar/wreastra' }],
    };
    render(
        <SiteLayout>
            <h1>Materi</h1>
        </SiteLayout>,
    );
    const trigger = screen.getByRole('button', { name: 'Paribasa Bali' });
    fireEvent.keyDown(trigger, { key: 'ArrowDown' });
    const lesson = screen.getByRole('link', { name: 'Cecimpedan' });
    expect(document.activeElement).toBe(lesson);
    expect(trigger.getAttribute('aria-expanded')).toBe('true');
    fireEvent.keyDown(document, { key: 'Escape' });
    expect(trigger.getAttribute('aria-expanded')).toBe('false');
    expect(document.activeElement).toBe(trigger);
    fireEvent.click(screen.getByRole('button', { name: 'Aksara Bali' }));
    expect(screen.getByRole('link', { name: 'Wreastra' }).getAttribute('href')).toBe('/belajar/wreastra');
    fireEvent.pointerDown(document.body);
    expect(screen.queryByRole('link', { name: 'Wreastra' })).toBeNull();
});
