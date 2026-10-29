import { createContext, useContext, useEffect, useRef, useState } from 'react';
import { Link as RouterLink, useLoaderData, useRouteError, ScrollRestoration, redirect } from 'react-router';
export { default as Head } from './Components/Head';

export const PageContext = createContext(null);
export const usePage = () => useContext(PageContext);
let instance;
let currentPage;
let resolvePage;
let nextVisit;
let stagedPage;
let sequence = 0;
const catalogCache = new Map();

const localUrl = (value) => {
    const url = new URL(value, window.location.href);
    if (url.origin !== window.location.origin) throw new Error('Tujuan navigasi harus berada di platform ini.');
    return url;
};
const pathOf = (url) => `${url.pathname}${url.search}${url.hash}`;
const identityOf = (page) => JSON.stringify([page?.props?.viewer ?? null, page?.props?.csrfToken]);

export class PageRequestError extends Error {
    constructor(status, errors = {}, message = 'Halaman belum dapat dimuat. Silakan coba lagi.') {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

export function configureNavigation(routerInstance, initialPage, resolver) {
    instance = routerInstance;
    currentPage = initialPage;
    resolvePage = resolver;
    catalogCache.clear();
    nextVisit = null;
    stagedPage = null;
}

export async function requestPage(url, { signal, method = 'get', data, only = [], forceFormData = false } = {}) {
    const headers = { Accept: 'application/json', 'X-React-Page': 'true', 'X-Requested-With': 'XMLHttpRequest' };
    if (only.length && currentPage) {
        headers['X-React-Only'] = only.join(',');
        headers['X-React-Component'] = currentPage.component;
    }
    let body;
    if (method !== 'get') {
        headers['X-CSRF-TOKEN'] = currentPage?.props?.csrfToken ?? '';
        if (forceFormData || containsFile(data)) {
            body = new FormData();
            appendFields(body, data ?? {});
        } else {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(data ?? {});
        }
    }
    const response = await fetch(localUrl(url), { method: method.toUpperCase(), headers, body, signal, credentials: 'same-origin', cache: 'no-store' });
    const result = response.headers.get('content-type')?.includes('application/json') ? await response.json() : null;
    if (!response.ok) {
        const errors = Object.fromEntries(Object.entries(result?.errors ?? {}).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]));
        throw new PageRequestError(response.status, errors, response.status === 419 ? 'Sesi telah berakhir. Muat ulang halaman sebelum mencoba lagi.' : 'Permintaan belum berhasil. Silakan coba lagi.');
    }
    if (!result?.component || !result.props || typeof result.url !== 'string') throw new PageRequestError(502);
    if (localUrl(result.url).origin !== window.location.origin) throw new PageRequestError(502);
    return result;
}

export async function pageLoader({ request }) {
    const url = pathOf(new URL(request.url));
    const visit = nextVisit?.url === url ? nextVisit : null;
    const options = visit?.options ?? {};
    try {
        let page;
        if (stagedPage?.url === url) {
            page = stagedPage.page;
            stagedPage = null;
        } else {
            const cached = catalogCache.get(url);
            page = cached && cached.expires > Date.now() && cached.identity === identityOf(currentPage)
                ? cached.page
                : await requestPage(url, { signal: request.signal, only: options.only });
            catalogCache.delete(url);
        }
        if (request.signal.aborted) throw new DOMException('Aborted', 'AbortError');
        const canonical = pathOf(localUrl(page.url));
        if (canonical !== url) {
            stagedPage = { url: canonical, page };
            return redirect(canonical);
        }
        if (options.only?.length && page.component === currentPage?.component && identityOf(page) !== identityOf(currentPage)) {
            page = await requestPage(page.url, { signal: request.signal });
        }
        if (options.only?.length && page.component === currentPage?.component && identityOf(page) === identityOf(currentPage)) {
            page.props = { ...currentPage.props, ...page.props };
        }
        if (identityOf(page) !== identityOf(currentPage)) catalogCache.clear();
        const module = await resolvePage(page.component);
        if (request.signal.aborted) throw new DOMException('Aborted', 'AbortError');
        page = { ...page, Component: module.default, stateKey: options.preserveState && page.component === currentPage?.component && identityOf(page) === identityOf(currentPage) ? currentPage.stateKey : ++sequence };
        if (visit) visit.result = page;
        return page;
    } catch (error) {
        if (error.status === 401) return redirect(pathOf(localUrl(currentPage?.props?.navigation?.login ?? '/masuk')));
        if (visit) visit.error = error;
        throw error;
    }
}

