import { StrictMode, useState } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { act, cleanup, fireEvent, render, screen, within } from '@testing-library/react';
import usePresence from '../../resources/js/react/Hooks/usePresence';
import Catalog from '../../resources/js/react/Pages/Learning/Catalog';
import Path from '../../resources/js/react/Pages/Learning/Path';
import SiteLayout from '../../resources/js/react/Layouts/SiteLayout';
import Dialog from '../../resources/js/react/Components/Dialog';
import { CategoryIcon } from '../../resources/js/react/Components/ui';
import Icon from '../../resources/js/react/Components/Icon';

const { page, get } = vi.hoisted(() => ({ page: { props: {} }, get: vi.fn() }));
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ ...page, component: page.component ?? 'Learning/Catalog' }),
    Link: ({ children, preserveScroll, preserveState, only, prefetch, cacheFor, ...props }) => <a {...props}>{children}</a>,
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

it.each([
    ['Cecimpedan', 'question'],
    ['Peparikan', 'conversation'],
    ['Sesonggan', 'leaf'],
])('uses the matching Paribasa icon for %s', (title, name) => {
    const { container, unmount } = render(<CategoryIcon group="paribasa" title={title} />);
    const path = container.querySelector('path').getAttribute('d');
    expect(container.querySelector('svg').classList.contains('size-6')).toBe(true);
    unmount();
    const expected = render(<Icon name={name} />);
    expect(expected.container.querySelector('path').getAttribute('d')).toBe(path);
});

it.each([
    'Aksara Wreastra & Gantungan',
    'Pengangge Suara',
    'Pengangge Arda Suara',
    'Pengangge Tengenan',
    'Angka Aksara Bali',
])('renders %s as a fixed-size centered vector without font placeholder circles', (title) => {
    const { container } = render(<CategoryIcon group="aksara" title={title} />);
    const icon = container.querySelector('svg');
    expect(icon.getAttribute('viewBox')).toBe('0 0 24 24');
    expect(icon.classList.contains('size-6')).toBe(true);
    expect(container.querySelector('text, tspan')).toBeNull();
    expect(screen.queryByText('...')).toBeNull();
    const path = icon.querySelector('path').getAttribute('d');
    expect(path).toMatch(/^M/);
    const coordinates = [...path.matchAll(/-?\d+\.\d+/g)].map(([value]) => Number(value));
    expect(Math.min(...coordinates)).toBeGreaterThanOrEqual(1.99);
    expect(Math.max(...coordinates)).toBeLessThanOrEqual(22.01);
});

it('uses a distinct vector shape for each Balinese subject', () => {
    const { container } = render(
        <>
            {[
                'Aksara Wreastra & Gantungan',
                'Pengangge Suara',
                'Pengangge Tengenan',
                'Pengangge Arda Suara',
                'Angka Aksara Bali',
            ].map((title) => (
                <CategoryIcon key={title} group="aksara" title={title} />
            ))}
        </>,
    );
    expect(new Set([...container.querySelectorAll('path')].map((path) => path.getAttribute('d'))).size).toBe(5);
});

it.each(['Pengangge Suara', 'Pengangge Tengenan', 'Pengangge Arda Suara'])(
    'uses the interface stroke weight for %s without filled glyphs',
    (title) => {
        const { container, unmount } = render(<CategoryIcon group="aksara" title={title} />);
        const mark = container.querySelector('svg');
        const weight = mark.getAttribute('stroke-width');
        expect(mark.getAttribute('fill')).toBe('none');
        expect(mark.getAttribute('stroke')).toBe('currentColor');
        expect(mark.getAttribute('stroke-linecap')).toBe('round');
        unmount();
        const reference = render(<Icon name="book" />);
        expect(weight).toBe(reference.container.querySelector('svg').getAttribute('stroke-width'));
    },
);

