import { afterEach, expect, it, vi } from 'vitest';
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import NavigationLink from '../../resources/js/react/Components/NavigationLink';
const { loadPage } = vi.hoisted(() => ({ loadPage: vi.fn().mockResolvedValue({}) }));
vi.mock('../../resources/js/react/pages', () => ({ loadPage }));
vi.mock('@inertiajs/react', () => ({ Link: ({ children, ...props }) => <a {...props}>{children}</a> }));
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