export function commitPage(page) {
    currentPage = page;
}

async function visit(url, data = {}, options = {}) {
    const destination = localUrl(url);
    Object.entries(data).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') destination.searchParams.set(key, value);
        else destination.searchParams.delete(key);
    });
    const operation = { url: pathOf(destination), options };
    nextVisit = operation;
    options.onStart?.();
    try {
        if (options.only?.length && !stagedPage) {
            const originPage = currentPage;
            const page = await requestPage(operation.url, { only: options.only, signal: options.signal ? AbortSignal.any([options.signal, AbortSignal.timeout(30000)]) : AbortSignal.timeout(30000) });
            if (originPage !== currentPage || nextVisit !== operation) return null;
            operation.url = pathOf(localUrl(page.url));
            stagedPage = { url: operation.url, page };
        }
        await instance.navigate(operation.url, { replace: Boolean(options.replace), preventScrollReset: Boolean(options.preserveScroll) });
        if (operation.error) throw operation.error;
        if (operation.result) options.onSuccess?.(operation.result);
        return operation.result;
    } catch (error) {
        if (error.status === 401) await visit(currentPage?.props?.navigation?.login ?? '/masuk');
        else if (error.name !== 'AbortError') options.onError?.({ request: error.message, ...error.errors });
        return null;
    } finally {
        if (nextVisit === operation) nextVisit = null;
        options.onFinish?.();
    }
}

export const router = {
    get: visit,
    visit: (url, options = {}) => visit(url, {}, options),
    reload: (options = {}) => visit(window.location.href, {}, { preserveState: true, preserveScroll: true, replace: true, ...options }),
};

export async function prefetchPage(url) {
    const key = pathOf(localUrl(url));
    if (catalogCache.get(key)?.expires > Date.now()) return;
    const identity = identityOf(currentPage);
    const page = await requestPage(key);
    if (page.component !== 'Learning/Catalog' || identityOf(page) !== identity || identity !== identityOf(currentPage)) return;
    if (catalogCache.size >= 8) catalogCache.delete(catalogCache.keys().next().value);
    catalogCache.set(key, { page, identity, expires: Date.now() + 15000 });
}

export function Link({ href, children, replace, preserveScroll, preserveState, only, prefetch, cacheFor, onClick, ...props }) {
    const external = /^(?:https?:)?\/\//.test(href ?? '') && typeof window !== 'undefined' && new URL(href, window.location.href).origin !== window.location.origin;
    const intercepted = (event) => {
        onClick?.(event);
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || props.target === '_blank' || props.download !== undefined || href?.startsWith('#')) return;
        event.preventDefault();
        router.get(href, {}, { replace, preserveScroll, preserveState, only });
    };
    if (external || props.download !== undefined || /^(mailto:|tel:)/.test(href ?? '')) return <a href={href} onClick={onClick} {...props}>{children}</a>;
    return <RouterLink to={href ?? '#'} replace={replace} preventScrollReset={preserveScroll} onClick={intercepted} {...props}>{children}</RouterLink>;
}

