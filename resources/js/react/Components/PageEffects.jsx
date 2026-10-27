import { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import Skeleton from './Skeleton';

export default function PageEffects({ children }) {
    const { component, url, props } = usePage();
    const content = useRef(null);
    const [loading, setLoading] = useState(false);
    const pageKey = `${component}:${url?.split('?')[0]}:${component === 'Learning/Interview' ? props.step : ''}`;

    useEffect(() => {
        let timer;
        const start = router.on('start', (event) => {
            if (event.detail.visit.method !== 'get') return;
            clearTimeout(timer);
            timer = setTimeout(() => setLoading(true), 180);
        });
        const finish = router.on('finish', () => {
            clearTimeout(timer);
            setLoading(false);
        });
        return () => {
            clearTimeout(timer);
            start();
            finish();
        };
    }, []);

    useEffect(() => {
        const root = content.current;
        if (!root || !('IntersectionObserver' in window)) return;
        const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (preference.matches) return;
        const targets = [...root.querySelectorAll('[data-reveal]')];
        const reveal = (target) => {
            target.classList.remove('reveal-pending');
            observer.unobserve(target);
        };
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) reveal(entry.target);
                });
            },
            { threshold: 0.08 },
        );
        targets.forEach((target) => {
            if (target.getBoundingClientRect().top > window.innerHeight) {
                target.classList.add('reveal-pending');
                observer.observe(target);
            }
        });
        const focused = (event) => {
            const target = event.target.closest('[data-reveal]');
            if (target) reveal(target);
        };
        const reduce = () => {
            if (preference.matches) targets.forEach(reveal);
        };
        root.addEventListener('focusin', focused);
        root.addEventListener('pointerdown', focused);
        preference.addEventListener('change', reduce);
        return () => {
            observer.disconnect();
            targets.forEach((target) => target.classList.remove('reveal-pending'));
            root.removeEventListener('focusin', focused);
            root.removeEventListener('pointerdown', focused);
            preference.removeEventListener('change', reduce);
        };
    }, [pageKey]);

    return (
        <div ref={content} className="relative min-w-0" aria-busy={loading}>
            {loading && (
                <div className="fixed inset-x-4 top-24 z-30 mx-auto max-w-lg pointer-events-none">
                    <Skeleton label="Memuat halaman…" compact />
                </div>
            )}
            <div className="react-page-enter" key={pageKey}>
                {children}
            </div>
        </div>
    );
}
