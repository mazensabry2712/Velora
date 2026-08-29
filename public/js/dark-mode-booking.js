(function () {
    'use strict';

    const STORAGE_KEY = 'bookingDarkMode';
    const ANY_STAFF = '__any__';

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

    function normalizeBrandFallback() {
        document.querySelectorAll('.vb2-fallback-logo img').forEach((image) => {
            const current = image.getAttribute('src') || '';
            if (current.includes('/tenancy/assets/logo-bais.png')) image.setAttribute('src', '/logo-bais.png');
            image.addEventListener('error', () => image.setAttribute('src', '/logo-bais.png'), { once: true });
        });
    }

    function observeBookingResult() {
        const success = document.getElementById('successMessage');
        if (!success) return;

        const sync = () => {
            const reference = window.__veloraBookingReference;
            if (!reference || success.classList.contains('hidden')) return;

            const target = `/queue/status?ref=${encodeURIComponent(reference)}`;
            success.querySelectorAll('a').forEach((link) => {
                if (link.textContent.toLowerCase().includes('queue')) link.href = target;
            });

            if (!window.__veloraBookingRedirected) {
                window.__veloraBookingRedirected = true;
                window.location.assign(target);
            }
        };

        new MutationObserver(sync).observe(success, { attributes: true, attributeFilter: ['class'], childList: true, subtree: true });
        new MutationObserver(sync).observe(document.getElementById('queueNumberText') || success, { childList: true, characterData: true, subtree: true });
        sync();
    }

    function installBookingResponseObserver() {
        if (window.__veloraBookingFetchWrapped || typeof window.fetch !== 'function') return;
        const nativeFetch = window.fetch.bind(window);
        window.fetch = async (...args) => {
            const response = await nativeFetch(...args);
            try {
                const input = args[0];
                const init = args[1] || {};
                const url = typeof input === 'string' ? input : input?.url || '';
                const method = String(init.method || (typeof input === 'object' && input?.method) || 'GET').toUpperCase();
                if (method === 'POST' && url.includes('/api/appointments') && response.ok) {
                    const payload = await response.clone().json();
                    window.__veloraBookingReference = payload?.data?.appointment?.public_reference || null;
                }
            } catch (_) {
                // Never interfere with the original fetch response.
            }
            return response;
        };
        window.__veloraBookingFetchWrapped = true;
    }

    function initBookingEnhancements() {
        const form = document.getElementById('bookingForm');
        if (!form) return;

        const service = document.getElementById('service_id');
        const staff = document.getElementById('staff_id');
        const date = document.getElementById('appointment_date');
        const time = document.getElementById('appointment_time');
        const submit = document.getElementById('submitBtn');
        if (!service || !staff || !date || !time || !submit) return;

        const stepDate = form.querySelector('.vb2-step[data-step="4"]');
        const stepDetails = form.querySelector('.vb2-step[data-step="1"]');
        const stepNotes = form.querySelector('.vb2-step[data-step="6"]');
        let requestSerial = 0;

        const nav = document.createElement('div');
        nav.className = 'vb2-wizard-progress';
        nav.innerHTML = [
            '<span class="is-active" data-progress="service">1</span><i></i>',
            '<span data-progress="staff">2</span><i></i>',
            '<span data-progress="date">3</span><i></i>',
            '<span data-progress="time">4</span><i></i>',
            '<span data-progress="details">5</span>',
        ].join('');
        const cardHead = form.parentElement?.querySelector('.vb2-card-head');
        if (cardHead && !cardHead.nextElementSibling?.classList.contains('vb2-wizard-progress')) cardHead.insertAdjacentElement('afterend', nav);

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
            '<div><span>Service</span><strong data-summary="service">—</strong></div>',
            '<div><span>Staff</span><strong data-summary="staff">—</strong></div>',
            '<div><span>Date</span><strong data-summary="date">—</strong></div>',
            '<div><span>Time</span><strong data-summary="time">—</strong></div>',
            '</div>',
        ].join('');
        const summaryHost = form.parentElement;
        if (summaryHost && !summaryHost.querySelector('.vb2-summary-card')) summaryHost.insertBefore(summary, form);

        const summaryValues = {
            service: summary.querySelector('[data-summary="service"]'),
            staff: summary.querySelector('[data-summary="staff"]'),
            date: summary.querySelector('[data-summary="date"]'),
            time: summary.querySelector('[data-summary="time"]'),
        };

        const text = {
            anyStaff: 'Any available specialist',
            checking: 'Checking all available specialists...',
            chooseTime: 'Choose an available time to continue.',
            noSlots: 'No times are available for the selected date.',
            selectTime: 'Select time',
        };

        function selectedText(select) { return select?.selectedOptions?.[0]?.textContent?.trim() || '—'; }
        function refreshSummary() {
            summaryValues.service.textContent = service.value ? selectedText(service) : '—';
            summaryValues.staff.textContent = staff.value ? (staff.value === ANY_STAFF ? text.anyStaff : selectedText(staff)) : '—';
            summaryValues.date.textContent = date.value || '—';
            summaryValues.time.textContent = time.value ? selectedText(time) : '—';
            const empty = summary.querySelector('.vb2-summary-empty');
            if (empty) empty.hidden = Boolean(service.value || staff.value || date.value || time.value);
        }

        function setProgress(active) {
            const order = ['service', 'staff', 'date', 'time', 'details'];
            const index = order.indexOf(active);
            nav.querySelectorAll('[data-progress]').forEach((node) => {
                const nodeIndex = order.indexOf(node.dataset.progress);
                node.classList.toggle('is-active', nodeIndex === index);
                node.classList.toggle('is-complete', nodeIndex < index);
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
            if (!options.length) { slotWrap.classList.add('hidden'); return; }
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

        function ensureAnyStaffOption() {
            if (staff.querySelector('option[value="' + ANY_STAFF + '"]')) return;
            const option = document.createElement('option');
            option.value = ANY_STAFF;
            option.textContent = text.anyStaff;
            staff.appendChild(option);
        }

        async function fetchSlotsForStaff(staffId, staffName) {
            const params = new URLSearchParams({
                date: date.value,
                staff_id: staffId,
                service_id: service.value,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'Africa/Cairo',
            });
            const response = await fetch('/api/booking/available-timeslots?' + params.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const payload = await response.json();
            if (!response.ok || !payload.success) return [];
            return (payload.data || []).map((slot) => ({ ...slot, staff_id: staffId, staff_name: staffName }));
        }

        async function loadAnyStaffAvailability() {
            const serial = ++requestSerial;
            const loading = document.getElementById('loadingBanner');
            const timeHint = document.getElementById('timeHint');
            const realStaff = Array.from(staff.options)
                .filter((option) => option.value && option.value !== ANY_STAFF)
                .map((option) => ({ id: option.value, name: option.textContent.trim() }));
            loading?.classList.remove('hidden');
            if (loading) loading.textContent = text.checking;
            if (timeHint) timeHint.textContent = text.checking;

            const results = await Promise.all(realStaff.map((item) => fetchSlotsForStaff(item.id, item.name).catch(() => [])));
            if (serial !== requestSerial) return;
            const slots = results.flat().sort((a, b) => a.start_time.localeCompare(b.start_time) || String(a.staff_name).localeCompare(String(b.staff_name)));
            const grid = slotWrap.querySelector('.vb2-slot-grid');
            grid.replaceChildren();

            if (!slots.length) {
                time.innerHTML = '<option value="">' + text.noSlots + '</option>';
                slotWrap.classList.remove('hidden');
                grid.innerHTML = '<p class="vb2-slots-empty">' + text.noSlots + '</p>';
                if (timeHint) timeHint.textContent = text.noSlots;
            } else {
                time.innerHTML = '<option value="">' + text.selectTime + '</option>';
                slots.forEach((slot) => {
                    const option = document.createElement('option');
                    option.value = slot.start_time;
                    option.textContent = slot.label || slot.start_time;
                    option.dataset.staffId = String(slot.staff_id);
                    time.appendChild(option);

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'vb2-slot-button';
                    button.dataset.value = slot.start_time;
                    button.dataset.staffId = String(slot.staff_id);
                    button.setAttribute('aria-pressed', 'false');
                    button.innerHTML = '<strong></strong><span></span>';
                    button.querySelector('strong').textContent = slot.label || slot.start_time;
                    button.querySelector('span').textContent = slot.staff_name;
                    button.addEventListener('click', () => {
                        staff.value = String(slot.staff_id);
                        time.value = slot.start_time;
                        refreshSummary();
                        grid.querySelectorAll('.vb2-slot-button').forEach((item) => item.setAttribute('aria-pressed', item === button ? 'true' : 'false'));
                        time.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    grid.appendChild(button);
                });
                slotWrap.classList.remove('hidden');
                if (timeHint) timeHint.textContent = text.chooseTime;
            }
            loading?.classList.add('hidden');
            refreshSummary();
            updateDetailsVisibility();
        }

        function updateDetailsVisibility() {
            const ready = Boolean(time.value);
            stepDetails?.classList.toggle('vb2-wizard-hidden', !ready);
            stepNotes?.classList.toggle('vb2-wizard-hidden', !ready);
            setProgress(ready ? 'details' : (date.value ? 'time' : (staff.value ? 'date' : (service.value ? 'staff' : 'service'))));
        }

        service.addEventListener('change', () => {
            resetFrom(1);
            ensureAnyStaffOption();
            refreshSummary();
            setProgress('staff');
        });
        staff.addEventListener('change', () => {
            refreshSummary();
            setProgress('date');
            if (!staff.value) return;
            reveal(stepDate);
        });
        date.addEventListener('change', () => {
            resetFrom(2);
            refreshSummary();
            setProgress('time');
            if (staff.value === ANY_STAFF) loadAnyStaffAvailability();
        });
        time.addEventListener('change', () => {
            if (staff.value === ANY_STAFF && time.value) {
                const staffId = time.selectedOptions[0]?.dataset?.staffId;
                if (staffId) staff.value = staffId;
            }
            refreshSummary();
            updateDetailsVisibility();
            renderSlots();
            if (time.value) reveal(stepDetails);
        });

        const observer = new MutationObserver(() => {
            if (service.value) ensureAnyStaffOption();
            renderSlots();
            updateDetailsVisibility();
            refreshSummary();
        });
        observer.observe(time, { childList: true, subtree: true, attributes: true });
        observer.observe(staff, { childList: true, subtree: true });

        ensureAnyStaffOption();
        hideDetailsUntilTime();
        updateDetailsVisibility();
        refreshSummary();
        renderSlots();
        normalizeBrandFallback();
        installBookingResponseObserver();
        observeBookingResult();
    }

    function hideDetailsUntilTime() {
        const form = document.getElementById('bookingForm');
        form?.querySelector('.vb2-step[data-step="1"]')?.classList.add('vb2-wizard-hidden');
        form?.querySelector('.vb2-step[data-step="6"]')?.classList.add('vb2-wizard-hidden');
    }

    function init() {
        apply(readPreference());
        normalizeBrandFallback();
        initBookingEnhancements();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
