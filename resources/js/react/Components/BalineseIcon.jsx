import { useEffect, useRef, useState } from 'react';

export default function BalineseIcon({ children, className = 'size-6' }) {
    const text = useRef(null);
    const [viewBox, setViewBox] = useState('-4 -28 40 40');
    useEffect(() => {
        let disposed = false;
        const center = () => {
            if (disposed || !text.current?.getBBox) return;
            let box = text.current.getBBox();
            if (typeof CanvasRenderingContext2D !== 'undefined') {
                const context = document.createElement('canvas').getContext('2d');
                if (context) {
                    context.font = `24px ${getComputedStyle(text.current).fontFamily}`;
                    const metrics = context.measureText(children);
                    const width = metrics.actualBoundingBoxLeft + metrics.actualBoundingBoxRight;
                    const height = metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent;
                    if (width > 0 && height > 0) {
                        box = { x: -metrics.actualBoundingBoxLeft, y: -metrics.actualBoundingBoxAscent, width, height };
                    }
                }
            }
            if (!box.width || !box.height) return;
            const side = Math.max(box.width, box.height) * 1.12;
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
