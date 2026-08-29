(function () {
    'use strict';

    document.body.classList.add('vb-final');

    const script = document.createElement('script');
    script.src = '/js/velora-booking-ui-polish.js';
    script.defer = false;
    script.onload = function () {
        document.documentElement.dataset.veloraBookingUi = 'ready';
    };
    document.head.appendChild(script);

    const saved = localStorage.getItem('bookingDarkMode');
    const isDark = saved === 'true' || (saved === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', isDark);
    const icon = document.getElementById('dark-mode-icon');
    if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    window.toggleDarkMode = function () {
        const next = !document.documentElement.classList.contains('dark');
        localStorage.setItem('bookingDarkMode', String(next));
        document.documentElement.classList.toggle('dark', next);
        const nextIcon = document.getElementById('dark-mode-icon');
        if (nextIcon) nextIcon.textContent = next ? '☀️' : '🌙';
    };
})();
