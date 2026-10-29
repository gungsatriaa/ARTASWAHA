import { StrictMode } from 'react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { createMemoryRouter, RouterProvider } from 'react-router';
import {
    configureNavigation, Deferred, Link, pageLoader, PageView, prefetchPage,
    requestPage, RouteError, router, useForm, usePage,
} from '../../resources/js/react/routing';
import PageEffects from '../../resources/js/react/Components/PageEffects';
import Editor from '../../resources/js/react/Pages/Quizzes/Editor';

let navigation;
let form;
let fetcher;
const viewer = { id: 1, mode: 'account', name: 'Made' };
const payload = (url, props = {}, component = 'Test') => ({
    component, url, props: { viewer, csrfToken: 'csrf-test', heading: url, ...props },
});
const response = (body, status = 200) => new Response(JSON.stringify(body), {
    status, headers: { 'Content-Type': 'application/json' },
});
function pending() {
    let resolve;
    const promise = new Promise((done) => { resolve = done; });
    return { promise, resolve };
}
function TestPage({ heading, statistics }) {
    const page = usePage();
    form = useForm({ name: '', notes: '', avatar: null });
    return (
        <PageEffects>
            <h1>{heading}</h1>
            <p>{page.props.viewer.name}</p>
            <p>{statistics?.total}</p>
            <input aria-label="Nama" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
            <output aria-label="Form status">{form.processing ? 'sending' : form.isDirty ? 'dirty' : 'clean'}</output>
            {Object.entries(form.errors).map(([key, error]) => <p role="alert" key={key}>{error}</p>)}
            <Link href="/next">Berikutnya</Link>
            <Link href="/next" target="_blank">Tab baru</Link>
            <Link href="/next" download>Unduh</Link>
            <Link href="/next" onClick={(event) => event.preventDefault()}>Dibatalkan</Link>
            <Link href="https://other.test/">Eksternal</Link>
            <Link href="mailto:made@example.test">Email</Link>
        </PageEffects>
    );
}
TestPage.layout = (children) => <main aria-label="Page layout">{children}</main>;
function mount(page = payload('/'), Component = TestPage) {
    window.history.replaceState(null, '', page.url);
    const initialPage = { ...page, Component, stateKey: 0 };
    navigation = createMemoryRouter([
        { id: 'page', path: '*', loader: pageLoader, element: <PageView />, errorElement: <RouteError /> },
    ], { initialEntries: [page.url], hydrationData: { loaderData: { page: initialPage } } });
    configureNavigation(navigation, initialPage, async (name) => ({ default: name === 'Quizzes/Editor' ? Editor : Component }));
    return render(<RouterProvider router={navigation} />);
}
beforeEach(() => {
    fetcher = vi.fn().mockRejectedValue(new Error('Unexpected fetch'));
    vi.stubGlobal('fetch', fetcher);
    vi.stubGlobal('matchMedia', () => ({ matches: false, addEventListener() {}, removeEventListener() {} }));
    vi.spyOn(window, 'scrollTo').mockImplementation(() => {});
});
afterEach(() => {
    cleanup();
    navigation?.dispose();
    vi.unstubAllGlobals();
    window.history.replaceState(null, '', '/');
});

it('keeps the current page and draft visible until the fetched destination is ready', async () => {
    const next = pending();
    fetcher.mockReturnValueOnce(next.promise);
    mount();
    fireEvent.change(screen.getByLabelText('Nama'), { target: { value: 'Draf' } });

    fireEvent.click(screen.getByRole('link', { name: 'Berikutnya' }));
    await waitFor(() => expect(fetcher).toHaveBeenCalledOnce());
    expect(screen.getByRole('heading').textContent).toBe('/');
    expect(screen.getByLabelText('Nama').value).toBe('Draf');
    await screen.findByRole('status', { name: 'Memuat halaman…' });
    await act(async () => next.resolve(response(payload('/next'))));

    expect(screen.getByRole('heading').textContent).toBe('/next');
    expect(screen.getByRole('main', { name: 'Page layout' })).toBeTruthy();
    expect(screen.getByLabelText('Nama').value).toBe('');
    expect(screen.queryByRole('status', { name: 'Memuat halaman…' })).toBeNull();
    expect(navigation.state.location.pathname).toBe('/next');
    expect(fetcher.mock.calls[0][1]).toMatchObject({
        method: 'GET', credentials: 'same-origin', cache: 'no-store',
        headers: { Accept: 'application/json', 'X-React-Page': 'true' },
    });
});

