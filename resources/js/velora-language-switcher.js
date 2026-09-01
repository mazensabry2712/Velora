(() => {
    'use strict';

    const closeAll = () => {
        document.querySelectorAll('.velora-inline-language-switcher').forEach((wrapper) => {
            const trigger = wrapper.querySelector('.velora-language-trigger');
            const menu = wrapper.querySelector('.velora-language-menu');

            if (!trigger || !menu) return;

            menu.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        });
    };

    const bind = () => {
        document.querySelectorAll('.velora-inline-language-switcher').forEach((wrapper) => {
            if (wrapper.dataset.veloraLanguageBound === '1') return;

            const trigger = wrapper.querySelector('.velora-language-trigger');
            const menu = wrapper.querySelector('.velora-language-menu');

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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind, { once: true });
    } else {
        bind();
    }

    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeAll();
    });
})();
