import { useEffect, useRef, useState } from 'react';
export default function Chart({ labels, series, type = 'line', label, percentage = false }) {
    const canvas = useRef(null),
        [error, setError] = useState(false);
    useEffect(() => {
        let disposed = false,
            chart;
        import('chart.js/auto')
            .then(({ default: Chart }) => {
                if (disposed) return;
                const font = { family: getComputedStyle(canvas.current).fontFamily };
                chart = new Chart(canvas.current, {
                    type,
                    data: {
                        labels,
                        datasets: series.map((item, index) => ({
                            label: item.label,
                            data: item.data,
                            borderColor: index ? '#ffc700' : '#20aaa8',
                            backgroundColor:
                                type === 'bar' ? (index ? '#ffc700' : '#20aaa8') : index ? '#ffc70012' : '#007c8020',
                            borderRadius: type === 'bar' ? 5 : undefined,
                            maxBarThickness: 28,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            pointHitRadius: 12,
                            tension: 0.35,
                            fill: type === 'line',
                        })),
                    },
                    options: {
                        responsive: true,
                        interaction: { mode: 'index', intersect: false },
                        maintainAspectRatio: false,
                        animation: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                            ? false
                            : { duration: 350 },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { font, color: '#f5f3eeaa', usePointStyle: true, boxWidth: 6, padding: 20 },
                            },
                            tooltip: {
                                titleFont: font,
                                bodyFont: font,
                                backgroundColor: '#1c2022',
                                titleColor: '#f5f3ee',
                                bodyColor: '#f5f3eebb',
                                padding: 12,
                                cornerRadius: 12,
                            },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { font, color: '#f5f3ee66', maxTicksLimit: 7 } },
                            y: {
                                beginAtZero: true,
                                ...(percentage ? { max: 100 } : {}),
                                grid: { color: '#ffffff08' },
                                ticks: { font, color: '#f5f3ee66', precision: 0 },
                            },
                        },
                    },
                });
            })
            .catch(() => setError(true));
        return () => {
            disposed = true;
            chart?.destroy();
        };
    }, [labels, series, type, percentage]);
    return (
        <>
            {error ? (
                <p role="alert" className="py-8 text-sm text-danger">
                    Grafik belum dapat dimuat. Data tetap tersedia di tabel berikut.
                </p>
            ) : (
                <div className="mt-5 h-64 sm:h-80">
                    <canvas ref={canvas} role="img" aria-label={label} />
                </div>
            )}
            <details className="mt-4">
                <summary className="min-h-11 cursor-pointer content-center text-xs font-semibold text-foreground/50">
                    Lihat tabel data
                </summary>
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs">
                        <caption className="sr-only">{label}</caption>
                        <thead>
                            <tr>
                                <th className="p-2">Tanggal</th>
                                {series.map((item) => (
                                    <th className="p-2" key={item.label}>
                                        {item.label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {labels.map((day, index) => (
                                <tr key={`${day}-${index}`}>
                                    <th className="p-2 font-normal">{day}</th>
                                    {series.map((item) => (
                                        <td className="p-2" key={item.label}>
                                            {item.data[index]}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </details>
        </>
    );
}