it('ignores an aborted slow navigation even when its response arrives after the next page', async () => {
    const slow = pending();
    fetcher.mockReturnValueOnce(slow.promise).mockResolvedValueOnce(response(payload('/fast')));
    mount();
    let first;
    act(() => { first = router.visit('/slow'); });
    const signal = fetcher.mock.calls[0][1].signal;

    await act(async () => router.visit('/fast'));
    expect(signal.aborted).toBe(true);
    await act(async () => { slow.resolve(response(payload('/slow'))); await first; });

    expect(screen.getByRole('heading').textContent).toBe('/fast');
    expect(navigation.state.location.pathname).toBe('/fast');
});

it('preserves drafts only when requested and resets them for a new viewer', async () => {
    mount();
    fetcher.mockResolvedValueOnce(response(payload('/?q=ha')))
        .mockResolvedValueOnce(response(payload('/?q=na')))
        .mockResolvedValueOnce(response(payload('/?q=ka', { viewer: { ...viewer, id: 2, name: 'Komang' } })));
    fireEvent.change(screen.getByLabelText('Nama'), { target: { value: 'Draf' } });

    await act(async () => router.get('/', { q: 'ha' }, { preserveState: true }));
    expect(screen.getByLabelText('Nama').value).toBe('Draf');
    await act(async () => router.get('/', { q: 'na' }));
    expect(screen.getByLabelText('Nama').value).toBe('');
    fireEvent.change(screen.getByLabelText('Nama'), { target: { value: 'Rahasia' } });
    await act(async () => router.get('/', { q: 'ka' }, { preserveState: true }));

    expect(screen.getByLabelText('Nama').value).toBe('');
    expect(screen.getByText('Komang')).toBeTruthy();
});

it('loads fresh pages on back and forward without putting private props in history state', async () => {
    mount(payload('/', { privateToken: 'secret-first' }));
    fetcher.mockResolvedValueOnce(response(payload('/next', { privateToken: 'secret-next' })))
        .mockResolvedValueOnce(response(payload('/', { heading: 'Fresh home' })))
        .mockResolvedValueOnce(response(payload('/next', { heading: 'Fresh next' })));

    await act(async () => router.visit('/next'));
    expect(navigation.state.location.state).toBeNull();
    await act(async () => navigation.navigate(-1));
    expect(screen.getByRole('heading').textContent).toBe('Fresh home');
    expect(navigation.state.location.state).toBeNull();
    await act(async () => navigation.navigate(1));

    expect(screen.getByRole('heading').textContent).toBe('Fresh next');
    expect(navigation.state.location.state).toBeNull();
    expect(fetcher).toHaveBeenCalledTimes(3);
});

it.each([{ ctrlKey: true }, { metaKey: true }, { shiftKey: true }, { altKey: true }, { button: 1 }])(
    'leaves modified link clicks to the browser: %j', (modifiers) => {
        mount();
        const link = screen.getByRole('link', { name: 'Berikutnya' });
        const click = new MouseEvent('click', { bubbles: true, cancelable: true, button: 0, ...modifiers });
        // Stop jsdom's document navigation after observing React's handling.
        let prevented;
        document.addEventListener('click', (event) => { prevented = event.defaultPrevented; event.preventDefault(); }, { once: true });

        fireEvent(link, click);

        expect(prevented).toBe(false);
        expect(fetcher).not.toHaveBeenCalled();
        expect(navigation.state.location.pathname).toBe('/');
    },
);

