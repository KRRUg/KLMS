const input = document.querySelector('[data-scan-input]');

if (input) {
    input.focus();
    input.select();

    document.addEventListener('click', (event) => {
        if (!event.target.closest('input, button, a, select, textarea')) {
            input.focus();
        }
    });
}