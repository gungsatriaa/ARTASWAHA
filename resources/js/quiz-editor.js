export function readQuestion(question) {
    return {
        prompt: question.querySelector('[name$="[prompt]"]').value,
        options: [...question.querySelectorAll('[name$="[options][]"]')].map(input => input.value),
        keyboard: question.querySelector('[type="checkbox"][name$="[keyboard_auto_open]"]').checked,
        seconds: Number(question.querySelector('[name$="[time_limit_seconds]"]').value) || null,
    };
}

export function previewAudio(question, values) {
    try {
        return question.dataset.sourcePrompt === values.prompt && JSON.stringify(JSON.parse(question.dataset.sourceOptions)) === JSON.stringify(values.options)
            ? question.dataset.audioUrl : '';
    } catch { return ''; }
}

export function moveQuestion(question, direction) {
    const sibling = direction === 'up' ? question.previousElementSibling : question.nextElementSibling;
    if (!sibling) return false;
    if (direction === 'up') sibling.before(question);
    else sibling.after(question);
    return true;
}

export function cloneQuestion(question) {
    const clone = question.cloneNode(true);
    const fields = question.querySelectorAll('input, textarea');
    clone.querySelectorAll('input, textarea').forEach((field, index) => {
        field.value = fields[index].value;
        if (field.type === 'radio' || field.type === 'checkbox') field.checked = fields[index].checked;
        if (field.name.endsWith('[id]')) field.remove();
        else field.name = field.name.replace(/^questions\[[^\]]+\]/, 'questions[__INDEX__]');
        field.removeAttribute('aria-invalid');
    });
    clone.querySelectorAll('[data-field-error], [data-answer-error]').forEach(error => { error.textContent = ''; });
    clone.querySelector('[data-question-audio]').textContent = 'Soal salinan belum memiliki audio sendiri. Simpan kuis, lalu siapkan audio.';
    clone.dataset.audioUrl = '';
    clone.dataset.sourcePrompt = '';
    clone.dataset.sourceOptions = '[]';
    clone.open = true;
    return clone;
}

export function renumberQuestionNames(questions) {
    questions.forEach((question, index) => question.querySelectorAll('[name]').forEach(field => {
        field.name = field.name.replace(/^questions\[[^\]]+\]/, `questions[temporary${index}]`);
    }));
    questions.forEach((question, index) => question.querySelectorAll('[name]').forEach(field => {
        field.name = field.name.replace(/^questions\[[^\]]+\]/, `questions[${index}]`);
    }));
}