it.each(['Tab baru', 'Unduh', 'Dibatalkan'])('does not fetch when clicking %s', (name) => {
    mount();
    document.addEventListener('click', (event) => event.preventDefault(), { once: true });

    fireEvent.click(screen.getByRole('link', { name }));

    expect(fetcher).not.toHaveBeenCalled();
});

it('keeps external and email destinations as document links', () => {
    mount();
    expect(screen.getByRole('link', { name: 'Eksternal' }).getAttribute('href')).toBe('https://other.test/');
    expect(screen.getByRole('link', { name: 'Email' }).getAttribute('href')).toBe('mailto:made@example.test');
    expect(fetcher).not.toHaveBeenCalled();
});

it('keeps the draft and reports Laravel validation errors without navigating', async () => {
    const failed = pending();
    fetcher.mockReturnValueOnce(failed.promise);
    mount();
    const onError = vi.fn(), onFinish = vi.fn();
    fireEvent.change(screen.getByLabelText('Nama'), { target: { value: 'Made' } });
    let submission;
    act(() => { submission = form.post('/profile', { onError, onFinish }); });
    expect(screen.getByLabelText('Form status').textContent).toBe('sending');
    await act(async () => form.post('/profile'));
    expect(fetcher).toHaveBeenCalledOnce();

    await act(async () => { failed.resolve(response({ errors: { name: ['Nama sudah digunakan.'] } }, 422)); await submission; });

    expect(screen.getByRole('alert').textContent).toBe('Nama sudah digunakan.');
    expect(screen.getByLabelText('Nama').value).toBe('Made');
    expect(screen.getByLabelText('Form status').textContent).toBe('dirty');
    expect(navigation.state.location.pathname).toBe('/');
    expect(onError).toHaveBeenCalledWith({ name: 'Nama sudah digunakan.' });
    expect(onFinish).toHaveBeenCalledOnce();
    expect(JSON.parse(fetcher.mock.calls[0][1].body)).toMatchObject({ name: 'Made' });
    expect(fetcher.mock.calls[0][1].headers).toMatchObject({ 'X-CSRF-TOKEN': 'csrf-test', 'X-React-Page': 'true' });
});

it.each(['post', 'put', 'delete'])('follows a successful %s response to its canonical page without another fetch', async (method) => {
    mount();
    fetcher.mockResolvedValueOnce(response(payload('/profile', { heading: 'Tersimpan' })));
    fireEvent.change(screen.getByLabelText('Nama'), { target: { value: 'Made' } });
    const onSuccess = vi.fn();
    form.transform((data) => ({ ...data, name: data.name.toUpperCase() }));

    await act(async () => form[method]('/profile', { onSuccess }));

    expect(screen.getByRole('heading').textContent).toBe('Tersimpan');
    expect(screen.getByLabelText('Nama').value).toBe('Made');
    expect(navigation.state.location.pathname).toBe('/profile');
    expect(fetcher).toHaveBeenCalledOnce();
    expect(fetcher.mock.calls[0][1]).toMatchObject({ method: method.toUpperCase(), credentials: 'same-origin' });
    expect(JSON.parse(fetcher.mock.calls[0][1].body).name).toBe('MADE');
    expect(onSuccess).toHaveBeenCalledOnce();
});

it('uploads nested files and arrays as multipart data without forcing the content type', async () => {
    mount();
    fetcher.mockResolvedValueOnce(response(payload('/')));
    const file = new File(['avatar'], 'made.png', { type: 'image/png' });
    act(() => form.setData({ name: 'Made', avatar: file, questions: [{ options: ['Ha', 'Na'], active: true }], empty: null }));

    await act(async () => form.post('/profile'));

    const options = fetcher.mock.calls[0][1];
    expect(options.body).toBeInstanceOf(FormData);
    expect(options.body.get('avatar').name).toBe('made.png');
    expect(options.body.get('questions[0][options][1]')).toBe('Na');
    expect(options.body.get('questions[0][active]')).toBe('1');
    expect(options.body.get('empty')).toBe('');
    expect(options.headers['Content-Type']).toBeUndefined();
});

