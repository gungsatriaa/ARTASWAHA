const pages = import.meta.glob('./Pages/**/*.jsx');

export async function loadPage(name) {
    return pages[`./Pages/${name}.jsx`]();
}
