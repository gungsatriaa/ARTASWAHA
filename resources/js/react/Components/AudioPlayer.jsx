import { useEffect, useRef, useState } from 'react';

export default function AudioPlayer({ audio, label = 'Dengarkan materi', deadline, serverNow }) {
    const ref = useRef(null);
    const [failed, setFailed] = useState(false);
    useEffect(() => {
        const node = ref.current;
        const stop = () => {
            node?.pause();
        };
        const visible = () => {
            if (document.visibilityState !== 'visible') stop();
        };
        const other = (event) => {
            if (event.detail !== node) stop();
        };
        document.addEventListener('visibilitychange', visible);
        document.addEventListener('artaswaha:audio', other);
        const timer = deadline
            ? setTimeout(stop, Math.max(0, Date.parse(deadline) - (serverNow ? Date.parse(serverNow) : Date.now())))
            : null;
        return () => {
            stop();
            clearTimeout(timer);
            document.removeEventListener('visibilitychange', visible);
            document.removeEventListener('artaswaha:audio', other);
        };
    }, [audio?.url, deadline, serverNow]);
    if (!audio?.url) return null;
    return (
        <section className="my-5 rounded-2xl bg-primary/10 p-4" aria-label={label}>
            <p className="mb-3 text-sm font-bold text-accent">{label}</p>
            <audio
                key={audio.url}
                ref={ref}
                src={audio.url}
                controls
                preload="none"
                className="w-full"
                onPlay={() => document.dispatchEvent(new CustomEvent('artaswaha:audio', { detail: ref.current }))}
                onError={() => setFailed(true)}
            />
            {failed && (
                <p className="mt-2 text-sm text-danger">Audio belum dapat diputar. Periksa koneksi lalu muat ulang.</p>
            )}
        </section>
    );
}