it('resets individual fields and then all defaults without losing unrelated changes', () => {
    mount();
    act(() => form.setData((data) => ({ ...data, name: 'Made', notes: 'Tetap' })));

    act(() => form.reset('name'));
    expect(form.data).toMatchObject({ name: '', notes: 'Tetap' });
    expect(form.isDirty).toBe(true);
    act(() => form.reset());

    expect(form.data).toEqual({ name: '', notes: '', avatar: null });
    expect(form.isDirty).toBe(false);
});

it('aborts an in-flight form request on unmount without reporting a validation error', async () => {
    fetcher.mockImplementationOnce((_url, { signal }) => new Promise((_resolve, reject) => {
        signal.addEventListener('abort', () => reject(new DOMException('Cancelled', 'AbortError')));
    }));
    const { unmount } = mount();
    const onError = vi.fn(), onFinish = vi.fn();
    let submission;
    act(() => { submission = form.post('/profile', { onError, onFinish }); });
    const signal = fetcher.mock.calls[0][1].signal;

    await act(async () => { unmount(); await submission; });

    expect(signal.aborted).toBe(true);
    expect(onError).not.toHaveBeenCalled();
    expect(onFinish).toHaveBeenCalledOnce();
});

it('retains the current page and draft when a partial reload fails', async () => {
    mount();
    fetcher.mockResolvedValueOnce(response({ message: 'Unavailable' }, 503));
    const onError = vi.fn();
    fireEvent.change(screen.getByLabelText('Nama'), { target: { value: 'Draf' } });

    await act(async () => router.reload({ only: ['statistics'], onError }));

    expect(onError).toHaveBeenCalledOnce();
    expect(screen.getByRole('heading').textContent).toBe('/');
    expect(screen.getByLabelText('Nama').value).toBe('Draf');
    expect(screen.queryByRole('alert')).toBeNull();
});

it('merges a partial reload with shared props while preserving the current draft', async () => {
    mount(payload('/', { statistics: { total: 1 } }));
    fetcher.mockResolvedValueOnce(response({ component: 'Test', url: '/', props: { viewer, csrfToken: 'csrf-test', statistics: { total: 8 } } }));
    fireEvent.change(screen.getByLabelText('Nama'), { target: { value: 'Draf' } });

    await act(async () => router.reload({ only: ['statistics'] }));

    expect(screen.getByText('8')).toBeTruthy();
    expect(screen.getByText('Made')).toBeTruthy();
    expect(screen.getByRole('heading').textContent).toBe('/');
    expect(screen.getByLabelText('Nama').value).toBe('Draf');
    expect(fetcher.mock.calls[0][1].headers).toMatchObject({ 'X-React-Only': 'statistics', 'X-React-Component': 'Test' });
});

it('renders deferred rescue content on a failed partial request without replacing the page', async () => {
    function DeferredPage() {
        return <><h1>Analitik</h1><Deferred data="statistics" fallback={<p>Memuat</p>} rescue={<p role="alert">Coba lagi</p>}><p>Statistik siap</p></Deferred></>;
    }
    fetcher.mockResolvedValueOnce(response({}, 503));
    mount(payload('/'), DeferredPage);

    expect(await screen.findByRole('alert')).toHaveProperty('textContent', 'Coba lagi');
    expect(screen.getByRole('heading').textContent).toBe('Analitik');
    expect(screen.queryByText('Memuat')).toBeNull();
    expect(fetcher).toHaveBeenCalledOnce();
});

it('shows the route error boundary for a failed full navigation', async () => {
    mount();
    fetcher.mockResolvedValueOnce(response({}, 404));

    await act(async () => router.visit('/missing'));

    expect(screen.getByRole('heading').textContent).toBe('Halaman tidak ditemukan');
    expect(screen.getByRole('link', { name: 'Muat ulang halaman' })).toBeTruthy();
});

