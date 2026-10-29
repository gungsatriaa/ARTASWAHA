import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { act, cleanup, render, screen, renderHook } from '@testing-library/react';
import { startBootLoader, markBootReady, failBootLoader } from '../../resources/js/react/boot-loader';
import PageEffects from '../../resources/js/react/Components/PageEffects';
import { useState } from 'react';
import { fireEvent } from '@testing-library/react';
import usePolling from '../../resources/js/react/Hooks/usePolling';
const { navigation, page } = vi.hoisted(() => ({
    navigation: { state: 'idle', formMethod: undefined },
    page: { component: 'Home', url: '/', props: {} },
}));
vi.mock('../../resources/js/react/routing', () => ({
    usePage: () => page,
}));
vi.mock('react-router', async (importOriginal) => ({
    ...await importOriginal(),
    useNavigation: () => navigation,
}));
let stop;

it('keeps the current screen mounted while navigation waits and avoids restarting the whole page animation', () => {
    function Draft() {
        const [value, setValue] = useState('');
        return <input aria-label="Catatan" value={value} onChange={(event) => setValue(event.target.value)} />;
    }
    const { rerender } = render(<PageEffects><Draft /></PageEffects>);
    fireEvent.change(screen.getByRole('textbox'), { target: { value: 'Tetap di sini' } });
    navigation.state = 'loading';
    rerender(<PageEffects><Draft /></PageEffects>);
    act(() => vi.advanceTimersByTime(500));
    expect(screen.getByRole('textbox').value).toBe('Tetap di sini');
    const original = page.url;
    page.url = '/belajar';
    rerender(<PageEffects><Draft /></PageEffects>);
    expect(screen.getByRole('textbox').value).toBe('Tetap di sini');
    expect(document.querySelector('.react-page-enter')).toBeNull();
    page.url = original;
});
beforeEach(() => {
    vi.useFakeTimers();
    vi.spyOn(HTMLElement.prototype, 'getClientRects').mockReturnValue([{}]);
    navigation.state = 'idle';
    navigation.formMethod = undefined;
    Object.defineProperty(document, 'visibilityState', { configurable: true, value: 'visible' });
    vi.stubGlobal('matchMedia', () => ({ matches: false, addEventListener: vi.fn(), removeEventListener: vi.fn() }));
});
afterEach(() => {
    stop?.();
    stop = null;
    cleanup();
    document.body.innerHTML = '';
    document.documentElement.classList.remove('page-loading');
    vi.useRealTimers();
    vi.unstubAllGlobals();
});
function loader() {
    document.documentElement.classList.add('page-loading');
    document.body.innerHTML =
        '<div id="app"><main tabindex="-1"></main></div><div data-react-loader><span data-loader-spinner></span><p data-loader-message>Loading Data</p></div>';
    stop = startBootLoader();
}
it('starts every document reload with a spinner and waits for React readiness before exiting', async () => {
    loader();
    await vi.advanceTimersByTimeAsync(1000);
    expect(document.getElementById('app')?.hasAttribute('inert')).toBe(true);
    expect(document.querySelector('[data-loader-message]').textContent).toBe('Loading Data');
    expect(document.querySelector('[data-loader-progress]')).toBeNull();
    expect(document.documentElement.classList.contains('page-loading')).toBe(true);
    markBootReady();
    await vi.advanceTimersByTimeAsync(1);
    expect(document.querySelector('[data-loader-spinner]').hasAttribute('hidden')).toBe(false);
    expect(document.documentElement.classList.contains('page-loading')).toBe(true);
    await vi.advanceTimersByTimeAsync(700);
    expect(document.documentElement.classList.contains('page-loading')).toBe(false);
    expect(document.getElementById('app')?.hasAttribute('inert')).toBe(false);
    loader();
    expect(document.querySelector('[data-loader-message]').textContent).toBe('Loading Data');
    stop();
    expect(vi.getTimerCount()).toBe(0);
});
it('does not pretend that a broken page loaded successfully and releases the overlay', async () => {
    loader();
    failBootLoader();
    expect(document.querySelector('[data-loader-message]').textContent).toContain('belum dapat dimuat');
    expect(document.querySelector('[data-loader-spinner]').hasAttribute('hidden')).toBe(true);
    await vi.advanceTimersByTimeAsync(5100);
    expect(document.documentElement.classList.contains('page-loading')).toBe(false);
});
it('announces slow GET navigation without a floating skeleton, never delays answer POSTs, and cleans pending timers', () => {
    const { unmount, rerender } = render(
        <PageEffects>
            <h1>Halaman</h1>
        </PageEffects>,
    );
    navigation.state = 'loading';
    navigation.formMethod = 'post';
    rerender(<PageEffects><h1>Halaman</h1></PageEffects>);
    act(() => vi.advanceTimersByTime(300));
    expect(screen.queryByRole('status')).toBeNull();
    navigation.formMethod = undefined;
    rerender(<PageEffects><h1>Halaman</h1></PageEffects>);
    act(() => vi.advanceTimersByTime(181));
    expect(screen.getByRole('status', { name: 'Memuat halaman…' })).toBeTruthy();
    expect(screen.getByRole('status', { name: 'Memuat halaman…' }).classList.contains('sr-only')).toBe(true);
    expect(document.querySelector('.skeleton-line')).toBeNull();
    navigation.state = 'idle';
    rerender(<PageEffects><h1>Halaman</h1></PageEffects>);
    expect(screen.queryByRole('status')).toBeNull();
    navigation.state = 'loading';
    rerender(<PageEffects><h1>Halaman</h1></PageEffects>);
    unmount();
    expect(vi.getTimerCount()).toBe(0);
});
it('caps polling attempts including tab reactivation and reports exhaustion instead of spinning forever', async () => {
    const fetch = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ status: 'processing' }) });
    vi.stubGlobal('fetch', fetch);
    const { result, unmount } = renderHook(() => usePolling('/pending', { limit: 2, interval: 100 }));
    await act(async () => {
        await vi.advanceTimersByTimeAsync(101);
    });
    expect(fetch).toHaveBeenCalledTimes(2);
    expect(result.current.exhausted).toBe(true);
    act(() => document.dispatchEvent(new Event('visibilitychange')));
    await act(async () => {
        await vi.advanceTimersByTimeAsync(1000);
    });
    expect(fetch).toHaveBeenCalledTimes(2);
    unmount();
    expect(vi.getTimerCount()).toBe(0);
});

