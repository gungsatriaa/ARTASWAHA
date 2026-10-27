import { useEffect, useState } from 'react';

export default function usePolling(url, { active = true, interval = 5000, limit = 120 } = {}) {
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);
    const [exhausted, setExhausted] = useState(false);
    useEffect(() => {
        if (!url || !active) return;
        setError(null);
        setExhausted(false);
        let timer,
            controller,
            disposed = false,
            attempts = 0;
        const schedule = () => {
            clearTimeout(timer);
            if (disposed) return;
            if (attempts < limit) timer = setTimeout(load, interval);
            else setExhausted(true);
        };
        const load = async () => {
            if (disposed || controller || attempts >= limit) return;
            if (document.visibilityState !== 'visible') {
                schedule();
                return;
            }
            attempts++;
            controller = new AbortController();
            const current = controller;
            const timeout = setTimeout(() => current.abort(), 15000);
            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: current.signal,
                });
                if (!response.ok) {
                    if ([401, 403, 404, 410, 429].includes(response.status)) attempts = limit;
                    throw new Error('Status belum dapat diperbarui. Muat ulang halaman untuk mencoba lagi.');
                }
                const result = await response.json();
                if (!disposed) {
                    setData({ url, result });
                    setError(null);
                }
            } catch (exception) {
                if (!disposed)
                    setError(
                        exception.name === 'AbortError' ? 'Koneksi terlalu lama. Mencoba kembali…' : exception.message,
                    );
            } finally {
                clearTimeout(timeout);
                controller = null;
                schedule();
            }
        };
        const visible = () => {
            if (document.visibilityState === 'visible') {
                clearTimeout(timer);
                load();
            }
        };
        load();
        document.addEventListener('visibilitychange', visible);
        return () => {
            disposed = true;
            clearTimeout(timer);
            controller?.abort();
            document.removeEventListener('visibilitychange', visible);
        };
    }, [url, active, interval, limit]);
    return { data: data && data.url === url ? data.result : null, error: url ? error : null, exhausted };
}
