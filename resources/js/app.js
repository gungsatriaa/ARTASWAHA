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

document.querySelectorAll('[data-avatar-dialog]').forEach((dialog) => {
    document.querySelector('[data-avatar-open]')?.addEventListener('click', () => dialog.showModal());
    dialog.querySelector('[data-avatar-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    if (dialog.hasAttribute('data-open-on-load')) {
        dialog.showModal();
    }

    const form = dialog.querySelector('form');
    const fileInput = dialog.querySelector('[data-avatar-file]');
    const cropPanel = dialog.querySelector('[data-avatar-crop]');
    const viewport = dialog.querySelector('[data-avatar-crop-viewport]');
    const zoomInput = dialog.querySelector('[data-avatar-zoom]');
    const cropStatus = dialog.querySelector('[data-avatar-crop-status]');

    if (!form || !fileInput || !cropPanel || !viewport || !zoomInput || !cropStatus) {
        return;
    }

    let image = null;
    let imageUrl = null;
    let scale = 1;
    let offsetX = 0;
    let offsetY = 0;
    let dragPoint = null;
    let cropReady = false;

    const positionImage = () => {
        if (!image) {
            return;
        }

        const bounds = viewport.getBoundingClientRect();
        const baseScale = Math.max(bounds.width / image.naturalWidth, bounds.height / image.naturalHeight);
        const width = image.naturalWidth * baseScale * scale;
        const height = image.naturalHeight * baseScale * scale;
        const centeredX = (bounds.width - width) / 2;
        const centeredY = (bounds.height - height) / 2;

        offsetX = Math.max(bounds.width - width - centeredX, Math.min(-centeredX, offsetX));
        offsetY = Math.max(bounds.height - height - centeredY, Math.min(-centeredY, offsetY));
        image.style.width = `${width}px`;
        image.style.height = `${height}px`;
        image.style.left = `${centeredX + offsetX}px`;
        image.style.top = `${centeredY + offsetY}px`;
    };

    fileInput.addEventListener('change', () => {
        cropReady = false;

        if (imageUrl) {
            URL.revokeObjectURL(imageUrl);
        }

        const file = fileInput.files?.[0];
        cropPanel.hidden = !file;

        if (!file) {
            return;
        }

        imageUrl = URL.createObjectURL(file);
        image = new Image();
        image.className = 'absolute max-w-none select-none';
        image.draggable = false;
        image.onload = () => {
            viewport.replaceChildren(image);
            scale = 1;
            offsetX = 0;
            offsetY = 0;
            zoomInput.value = '1';
            positionImage();
        };
        image.onerror = () => {
            cropStatus.textContent = 'Gambar tidak dapat dibuka. Pilih file gambar lain.';
        };
        image.src = imageUrl;
    });

    zoomInput.addEventListener('input', () => {
        scale = Number(zoomInput.value);
        positionImage();
    });

    viewport.addEventListener('pointerdown', (event) => {
        if (!image) {
            return;
        }

        dragPoint = { x: event.clientX, y: event.clientY };
        viewport.setPointerCapture(event.pointerId);
    });

    viewport.addEventListener('pointermove', (event) => {
        if (!dragPoint) {
            return;
        }

        offsetX += event.clientX - dragPoint.x;
        offsetY += event.clientY - dragPoint.y;
        dragPoint = { x: event.clientX, y: event.clientY };
        positionImage();
    });

    viewport.addEventListener('pointerup', () => {
        dragPoint = null;
    });

    viewport.addEventListener('pointercancel', () => {
        dragPoint = null;
    });

    viewport.addEventListener('keydown', (event) => {
        const directions = {
            ArrowDown: [0, 1],
            ArrowLeft: [-1, 0],
            ArrowRight: [1, 0],
            ArrowUp: [0, -1],
        };
        const direction = directions[event.key];

        if (!direction) {
            return;
        }

        event.preventDefault();
        const distance = event.shiftKey ? 30 : 10;
        offsetX += direction[0] * distance;
        offsetY += direction[1] * distance;
        positionImage();
    });

    form.addEventListener('submit', async (event) => {
        if (cropReady || !fileInput.files?.length) {
            return;
        }

        event.preventDefault();

        if (!image?.complete || !image.naturalWidth) {
            cropStatus.textContent = 'Tunggu sampai pratinjau gambar siap.';
            return;
        }

        cropStatus.textContent = 'Menyiapkan avatar…';
        const bounds = viewport.getBoundingClientRect();
        const renderedScale = image.width / image.naturalWidth;
        const sourceSize = Math.min(bounds.width, bounds.height) / renderedScale;
        const sourceX = -image.offsetLeft / renderedScale;
        const sourceY = -image.offsetTop / renderedScale;
        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 512;

        try {
            const context = canvas.getContext('2d');

            if (!context) {
                throw new Error('canvas-unavailable');
            }

            context.drawImage(image, sourceX, sourceY, sourceSize, sourceSize, 0, 0, 512, 512);
            const blob = await new Promise((resolve, reject) => {
                canvas.toBlob((result) => result ? resolve(result) : reject(new Error('crop-failed')), 'image/webp', 0.9);
            });
            const croppedFile = new File([blob], 'avatar-crop.webp', { type: 'image/webp' });
            const transfer = new DataTransfer();
            transfer.items.add(croppedFile);
            fileInput.files = transfer.files;
            cropReady = true;
            form.requestSubmit(event.submitter);
        } catch {
            cropStatus.textContent = 'Avatar gagal diproses. Coba pilih gambar lain.';
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
