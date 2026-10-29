import { Link, prefetchPage, usePage } from '../routing';
import { loadPage } from '../pages';

export default function NavigationLink({ page, onMouseEnter, onFocus, onTouchStart, ...props }) {
    const { viewer } = usePage().props;
    const connection = typeof navigator !== 'undefined' ? navigator.connection : null;
    const constrained = connection?.saveData || ['slow-2g', '2g'].includes(connection?.effectiveType);
    const warm = () => {
        if (!page || constrained) return;
        // Keep live progress fresh and avoid enqueueing profile analysis on hover.
        loadPage(page).catch(() => {});
        if (page === 'Learning/Catalog' && viewer) prefetchPage(props.href).catch(() => {});
    };
    return (
        <Link
            {...props}
            prefetch={page === 'Learning/Catalog' && viewer && !constrained ? ['hover', 'click'] : false}
            cacheFor="15s"
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