function containsFile(value) {
    return value instanceof Blob || (value && typeof value === 'object' && Object.values(value).some(containsFile));
}
function appendFields(body, values, prefix = '') {
    Object.entries(values).forEach(([key, value]) => {
        const name = prefix ? `${prefix}[${key}]` : key;
        if (value instanceof Blob) body.append(name, value);
        else if (value !== null && typeof value === 'object') appendFields(body, value, name);
        else body.append(name, value === null ? '' : typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
    });
}
function equal(a, b) {
    if (a === b) return true;
    if (!a || !b || typeof a !== 'object' || typeof b !== 'object' || a instanceof Blob || b instanceof Blob) return false;
    const keys = Object.keys(a);
    return keys.length === Object.keys(b).length && keys.every((key) => equal(a[key], b[key]));
}

export function useForm(initial) {
    const defaults = useRef(initial);
    const [data, updateData] = useState(initial);
    const dataRef = useRef(data);
    const transform = useRef((value) => value);
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);
    const controller = useRef(null);
    const mounted = useRef(true);
    useEffect(() => { mounted.current = true; return () => { mounted.current = false; controller.current?.abort(); }; }, []);
    const setData = (key, value) => {
        const next = typeof key === 'string' ? { ...dataRef.current, [key]: value } : typeof key === 'function' ? key(dataRef.current) : key;
        dataRef.current = next;
        updateData(next);
    };
    const submit = async (method, url, options = {}) => {
        if (controller.current) return;
        controller.current = new AbortController();
        setProcessing(true);
        setErrors({});
        catalogCache.clear();
        options.onStart?.();
        try {
            const page = await requestPage(url, { method, data: transform.current(dataRef.current), signal: controller.current.signal, forceFormData: options.forceFormData });
            if (Object.keys(page.props.errors ?? {}).length) throw new PageRequestError(422, page.props.errors);
            stagedPage = { url: pathOf(localUrl(page.url)), page };
            const result = await visit(page.url, {}, { preserveState: true, preserveScroll: true, ...options, onStart: undefined, onSuccess: undefined, onFinish: undefined, onError: (next) => { throw new PageRequestError(500, next); } });
            if (result) options.onSuccess?.(result);
        } catch (error) {
            if (error.status === 401) await visit(currentPage?.props?.navigation?.login ?? '/masuk');
            else if (mounted.current && error.name !== 'AbortError') {
                const next = Object.keys(error.errors ?? {}).length ? error.errors : { request: error.message };
                setErrors(next);
                options.onError?.(next);
            }
        } finally {
            controller.current = null;
            if (mounted.current) setProcessing(false);
            options.onFinish?.();
        }
    };
    return {
        data, errors, processing, isDirty: !equal(data, defaults.current), setData,
        transform: (callback) => { transform.current = callback; },
        reset: (...fields) => setData(fields.length ? { ...dataRef.current, ...Object.fromEntries(fields.map((key) => [key, defaults.current[key]])) } : defaults.current),
        post: (url, options) => submit('post', url, options),
        put: (url, options) => submit('put', url, options),
        delete: (url, options) => submit('delete', url, options),
    };
}

export function Deferred({ data, fallback, rescue, children }) {
    const page = usePage();
    const [failed, setFailed] = useState(false);
    const missing = page.props[data] === undefined;
    useEffect(() => {
        if (!missing) { setFailed(false); return; }
        let disposed = false;
        const controller = new AbortController();
        const timer = setTimeout(() => router.reload({ only: [data], signal: controller.signal, onError: () => { if (!disposed) setFailed(true); } }), 0);
        return () => { disposed = true; clearTimeout(timer); controller.abort(); };
    }, [data, page.component, missing]);
    return missing ? (failed ? rescue : fallback) : typeof children === 'function' ? children() : children;
}

export function PageView() {
    const page = useLoaderData();
    if (typeof window !== 'undefined') commitPage(page);
    const Component = page.Component;
    const content = <Component key={page.stateKey} {...page.props} />;
    return <PageContext.Provider value={page}>{Component.layout ? Component.layout(content) : content}<ScrollRestoration /></PageContext.Provider>;
}

export function RouteError() {
    const error = useRouteError();
    return <main className="mx-auto max-w-xl p-6 sm:p-10" role="alert"><h1 className="font-display text-2xl font-bold">{error?.status === 404 ? 'Halaman tidak ditemukan' : 'Halaman belum dapat dimuat'}</h1><p className="mt-4 leading-7 text-foreground/60">Periksa koneksi lalu coba lagi. Data yang sudah tersimpan tetap aman.</p><a className="mt-6 inline-flex min-h-11 items-center rounded-full bg-accent px-5 font-bold text-background" href={typeof window === 'undefined' ? '/' : window.location.href}>Muat ulang halaman</a></main>;
}
