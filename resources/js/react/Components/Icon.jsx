import BalineseIcon from './BalineseIcon';
const paths = {
    mail: 'M3 5h18v14H3zM3 7l9 6 9-6',
    phone: 'M7 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-3l-5-2-2 2a15 15 0 0 1-7-7l2-2z',
    chevron: 'm6 9 6 6 6-6',
    aksara: 'M4 18 10 5l6 13M6 14h8M17 10l4 8M19 10v8',
    edit: 'm15 5 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15z',
    info: 'M12 10v7M12 7h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0',
    login: 'M14 4h6v16h-6M3 12h12m-4-4 4 4-4 4',
    calendar: 'M7 3v4M17 3v4M4 10h16M4 5h16v16H4zM8 15l2 2 5-5',
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
    chart: 'M4 4v16h16M8 15l4-5 4 2 4-7',
    spark: 'm12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5z',
    audio: 'M4 10v4h4l5 4V6l-5 4zM17 9a5 5 0 0 1 0 6M20 6a9 9 0 0 1 0 12',
    route: 'M7 19h6a5 5 0 0 0 5-5V7M4 17h4v4H4zM16 3h4v4h-4z',
};

export default function Icon({ name, className = 'size-5', ...props }) {
    if (name === 'aksara') {
        return <BalineseIcon className={className}>ᬳ</BalineseIcon>;
    }
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
            aria-hidden="true"
            {...props}
        >
            <path d={paths[name] ?? paths.book} />
        </svg>
    );
}
