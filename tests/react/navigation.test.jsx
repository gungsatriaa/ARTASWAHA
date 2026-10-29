import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import NavigationLink from '../../resources/js/react/Components/NavigationLink';
const { loadPage } = vi.hoisted(() => ({ loadPage: vi.fn().mockResolvedValue({}) }));
vi.mock('../../resources/js/react/pages', () => ({ loadPage }));
const { page, linkProps } = vi.hoisted(() => ({ page: { props: { viewer: { mode: 'account' } } }, linkProps: vi.fn() }));
vi.mock('@inertiajs/react', () => ({
    usePage: () => page,
    Link: ({ children, prefetch, cacheFor, ...props }) => {
        linkProps({ prefetch, cacheFor });
        return <a {...props}>{children}</a>;
    },
}));
beforeEach(() => { page.props.viewer = { mode: 'account' }; linkProps.mockClear(); });
afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
    loadPage.mockClear();
    delete navigator.connection;
});

it('only warms page code on navigation intent without prefetching user data', () => {
    const focus = vi.fn();
    render(<NavigationLink page="Profile/Show" href="/profil" onFocus={focus}>Profil</NavigationLink>);
    const link = screen.getByRole('link', { name: 'Profil' });
    expect(loadPage).not.toHaveBeenCalled();
    fireEvent.mouseEnter(link);
    fireEvent.focus(link);
    fireEvent.touchStart(link);
    expect(loadPage).toHaveBeenCalledTimes(3);
    expect(loadPage).toHaveBeenLastCalledWith('Profile/Show');
    expect(focus).toHaveBeenCalledOnce();
    expect(link.hasAttribute('prefetch')).toBe(false);
    expect(link.getAttribute('href')).toBe('/profil');
});

it.each([{ saveData: true }, { effectiveType: '2g' }, { effectiveType: 'slow-2g' }])('skips speculative code downloads on constrained connections %j', (connection) => {
    Object.defineProperty(navigator, 'connection', { configurable: true, get: () => connection });
    render(<NavigationLink page="Learning/Catalog" href="/belajar">Belajar</NavigationLink>);
    fireEvent.focus(screen.getByRole('link'));
    expect(loadPage).not.toHaveBeenCalled();
});

it('leaves normal navigation available when a code preload fails', async () => {
    loadPage.mockRejectedValueOnce(new Error('Network offline'));
    render(<NavigationLink page="Home" href="/">Beranda</NavigationLink>);
    fireEvent.mouseEnter(screen.getByRole('link'));
    await Promise.resolve();
    expect(screen.getByRole('link').getAttribute('href')).toBe('/');
});

it.each(['account', 'anonymous'])('prefetches only the read-only catalog for %s viewers for 15 seconds', (mode) => {
    page.props.viewer = { mode };
    render(<NavigationLink page="Learning/Catalog" href="/belajar">Belajar</NavigationLink>);
    expect(linkProps).toHaveBeenLastCalledWith({ prefetch: ['hover', 'click'], cacheFor: '15s' });
});

it.each(['Learning/Lesson', 'Learning/Path', 'Home', 'Quizzes/Index', 'Profile/Show', 'Rooms/Show'])('never prefetches %s response data', (component) => {
    render(<NavigationLink page={component} href="/tujuan">Tujuan</NavigationLink>);
    expect(linkProps.mock.lastCall[0].prefetch).toBe(false);
});

it('does not prefetch the guest interview redirect or data on Save-Data', () => {
    page.props.viewer = null;
    const view = render(<NavigationLink page="Learning/Catalog" href="/belajar">Belajar</NavigationLink>);
    expect(linkProps.mock.lastCall[0].prefetch).toBe(false);
    page.props.viewer = { mode: 'account' };
    Object.defineProperty(navigator, 'connection', { configurable: true, value: { saveData: true } });
    view.rerender(<NavigationLink page="Learning/Catalog" href="/belajar">Belajar</NavigationLink>);
    expect(linkProps.mock.lastCall[0].prefetch).toBe(false);
});
