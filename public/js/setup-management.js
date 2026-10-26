(() => {
    const card = document.querySelector('.setup-management-card');
    if (!card) return;
    const toolbar = card.firstElementChild;
    toolbar.className = 'pcare-toolbar';
    toolbar.firstElementChild.classList.add('pcare-heading');
    const heading = toolbar.firstElementChild;
    const headingContent = document.createElement('div');
    while (heading.firstChild) headingContent.append(heading.firstChild);
    heading.append(headingContent);
    const add = toolbar.querySelector('[data-modal-open]');
    add.className = 'pcare-add';
    const tools = document.createElement('div');
    tools.className = 'pcare-tools';
    tools.innerHTML = '<label class="pcare-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10" cy="10" r="7"/><path d="m15 15 5 5"/></svg><input id="pcare-search" type="search" placeholder="Cari data..." aria-label="Cari data pada tabel"></label>';
    tools.append(add);
    toolbar.append(tools);
    const table = card.querySelector('table');
    table.className = 'pcare-table';
    table.parentElement.classList.add('pcare-table-wrap');
    const rows = Array.from(table.tBodies[0].rows);
    rows.forEach((row, index) => {
        if (row.cells.length === 1) { row.id = 'pcare-empty'; return; }
        row.dataset.pcareRow = '';
        row.dataset.search = row.textContent.toLowerCase();
        const cell = row.lastElementChild;
        const actions = Array.from(cell.querySelectorAll('button'));
        if (!actions.length) return;
        const wrap = document.createElement('div');
        wrap.className = 'pcare-actions';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'pcare-more';
        trigger.setAttribute('popovertarget', `setup-actions-${index}`);
        trigger.setAttribute('aria-label', 'Aksi ' + row.cells[0].textContent.trim());
        trigger.innerHTML = '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>';
        const menu = document.createElement('div');
        menu.id = `setup-actions-${index}`;
        menu.className = 'pcare-action-menu';
        menu.setAttribute('popover', 'auto');
        actions.forEach(button => {
            const label = button.textContent.trim() || (button.hasAttribute('data-modal-open') ? 'Edit' : 'Hapus');
            const isDelete = /hapus/i.test(label);
            const path = isDelete ? '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>' : /tes/i.test(label) ? '<path d="m5 12 4 4L19 6"/>' : '<path d="m14 5 5 5M4 20l5-1L21 7l-5-5L4 14Z"/>';
            button.className = isDelete ? 'pcare-delete' : '';
            button.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">${path}</svg>`;
            const text = document.createElement('span'); text.textContent = label; button.append(text);
            menu.append(button.closest('form') || button);
        });
        cell.replaceChildren(wrap); wrap.append(trigger, menu);
    });
    if (!table.querySelector('#pcare-empty')) {
        const empty = table.tBodies[0].insertRow(); empty.id = 'pcare-empty'; empty.hidden = true;
        const cell = empty.insertCell(); cell.colSpan = table.tHead.rows[0].cells.length;
        cell.textContent = 'Tidak ada data yang sesuai pencarian.';
    }
    const footer = document.createElement('div'); footer.className = 'pcare-footer';
    footer.innerHTML = '<p id="pcare-count" aria-live="polite"></p><div class="pcare-pagination"><select id="pcare-size" aria-label="Jumlah data per halaman"><option value="10">10 / halaman</option><option value="25">25 / halaman</option><option value="50">50 / halaman</option></select><button id="pcare-prev" aria-label="Halaman sebelumnya">&#8249;</button><span id="pcare-page-number">1</span><button id="pcare-next" aria-label="Halaman berikutnya">&#8250;</button></div>';
    card.append(footer);
})();