document.querySelectorAll('[data-quiz-builder]').forEach((form) => {
    const list = form.querySelector('[data-question-list]');
    const template = form.querySelector('[data-question-template]');
    const optionTemplate = form.querySelector('[data-option-template]');
    const addButton = form.querySelector('[data-add-question]');
    const steps = [...form.querySelectorAll('[data-quiz-step]')];
    const stepLinks = [...form.querySelectorAll('[data-step-link]')];
    const next = form.querySelector('[data-step-next]');
    const previous = form.querySelector('[data-step-prev]');
    const save = form.querySelector('[data-save-quiz]');
    const status = form.querySelector('[data-editor-status]');
    let activeStep = 0;
    let previewIndex = 0;
    let dirty = form.dataset.hasErrors === '1';
    let submitting = false;
    const markDirty = () => {
        dirty = true;
        const message = 'Ada perubahan yang belum disimpan.';
        if (status.textContent !== message) status.textContent = message;
    };
    const pauseAudio = () => form.querySelectorAll('audio').forEach(player => player.pause());

    const renderPreview = () => {
        const questions = [...list.querySelectorAll('[data-question]')];
        previewIndex = Math.min(previewIndex, questions.length - 1);
        const question = questions[previewIndex];
        const values = readQuestion(question);
        form.querySelector('[data-preview-position]').textContent = `Soal ${previewIndex + 1} dari ${questions.length}`;
        const seconds = values.seconds || Number(form.querySelector('[name="seconds_per_question"]').value);
        const total = Number(form.querySelector('[name="time_limit_minutes"]').value);
        form.querySelector('[data-preview-timing]').textContent = `${seconds ? `${seconds} detik untuk soal ini` : 'Soal tanpa batas waktu sendiri'} · ${total ? `Batas total ${total} menit` : 'Tanpa batas total'}. Timer tidak berjalan dalam preview.`;
        form.querySelector('[data-preview-prompt]').textContent = values.prompt;
        const options = form.querySelector('[data-preview-options]');
        const fragment = document.createDocumentFragment();
        values.options.forEach((text, index) => {
            const label = document.createElement('label');
            label.className = 'flex min-h-14 items-center gap-3 rounded-control border border-white/15 p-4 has-[:checked]:border-accent';
            const radio = document.createElement('input');
            radio.type = 'radio'; radio.name = 'preview_answer'; radio.value = index;
            radio.className = 'size-5 shrink-0 accent-[var(--color-accent)]';
            const span = document.createElement('span'); span.className = 'min-w-0 break-words'; span.textContent = text;
            label.append(radio, span); fragment.append(label);
        });
        options.replaceChildren(fragment);
        const audio = form.querySelector('[data-preview-audio]');
        pauseAudio();
        const url = previewAudio(question, values);
        if (url) audio.src = url;
        else audio.removeAttribute('src');
        audio.hidden = !url;
        audio.load();
        form.querySelector('[data-preview-audio-note]').textContent = url ? 'Audio tersimpan yang sudah disetujui.' : 'Belum ada audio yang sesuai. Simpan kuis, lalu kelola audio pada soal.';
        const keyboard = steps[2].querySelector('[data-aksara-keyboard]');
        keyboard.open = values.keyboard;
        steps[2].querySelector('[data-aksara-input]').value = '';
        form.querySelector('[data-preview-prev]').disabled = previewIndex === 0;
        form.querySelector('[data-preview-next]').disabled = previewIndex === questions.length - 1;
    };

    const showStep = index => {
        activeStep = index;
        pauseAudio();
        steps.forEach((step, position) => { step.hidden = position !== index; });
        stepLinks.forEach((link, position) => {
            if (position === index) link.setAttribute('aria-current', 'step');
            else link.removeAttribute('aria-current');
        });
        previous.hidden = index === 0;
        next.hidden = index === 2;
        next.textContent = index === 1 ? 'Preview sebagai peserta' : 'Lanjut ke soal';
        save.hidden = index !== 2;
        if (index === 2) renderPreview();
    };
    const fieldError = field => {
        const valid = field.checkValidity();
        const message = valid ? '' : field.validity.valueMissing ? 'Lengkapi kolom ini terlebih dahulu.' : field.validationMessage;
        const error = field.type === 'radio' ? field.closest('[data-options-fieldset]')?.querySelector('[data-answer-error]') : field.closest('label')?.querySelector('[data-field-error]');
        if (error) error.textContent = message;
        if (valid) field.removeAttribute('aria-invalid');
        else field.setAttribute('aria-invalid', 'true');
        return valid;
    };
    const validateStep = index => {
        for (const field of steps[index].querySelectorAll('input, textarea')) {
            if (field.type === 'hidden' || field.disabled || !field.willValidate) continue;
            if (!fieldError(field)) {
                showStep(index);
                const question = field.closest('[data-question]');
                if (question) question.open = true;
                field.reportValidity();
                field.focus();
                status.textContent = 'Periksa kolom yang ditandai sebelum melanjutkan.';
                return false;
            }
        }
        return true;
    };
    const goTo = index => {
        for (let position = 0; position < index; position++) if (!validateStep(position)) return;
        showStep(index);
        steps[index].querySelector('h2').focus({ preventScroll: true });
        steps[index].scrollIntoView({ block: 'start', behavior: 'auto' });
    };

    const refreshQuestions = () => {
        renumberQuestionNames(list.querySelectorAll('[data-question]'));
        list.querySelectorAll('[data-question]').forEach((question, questionIndex) => {
            question.querySelector('[data-question-number]').textContent = questionIndex + 1;
            question.querySelector('[data-move-question="up"]').disabled = questionIndex === 0;
            question.querySelector('[data-move-question="down"]').disabled = questionIndex === list.children.length - 1;
            question.querySelector('[data-remove-question]').disabled = list.children.length === 1;
            question.querySelector('[data-duplicate-question]').disabled = list.children.length >= 20;
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
    form.noValidate = true;
    form.querySelector('[data-quiz-steps]').hidden = false;
    showStep(form.dataset.hasErrors === '1' ? Number(form.dataset.errorStep) : 0);
    if (form.dataset.previewRequested === '1') goTo(2);
    next.addEventListener('click', () => goTo(activeStep + 1));
    previous.addEventListener('click', () => goTo(activeStep - 1));
    stepLinks.forEach(link => link.addEventListener('click', () => goTo(Number(link.dataset.stepLink))));
    form.querySelector('[data-preview-prev]').addEventListener('click', () => { previewIndex--; renderPreview(); });
    form.querySelector('[data-preview-next]').addEventListener('click', () => { previewIndex++; renderPreview(); });
    form.addEventListener('input', event => {
        if (!event.target.name || event.target.closest('[data-quiz-step="2"]')) return;
        markDirty();
        if (event.target.name.endsWith('[prompt]')) event.target.closest('[data-question]').querySelector('[data-question-summary]').textContent = event.target.value || 'Pertanyaan baru';
    });
    form.addEventListener('focusout', event => {
        if (event.target.matches('input, textarea') && !event.target.closest('[data-quiz-step="2"]') && event.target.willValidate) fieldError(event.target);
    });
    form.addEventListener('change', event => {
        if (!event.target.name || event.target.closest('[data-quiz-step="2"]')) return;
        markDirty();
        if (event.target.type === 'radio') {
            const options = event.target.closest('[data-options-fieldset]');
            options.querySelector('[data-answer-error]').textContent = '';
            options.querySelectorAll('[type="radio"]').forEach(radio => radio.removeAttribute('aria-invalid'));
        }
    });
    form.addEventListener('submit', event => {
        if (submitting) { event.preventDefault(); return; }
        if (!validateStep(0) || !validateStep(1)) { event.preventDefault(); return; }
        if (activeStep !== 2) { event.preventDefault(); goTo(2); return; }
        submitting = true;
        dirty = false;
        save.disabled = true;
        form.setAttribute('aria-busy', 'true');
        status.textContent = 'Menyimpan kuis…';
    });
    window.addEventListener('beforeunload', event => {
        if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
    });
    window.addEventListener('pageshow', () => { submitting = false; save.disabled = false; form.removeAttribute('aria-busy'); });
    window.addEventListener('pagehide', pauseAudio);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') pauseAudio(); });
    form.querySelector('[data-preview-audio]').addEventListener('error', () => {
        form.querySelector('[data-preview-audio-note]').textContent = 'Audio belum dapat diputar. Periksa file melalui Kelola audio soal.';
    });

    document.querySelector('[data-ai-submit]')?.addEventListener('click', async () => {
        const panel = document.querySelector('[data-ai-quiz]');
        const file = panel.querySelector('[data-ai-file]').files[0];
        const status = panel.querySelector('[data-ai-status]');
        if (!file) { status.textContent = 'Pilih gambar terlebih dahulu.'; return; }
        const trigger = panel.querySelector('[data-ai-submit]');
        if (trigger.disabled) return;
        trigger.disabled = true;
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
                fieldset.querySelector('[name$="[explanation]"]').value = item.explanation ?? '';
                fieldset.querySelector('[data-question-summary]').textContent = item.prompt;
            });
            panel.querySelector('[data-ai-text]').textContent = result.source_text;
            panel.querySelector('[data-ai-source]').classList.remove('hidden');
            status.textContent = 'Draf siap. Tinjau dan edit sebelum menyimpan kuis.';
            markDirty();
            showStep(1);
        } catch (error) { status.textContent = error.message; }
        finally { trigger.disabled = false; }
    });

    addButton.addEventListener('click', () => {
        if (list.querySelectorAll('[data-question]').length >= 20) {
            return;
        }

        list.append(template.content.cloneNode(true));
        refreshQuestions();
        markDirty();
        list.lastElementChild.querySelector('[name$="[prompt]"]').focus();
    });

    list.addEventListener('click', (event) => {
        const question = event.target.closest('[data-question]');
        const move = event.target.closest('[data-move-question]');
        if (move && moveQuestion(question, move.dataset.moveQuestion)) {
            refreshQuestions(); markDirty();
            question.querySelector('summary').focus({ preventScroll: true });
            return;
        }
        const duplicate = event.target.closest('[data-duplicate-question]');
        if (duplicate && list.children.length < 20) {
            const clone = cloneQuestion(question);
            question.after(clone); refreshQuestions(); markDirty();
            clone.querySelector('summary').focus({ preventScroll: true });
            return;
        }
        const removeButton = event.target.closest('[data-remove-question]');

        if (removeButton && list.querySelectorAll('[data-question]').length > 1) {
            const nextQuestion = question.nextElementSibling || question.previousElementSibling;
            removeButton.closest('[data-question]').remove();
            refreshQuestions();
            markDirty();
            nextQuestion.querySelector('summary').focus({ preventScroll: true });
            return;
        }

        const addOptionButton = event.target.closest('[data-add-option]');
        if (addOptionButton && !addOptionButton.disabled) {
            addOptionButton.closest('[data-question]').querySelector('[data-options]').append(optionTemplate.content.cloneNode(true));
            refreshQuestions();
            markDirty();
            return;
        }

        const removeOptionButton = event.target.closest('[data-remove-option]');
        if (removeOptionButton && !removeOptionButton.disabled) {
            removeOptionButton.closest('[data-option-row]').remove();
            refreshQuestions();
            markDirty();
        }
    });
});
