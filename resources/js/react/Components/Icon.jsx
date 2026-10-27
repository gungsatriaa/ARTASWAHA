const paths = {
    home: 'm3 10 9-7 9 7M5 9v12h14V9M9 21v-7h6v7',
    book: 'M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22zM4 5.5v14A2.5 2.5 0 0 1 6.5 17H20',
    quiz: 'M8 5V3h8v2M6 5h12v16H6zM9 10h6M9 14h6M9 18h3',
    user: 'M16 8a4 4 0 1 1-8 0 4 4 0 0 1 8 0M4 21v-2a8 8 0 0 1 16 0v2',
    chat: 'M21 11a8 8 0 0 1-8 8H8l-5 3V11a9 9 0 0 1 18 0ZM8 11h.01M12 11h.01M16 11h.01',
    arrow: 'M5 12h14m-6-6 6 6-6 6',
    back: 'M19 12H5m6-6-6 6 6 6',
    close: 'm6 6 12 12M18 6 6 18',
    menu: 'M4 6h16M4 12h16M4 18h16',
    search: 'M21 21l-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',
    check: 'm5 12 4 4L19 6',
};

export default function Icon({ name, className = 'size-5', ...props }) {
    return <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className={className} aria-hidden="true" {...props}><path d={paths[name] ?? paths.book} /></svg>;
}
