export function initializeRoomCountdown(room, onExpire) {
    const offset = Date.parse(room.dataset.serverNow) - Date.now();
    if (!Number.isFinite(offset)) return;
    const timers = [
        { label: room.querySelector('[data-room-countdown]'), deadline: Date.parse(room.dataset.questionEndsAt), progress: room.querySelector('[data-room-time-progress]'), bar: room.querySelector('[data-room-time-bar]') },
        { label: room.querySelector('[data-room-total-countdown]'), deadline: Date.parse(room.dataset.roomEndsAt) },
    ].filter(timer => timer.label && Number.isFinite(timer.deadline));
    if (!timers.length) return;
    let interval = null;
    let lastCheck = -Infinity;
    let checking = false;
    let suspended = false;
    const update = () => {
        const now = Date.now();
        let expired = false;
        for (const timer of timers) {
            const seconds = Math.max(0, Math.ceil((timer.deadline - now - offset) / 1000));
            const minutes = Math.floor(seconds / 60);
            timer.label.textContent = `${minutes}:${String(seconds % 60).padStart(2, '0')}`;
            timer.label.classList.toggle('text-accent', seconds > 5);
            timer.label.classList.toggle('text-danger', seconds <= 5);
            const total = Number(timer.progress?.getAttribute('aria-valuemax'));
            if (timer.bar && total > 0) {
                timer.bar.style.width = `${Math.min(100, seconds / total * 100)}%`;
                timer.progress.setAttribute('aria-valuenow', seconds);
            }
            expired ||= seconds === 0;
        }
        if (expired && !checking && now - lastCheck >= 3000) {
            lastCheck = now;
            checking = true;
            Promise.resolve().then(onExpire).catch(() => {}).finally(() => { checking = false; });
        }
    };
    const pause = () => { window.clearInterval(interval); interval = null; };
    const start = () => {
        if (interval !== null || suspended || document.visibilityState !== 'visible') return;
        update();
        interval = window.setInterval(update, 1000);
    };
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') start(); else pause(); });
    window.addEventListener('pagehide', () => { suspended = true; pause(); });
    window.addEventListener('pageshow', () => { suspended = false; start(); });
    start();
}
