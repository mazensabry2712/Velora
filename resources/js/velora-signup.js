(() => {
    'use strict';

    const form = document.getElementById('signupForm');
    if (!form || form.dataset.veloraSignupBound === '1') return;

    form.dataset.veloraSignupBound = '1';

    const submit = form.querySelector('button[type="submit"], input[type="submit"]');
    if (!submit) return;

    const originalLabel = submit.tagName === 'INPUT' ? submit.value : submit.innerHTML;
    let submitted = false;
    let timer = null;
    const messages = [
        'Creating your workspace…',
        'Setting up your business…',
        'Preparing your dashboard…',
    ];

    const setLabel = (message) => {
        if (submit.tagName === 'INPUT') {
            submit.value = message;
            return;
        }

        submit.innerHTML = `<span class="inline-flex items-center justify-center gap-2"><span class="inline-block w-4 h-4 rounded-full border-2 border-white/40 border-t-white animate-spin" aria-hidden="true"></span><span>${message}</span></span>`;
    };

    form.addEventListener('submit', (event) => {
        if (submitted) {
            event.preventDefault();
            return;
        }

        submitted = true;
        form.setAttribute('aria-busy', 'true');
        submit.disabled = true;
        submit.setAttribute('aria-disabled', 'true');
        submit.style.cursor = 'wait';
        setLabel(messages[0]);

        let index = 0;
        timer = window.setInterval(() => {
            index = Math.min(index + 1, messages.length - 1);
            setLabel(messages[index]);
        }, 1200);
    });

    window.addEventListener('pageshow', () => {
        if (timer) window.clearInterval(timer);
        submitted = false;
        form.removeAttribute('aria-busy');
        submit.disabled = false;
        submit.removeAttribute('aria-disabled');
        submit.style.cursor = '';

        if (submit.tagName === 'INPUT') {
            submit.value = originalLabel;
        } else {
            submit.innerHTML = originalLabel;
        }
    });
})();
