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

            const pageResponse = await fetch(window.location.href, {
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
            });
            if (!pageResponse.ok) {
                const errorData = await pageResponse.json().catch(() => null);
                throw new Error(errorData?.message || 'Klinik berhasil dipilih, tetapi data klinik gagal dimuat.');
            }

            const html = await pageResponse.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextMain = nextDocument.querySelector('main');
            if (!nextMain) throw new Error('Konten halaman tidak ditemukan.');

            main.replaceWith(nextMain);
            sessionStorage.removeItem('monitoring-bridging.sidebar-navigation');
            window.dispatchEvent(new CustomEvent('app:content-updated'));
            window.dispatchEvent(new CustomEvent('app:loading', {detail: {show: false}}));
        } catch (error) {
            select.disabled = false;
            main.classList.remove('pointer-events-none', 'opacity-60');
            window.dispatchEvent(new CustomEvent('app:loading', {detail: {show: false}}));
            window.alert(error.message || 'Gagal mengganti klinik.');
        } finally {
            switching = false;
        }
    });
})();
