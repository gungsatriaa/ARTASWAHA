import { useEffect, useRef, useState } from 'react';

export default function BalineseIcon({ children, className = 'size-6' }) {
    const text = useRef(null);
    const [viewBox, setViewBox] = useState('-4 -28 40 40');
    useEffect(() => {
        let disposed = false;
        const center = () => {
            if (disposed || !text.current?.getBBox) return;
            const box = text.current.getBBox();
            if (!box.width || !box.height) return;
            const side = Math.max(box.width, box.height, 24) + 4;
            setViewBox(`${box.x + (box.width - side) / 2} ${box.y + (box.height - side) / 2} ${side} ${side}`);
        };
        center();
        document.fonts?.ready.then(center);
        return () => {
            disposed = true;
        };
    }, [children]);
    return (
        <svg aria-hidden="true" viewBox={viewBox} className={`shrink-0 overflow-visible ${className}`}>
            <text ref={text} lang="ban" x="0" y="0" fontSize="24" fill="currentColor" className="font-balinese">
                {children}
            </text>
        </svg>
    );
}
