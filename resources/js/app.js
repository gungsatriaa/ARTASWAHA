import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const presenceUrl = document.body.dataset.presenceUrl;

if (presenceUrl) {
    const pingPresence = () => {
        if (document.visibilityState !== 'visible') return;

        fetch(presenceUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
        }).catch(() => {});
    };

    pingPresence();
    window.setInterval(pingPresence, 30000);
    document.addEventListener('visibilitychange', pingPresence);
}

if (document.getElementById('analytics-chart-data')) {
    import('./admin-analytics.js');
}

const roomPage = document.querySelector('[data-room-code]');
const scrollLockedDialogs = new Set();

const syncPageScrollLock = () => {
    const isLocked = scrollLockedDialogs.size > 0;
    document.body.classList.toggle('dialog-open', isLocked);
};

const closeMobileNavigation = (sidebar, backdrop, openButton) => {
    if (!sidebar.classList.contains('is-open') || sidebar.classList.contains('is-closing')) {
        return;
    }

    sidebar.classList.add('is-closing');
    sidebar.classList.remove('is-open');
    backdrop.classList.add('is-closing');
    backdrop.classList.remove('is-open');

    let fallbackTimeout;
    const finishClosing = () => {
        sidebar.removeEventListener('transitionend', handleTransitionEnd);
        window.clearTimeout(fallbackTimeout);
        sidebar.classList.remove('is-closing');
        sidebar.inert = true;
        sidebar.setAttribute('aria-hidden', 'true');
        backdrop.classList.remove('is-closing');
        scrollLockedDialogs.delete(sidebar);
        syncPageScrollLock();
        openButton.setAttribute('aria-expanded', 'false');
        openButton.focus();
    };
    const handleTransitionEnd = (event) => {
        if (event.target === sidebar && event.propertyName === 'transform') {
            finishClosing();
        }
    };

    sidebar.addEventListener('transitionend', handleTransitionEnd);
    fallbackTimeout = window.setTimeout(finishClosing, 350);
};

