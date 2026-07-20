(() => {
    const sidebar = document.querySelector('aside nav');

    if (!sidebar) {
        return;
    }

    const scrollStorageKey = 'monitoring-bridging.sidebar-scroll';
    const dropdownStorageKey = 'monitoring-bridging.sidebar-dropdowns';
    const navigationStorageKey = 'monitoring-bridging.sidebar-navigation';
    const shouldRestore = sessionStorage.getItem(navigationStorageKey) === '1';
    sessionStorage.removeItem(navigationStorageKey);
    const dropdownButtons = Array.from(sidebar.querySelectorAll('[data-dropdown-toggle]'));

    const buttonKey = (button) => {
        const label = button.querySelector('.font-semibold')?.textContent?.trim();
        return label || `dropdown-${dropdownButtons.indexOf(button)}`;
    };

    let openDropdowns = [];
    try {
        openDropdowns = shouldRestore ? JSON.parse(sessionStorage.getItem(dropdownStorageKey) || '[]') : [];
    } catch (_) {
        openDropdowns = [];
    }

    dropdownButtons.forEach((button) => {
        const panel = button.parentElement?.querySelector('[data-dropdown-panel]');
        const icon = button.querySelector('[data-dropdown-icon]');
        const open = openDropdowns.includes(buttonKey(button));

        if (!panel) return;
        panel.classList.toggle('hidden', !open);
        icon?.classList.toggle('rotate-180', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    const savedPosition = shouldRestore ? Number.parseInt(sessionStorage.getItem(scrollStorageKey) ?? '0', 10) : 0;
    const restorePosition = () => {
        if (Number.isFinite(savedPosition)) sidebar.scrollTop = savedPosition;
    };

    restorePosition();
    requestAnimationFrame(() => {
        restorePosition();
        requestAnimationFrame(restorePosition);
    });

    const savePosition = () => {
        sessionStorage.setItem(scrollStorageKey, String(sidebar.scrollTop));
    };

    const saveDropdowns = () => {
        const open = dropdownButtons
            .filter((button) => button.getAttribute('aria-expanded') === 'true')
            .map(buttonKey);
        sessionStorage.setItem(dropdownStorageKey, JSON.stringify(open));
    };

    sidebar.addEventListener('scroll', savePosition, { passive: true });
    dropdownButtons.forEach((button) => {
        button.addEventListener('click', () => {
            requestAnimationFrame(() => {
                saveDropdowns();
                savePosition();
            });
        });
    });
    sidebar.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => {
            sessionStorage.setItem(navigationStorageKey, '1');
            saveDropdowns();
            savePosition();
        });
    });
    document.querySelectorAll('form[data-preserve-sidebar]').forEach((form) => {
        form.addEventListener('submit', () => {
            sessionStorage.setItem(navigationStorageKey, '1');
            saveDropdowns();
            savePosition();
        });
    });
    window.addEventListener('pagehide', () => {
        saveDropdowns();
        savePosition();
    });
})();
