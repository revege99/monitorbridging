(() => {
    let switching = false;

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-preserve-sidebar]');
        if (!form || switching) return;

        event.preventDefault();
        const main = document.querySelector('main');
        const select = form.querySelector('select[name="clinic_id"]');
        if (!main || !select) return;
        const formData = new FormData(form);

        switching = true;
        window.dispatchEvent(new CustomEvent('app:loading', {detail: {show: true, message: 'Mengganti klinik...'}}));
        select.disabled = true;
        main.classList.add('pointer-events-none', 'opacity-60');

        try {
            const switchResponse = await fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!switchResponse.ok) {
                const errorData = await switchResponse.json().catch(() => null);
                const validationMessage = errorData?.errors
                    ? Object.values(errorData.errors).flat()[0]
                    : null;
                throw new Error(validationMessage || errorData?.message || 'Gagal mengganti klinik.');
            }

            // Reload the selected clinic with fresh data and all page handlers.
            window.location.reload();
        } catch (error) {
            select.disabled = false;
            main.classList.remove('pointer-events-none', 'opacity-60');
            window.dispatchEvent(new CustomEvent('app:loading', {detail: {show: false}}));
            // The request may have reached the server even if its response was lost.
            // Remove old clinic content rather than leave potentially stale actions usable.
            Array.from(main.children).filter(child => !child.matches('header')).forEach(child => child.remove());
            const panel = document.createElement('div');
            panel.className = 'pcare-card';
            panel.style.margin = '24px';
            panel.textContent = 'Data klinik belum dapat dimuat. Muat ulang halaman untuk memastikan klinik yang aktif.';
            const retry = document.createElement('button');
            retry.className = 'pcare-add';
            retry.textContent = 'Muat ulang';
            retry.onclick = () => window.location.reload();
            panel.append(retry);
            main.append(panel);
            const toast = document.createElement('div');
            toast.className = 'clinic-notice-toast';
            toast.setAttribute('role', 'alert');
            toast.textContent = error.message || 'Gagal mengganti klinik.';
            document.body.append(toast);
            setTimeout(() => toast.remove(), 6000);
        } finally {
            switching = false;
        }
    });
})();
