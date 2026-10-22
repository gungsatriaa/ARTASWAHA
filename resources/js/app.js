import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const roomPage = document.querySelector('[data-room-code]');

if (roomPage && import.meta.env.VITE_REVERB_APP_KEY && import.meta.env.VITE_REVERB_HOST) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}

document.querySelectorAll('[data-quiz-builder]').forEach((form) => {
    const list = form.querySelector('[data-question-list]');
    const template = form.querySelector('[data-question-template]');
    const addButton = form.querySelector('[data-add-question]');
    let nextIndex = list.querySelectorAll('[data-question]').length;

    addButton.addEventListener('click', () => {
        if (list.querySelectorAll('[data-question]').length >= 20) {
            return;
        }

        const question = template.content.cloneNode(true);
        question.querySelectorAll('[name]').forEach((field) => {
            field.name = field.name.replaceAll('__INDEX__', nextIndex);
        });
        question.querySelector('[data-question-number]').textContent = nextIndex + 1;
        list.append(question);
        nextIndex += 1;
        addButton.disabled = list.querySelectorAll('[data-question]').length >= 20;
    });

    list.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-question]');

        if (removeButton && list.querySelectorAll('[data-question]').length > 1) {
            removeButton.closest('[data-question]').remove();
            addButton.disabled = false;
        }
    });
});

document.querySelectorAll('[data-exercise-toggle]').forEach((toggle) => {
    const fields = toggle.form.querySelector('[data-exercise-fields]');
    const syncFields = () => {
        fields.disabled = !toggle.checked;
        fields.querySelectorAll('input, textarea').forEach((field) => {
            field.disabled = !toggle.checked;
        });
    };

    toggle.addEventListener('change', syncFields);
    syncFields();
});

document.querySelectorAll('[data-copy-room]').forEach((button) => {
    button.addEventListener('click', async () => {
        const status = document.querySelector('[data-copy-status]');

        try {
            await navigator.clipboard.writeText(button.dataset.copyRoom);
            status.textContent = 'Tautan undangan disalin.';
        } catch {
            status.textContent = `Salin tautan ini: ${button.dataset.copyRoom}`;
        }
    });
});

if (window.Echo && roomPage) {
    window.Echo.private(`rooms.${roomPage.dataset.roomCode}`)
        .listen('.room.updated', () => window.location.reload());

    const status = roomPage.querySelector('[data-live-status]');
    let hasConnected = false;

    window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
        if (current === 'connected') {
            if (hasConnected) {
                window.location.reload();
            }

            hasConnected = true;

            if (status) {
                status.textContent = 'Pembaruan room realtime aktif.';
            }

            return;
        }

        if (status) {
            status.textContent = 'Koneksi realtime terputus. Segarkan halaman untuk memeriksa pembaruan.';
        }
    });
}
