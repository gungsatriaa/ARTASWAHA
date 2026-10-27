export default function Skeleton({ label = 'Memuat konten…', compact = false }) {
    return (
        <div
            role="status"
            aria-label={label}
            className={`min-w-0 rounded-card bg-surface p-5 sm:p-7 ${compact ? '' : 'min-h-56'}`}
        >
            <span className="sr-only">{label}</span>
            <span aria-hidden="true" className="block space-y-4">
                <span className="skeleton-line block h-5 w-2/3 rounded-full" />
                <span className="skeleton-line block h-3 w-full rounded-full" />
                <span className="skeleton-line block h-3 w-4/5 rounded-full" />
                {!compact && <span className="skeleton-line mt-6 block h-24 w-full rounded-2xl" />}
            </span>
        </div>
    );
}