it('reveals below-fold content on scroll or focus and disconnects its observer', () => {
    let observer;
    vi.stubGlobal(
        'IntersectionObserver',
        class {
            constructor(callback) {
                this.callback = callback;
                this.observe = vi.fn();
                this.unobserve = vi.fn();
                this.disconnect = vi.fn();
                observer = this;
            }
        },
    );
    vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockReturnValue({ top: 2000 });
    const { unmount } = render(
        <PageEffects>
            <section data-reveal>
                <button>Buka materi</button>
            </section>
        </PageEffects>,
    );
    const card = screen.getByRole('button').closest('section');
    expect(card.classList.contains('reveal-pending')).toBe(true);
    act(() => screen.getByRole('button').focus());
    expect(card.classList.contains('reveal-pending')).toBe(false);
    expect(observer.unobserve).toHaveBeenCalledWith(card);
    unmount();
    expect(observer.disconnect).toHaveBeenCalledOnce();
});

it('keeps content immediately visible for reduced motion', () => {
    const observer = vi.fn();
    vi.stubGlobal('IntersectionObserver', observer);
    vi.stubGlobal('matchMedia', () => ({ matches: true }));
    render(
        <PageEffects>
            <section data-reveal>Materi</section>
        </PageEffects>,
    );
    expect(observer).not.toHaveBeenCalled();
    expect(screen.getByText('Materi').classList.contains('reveal-pending')).toBe(false);
});

it('does not replace a successful completion with a timeout during its closing animation', async () => {
    loader();
    await vi.advanceTimersByTimeAsync(7900);
    markBootReady();
    await vi.advanceTimersByTimeAsync(200);
    expect(document.querySelector('[data-loader-spinner]').hasAttribute('hidden')).toBe(false);
    expect(document.querySelector('[data-loader-message]').textContent).not.toContain('belum dapat dimuat');
    await vi.advanceTimersByTimeAsync(600);
    expect(document.documentElement.classList.contains('page-loading')).toBe(false);
});

it('animates visible cards on navigation, observes new content, and cancels on unmount', async () => {
    const animation = { cancel: vi.fn(), finish: vi.fn() };
    const animate = vi.fn(() => animation);
    vi.stubGlobal('matchMedia', () => ({ matches: false, addEventListener() {}, removeEventListener() {} }));
    vi.stubGlobal('IntersectionObserver', class {
        constructor() {}
        observe() {}
        unobserve() {}
        disconnect() {}
    });
    vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockReturnValue({ top: 20 });
    Object.defineProperty(HTMLElement.prototype, 'animate', { configurable: true, value: animate });
    const { rerender, unmount } = render(<PageEffects><section className="rounded-card">Materi<button>Mulai</button></section></PageEffects>);
    expect(animate).toHaveBeenCalledTimes(1);
    expect(animate.mock.calls[0][1]).toMatchObject({ duration: 460, delay: 0 });
    const original = page.url;
    page.url = '/materi-baru';
    rerender(<PageEffects><section className="rounded-card">Materi baru<button>Mulai</button></section><article>Modul</article></PageEffects>);
    expect(animate).toHaveBeenCalledTimes(3);
    await act(async () => { rerender(<PageEffects><section className="rounded-card">Materi baru</section><article>Modul</article><section>Hasil baru</section></PageEffects>); });
    expect(animate).toHaveBeenCalledTimes(4);
    unmount();
    expect(animation.cancel).toHaveBeenCalled();
    page.url = original;
    delete HTMLElement.prototype.animate;
});