it('uses the server canonical URL after a GET redirect without requesting its payload twice', async () => {
    mount();
    fetcher.mockResolvedValueOnce(response(payload('/canonical')));

    await act(async () => router.visit('/old'));

    expect(navigation.state.location.pathname).toBe('/canonical');
    expect(screen.getByRole('heading').textContent).toBe('/canonical');
    expect(fetcher).toHaveBeenCalledOnce();
});

it('reuses a warm catalog response once, then fetches fresh data on the next visit', async () => {
    mount();
    fetcher.mockResolvedValueOnce(response(payload('/belajar', { heading: 'Katalog hangat' }, 'Learning/Catalog')))
        .mockResolvedValueOnce(response(payload('/belajar', { heading: 'Katalog baru' }, 'Learning/Catalog')));
    await prefetchPage('/belajar');
    await prefetchPage('/belajar');
    expect(fetcher).toHaveBeenCalledOnce();

    await act(async () => router.visit('/belajar'));
    expect(screen.getByRole('heading').textContent).toBe('Katalog hangat');
    expect(fetcher).toHaveBeenCalledOnce();
    await act(async () => router.visit('/belajar'));

    expect(screen.getByRole('heading').textContent).toBe('Katalog baru');
    expect(fetcher).toHaveBeenCalledTimes(2);
});

it.each([419, 500])('keeps form input and reports a useful request error for HTTP %s', async (status) => {
    mount();
    fetcher.mockResolvedValueOnce(response({}, status));
    fireEvent.change(screen.getByLabelText('Nama'), { target: { value: 'Made' } });

    await act(async () => form.post('/profile'));

    expect(screen.getByRole('alert').textContent).toContain(status === 419 ? 'Sesi telah berakhir' : 'Permintaan belum berhasil');
    expect(screen.getByLabelText('Nama').value).toBe('Made');
    expect(navigation.state.location.pathname).toBe('/');
});

it('rejects malformed or external page responses instead of displaying them', async () => {
    mount();
    fetcher.mockResolvedValueOnce(response({ props: {}, url: '/' }))
        .mockResolvedValueOnce(response(payload('https://other.test/private')));

    await expect(requestPage('/bad')).rejects.toThrow();
    await expect(requestPage('/external')).rejects.toThrow();
    expect(screen.getByRole('heading').textContent).toBe('/');
});

it('lets the learner cancel leaving a dirty editor, then confirms departure through the native blocker', async () => {
    const page = payload('/kuis/baru', { quiz: null, links: { 'quizzes.store': '/kuis' }, navigation: { quizzes: '/kuis' }, brand: {} }, 'Quizzes/Editor');
    mount(page, Editor);
    const confirm = vi.spyOn(window, 'confirm').mockReturnValueOnce(false).mockReturnValueOnce(true);
    fetcher.mockResolvedValueOnce(response(payload('/kuis', { quiz: null }, 'Test')));
    fireEvent.change(screen.getByRole('textbox', { name: 'Judul kuis' }), { target: { value: 'Draf kuis' } });

    await act(async () => router.visit('/kuis'));
    expect(confirm).toHaveBeenCalledOnce();
    expect(navigation.state.location.pathname).toBe('/kuis/baru');
    expect(screen.getByRole('textbox', { name: 'Judul kuis' }).value).toBe('Draf kuis');
    expect(fetcher).not.toHaveBeenCalled();
    await act(async () => router.visit('/kuis'));

    await waitFor(() => expect(navigation.state.location.pathname).toBe('/kuis'));
    expect(confirm).toHaveBeenCalledTimes(2);
    expect(fetcher).toHaveBeenCalledOnce();
});

it('discards a partial reload response that arrives after navigating to another page', async () => {
    mount();
    const partial = pending();
    fetcher.mockReturnValueOnce(partial.promise).mockResolvedValueOnce(response(payload('/next')));
    const onSuccess = vi.fn();
    let reloading;
    act(() => { reloading = router.reload({ only: ['statistics'], onSuccess }); });

    await act(async () => router.visit('/next'));
    await act(async () => {
        partial.resolve(response(payload('/', { statistics: { total: 99 } })));
        await reloading;
    });

    expect(screen.getByRole('heading').textContent).toBe('/next');
    expect(screen.queryByText('99')).toBeNull();
    expect(onSuccess).not.toHaveBeenCalled();
    expect(fetcher).toHaveBeenCalledTimes(2);
});

