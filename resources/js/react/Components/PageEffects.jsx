import { useEffect, useLayoutEffect, useRef, useState } from 'react';
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

    useLayoutEffect(() => {
        const root = content.current;
        if (!root || !('IntersectionObserver' in window)) return;
        const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
        if (preference.matches) return;
        const selector = '[data-reveal], .rounded-card, article, section, .react-page-content h1';
        const targets = new Set();
        const animations = new Set();
        const reveal = (target, delay = 0) => {
            target.classList.remove('reveal-pending');
            observer.unobserve(target);
            if (preference.matches) return;
            const animation = target.animate?.([
                { opacity: 0, transform: 'translateY(16px) scale(.99)' },
                { opacity: 1, transform: 'translateY(0) scale(1)' },
            ], { duration: 460, delay, easing: 'cubic-bezier(.22, 1, .36, 1)', fill: 'backwards' });
            if (animation) {
                animations.add(animation);
                animation.onfinish = () => animations.delete(animation);
            }
        };
        const observer = new IntersectionObserver((entries) => {
            entries.filter((entry) => entry.isIntersecting).forEach((entry, index) => {
                reveal(entry.target, Math.min(index, 3) * 55);
            });
        }, { threshold: 0.01 });
        const scan = () => {
            let visibleIndex = 0;
            root.querySelectorAll(selector).forEach((target) => {
                // Animate one container per card, so nested controls never move twice.
                if (targets.has(target)) return;
                if (!target.matches('[data-reveal], .rounded-card') && target.querySelector('[data-reveal], .rounded-card')) return;
                if ([...targets].some((container) => container.contains(target))) return;
                if (!target.getClientRects().length) return;
                targets.add(target);
                if (target.getBoundingClientRect().top < window.innerHeight) {
                    reveal(target, Math.min(visibleIndex++, 3) * 55);
                } else {
                    target.classList.add('reveal-pending');
                    observer.observe(target);
                }
            });
        };
        scan();
        const mutations = new MutationObserver(scan);
        mutations.observe(root, { childList: true, subtree: true });
        const focused = (event) => {
            const target = [...targets].find((item) => item.contains(event.target));
            if (target) {
                target.classList.remove('reveal-pending');
                observer.unobserve(target);
                animations.forEach((animation) => animation.finish());
            }
        };
        const reduce = () => {
            if (!preference.matches) return;
            targets.forEach((target) => target.classList.remove('reveal-pending'));
            animations.forEach((animation) => animation.cancel());
            observer.disconnect();
        };
        root.addEventListener('focusin', focused);
        root.addEventListener('pointerdown', focused);
        preference.addEventListener('change', reduce);
        return () => {
            observer.disconnect();
            mutations.disconnect();
            animations.forEach((animation) => animation.cancel());
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
