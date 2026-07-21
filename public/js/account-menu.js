(() => {
    const closeMenus = (except = null) => {
        document.querySelectorAll('[data-account-menu]').forEach((menu) => {
            if (menu === except) return;
            menu.querySelector('[data-account-panel]')?.classList.add('hidden');
            menu.querySelector('[data-account-toggle]')?.setAttribute('aria-expanded', 'false');
            menu.querySelector('[data-account-chevron]')?.classList.remove('rotate-180');
        });
    };

    const toast = (message) => {
        document.querySelector('[data-account-toast]')?.remove();
        const item = document.createElement('div');
        item.dataset.accountToast = '';
        item.className = 'fixed bottom-6 right-6 z-[200] rounded-2xl border border-emerald-200 bg-white px-5 py-3 text-sm font-semibold text-emerald-700 shadow-2xl';
        item.textContent = message;
        document.body.appendChild(item);
        setTimeout(() => item.remove(), 3500);
    };

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-account-toggle]');
        if (toggle) {
            const menu = toggle.closest('[data-account-menu]');
            const panel = menu?.querySelector('[data-account-panel]');
            const willOpen = panel?.classList.contains('hidden');
            closeMenus(menu);
            panel?.classList.toggle('hidden', !willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            menu?.querySelector('[data-account-chevron]')?.classList.toggle('rotate-180', willOpen);
            return;
        }

        const open = event.target.closest('[data-password-open]');
        if (open) {
            const dialog = document.querySelector('[data-password-dialog]');
            closeMenus();
            dialog?.querySelector('[data-password-error]')?.classList.add('hidden');
            dialog?.showModal();
            return;
        }

        if (event.target.closest('[data-password-close]')) {
            event.target.closest('dialog')?.close();
            return;
        }

        if (!event.target.closest('[data-account-menu]')) closeMenus();
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-password-form]');
        if (!form) return;
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const errorBox = form.querySelector('[data-password-error]');
        button.disabled = true;
        button.textContent = 'Menyimpan...';
        errorBox.classList.add('hidden');

        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Password gagal diubah.');
            form.reset();
            form.closest('dialog')?.close();
            toast(data.message || 'Password berhasil diubah.');
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = 'Simpan Password';
        }
    });
})();
