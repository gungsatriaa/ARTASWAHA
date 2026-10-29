import SiteLayout from './Layouts/SiteLayout';
import AdminLayout from './Layouts/AdminLayout';
import AuthLayout from './Layouts/AuthLayout';
import PageLoadError from './Components/PageLoadError';
import { loadPage } from './pages';
export const title = (text) => (text.includes('ARTASWAHA') ? text : `${text} — ARTASWAHA`);
export async function resolve(name) {
    let page;
    try {
        page = await loadPage(name);
    } catch (error) {
        if (typeof window === 'undefined') throw error;
        page = { default: PageLoadError };
    }
    page.default.layout ??= (content) =>
        name.startsWith('Admin/') ? (
            <AdminLayout>{content}</AdminLayout>
        ) : name === 'Learning/Interview' ? (
            <AuthLayout onboarding>{content}</AuthLayout>
        ) : name.startsWith('Auth/') ? (
            <AuthLayout>{content}</AuthLayout>
        ) : (
            <SiteLayout>{content}</SiteLayout>
        );
    return page;
}
