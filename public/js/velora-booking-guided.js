(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('bookingForm');
        const service = document.getElementById('service_id');
        const staff = document.getElementById('staff_id');
        const date = document.getElementById('appointment_date');
        const time = document.getElementById('appointment_time');
        const dateSection = document.getElementById('dateSection');
        const timeSection = document.getElementById('timeSection');
        const timeHint = document.getElementById('timeHint');
        const loading = document.getElementById('loadingBanner');
        const error = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        if (!form || !service || !staff || !date || !time) return;

        const ANY = '__any__';
        let currentStaff = [];
        let requestSerial = 0;

        function refreshStaffCache() {
            currentStaff = Array.from(staff.options)
                .filter((option) => option.value && option.value !== ANY)
                .map((option) => ({ id: option.value, name: option.textContent.trim() }));
        }

        function ensureAnyOption() {
            if (staff.querySelector('option[value="' + ANY + '"]')) return;
            const option = document.createElement('option');
            option.value = ANY;
            option.textContent = 'Any available specialist';
            staff.appendChild(option);
        }

        function showLoading(message) {
            if (!loading) return;
            loading.textContent = message;
            loading.classList.remove('hidden');
        }

        function clearLoading() { loading?.classList.add('hidden'); }
        function showError(message) {
            if (!error || !errorText) return;
            errorText.textContent = message;
            error.classList.remove('hidden');
        }
        function hideError() {
            error?.classList.add('hidden');
            if (errorText) errorText.textContent = '';
        }

        async function fetchSlots(staffItem) {
            const params = new URLSearchParams({
                date: date.value,
                staff_id: staffItem.id,
                service_id: service.value,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'Africa/Cairo',
            });
            const response = await fetch('/api/booking/available-timeslots?' + params.toString(), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) return [];
            return (payload.data || []).map((slot) => ({ ...slot, staff_id: staffItem.id, staff_name: staffItem.name }));
        }

        function renderSlots(slots, serial) {
            if (serial !== requestSerial) return;
            const picker = form.querySelector('.vb2-slot-picker');
            const grid = picker?.querySelector('.vb2-slot-grid');
            if (!picker || !grid) return;
            grid.replaceChildren();

            const unique = new Map();
            slots.forEach((slot) => unique.set(slot.start_time + '|' + slot.staff_id, slot));
            const ordered = Array.from(unique.values()).sort((a, b) => {
                if (a.start_time === b.start_time) return String(a.staff_name).localeCompare(String(b.staff_name));
                return a.start_time.localeCompare(b.start_time);
            });

            ordered.forEach((slot) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'vb2-slot-button';
                button.dataset.value = slot.start_time;
                button.setAttribute('aria-pressed', 'false');
                button.innerHTML = '<strong></strong><span></span>';
                button.querySelector('strong').textContent = slot.label || slot.start_time;
                button.querySelector('span').textContent = slot.staff_name || '';
                button.addEventListener('click', () => {
                    const option = Array.from(staff.options).find((item) => item.value === slot.staff_id);
                    if (!option) return;
                    staff.value = slot.staff_id;
                    time.value = slot.start_time;
                    grid.querySelectorAll('.vb2-slot-button').forEach((item) => item.setAttribute('aria-pressed', item === button ? 'true' : 'false'));
                    time.dispatchEvent(new Event('change', { bubbles: true }));
                });
                grid.appendChild(button);
            });

            picker.classList.remove('hidden');
            const title = picker.querySelector('.vb2-slot-picker-title');
            if (title) title.textContent = ordered.length ? 'Available times across the team' : 'No available times';
        }

        async function loadAnyStaffAvailability() {
            const serial = ++requestSerial;
            refreshStaffCache();
            hideError();
            showLoading('Checking all available specialists...');
            timeSection?.classList.remove('hidden');
            if (timeHint) timeHint.textContent = 'Checking all available specialists...';
            try {
                const results = await Promise.all(currentStaff.map((item) => fetchSlots(item).catch(() => [])));
                const slots = results.flat();
                renderSlots(slots, serial);
                if (serial === requestSerial && timeHint) {
                    timeHint.textContent = slots.length
                        ? 'Choose an available time. The selected specialist will be booked automatically.'
                        : 'No times are available for the selected date.';
                }
            } finally {
                if (serial === requestSerial) clearLoading();
            }
        }

        service.addEventListener('change', () => {
            window.setTimeout(() => { ensureAnyOption(); refreshStaffCache(); }, 0);
        });

        staff.addEventListener('change', () => {
            if (staff.value === ANY) {
                refreshStaffCache();
                dateSection?.classList.remove('hidden');
            }
        });

        // Capture this event before the existing single-staff availability handler.
        date.addEventListener('change', (event) => {
            if (staff.value !== ANY) return;
            event.stopImmediatePropagation();
            loadAnyStaffAvailability();
        }, true);

        const observer = new MutationObserver(() => {
            if (service.value) {
                ensureAnyOption();
                refreshStaffCache();
            }
        });
        observer.observe(staff, { childList: true });
    });
})();
