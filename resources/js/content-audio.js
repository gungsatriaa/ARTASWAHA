const players = [];
const stop = (player) => {
    player.pause();
    if (player.readyState > 0) player.currentTime = 0;
};
const stopAll = () => players.forEach(stop);
const initializePlayers = () => {
    for (const player of document.querySelectorAll('[data-content-audio] audio')) {
        if (players.includes(player)) continue;
        players.push(player);
        player.addEventListener('play', () => {
            players.filter((other) => other !== player).forEach(stop);
        });
        player.addEventListener('error', () => {
            const message = player.closest('[data-content-audio]')?.querySelector('[data-audio-error]');
            if (message) message.textContent = 'Audio belum dapat diputar. Materi tetap bisa dibaca melalui teks.';
        });
        player.closest('[data-content-audio]')?.querySelector('[data-audio-stop]')?.addEventListener('click', () => stop(player));
    }
};
initializePlayers();
window.addEventListener('content-audio-updated', initializePlayers);
window.addEventListener('pagehide', stopAll);
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') stopAll();
});
const room = document.querySelector('[data-room-code]');
if (room?.dataset.questionEndsAt) {
    const offset = Date.parse(room.dataset.serverNow) - Date.now();
    const remaining = Date.parse(room.dataset.questionEndsAt) - Date.now() - offset;
    if (Number.isFinite(remaining)) window.setTimeout(stopAll, Math.max(0, remaining));
}
