import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';

export default function useRoom(room, viewer, csrfToken) {
    const current = useRef(room),
        busy = useRef(false),
        reloadRef = useRef(null);
    const [unavailable, setUnavailable] = useState(null),
        [connection, setConnection] = useState('Memeriksa koneksi room…');
    current.current = room;
    const reload = () => {
        if (busy.current) return;
        busy.current = true;
        router.reload({
            only: ['room'],
            preserveScroll: true,
            onFinish: () => {
                busy.current = false;
            },
        });
    };
    reloadRef.current = reload;
    const live = !unavailable && ['waiting', 'active'].includes(room.status);
    useEffect(() => {
        if (!live) return;
        let disposed = false,
            controller = null,
            echo = null,
            timer = null;
        const stop = (status) => {
            if (!disposed) {
                setUnavailable(status);
                disposed = true;
                clearTimeout(timer);
            }
        };
        const check = async () => {
            if (disposed || controller || document.visibilityState !== 'visible') return;
            controller = new AbortController();
            const timeout = setTimeout(() => controller?.abort(), 10000);
            try {
                const response = await fetch(current.current.urls.status, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal,
                });
                if ([403, 404, 410].includes(response.status)) {
                    stop(response.status === 410 ? 'inactive' : 'deleted');
                    return;
                }
                if (!response.ok) throw new Error();
                const status = response.headers.get('X-Room-Status'),
                    count = Number(response.headers.get('X-Room-Participants'));
                if (!disposed) {
                    setConnection('Terhubung ke room');
                    if (status !== current.current.status || count !== current.current.participants.length)
                        reloadRef.current();
                }
            } catch {
                if (!disposed) setConnection('Koneksi terputus. Mencoba menyambungkan kembali…');
            } finally {
                clearTimeout(timeout);
                controller = null;
                if (!disposed) timer = setTimeout(check, 2500);
            }
        };
        const visible = () => {
            clearTimeout(timer);
            if (document.visibilityState === 'visible') check();
        };
        check();
        document.addEventListener('visibilitychange', visible);
        if (viewer?.mode === 'account' && import.meta.env.VITE_REVERB_APP_KEY && import.meta.env.VITE_REVERB_HOST) {
            Promise.all([import('laravel-echo'), import('pusher-js')])
                .then(([{ default: Echo }, { default: Pusher }]) => {
                    if (disposed) return;
                    echo = new Echo({
                        broadcaster: 'reverb',
                        Pusher,
                        key: import.meta.env.VITE_REVERB_APP_KEY,
                        wsHost: import.meta.env.VITE_REVERB_HOST,
                        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
                        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
                        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
                        enabledTransports: ['ws', 'wss'],
                        auth: { headers: { 'X-CSRF-TOKEN': csrfToken } },
                    });
                    echo.join(`rooms.${room.code}`)
                        .here(() => reloadRef.current())
                        .joining(() => reloadRef.current())
                        .leaving(() => reloadRef.current())
                        .listen('.room.updated', () => reloadRef.current());
                })
                .catch(() => {
                    if (!disposed) setConnection('Memakai pemeriksaan berkala untuk memperbarui room.');
                });
        }
        return () => {
            disposed = true;
            clearTimeout(timer);
            controller?.abort();
            document.removeEventListener('visibilitychange', visible);
            echo?.leave(`rooms.${room.code}`);
            echo?.disconnect();
        };
    }, [room.code, live, viewer?.mode, csrfToken]);
    useEffect(() => {
        if (!room.isHost || !live) return;
        let controller;
        const heartbeat = () => {
            if (document.visibilityState !== 'visible' || controller) return;
            controller = new AbortController();
            const timeout = setTimeout(() => controller?.abort(), 10000);
            fetch(room.urls.heartbeat, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                credentials: 'same-origin',
                signal: controller.signal,
            })
                .catch(() => {})
                .finally(() => {
                    clearTimeout(timeout);
                    controller = null;
                });
        };
        heartbeat();
        const timer = setInterval(heartbeat, 15000);
        // A reload also fires pagehide; tab closure is handled by the server's heartbeat expiry.
        const leave = () => {
            const body = new FormData();
            body.append('_token', csrfToken);
            navigator.sendBeacon(room.urls.leave, body);
        };
        const before = router.on('before', (event) => {
            if (
                event.detail.visit.method === 'get' &&
                event.detail.visit.url.pathname !== new URL(room.urls.show).pathname
            )
                leave();
        });
        return () => {
            clearInterval(timer);
            controller?.abort();
            before();
        };
    }, [room.code, room.isHost, live, csrfToken]);
    useEffect(() => {
        if (!room.feedback) return;
        const timer = setTimeout(
            () => router.get(room.urls.show, {}, { replace: true, preserveScroll: true, only: ['room'] }),
            3000,
        );
        return () => clearTimeout(timer);
    }, [room.feedback, room.question?.id, room.urls.show]);
    return { reload, unavailable, connection };
}
