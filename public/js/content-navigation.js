(() => {
    let activeController = null;
    let navigationSequence = 0;

    const commonScripts = [
        '/js/app-loading.js',
        '/js/content-navigation.js',
        '/js/clinic-switcher.js',
        '/js/account-menu.js',
        '/js/sidebar-state.js',
    ];

    const isCommonScript = (source) => {
        if (!source) return false;
        const pathname = new URL(source, window.location.href).pathname;
        return commonScripts.some((item) => pathname.endsWith(item));
    };

    const canNavigate = (link, event) => {
        if (
            !link
            || event.defaultPrevented
            || event.button !== 0
            || event.ctrlKey
            || event.metaKey
            || event.shiftKey
            || event.altKey
        ) return false;

        const href = link.getAttribute('href');
        if (
            !href
            || href === '#'
            || href.startsWith('#')
            || link.target === '_blank'
            || link.hasAttribute('download')
            || link.hasAttribute('data-full-navigation')
        ) return false;

        const url = new URL(link.href, window.location.href);
        return url.origin === window.location.origin;
    };

    const executePageScripts = async (nextDocument, sequence) => {
        document.querySelectorAll('script[data-content-page-script]').forEach((script) => script.remove());

        const scripts = Array.from(nextDocument.body.querySelectorAll('script'))
            .filter((script) => !isCommonScript(script.src))
            .filter((script) => script.type !== 'application/json');

        for (const sourceScript of scripts) {
            if (sequence !== navigationSequence) return;

            const script = document.createElement('script');
            script.dataset.contentPageScript = '';

            for (const attribute of sourceScript.attributes) {
                if (!['src', 'defer', 'async'].includes(attribute.name)) {
                    script.setAttribute(attribute.name, attribute.value);
                }
            }

            if (sourceScript.src) {
                script.src = sourceScript.src;
                script.async = false;
                await new Promise((resolve) => {
                    script.addEventListener('load', resolve, { once: true });
                    script.addEventListener('error', resolve, { once: true });
                    document.body.appendChild(script);
                });
            } else if (sourceScript.textContent.trim()) {
                script.textContent = `(() => {\n${sourceScript.textContent}\n})();`;
                document.body.appendChild(script);
            }
        }
    };

    const markCurrentSidebarLink = (targetUrl) => {
        const target = new URL(targetUrl, window.location.href);
        const links = document.querySelectorAll('aside nav a[href]');

        links.forEach((link) => {
            if (link.target === '_blank') return;
            const linkUrl = new URL(link.href, window.location.href);
            const current = linkUrl.pathname === target.pathname;

            link.classList.remove('bg-cyan-400/8', 'ring-1', 'ring-cyan-400/10');
            link.toggleAttribute('aria-current', current);
            link.classList.toggle('sidebar-current-link', current);

            if (current && link.closest('[data-dropdown-panel]')) {
                link.classList.add('bg-cyan-400/8', 'ring-1', 'ring-cyan-400/10');
            }
        });
    };

    const sidebarButtonKey = (button) =>
        button.querySelector('.font-semibold')?.textContent?.trim() || '';

    const captureSidebarState = () => ({
        scrollTop: document.querySelector('aside nav')?.scrollTop || 0,
        openDropdowns: Array.from(document.querySelectorAll('aside nav [data-dropdown-toggle]'))
            .filter((button) => button.getAttribute('aria-expanded') === 'true')
            .map(sidebarButtonKey)
            .filter(Boolean),
    });

    const restoreSidebarState = (state) => {
        const sidebar = document.querySelector('aside nav');
        if (!sidebar) return;

        sidebar.querySelectorAll('[data-dropdown-toggle]').forEach((button) => {
            const open = state.openDropdowns.includes(sidebarButtonKey(button));
            const panel = button.parentElement?.querySelector('[data-dropdown-panel]');
            const icon = button.querySelector('[data-dropdown-icon]');

            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            panel?.classList.toggle('hidden', !open);
            icon?.classList.toggle('rotate-180', open);
        });

        sidebar.scrollTop = state.scrollTop;
        sessionStorage.setItem(
            'monitoring-bridging.sidebar-dropdowns',
            JSON.stringify(state.openDropdowns),
        );
        sessionStorage.setItem(
            'monitoring-bridging.sidebar-scroll',
            String(state.scrollTop),
        );
    };

    const loadContent = async (url, options = {}) => {
        const currentMain = document.querySelector('main');
        if (!currentMain) {
            window.location.assign(url);
            return;
        }

        activeController?.abort();
        activeController = new AbortController();
        const sequence = ++navigationSequence;

        window.dispatchEvent(new CustomEvent('app:loading', {
            detail: {
                show: true,
                message: options.message || 'Memuat konten...',
            },
        }));

        currentMain.classList.add('content-navigation-leaving');

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                signal: activeController.signal,
            });

            if (!response.ok) throw new Error('Konten gagal dimuat.');

            const html = await response.text();
            if (sequence !== navigationSequence) return;

            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextMain = nextDocument.querySelector('main');
            if (!nextMain) throw new Error('Area konten tidak ditemukan.');

            nextMain.classList.add('content-navigation-entering');
            currentMain.replaceWith(nextMain);
            document.title = nextDocument.title || document.title;

            if (options.history !== false) {
                history.pushState(
                    { contentNavigation: true, url: response.url || url },
                    '',
                    response.url || url,
                );
            }

            const sidebarState = captureSidebarState();
            markCurrentSidebarLink(response.url || url);
            await executePageScripts(nextDocument, sequence);
            restoreSidebarState(sidebarState);

            requestAnimationFrame(() => {
                nextMain.classList.remove('content-navigation-entering');
            });

            window.scrollTo({ top: 0, behavior: 'instant' });
            window.dispatchEvent(new CustomEvent('app:content-updated', {
                detail: { url: response.url || url },
            }));
        } catch (error) {
            if (error.name === 'AbortError') return;
            window.location.assign(url);
            return;
        } finally {
            if (sequence === navigationSequence) {
                activeController = null;
                document.querySelector('main')?.classList.remove('content-navigation-leaving');
                window.dispatchEvent(new CustomEvent('app:loading', { detail: { show: false } }));
            }
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('aside nav a[href], main a[href]');
        if (!canNavigate(link, event)) return;

        event.preventDefault();
        loadContent(link.href, {
            message: link.closest('aside') ? 'Membuka menu...' : 'Mengganti tampilan...',
        });
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('aside nav [data-dropdown-toggle]');
        if (!button) return;

        event.preventDefault();
        event.stopImmediatePropagation();

        const panel = button.parentElement?.querySelector('[data-dropdown-panel]');
        const icon = button.querySelector('[data-dropdown-icon]');
        const open = button.getAttribute('aria-expanded') === 'true';
        const nextOpen = !open;

        button.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
        panel?.classList.toggle('hidden', !nextOpen);
        icon?.classList.toggle('rotate-180', nextOpen);

        const state = captureSidebarState();
        sessionStorage.setItem(
            'monitoring-bridging.sidebar-dropdowns',
            JSON.stringify(state.openDropdowns),
        );
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('main form[method="GET"], main form:not([method])');
        if (!form || event.defaultPrevented || form.hasAttribute('data-full-navigation')) return;

        event.preventDefault();
        const url = new URL(form.action || window.location.href, window.location.href);
        url.search = '';
        new FormData(form).forEach((value, key) => url.searchParams.append(key, value));
        loadContent(url.href, { message: 'Menerapkan filter...' });
    });

    window.addEventListener('popstate', () => {
        loadContent(window.location.href, {
            history: false,
            message: 'Memuat tampilan...',
        });
    });

    history.replaceState(
        { ...(history.state || {}), contentNavigation: true, url: window.location.href },
        '',
        window.location.href,
    );
    markCurrentSidebarLink(window.location.href);
})();
