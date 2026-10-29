import { Link } from '@inertiajs/react';
import { loadPage } from '../pages';

export default function NavigationLink({ page, onMouseEnter, onFocus, onTouchStart, ...props }) {
    const warm = () => {
        const connection = navigator.connection;
        if (!page || connection?.saveData || ['slow-2g', '2g'].includes(connection?.effectiveType)) return;
        // Only download page code: prefetching GET data can start a lesson or enqueue AI analysis.
        loadPage(page).catch(() => {});
    };
    return (
        <Link
            {...props}
            onMouseEnter={(event) => {
                warm();
                onMouseEnter?.(event);
            }}
            onFocus={(event) => {
                warm();
                onFocus?.(event);
            }}
            onTouchStart={(event) => {
                warm();
                onTouchStart?.(event);
            }}
        />
    );
}
