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
