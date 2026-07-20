(() => {
    let loading = false;

    const loadContent = async (url, options = {}) => {
        if (loading) return;
        const currentMain = document.querySelector('main');
        if (!currentMain) {
            window.location.assign(url);
            return;
        }

        loading = true;
        window.dispatchEvent(new CustomEvent('app:loading', {detail: {show: true, message: options.message || 'Memuat konten...'}}));

        try {
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
            });
            if (!response.ok) throw new Error('Konten gagal dimuat.');

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextMain = nextDocument.querySelector('main');
            if (!nextMain) throw new Error('Area konten tidak ditemukan.');

            currentMain.replaceWith(nextMain);
            document.title = nextDocument.title || document.title;
            if (options.history !== false) history.pushState({contentNavigation: true}, '', response.url || url);
            window.dispatchEvent(new CustomEvent('app:content-updated'));
        } catch (_) {
            window.location.assign(url);
            return;
        } finally {
            loading = false;
            window.dispatchEvent(new CustomEvent('app:loading', {detail: {show: false}}));
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('main a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('#') || link.target === '_blank' || link.hasAttribute('download')) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;

        event.preventDefault();
        loadContent(url.href, {message: 'Mengganti tampilan...'});
    });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('main form[method="GET"], main form:not([method])');
        if (!form || event.defaultPrevented) return;
        event.preventDefault();
        const url = new URL(form.action || window.location.href, window.location.href);
        new FormData(form).forEach((value, key) => url.searchParams.set(key, value));
        loadContent(url.href, {message: 'Menerapkan filter...'});
    });

    window.addEventListener('popstate', () => loadContent(window.location.href, {history: false, message: 'Memuat tampilan...'}));
})();