document.querySelectorAll('[data-mobile-nav]').forEach((sidebar) => {
    const openButton = document.querySelector(`[aria-controls="${sidebar.id}"]`);
    const closeButton = sidebar.querySelector('[data-mobile-nav-close]');
    const backdrop = document.querySelector(`[data-mobile-nav-backdrop][aria-controls="${sidebar.id}"]`);

    openButton?.addEventListener('click', () => {
        sidebar.inert = false;
        sidebar.setAttribute('aria-hidden', 'false');
        sidebar.classList.remove('is-closing');
        backdrop.classList.remove('is-closing');
        sidebar.classList.add('is-open');
        backdrop.classList.add('is-open');
        scrollLockedDialogs.add(sidebar);
        syncPageScrollLock();
        openButton.setAttribute('aria-expanded', 'true');
        closeButton.focus();
    });
    closeButton?.addEventListener('click', () => closeMobileNavigation(sidebar, backdrop, openButton));
    sidebar.querySelectorAll('a, form button').forEach((item) => {
        item.addEventListener('click', () => closeMobileNavigation(sidebar, backdrop, openButton));
    });
    backdrop.addEventListener('click', () => closeMobileNavigation(sidebar, backdrop, openButton));
    sidebar.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeMobileNavigation(sidebar, backdrop, openButton);
        }

        if (event.key === 'Tab') {
            const focusableItems = [...sidebar.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')].filter((item) => item.getClientRects().length > 0);
            const firstItem = focusableItems[0];
            const lastItem = focusableItems[focusableItems.length - 1];

            if (event.shiftKey && document.activeElement === firstItem) {
                event.preventDefault();
                lastItem.focus();
            } else if (!event.shiftKey && document.activeElement === lastItem) {
                event.preventDefault();
                firstItem.focus();
            }
        }
    });
});

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.getAttribute('aria-controls'));

    if (!input) {
        return;
    }

    button.addEventListener('click', () => {
        const shouldShowPassword = input.type === 'password';
        input.type = shouldShowPassword ? 'text' : 'password';
        button.textContent = shouldShowPassword ? 'Sembunyikan' : 'Tampilkan';
        button.setAttribute('aria-pressed', String(shouldShowPassword));
    });
});

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
    const optionTemplate = form.querySelector('[data-option-template]');
    const addButton = form.querySelector('[data-add-question]');

    const refreshQuestions = () => {
        list.querySelectorAll('[data-question]').forEach((question, questionIndex) => {
            question.querySelector('[data-question-number]').textContent = questionIndex + 1;
            question.querySelectorAll('[name]').forEach((field) => {
                field.name = field.name.replace(/questions\[(?:\d+|__INDEX__)\]/, `questions[${questionIndex}]`);
            });
            const options = question.querySelectorAll('[data-option-row]');
            options.forEach((option, optionIndex) => {
                const radio = option.querySelector('input[type="radio"]');
                const input = option.querySelector('input[name$="[options][]"]');
                radio.value = optionIndex;
                radio.setAttribute('aria-label', `Jawaban benar: pilihan ${optionIndex + 1}`);
                input.setAttribute('aria-label', `Pilihan ${optionIndex + 1}`);
                input.placeholder = `Pilihan ${optionIndex + 1}`;
                option.querySelector('[data-remove-option]').setAttribute('aria-label', `Hapus pilihan ${optionIndex + 1}`);
            });
            question.querySelector('[data-add-option]').disabled = options.length >= 5;
            question.querySelectorAll('[data-remove-option]').forEach((button) => { button.disabled = options.length <= 2; });
        });
        addButton.disabled = list.querySelectorAll('[data-question]').length >= 20;
    };

    refreshQuestions();

    document.querySelector('[data-ai-submit]')?.addEventListener('click', async () => {
        const panel = document.querySelector('[data-ai-quiz]');
        const file = panel.querySelector('[data-ai-file]').files[0];
        const status = panel.querySelector('[data-ai-status]');
        if (!file) { status.textContent = 'Pilih gambar terlebih dahulu.'; return; }
        const data = new FormData(); data.append('file', file);
        try {
            status.textContent = 'Mengunggah dokumen…';
            const response = await fetch(panel.dataset.uploadUrl, { method: 'POST', body: data, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' } });
            if (!response.ok) throw new Error('Upload gagal. Pastikan format gambar dan ukuran file sesuai.');
            const job = await response.json();
            let result;
            do { await new Promise((resolve) => setTimeout(resolve, 2000)); result = await (await fetch(job.status_url, { headers: { Accept: 'application/json' } })).json(); status.textContent = result.status === 'processing' ? 'OCR dan AI sedang bekerja…' : 'Sedang menunggu antrean…'; } while (!['completed', 'failed'].includes(result.status));
            if (result.status === 'failed') throw new Error(result.error);
            result.questions.forEach((item, position) => {
                if (position > 0) addButton.click();
                const fieldset = list.querySelectorAll('[data-question]')[position];
                if (!fieldset) return;
                fieldset.querySelector('textarea[name$="[prompt]"]').value = item.prompt;
                while (fieldset.querySelectorAll('[data-option-row]').length < Math.min(item.options.length, 5)) fieldset.querySelector('[data-add-option]').click();
                while (fieldset.querySelectorAll('[data-option-row]').length > Math.max(item.options.length, 2)) fieldset.querySelector('[data-option-row]:last-child [data-remove-option]').click();
                fieldset.querySelectorAll('input[name$="[options][]"]').forEach((input, index) => { input.value = item.options[index] ?? ''; });
                const radio = fieldset.querySelector(`input[type="radio"][value="${item.correct_option}"]`); if (radio) radio.checked = true;
                fieldset.querySelector('input[name$="[explanation]"]').value = item.explanation ?? '';
            });
            panel.querySelector('[data-ai-text]').textContent = result.source_text;
            panel.querySelector('[data-ai-source]').classList.remove('hidden');
            status.textContent = 'Draf siap. Tinjau dan edit sebelum menyimpan kuis.';
        } catch (error) { status.textContent = error.message; }
    });

    addButton.addEventListener('click', () => {
        if (list.querySelectorAll('[data-question]').length >= 20) {
            return;
        }

        list.append(template.content.cloneNode(true));
        refreshQuestions();
    });

    list.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-question]');

        if (removeButton && list.querySelectorAll('[data-question]').length > 1) {
            removeButton.closest('[data-question]').remove();
            refreshQuestions();
            return;
        }

        const addOptionButton = event.target.closest('[data-add-option]');
        if (addOptionButton && !addOptionButton.disabled) {
            addOptionButton.closest('[data-question]').querySelector('[data-options]').append(optionTemplate.content.cloneNode(true));
            refreshQuestions();
            return;
        }

        const removeOptionButton = event.target.closest('[data-remove-option]');
        if (removeOptionButton && !removeOptionButton.disabled) {
            removeOptionButton.closest('[data-option-row]').remove();
            refreshQuestions();
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

const roomJoinClosedDialog = document.querySelector('[data-room-join-closed-dialog]');
if (roomJoinClosedDialog) {
    roomJoinClosedDialog.showModal();
    scrollLockedDialogs.add(roomJoinClosedDialog);
    syncPageScrollLock();
    roomJoinClosedDialog.querySelector('[data-room-join-closed-close]').addEventListener('click', () => roomJoinClosedDialog.close());
    roomJoinClosedDialog.addEventListener('close', () => {
        scrollLockedDialogs.delete(roomJoinClosedDialog);
        syncPageScrollLock();
    });
}

document.querySelectorAll('[data-avatar-dialog]').forEach((dialog) => {
    document.querySelector('[data-avatar-open]')?.addEventListener('click', () => {
        dialog.showModal();
        scrollLockedDialogs.add(dialog);
        syncPageScrollLock();
    });
    dialog.querySelector('[data-avatar-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
        scrollLockedDialogs.delete(dialog);
        syncPageScrollLock();
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    if (dialog.hasAttribute('data-open-on-load')) {
        dialog.showModal();
        scrollLockedDialogs.add(dialog);
        syncPageScrollLock();
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

document.querySelectorAll('[data-learning-picker]').forEach((dialog) => {
    const openButton = document.querySelector(`[aria-controls="${dialog.id}"]`);

    openButton?.addEventListener('click', () => {
        dialog.showModal();
        scrollLockedDialogs.add(dialog);
        syncPageScrollLock();
    });
    dialog.querySelector('[data-learning-picker-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
        scrollLockedDialogs.delete(dialog);
        syncPageScrollLock();
    });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
});

document.querySelectorAll('[data-testimonial-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-testimonial-track]');
    const slides = [...carousel.querySelectorAll('[data-testimonial-slide]')];
    const previousButton = carousel.querySelector('[data-testimonial-previous]');
    const nextButton = carousel.querySelector('[data-testimonial-next]');
    const status = carousel.querySelector('[data-testimonial-status]');

    if (!track || slides.length === 0 || !previousButton || !nextButton || !status) {
        return;
    }

    carousel.dataset.overflow = String(slides.length > 3);

    const currentIndex = () => {
        const trackLeft = track.getBoundingClientRect().left + Number.parseFloat(getComputedStyle(track).paddingLeft);

        return slides.reduce((closestIndex, slide, index) => {
            const closestDistance = Math.abs(slides[closestIndex].getBoundingClientRect().left - trackLeft);
            const distance = Math.abs(slide.getBoundingClientRect().left - trackLeft);

            return distance < closestDistance ? index : closestIndex;
        }, 0);
    };

    const updateControls = () => {
        const index = currentIndex();
        previousButton.disabled = index === 0;
        nextButton.disabled = index === slides.length - 1;
        status.textContent = `${index + 1} / ${slides.length}`;
    };

    const scrollBySlide = (direction) => {
        const trackStyles = getComputedStyle(track);
        const gap = Number.parseFloat(trackStyles.columnGap) || 0;
        const distance = slides[0].getBoundingClientRect().width + gap;

        track.scrollBy({
            left: distance * direction,
            behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        });
    };

    previousButton.addEventListener('click', () => scrollBySlide(-1));
    nextButton.addEventListener('click', () => scrollBySlide(1));
    track.addEventListener('scroll', updateControls, { passive: true });
    updateControls();
});

const roomDeletedDialog = document.querySelector('[data-room-deleted-dialog]');
const roomInactiveDialog = document.querySelector('[data-room-inactive-dialog]');
const liveRoomPage = roomPage && roomPage.dataset.roomStatus !== 'inactive';
let roomWasDeleted = false;
let roomWasInactive = false;

const showRoomDeleted = () => {
    if (roomWasDeleted || roomWasInactive || !roomDeletedDialog) {
        return;
    }

    roomWasDeleted = true;
    window.Echo?.leave(`rooms.${roomPage.dataset.roomCode}`);
    roomDeletedDialog.showModal();
    scrollLockedDialogs.add(roomDeletedDialog);
    syncPageScrollLock();
};

const showRoomInactive = () => {
    if (roomWasDeleted || roomWasInactive || !roomInactiveDialog) {
        return;
    }

    roomWasInactive = true;
    window.Echo?.leave(`rooms.${roomPage.dataset.roomCode}`);
    roomInactiveDialog.showModal();
    scrollLockedDialogs.add(roomInactiveDialog);
    syncPageScrollLock();
};

const checkRoomStillExists = async (reloadIfPresent = false) => {
    if (roomWasDeleted || roomWasInactive) {
        return;
    }

    try {
        const response = await fetch(roomPage.dataset.roomStatusUrl, { cache: 'no-store' });

        if (response.status === 404) {
            showRoomDeleted();
        } else if (response.status === 410) {
            showRoomInactive();
        } else if (response.ok && roomPage.hasAttribute('data-room-anonymous') && (
            response.headers.get('X-Room-Status') !== roomPage.dataset.roomStatus ||
            response.headers.get('X-Room-Participants') !== roomPage.dataset.roomParticipants
        )) {
            window.location.reload();
        } else if (reloadIfPresent && response.ok) {
            window.location.reload();
        }
    } catch {
        // Keep the page open during a temporary network interruption.
    }
};

if (liveRoomPage) {
    if (roomPage.dataset.roomHeartbeatUrl) {
        const pingHost = async () => {
            try {
                const response = await fetch(roomPage.dataset.roomHeartbeatUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                });
                if (response.status === 410) showRoomInactive();
            } catch {
                // A failed ping is retried; the server expires an absent host.
            }
        };

        pingHost();
        window.setInterval(pingHost, 20000);
    }

    if (roomPage.dataset.roomFeedbackNext) {
        window.setTimeout(() => window.location.replace(roomPage.dataset.roomFeedbackNext), 3000);
    }

    const countdown = roomPage.querySelector('[data-room-countdown]');

    if (countdown && roomPage.dataset.questionEndsAt) {
        const deadline = Date.parse(roomPage.dataset.questionEndsAt);
        const timeOffset = Date.parse(roomPage.dataset.serverNow) - Date.now();
        const timeProgress = roomPage.querySelector('[data-room-time-progress]');
        const timeBar = roomPage.querySelector('[data-room-time-bar]');
        const totalSeconds = Number(timeProgress?.getAttribute('aria-valuemax'));
        let lastExpiryCheck = 0;

        const updateCountdown = () => {
            const secondsLeft = Math.max(0, Math.ceil((deadline - Date.now() - timeOffset) / 1000));
            countdown.textContent = `${Math.floor(secondsLeft / 60)}:${String(secondsLeft % 60).padStart(2, '0')}`;
            countdown.classList.toggle('text-accent', secondsLeft > 5);
            countdown.classList.toggle('text-danger', secondsLeft <= 5);

            if (timeBar && totalSeconds > 0) {
                timeBar.style.width = `${Math.min(100, secondsLeft / totalSeconds * 100)}%`;
                timeProgress.setAttribute('aria-valuenow', secondsLeft);
            }

            if (secondsLeft === 0 && Date.now() - lastExpiryCheck >= 3000) {
                lastExpiryCheck = Date.now();
                checkRoomStillExists(true);
            }
        };

        updateCountdown();
        window.setInterval(updateCountdown, 1000);
    }

    const deleteConfirmation = document.querySelector('[data-room-delete-confirm]');
    document.querySelector('[data-room-delete-open]')?.addEventListener('click', () => {
        deleteConfirmation.showModal();
        scrollLockedDialogs.add(deleteConfirmation);
        syncPageScrollLock();
    });
    document.querySelector('[data-room-delete-cancel]')?.addEventListener('click', () => deleteConfirmation.close());
    deleteConfirmation?.addEventListener('close', () => {
        scrollLockedDialogs.delete(deleteConfirmation);
        syncPageScrollLock();
    });
    roomDeletedDialog?.addEventListener('cancel', (event) => event.preventDefault());
    roomInactiveDialog?.addEventListener('cancel', (event) => event.preventDefault());
    window.setInterval(checkRoomStillExists, roomPage.hasAttribute('data-room-anonymous') ? 5000 : 15000);
}

if (window.Echo && liveRoomPage && !roomPage.hasAttribute('data-room-anonymous')) {
    const roomStatus = roomPage.querySelector('[data-live-status]');
    let roomPresenceUsers = [];

    const renderRoomPresence = (users) => {
        roomPresenceUsers = [...new Map(users.map((user) => [String(user.id), user])).values()];
        const onlineUserIds = new Set(roomPresenceUsers.map((user) => String(user.id)));

        roomPage.querySelectorAll('[data-room-presence-user]').forEach((row) => {
            row.hidden = !row.dataset.roomPresenceUser.startsWith('anon-') && !onlineUserIds.has(row.dataset.roomPresenceUser);
        });

        if (roomStatus) {
            roomStatus.textContent = 'Pembaruan room realtime aktif.';
        }
    };

    window.Echo.join(`rooms.${roomPage.dataset.roomCode}`)
        .here(renderRoomPresence)
        .joining((user) => renderRoomPresence([...roomPresenceUsers, user]))
        .leaving((user) => renderRoomPresence(roomPresenceUsers.filter((onlineUser) => String(onlineUser.id) !== String(user.id))))
        .listen('.room.updated', (event) => {
            if (event.status === 'deleted') {
                showRoomDeleted();
            } else if (event.status === 'inactive') {
                showRoomInactive();
            } else if (!roomWasDeleted && !roomWasInactive) {
                window.location.reload();
            }
        })
        .error((error) => {
            if (roomStatus) {
                roomStatus.textContent = 'Tidak dapat bergabung ke kanal realtime room.';
            }

            console.error('Room presence subscription failed:', error);
        });

    let hasConnected = false;
    window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
        if (current === 'connected') {
            if (hasConnected) {
                checkRoomStillExists(true);
            }

            hasConnected = true;
            return;
        }

        if (roomStatus) {
            roomStatus.textContent = 'Koneksi realtime terputus. Menyambungkan kembali…';
        }
    });
} else if (liveRoomPage) {
    const roomStatus = roomPage.querySelector('[data-live-status]');

    if (roomStatus) {
        roomStatus.textContent = roomPage.hasAttribute('data-room-anonymous') ? 'Memeriksa pembaruan room secara berkala.' : 'Realtime belum dikonfigurasi pada aplikasi ini.';
    }
}