beforeEach(() => {
    page.component = 'Learning/Catalog';
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
            privacy: '/kebijakan-privasi',
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

    it('shows an honest empty module list with a way to browse other materials', () => {
        render(<Path path={{ title: 'Cecimpedan', group: 'paribasa' }} lessons={{ data: [], total: 0 }} meta={meta} />);
        expect(screen.getByRole('heading', { name: 'Daftar modul' })).toBeTruthy();
        expect(screen.getByText('Pelajaran sedang disiapkan')).toBeTruthy();
        expect(screen.getByRole('link', { name: /Jelajahi materi lain/ }).getAttribute('href')).toBe('/belajar');
        expect(screen.queryByRole('list')).toBeNull();
    });

    it('renders the material and module progress bars from persisted percentages', () => {
        render(
            <Path
                path={{ title: 'Cecimpedan', group: 'paribasa', progress: 50, completedLessons: 1 }}
                lessons={{
                    data: [
                        { id: 1, title: 'Modul pertama', url: '/pelajaran/pertama', progress: 100 },
                        { id: 2, title: 'Modul kedua', url: '/pelajaran/kedua', progress: 0 },
                    ],
                    from: 1,
                    total: 2,
                }}
                meta={meta}
            />,
        );
        expect(screen.getByRole('progressbar', { name: 'Progres materi Cecimpedan' }).value).toBe(50);
        expect(screen.getByRole('progressbar', { name: 'Progres modul Modul pertama' }).value).toBe(100);
        expect(screen.getByRole('progressbar', { name: 'Progres modul Modul kedua' }).value).toBe(0);
        expect(screen.getByText('1 dari 2 modul selesai')).toBeTruthy();
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
    it('opens the guest menu as a side drawer and closes it after choosing a destination', () => {
        page.props.viewer = null;
        render(
            <SiteLayout>
                <h1>Materi</h1>
            </SiteLayout>,
        );
        const trigger = screen.getByRole('button', { name: 'Buka menu' });
        trigger.focus();
        fireEvent.click(trigger);
        const drawer = screen.getByRole('dialog', { name: 'Menu ARTASWAHA' });
        expect(drawer.classList.contains('site-drawer')).toBe(true);
        expect(trigger.getAttribute('aria-expanded')).toBe('true');
        expect(within(drawer).getByRole('link', { name: 'Masuk', exact: true }).getAttribute('href')).toBe('/masuk');
        expect(within(drawer).getByRole('link', { name: 'Daftar akun' }).getAttribute('href')).toBe('/daftar');
        const destination = within(drawer).getByRole('link', { name: 'Semua materi' });
        expect(destination.getAttribute('aria-current')).toBe('page');
        destination.addEventListener('click', (event) => event.preventDefault());
        fireEvent.click(destination);
        expect(screen.queryByRole('dialog')).toBeNull();
        expect(document.body.style.overflow).not.toBe('hidden');
        expect(document.activeElement).toBe(trigger);
    });

    it.each(['anonymous', 'account'])('keeps the mobile lesson navigation for an %s identity', (mode) => {
        page.component = 'Learning/Lesson';
        page.props.viewer.mode = mode;
        render(
            <SiteLayout>
                <h1>Pelajaran</h1>
            </SiteLayout>,
        );
        const navigation = screen.getByRole('navigation', { name: 'Navigasi utama mobile' });
        expect(within(navigation).getByRole('link', { name: 'Belajar' }).getAttribute('aria-current')).toBe('page');
        expect(within(navigation).getAllByRole('link')).toHaveLength(4);
        expect(screen.queryByRole('button', { name: 'Buka menu' })).toBeNull();
        expect(document.getElementById('main-content').classList.contains('pb-32')).toBe(true);
    });

    it('keeps app navigation hidden during an active room', () => {
        page.component = 'Rooms/Show';
        page.props.room = { status: 'active' };
        render(
            <SiteLayout>
                <h1>Kuis</h1>
            </SiteLayout>,
        );
        expect(screen.queryByRole('navigation', { name: 'Navigasi utama mobile' })).toBeNull();
    });

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

    it.each([
        ['success', 'text-success'],
        ['error', 'text-danger'],
        ['info', ''],
    ])('uses the shared %s dialog treatment', (variant, color) => {
        render(
            <Dialog open onClose={() => {}} title="Status proses" id="status-dialog" variant={variant}>
                Pesan proses.
            </Dialog>,
        );
        const heading = screen.getByRole('heading', { name: 'Status proses' });
        expect(screen.getByRole('dialog').getAttribute('data-variant')).toBe(variant);
        if (color) expect(heading.classList.contains(color)).toBe(true);
        else {
            expect(heading.classList.contains('text-success')).toBe(false);
            expect(heading.classList.contains('text-danger')).toBe(false);
        }
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
        expect(fetcher).not.toHaveBeenCalled();
        await act(async () => vi.advanceTimersByTime(0));
        expect(fetcher).toHaveBeenCalledOnce();
        fireEvent(document, new Event('visibilitychange'));
        expect(fetcher).toHaveBeenCalledOnce();
        expect(vi.getTimerCount()).toBe(1);
        Object.defineProperty(document, 'visibilityState', { configurable: true, value: 'hidden' });
        const calls = fetcher.mock.calls.length;
        await act(async () => vi.advanceTimersByTime(30000));
        expect(fetcher).toHaveBeenCalledTimes(calls);
        fetcher.mockImplementationOnce((_url, { signal }) => new Promise((_resolve, reject) => {
            signal.addEventListener('abort', () => reject(new DOMException('Cancelled', 'AbortError')));
        }));
        Object.defineProperty(document, 'visibilityState', { configurable: true, value: 'visible' });
        fireEvent(document, new Event('visibilitychange'));
        expect(fetcher).toHaveBeenCalledTimes(calls + 1);
        mounted.unmount();
        expect(fetcher.mock.lastCall[1].signal.aborted).toBe(true);
        await act(async () => {});
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

it('shows the shared homepage footer for an existing identity with published subject links and contact', () => {
    page.component = 'Home';
    page.props.materials = { paribasa: [{ id: 1, title: 'Cecimpedan', url: '/belajar/cecimpedan' }], aksara: [] };
    render(
        <SiteLayout>
            <h1>Beranda</h1>
        </SiteLayout>,
    );
    expect(screen.getByRole('contentinfo', { name: 'Footer ARTASWAHA' })).toBeTruthy();
    const contact = screen.getByRole('link', { name: '0813 3725 3221' }).closest('address');
    expect(contact).toBeTruthy();
    expect(contact.textContent).not.toContain('Main kuis bareng');
    expect(screen.getByRole('navigation', { name: 'Informasi layanan' }).textContent).toContain('Main kuis bareng');
    expect(screen.getByRole('link', { name: 'Cecimpedan' }).getAttribute('href')).toBe('/belajar/cecimpedan');
    expect(screen.getByRole('link', { name: 'Kebijakan privasi' }).getAttribute('href')).toBe('/kebijakan-privasi');
    expect(screen.getByRole('link', { name: 'kerthajayasoftware@gmail.com' }).getAttribute('href')).toBe(
        'mailto:kerthajayasoftware@gmail.com',
    );
});