it('expires catalog prefetch data after fifteen seconds', async () => {
    mount();
    const now = vi.spyOn(Date, 'now').mockReturnValue(100000);
    fetcher.mockResolvedValueOnce(response(payload('/belajar', { heading: 'Lama' }, 'Learning/Catalog')))
        .mockResolvedValueOnce(response(payload('/belajar', { heading: 'Baru' }, 'Learning/Catalog')));
    await prefetchPage('/belajar');
    now.mockReturnValue(115001);

    await act(async () => router.visit('/belajar'));

    expect(screen.getByRole('heading').textContent).toBe('Baru');
    expect(fetcher).toHaveBeenCalledTimes(2);
});

it('does not reuse prefetched catalog data belonging to another viewer', async () => {
    mount();
    fetcher.mockResolvedValueOnce(response(payload('/belajar', { heading: 'Private old catalog' }, 'Learning/Catalog')))
        .mockResolvedValueOnce(response(payload('/profile', { viewer: { ...viewer, id: 2, name: 'Komang' } })))
        .mockResolvedValueOnce(response(payload('/belajar', { heading: 'Komang catalog', viewer: { ...viewer, id: 2, name: 'Komang' } }, 'Learning/Catalog')));
    await prefetchPage('/belajar');
    await act(async () => router.visit('/profile'));

    await act(async () => router.visit('/belajar'));

    expect(screen.getByRole('heading').textContent).toBe('Komang catalog');
    expect(screen.queryByText('Private old catalog')).toBeNull();
    expect(fetcher).toHaveBeenCalledTimes(3);
});


it('starts one deferred request under Strict Mode and aborts it when the page unmounts', async () => {
    function DeferredPage() {
        return <><h1>Analitik</h1><Deferred data="statistics" fallback={<p>Memuat</p>} rescue={<p role="alert">Coba lagi</p>}><p>Statistik siap</p></Deferred></>;
    }
    fetcher.mockImplementationOnce((_url, { signal }) => new Promise((_resolve, reject) => {
        signal.addEventListener('abort', () => reject(new DOMException('Cancelled', 'AbortError')));
    }));
    const view = mount(payload('/'), DeferredPage);
    view.rerender(<StrictMode><RouterProvider router={navigation} /></StrictMode>);
    await waitFor(() => expect(fetcher).toHaveBeenCalledOnce());
    const { signal, headers } = fetcher.mock.calls[0][1];
    expect(headers['X-React-Only']).toBe('statistics');
    expect(signal.aborted).toBe(false);
    expect(screen.getByText('Memuat')).toBeTruthy();

    await act(async () => view.unmount());

    expect(signal.aborted).toBe(true);
    expect(fetcher).toHaveBeenCalledOnce();
    expect(navigation.state.location.pathname).toBe('/');
    expect(navigation.state.errors).toBeNull();
});

it('cancels deferred work before its scheduled request starts when immediately unmounted', async () => {
    function DeferredPage() {
        return <Deferred data="statistics" fallback={<p>Memuat</p>} rescue={<p>Coba lagi</p>}><p>Statistik siap</p></Deferred>;
    }
    vi.useFakeTimers();
    try {
        const view = mount(payload('/'), DeferredPage);

        view.unmount();
        await act(async () => vi.runOnlyPendingTimersAsync());

        expect(fetcher).not.toHaveBeenCalled();
    } finally {
        vi.useRealTimers();
    }
});

