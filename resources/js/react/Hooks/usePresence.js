import { useEffect } from 'react';

export default function usePresence(url) {
    useEffect(() => {
        if (!url) return;
        let controller;
        let lastPing = -Infinity;
        const ping = () => {
            if (document.visibilityState !== 'visible' || controller || Date.now() - lastPing < 30000) return;
            lastPing = Date.now();
            controller = new AbortController();
            const current = controller;
            const timeout = setTimeout(() => current.abort(), 10000);
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                signal: current.signal,
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            })
                .catch(() => {})
                .finally(() => {
                    clearTimeout(timeout);
                    if (controller === current) controller = undefined;
                });
        };
        const initial = window.setTimeout(ping, 0);
        const timer = window.setInterval(ping, 30000);
        document.addEventListener('visibilitychange', ping);
        return () => {
            window.clearTimeout(initial);
            window.clearInterval(timer);
            document.removeEventListener('visibilitychange', ping);
            controller?.abort();
        };
    }, [url]);
}
