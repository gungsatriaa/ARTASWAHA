import { useEffect, useRef, useState } from 'react';

export default function BalineseIcon({ children, className = 'size-6' }) {
    const text = useRef(null);
    const isCombiningMark = ['ᬶ', 'ᬂ', '᭄ᬬ'].includes(children);
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
        <span aria-hidden="true" className={`relative inline-grid shrink-0 place-items-center ${className}`}>
            <svg viewBox={viewBox} className="h-full w-full overflow-visible">
                {isCombiningMark ? (
                    <text x="0" y="0" fontSize="24" className="font-balinese">
                        <tspan fill="transparent">◌</tspan>
                        <tspan ref={text} lang="ban" fill="currentColor">
                            {children}
                        </tspan>
                    </text>
                ) : (
                    <text ref={text} lang="ban" x="0" y="0" fontSize="24" fill="currentColor" className="font-balinese">
                        {children}
                    </text>
                )}
            </svg>
        </span>
    );
}
