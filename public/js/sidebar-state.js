(() => {
    const sidebar = document.querySelector('aside nav');

    if (!sidebar) {
        return;
    }

    if (!sidebar.querySelector('[data-queue-display-link]')) {
        const displayLink = document.createElement('a');
        displayLink.href = '/display/antrean';
        displayLink.target = '_blank';
        displayLink.rel = 'noopener';
        displayLink.dataset.queueDisplayLink = '';
        displayLink.className = 'mb-2 flex items-center gap-3 rounded-xl border border-cyan-300/15 bg-cyan-300/10 px-2 py-2.5 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-300/15';
        displayLink.innerHTML = `
            <span class="flex h-7 w-7 items-center justify-center text-cyan-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="13" rx="2"></rect>
                    <path d="M8 21h8M12 17v4"></path>
                    <path d="M8 10h8"></path>
                </svg>
            </span>
            <span class="min-w-0 flex-1">Display Antrean</span>
        `;

        const dashboardLink = Array.from(sidebar.querySelectorAll('a[href]'))
            .find((link) => link.textContent.trim().toLowerCase().includes('dashboard'));

        if (dashboardLink) {
            dashboardLink.insertAdjacentElement('afterend', displayLink);
        } else {
            sidebar.prepend(displayLink);
        }
    }

    if (!sidebar.querySelector('[data-kiosk-link]')) {
        const kioskLink = document.createElement('a');
        kioskLink.href = '/anjungan';
        kioskLink.target = '_blank';
        kioskLink.rel = 'noopener';
        kioskLink.dataset.kioskLink = '';
        kioskLink.className = 'mb-2 flex items-center gap-3 rounded-xl border border-blue-300/15 bg-blue-300/10 px-2 py-2.5 text-sm font-semibold text-blue-100 transition hover:bg-blue-300/15';
        kioskLink.innerHTML = `
            <span class="flex h-7 w-7 items-center justify-center text-blue-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M9 7h6M9 11h6M9 15h3"></path>
                </svg>
            </span>
            <span class="min-w-0 flex-1">Anjungan Mandiri</span>
        `;
        const displayLink = sidebar.querySelector('[data-queue-display-link]');
        if (displayLink) displayLink.insertAdjacentElement('afterend', kioskLink);
        else sidebar.prepend(kioskLink);
    }

    const scrollStorageKey = 'monitoring-bridging.sidebar-scroll';
    const dropdownStorageKey = 'monitoring-bridging.sidebar-dropdowns';
    const dropdownButtons = Array.from(sidebar.querySelectorAll('[data-dropdown-toggle]'));

    const buttonKey = (button) => {
        const label = button.querySelector('.font-semibold')?.textContent?.trim();
        return label || `dropdown-${dropdownButtons.indexOf(button)}`;
    };

    let openDropdowns = [];
    try {
        openDropdowns = JSON.parse(sessionStorage.getItem(dropdownStorageKey) || '[]');
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

    const savedPosition = Number.parseInt(sessionStorage.getItem(scrollStorageKey) ?? '0', 10);
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
            saveDropdowns();
            savePosition();
        });
    });
    document.addEventListener('submit', () => {
        saveDropdowns();
        savePosition();
    }, { capture: true });
    window.addEventListener('beforeunload', () => {
        saveDropdowns();
        savePosition();
    });
    window.addEventListener('pagehide', () => {
        saveDropdowns();
        savePosition();
    });
})();
