(function () {
    'use strict';
    const STORAGE_KEY = 'bookingDarkMode';

    function apply(isDark) {
        document.documentElement.classList.toggle('dark', isDark);
        const icon = document.getElementById('dark-mode-icon');
        if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    }

    function readPreference() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved === 'true') return true;
        if (saved === 'false') return false;
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    window.toggleDarkMode = function () {
        const isDark = !document.documentElement.classList.contains('dark');
        localStorage.setItem(STORAGE_KEY, String(isDark));
        apply(isDark);
    };

    function init() {
        apply(readPreference());
        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const handleSystemChange = (event) => {
            if (localStorage.getItem(STORAGE_KEY) === null) apply(event.matches);
        };
        if (typeof media.addEventListener === 'function') media.addEventListener('change', handleSystemChange);
        else if (typeof media.addListener === 'function') media.addListener(handleSystemChange);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
