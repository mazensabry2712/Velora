(() => {
    'use strict';

    const selector = '.velora-inline-language-switcher, .velora-language-switcher';

    const getParts = (wrapper) => ({
        trigger: wrapper?.querySelector('.velora-language-trigger'),
        menu: wrapper?.querySelector('.velora-language-menu'),
    });

    const closeAll = () => {
        document.querySelectorAll(selector).forEach((wrapper) => {
            const { trigger, menu } = getParts(wrapper);
            if (!trigger || !menu) return;

            menu.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        });
    };

    const openFirst = () => {
        const wrapper = document.querySelector(selector);
        if (!wrapper) return false;

        const { trigger, menu } = getParts(wrapper);
        if (!trigger || !menu) return false;

        closeAll();
        menu.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        return true;
    };

    const bind = () => {
        document.querySelectorAll(selector).forEach((wrapper) => {
            // The response middleware also installs a fallback binding before
            // this deferred Vite module executes. Treat both bindings as the
            // same integration point so a click cannot toggle twice.
            if (wrapper.dataset.veloraLanguageBound === '1' || wrapper.dataset.ready === '1') return;

            const { trigger, menu } = getParts(wrapper);
            if (!trigger || !menu) return;

            wrapper.dataset.veloraLanguageBound = '1';

            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const isOpen = trigger.getAttribute('aria-expanded') === 'true';
                closeAll();

                if (!isOpen) {
                    menu.hidden = false;
                    trigger.setAttribute('aria-expanded', 'true');
                }
            });

            menu.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        });
    };

    const init = () => {
        bind();

        // Backward compatibility with the legacy landing-page trigger.
        window.addEventListener('velora:open-lang-switcher', (event) => {
            event.preventDefault?.();
            event.stopPropagation?.();
            openFirst();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }

    document.addEventListener('click', (event) => {
        if (event.target.closest?.('.velora-language-switcher, .velora-inline-language-switcher')) return;
        closeAll();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeAll();
    });
})();
