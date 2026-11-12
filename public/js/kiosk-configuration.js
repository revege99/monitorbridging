(() => {
    document.querySelectorAll('[data-dropdown-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const open = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', String(!open));
            button.parentElement.querySelector('[data-dropdown-panel]')?.classList.toggle('hidden', open);
            button.querySelector('[data-dropdown-icon]')?.classList.toggle('rotate-180', !open);
        });
    });
    const form = document.getElementById('kiosk-format-form');
    if (!form) return;
    const digits = document.getElementById('rm-digits');
    const useSeparator = document.getElementById('rm-use-separator');
    const separator = document.getElementById('rm-separator');
    const groups = document.getElementById('rm-groups');
    const preview = document.getElementById('rm-preview');
    const update = () => {
        const length = Number(digits.value);
        const use = useSeparator.value === '1';
        groups.required = use;
        if (!Number.isInteger(length) || length < 1 || length > 12) {
            preview.textContent = 'Periksa jumlah digit';
            return;
        }
        const number = '1'.padStart(length, '0');
        if (!use) { preview.textContent = number; return; }
        const parts = groups.value.split('-').map(Number);
        if (!/^[1-9][0-9]*(?:-[1-9][0-9]*)*$/.test(groups.value) ||
            parts.reduce((sum, value) => sum + value, 0) !== length || length + parts.length - 1 > 15) {
            preview.textContent = 'Periksa pengelompokan digit';
            return;
        }
        let offset = 0;
        preview.textContent = parts.map(size => {
            const part = number.slice(offset, offset + size); offset += size; return part;
        }).join(separator.value);
    };
    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
})();
