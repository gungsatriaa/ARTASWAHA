import SiteLayout from './Layouts/SiteLayout';
import AdminLayout from './Layouts/AdminLayout';
import PageLoadError from './Components/PageLoadError';
const pages = import.meta.glob('./Pages/**/*.jsx');
export const title = (text) => (text.includes('ARTASWAHA') ? text : `${text} — ARTASWAHA`);
export async function resolve(name) {
    let page;
    try {
        page = await pages[`./Pages/${name}.jsx`]();
    } catch (error) {
        if (typeof window === 'undefined') throw error;
        page = { default: PageLoadError };
    }
    page.default.layout ??= (content) =>
        name.startsWith('Admin/') ? <AdminLayout>{content}</AdminLayout> : <SiteLayout>{content}</SiteLayout>;
    return page;
}
