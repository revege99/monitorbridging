(() => {
    const rows = Array.from(document.querySelectorAll('[data-pcare-row]'));
    const search = document.getElementById('pcare-search');
    if (!search) return;
    document.querySelectorAll('.pcare-more').forEach(button => {
        const menu = document.getElementById(button.getAttribute('popovertarget'));
        menu.addEventListener('toggle', event => {
            if (event.newState !== 'open') return;
            const bounds = button.getBoundingClientRect();
            menu.style.left = `${Math.max(8, Math.min(bounds.right - menu.offsetWidth, window.innerWidth - menu.offsetWidth - 8))}px`;
            menu.style.top = `${bounds.bottom + menu.offsetHeight + 8 < window.innerHeight ? bounds.bottom + 6 : Math.max(8, bounds.top - menu.offsetHeight - 6)}px`;
        });
        menu.querySelectorAll('button').forEach(action => action.addEventListener('click', () => menu.hidePopover()));
    });
    const size = document.getElementById('pcare-size');
    let page = 1;
    const render = () => {
        const filtered = rows.filter(row => row.dataset.search.includes(search.value.trim().toLowerCase()));
        const limit = Number(size.value);
        const pages = Math.max(1, Math.ceil(filtered.length / limit));
        page = Math.min(page, pages);
        rows.forEach(row => row.hidden = true);
        filtered.slice((page - 1) * limit, page * limit).forEach(row => row.hidden = false);
        document.getElementById('pcare-empty').hidden = filtered.length > 0;
        document.getElementById('pcare-count').textContent = `Menampilkan ${filtered.length ? (page - 1) * limit + 1 : 0}–${Math.min(page * limit, filtered.length)} dari ${filtered.length} konfigurasi`;
        document.getElementById('pcare-page-number').textContent = page;
        document.getElementById('pcare-prev').disabled = page === 1;
        document.getElementById('pcare-next').disabled = page === pages;
    };
    search.addEventListener('input', () => { page = 1; render(); });
    size.addEventListener('change', () => { page = 1; render(); });
    document.getElementById('pcare-prev').onclick = () => { page--; render(); };
    document.getElementById('pcare-next').onclick = () => { page++; render(); };
    document.querySelectorAll('[data-reveal]').forEach(button => button.onclick = () => {
        const shown = button.getAttribute('aria-pressed') !== 'true';
        button.setAttribute('aria-pressed', String(shown));
        button.setAttribute('aria-label', button.getAttribute('aria-label').replace(shown ? 'Tampilkan' : 'Sembunyikan', shown ? 'Sembunyikan' : 'Tampilkan'));
        button.parentElement.querySelector('[data-masked-value]').textContent = shown ? button.parentElement.dataset.value : '\u2022'.repeat(12);
    });
    document.querySelectorAll('[data-copy]').forEach(button => button.onclick = async () => {
        const feedback = document.getElementById('pcare-feedback');
        try {
            await navigator.clipboard.writeText(button.parentElement.dataset.value);
            feedback.textContent = 'Berhasil disalin.';
        } catch (_) { feedback.textContent = 'Tidak dapat menyalin. Tampilkan nilai lalu salin secara manual.'; }
    });
    render();
})();
