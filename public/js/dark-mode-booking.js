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

    function initBookingEnhancements() {
        const form = document.getElementById('bookingForm');
        if (!form) return;

        const service = document.getElementById('service_id');
        const staff = document.getElementById('staff_id');
        const date = document.getElementById('appointment_date');
        const time = document.getElementById('appointment_time');
        const submit = document.getElementById('submitBtn');

        if (!service || !staff || !date || !time || !submit) return;

        const stepService = form.querySelector('.vb2-step[data-step="2"]');
        const stepStaff = form.querySelector('.vb2-step[data-step="3"]');
        const stepDate = form.querySelector('.vb2-step[data-step="4"]');
        const stepTime = form.querySelector('.vb2-step[data-step="5"]');
        const stepDetails = form.querySelector('.vb2-step[data-step="1"]');
        const stepNotes = form.querySelector('.vb2-step[data-step="6"]');

        const nav = document.createElement('div');
        nav.className = 'vb2-wizard-progress';
        nav.innerHTML = [
            '<span class="is-active" data-progress="service">1</span>',
            '<i></i>',
            '<span data-progress="staff">2</span>',
            '<i></i>',
            '<span data-progress="date">3</span>',
            '<i></i>',
            '<span data-progress="time">4</span>',
            '<i></i>',
            '<span data-progress="details">5</span>',
        ].join('');

        const cardHead = form.parentElement?.querySelector('.vb2-card-head');
        if (cardHead && !cardHead.nextElementSibling?.classList.contains('vb2-wizard-progress')) {
            cardHead.insertAdjacentElement('afterend', nav);
        }

        const slotWrap = document.createElement('div');
        slotWrap.className = 'vb2-slot-picker hidden';
        slotWrap.setAttribute('aria-live', 'polite');
        slotWrap.innerHTML = '<div class="vb2-slot-picker-title">Available times</div><div class="vb2-slot-grid"></div>';
        time.insertAdjacentElement('afterend', slotWrap);

        const summary = document.createElement('aside');
        summary.className = 'vb2-summary-card';
        summary.innerHTML = [
            '<div class="vb2-summary-title">Your appointment</div>',
            '<div class="vb2-summary-empty">Choose a service to get started.</div>',
            '<div class="vb2-summary-rows">',
            '  <div><span>Service</span><strong data-summary="service">—</strong></div>',
            '  <div><span>Staff</span><strong data-summary="staff">—</strong></div>',
            '  <div><span>Date</span><strong data-summary="date">—</strong></div>',
            '  <div><span>Time</span><strong data-summary="time">—</strong></div>',
            '</div>',
        ].join('');

        const summaryHost = form.parentElement;
        if (summaryHost && !summaryHost.querySelector('.vb2-summary-card')) {
            summaryHost.insertBefore(summary, form);
        }

        const summaryValues = {
            service: summary.querySelector('[data-summary="service"]'),
            staff: summary.querySelector('[data-summary="staff"]'),
            date: summary.querySelector('[data-summary="date"]'),
            time: summary.querySelector('[data-summary="time"]'),
        };

        function selectedText(select) {
            return select?.selectedOptions?.[0]?.textContent?.trim() || '—';
        }

        function refreshSummary() {
            summaryValues.service.textContent = service.value ? selectedText(service) : '—';
            summaryValues.staff.textContent = staff.value ? selectedText(staff) : '—';
            summaryValues.date.textContent = date.value || '—';
            summaryValues.time.textContent = time.value ? selectedText(time) : '—';
            const empty = summary.querySelector('.vb2-summary-empty');
            if (empty) empty.hidden = Boolean(service.value || staff.value || date.value || time.value);
        }

        function setProgress(active) {
            nav.querySelectorAll('[data-progress]').forEach((node) => {
                const order = ['service', 'staff', 'date', 'time', 'details'];
                const activeIndex = order.indexOf(active);
                const nodeIndex = order.indexOf(node.dataset.progress);
                node.classList.toggle('is-active', node.dataset.progress === active);
                node.classList.toggle('is-complete', nodeIndex < activeIndex);
            });
        }

        function reveal(step) {
            if (step && step.classList.contains('hidden')) step.classList.remove('hidden');
            step?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function renderSlots() {
            const grid = slotWrap.querySelector('.vb2-slot-grid');
            grid.replaceChildren();
            const options = Array.from(time.options).filter((option) => option.value);

            if (!options.length) {
                slotWrap.classList.add('hidden');
                return;
            }

            options.forEach((option) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'vb2-slot-button';
                button.dataset.value = option.value;
                button.textContent = option.textContent;
                button.setAttribute('aria-pressed', option.selected ? 'true' : 'false');
                button.addEventListener('click', () => {
                    time.value = option.value;
                    time.dispatchEvent(new Event('change', { bubbles: true }));
                    grid.querySelectorAll('.vb2-slot-button').forEach((item) => item.setAttribute('aria-pressed', item === button ? 'true' : 'false'));
                });
                grid.appendChild(button);
            });

            slotWrap.classList.remove('hidden');
        }

        function hideDetailsUntilTime() {
            if (stepDetails) stepDetails.classList.add('vb2-wizard-hidden');
            if (stepNotes) stepNotes.classList.add('vb2-wizard-hidden');
        }

        function updateDetailsVisibility() {
            const ready = Boolean(time.value);
            if (stepDetails) stepDetails.classList.toggle('vb2-wizard-hidden', !ready);
            if (stepNotes) stepNotes.classList.toggle('vb2-wizard-hidden', !ready);
            setProgress(ready ? 'details' : (date.value ? 'time' : (staff.value ? 'date' : (service.value ? 'staff' : 'service'))));
        }

        service.addEventListener('change', () => {
            refreshSummary();
            setProgress('staff');
        });

        staff.addEventListener('change', () => {
            refreshSummary();
            setProgress('date');
            if (stepDate && !stepDate.classList.contains('hidden')) reveal(stepDate);
        });

        date.addEventListener('change', () => {
            refreshSummary();
            setProgress('time');
        });

        time.addEventListener('change', () => {
            refreshSummary();
            updateDetailsVisibility();
            renderSlots();
            if (time.value) {
                reveal(stepDetails);
                stepDetails?.querySelector('#name')?.focus();
            }
        });

        const observer = new MutationObserver(() => {
            renderSlots();
            updateDetailsVisibility();
            refreshSummary();
        });
        observer.observe(time, { childList: true, subtree: true, attributes: true });

        hideDetailsUntilTime();
        refreshSummary();
        renderSlots();
        updateDetailsVisibility();
    }

    function loadGuidedAnyStaffEnhancement() {
        if (!document.getElementById('bookingForm') || document.getElementById('velora-booking-guided-script')) return;
        const script = document.createElement('script');
        script.id = 'velora-booking-guided-script';
        script.src = '/js/velora-booking-guided.js';
        script.defer = true;
        document.head.appendChild(script);
    }

    function init() {
        apply(readPreference());

        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const handleSystemChange = (event) => {
            if (localStorage.getItem(STORAGE_KEY) === null) apply(event.matches);
        };
        if (typeof media.addEventListener === 'function') media.addEventListener('change', handleSystemChange);
        else if (typeof media.addListener === 'function') media.addListener(handleSystemChange);

        initBookingEnhancements();
        loadGuidedAnyStaffEnhancement();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
