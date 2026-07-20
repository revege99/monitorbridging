(() => {
    const sidebar = document.querySelector('aside nav');

    if (!sidebar) {
        return;
    }

    const storageKey = 'monitoring-bridging.sidebar-scroll';
    const savedPosition = Number.parseInt(sessionStorage.getItem(storageKey) ?? '0', 10);

    if (Number.isFinite(savedPosition)) {
        sidebar.scrollTop = savedPosition;
        requestAnimationFrame(() => {
            sidebar.scrollTop = savedPosition;
        });
    }

    const savePosition = () => {
        sessionStorage.setItem(storageKey, String(sidebar.scrollTop));
    };

    sidebar.addEventListener('scroll', savePosition, { passive: true });
    sidebar.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', savePosition);
    });
    window.addEventListener('pagehide', savePosition);
})();
