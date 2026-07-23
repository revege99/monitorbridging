(() => {
    const sidebar = document.querySelector('aside nav');
    const script = document.currentScript || document.querySelector('script[src*="/js/sidebar-state.js"]');
    const applicationBaseUrl = script?.src
        ? new URL('../', script.src)
        : new URL('./', window.location.href);

    if (!sidebar) {
        return;
    }

    const dashboardLink = Array.from(sidebar.querySelectorAll('a[href]'))
        .find((link) => link.textContent.trim().toLowerCase().includes('dashboard'));

    if (dashboardLink) {
        dashboardLink.className = 'flex h-10 w-full items-center gap-2 rounded-2xl px-0 text-left text-white transition hover:bg-white/5';
        dashboardLink.innerHTML = `
            <span class="flex h-10 w-11 shrink-0 items-center justify-center text-blue-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m3 10 9-7 9 7"></path>
                    <path d="M5 9v11h14V9"></path>
                    <path d="M9 20v-6h6v6"></path>
                </svg>
            </span>
            <span class="min-w-0 flex-1 text-sm font-semibold">Dashboard</span>
        `;

        const dashboardWrapper = dashboardLink.parentElement;
        if (
            dashboardWrapper
            && dashboardWrapper !== sidebar
            && dashboardWrapper.children.length === 1
        ) {
            dashboardWrapper.classList.remove('-ml-[5px]', 'mb-2');
        }
    }

    if (!sidebar.querySelector('[data-queue-display-link]')) {
        const displayLink = document.createElement('a');
        displayLink.href = new URL('display/antrean', applicationBaseUrl).href;
        displayLink.target = '_blank';
        displayLink.rel = 'noopener';
        displayLink.dataset.queueDisplayLink = '';
        displayLink.className = 'flex h-10 w-full items-center gap-2 rounded-2xl px-0 text-left text-white transition hover:bg-white/5';
        displayLink.innerHTML = `
            <span class="flex h-10 w-11 shrink-0 items-center justify-center text-cyan-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="13" rx="2"></rect>
                    <path d="M8 21h8M12 17v4"></path>
                    <path d="M8 10h8"></path>
                </svg>
            </span>
            <span class="min-w-0 flex-1 text-sm font-semibold">Display Antrean</span>
        `;

        if (dashboardLink) {
            const dashboardWrapper = dashboardLink.parentElement;
            const insertionTarget = dashboardWrapper
                && dashboardWrapper !== sidebar
                && dashboardWrapper.children.length === 1
                ? dashboardWrapper
                : dashboardLink;

            insertionTarget.insertAdjacentElement('afterend', displayLink);
        } else {
            sidebar.prepend(displayLink);
        }
    }

    if (!sidebar.querySelector('[data-kiosk-link]')) {
        const kioskLink = document.createElement('a');
        kioskLink.href = new URL('anjungan', applicationBaseUrl).href;
        kioskLink.target = '_blank';
        kioskLink.rel = 'noopener';
        kioskLink.dataset.kioskLink = '';
        kioskLink.className = 'flex h-10 w-full items-center gap-2 rounded-2xl px-0 text-left text-white transition hover:bg-white/5';
        kioskLink.innerHTML = `
            <span class="flex h-10 w-11 shrink-0 items-center justify-center text-blue-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M9 7h6M9 11h6M9 15h3"></path>
                </svg>
            </span>
            <span class="min-w-0 flex-1 text-sm font-semibold">Anjungan Mandiri</span>
        `;
        const displayLink = sidebar.querySelector('[data-queue-display-link]');
        if (displayLink) displayLink.insertAdjacentElement('afterend', kioskLink);
        else sidebar.prepend(kioskLink);
    }

    if (!sidebar.querySelector('[data-service-monitor-link]')) {
        const serviceLink = document.createElement('a');
        serviceLink.href = new URL('service-monitor/antrean', applicationBaseUrl).href;
        serviceLink.dataset.serviceMonitorLink = '';
        serviceLink.className = 'flex h-10 w-full items-center gap-2 rounded-2xl px-0 text-left text-white transition hover:bg-white/5';
        serviceLink.innerHTML = `
            <span class="flex h-10 w-11 shrink-0 items-center justify-center text-cyan-300">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12h4l2-5 4 10 2-5h6"></path>
                    <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"></path>
                </svg>
            </span>
            <span class="min-w-0 flex-1 text-sm font-semibold">Monitor Service</span>
        `;
        const kioskLink = sidebar.querySelector('[data-kiosk-link]');
        if (kioskLink) kioskLink.insertAdjacentElement('afterend', serviceLink);
        else sidebar.prepend(serviceLink);
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
