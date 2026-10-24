import Chart from 'chart.js/auto';

const chart = JSON.parse(document.getElementById('analytics-chart-data').textContent);
const gridColor = 'rgba(255, 255, 255, .08)';
const labelsColor = 'rgba(245, 243, 238, .55)';

Chart.defaults.color = labelsColor;
Chart.defaults.font.family = 'Plus Jakarta Sans, sans-serif';

const scales = {
    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 7 } },
    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0 } },
};

new Chart(document.getElementById('learning-activity-chart'), {
    type: 'line',
    data: {
        labels: chart.labels,
        datasets: [
            { label: 'Pelajaran selesai', data: chart.completions, borderColor: '#ffc700', backgroundColor: 'rgba(255, 199, 0, .12)', fill: true, tension: .35, pointRadius: 2, pointHoverRadius: 5 },
            { label: 'Jawaban kuis', data: chart.answers, borderColor: '#58d7d2', backgroundColor: 'rgba(88, 215, 210, .08)', fill: true, tension: .35, pointRadius: 2, pointHoverRadius: 5 },
        ],
    },
    options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, scales, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } } },
});

new Chart(document.getElementById('registration-chart'), {
    type: 'bar',
    data: { labels: chart.labels, datasets: [{ label: 'Akun baru', data: chart.users, backgroundColor: '#ffc700', hoverBackgroundColor: '#ffdb52', borderRadius: 5, maxBarThickness: 22 }] },
    options: { responsive: true, maintainAspectRatio: false, scales, plugins: { legend: { display: false } } },
});

const onlinePanel = document.querySelector('[data-online-url]');
const refreshOnline = async () => {
    try {
        const response = await fetch(onlinePanel.dataset.onlineUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!response.ok) throw new Error('Gagal mengambil status online');

        const online = await response.json();
        onlinePanel.querySelector('[data-online-total]').textContent = online.total.toLocaleString('id-ID');
        onlinePanel.querySelector('[data-online-accounts]').textContent = online.accounts.toLocaleString('id-ID');
        onlinePanel.querySelector('[data-online-anonymous]').textContent = online.anonymous.toLocaleString('id-ID');
        onlinePanel.querySelector('[data-online-status]').textContent = `Diperbarui ${new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(new Date())}`;
    } catch {
        onlinePanel.querySelector('[data-online-status]').textContent = 'Status online belum dapat diperbarui.';
    }
};

refreshOnline();
window.setInterval(refreshOnline, 15000);
