import { useEffect, useRef, useState } from 'react';
import { usePage } from '../routing';
import { useNavigation } from 'react-router';

export default function PageEffects({ children }) {
    const { component, url, props } = usePage();
    const content = useRef(null);
    const [loading, setLoading] = useState(false);
    const pageKey = `${component}:${url?.split('?')[0]}:${component === 'Learning/Interview' ? props.step : ''}`;

    const navigation = useNavigation();
    useEffect(() => {
        if (navigation.state === 'idle' || navigation.formMethod) {
            setLoading(false);
            return;
        }
        const timer = setTimeout(() => setLoading(true), 180);
        return () => clearTimeout(timer);
    }, [navigation.state, navigation.formMethod]);

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
                <span role="status" aria-label="Memuat halaman…" className="sr-only">
                    Memuat halaman…
                </span>
            )}
            <div className="react-page-content">{children}</div>
        </div>
    );
}