it('reports business errors returned by a successful redirect response without treating the form as saved', async () => {
    mount();
    const errors = { room_not_found: 'Room tidak ditemukan. Periksa kode dari host.' };
    fetcher.mockResolvedValueOnce(response(payload('/kuis?code=012345', { errors })));
    const onError = vi.fn(), onSuccess = vi.fn(), onFinish = vi.fn();
    fireEvent.change(screen.getByLabelText('Nama'), { target: { value: 'Made' } });

    await act(async () => form.post('/room/join', { onError, onSuccess, onFinish }));

    expect(screen.getByRole('alert').textContent).toBe(errors.room_not_found);
    expect(screen.getByLabelText('Nama').value).toBe('Made');
    expect(screen.getByLabelText('Form status').textContent).toBe('dirty');
    expect(onError).toHaveBeenCalledExactlyOnceWith(errors);
    expect(onSuccess).not.toHaveBeenCalled();
    expect(onFinish).toHaveBeenCalledOnce();
    expect(navigation.state.location.pathname).toBe('/');
    expect(fetcher).toHaveBeenCalledOnce();
});

it('redirects an expired session to the configured login page after HTTP 401 during full navigation', async () => {
    function SessionPage({ heading }) {
        return <><h1>{heading}</h1><p>{usePage().props.viewer?.name ?? 'Guest'}</p></>;
    }
    mount(payload('/', { navigation: { login: '/masuk' } }), SessionPage);
    fetcher.mockResolvedValueOnce(response({ message: 'Unauthenticated.' }, 401))
        .mockResolvedValueOnce(response(payload('/masuk', { heading: 'Masuk', viewer: null })));

    await act(async () => router.visit('/admin'));

    expect(navigation.state.location.pathname).toBe('/masuk');
    expect(screen.getByRole('heading').textContent).toBe('Masuk');
    expect(screen.getByText('Guest')).toBeTruthy();
    expect(screen.queryByRole('alert')).toBeNull();
    expect(fetcher.mock.calls.map(([url]) => new URL(url).pathname)).toEqual(['/admin', '/masuk']);
});

it('fetches a complete page instead of merging private props after a session changes during partial reload', async () => {
    mount(payload('/', { privateNote: 'Private previous session' }));
    fetcher.mockResolvedValueOnce(response(payload('/', { csrfToken: 'rotated', statistics: { total: 2 } })))
        .mockResolvedValueOnce(response(payload('/', { csrfToken: 'rotated', heading: 'New session' })));

    await act(async () => router.reload({ only: ['statistics'] }));

    expect(fetcher).toHaveBeenCalledTimes(2);
    expect(fetcher.mock.calls[1][1].headers['X-React-Only']).toBeUndefined();
    expect(screen.getByRole('heading').textContent).toBe('New session');
    expect(navigation.state.loaderData.page.props.privateNote).toBeUndefined();
});

it('shares an in-flight catalog prefetch between hover, focus, and navigation', async () => {
    mount();
    const request = pending();
    fetcher.mockReturnValueOnce(request.promise);
    const hover = prefetchPage('/belajar');
    const focus = prefetchPage('/belajar');
    let visit;
    await act(async () => {
        visit = router.visit('/belajar');
    });
    expect(fetcher).toHaveBeenCalledOnce();
    await act(async () => {
        request.resolve(response(payload('/belajar', { heading: 'Katalog' }, 'Learning/Catalog')));
        await Promise.all([hover, focus, visit]);
    });
    expect(screen.getByRole('heading').textContent).toBe('Katalog');
    expect(fetcher).toHaveBeenCalledOnce();
});

it('retries navigation normally when a shared prefetch fails', async () => {
    mount();
    const request = pending();
    fetcher.mockReturnValueOnce(request.promise)
        .mockResolvedValueOnce(response(payload('/belajar', { heading: 'Pulih' }, 'Learning/Catalog')));
    const hover = prefetchPage('/belajar').catch(() => null);
    let visit;
    await act(async () => { visit = router.visit('/belajar'); });
    await act(async () => {
        request.resolve(response({}, 503));
        await Promise.all([hover, visit]);
    });
    expect(fetcher).toHaveBeenCalledTimes(2);
    expect(screen.getByRole('heading').textContent).toBe('Pulih');
});
