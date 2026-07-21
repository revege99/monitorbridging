(() => {
    const host = document.createElement('div');
    host.id = 'app-loading-overlay';
    host.className = 'app-loading-overlay';
    host.setAttribute('aria-live', 'polite');
    host.setAttribute('aria-hidden', 'true');
    host.innerHTML = `
        <div class="app-loading-progress"><span></span></div>
        <div class="app-loading-card">
            <div class="app-loading-spinner">
                <span class="app-loading-ring app-loading-ring-outer"></span>
                <span class="app-loading-ring app-loading-ring-inner"></span>
                <span class="app-loading-pulse"></span>
            </div>
            <div>
                <p class="app-loading-title">Memuat data</p>
                <p class="app-loading-message" data-loading-message>Mohon tunggu sebentar...</p>
            </div>
        </div>`;
    document.body.appendChild(host);

    let hideTimer;
    const show = (message = 'Mohon tunggu sebentar...') => {
        clearTimeout(hideTimer);
        host.querySelector('[data-loading-message]').textContent = message;
        host.classList.add('is-visible');
        host.setAttribute('aria-hidden', 'false');
    };
    const hide = () => {
        host.classList.remove('is-visible');
        host.setAttribute('aria-hidden', 'true');
    };

    window.addEventListener('app:loading', (event) => {
        if (event.detail?.show) show(event.detail.message);
        else hideTimer = setTimeout(hide, 180);
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('#') || link.target === '_blank' || link.hasAttribute('download')) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        show('Membuka halaman...');
    });

    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented || event.target.matches('[data-preserve-sidebar], [data-no-loading]')) return;
        show('Memproses permintaan...');
    });

    window.addEventListener('pageshow', hide);
})();
