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

        let currentStaff = [];
        let slotButtons = null;
        const anyValue = '__any__';

        const text = {
            anyStaff: 'Any available specialist',
            anyStaffHint: 'We will find the earliest available time across the team.',
            checking: 'Checking all available specialists...',
            noSlots: 'No times are available for the selected date.',
            chooseTime: 'Choose an available time to continue.',
            network: 'Unable to load availability. Please try again.',
            selectTime: 'Select time',
        };

        const applyI18n = function () {
            const translations = window.veloraBookingText || {};
            Object.keys(text).forEach((key) => {
                if (translations[key]) text[key] = translations[key];
            });
        };
        applyI18n();

        function setLoading(message) {
            if (!loading) return;
            loading.textContent = message;
            loading.classList.remove('hidden');
        }

        function clearLoading() {
            if (loading) loading.classList.add('hidden');
        }

        function showError(message) {
            if (!error || !errorText) return;
            errorText.textContent = message;
            error.classList.remove('hidden');
        }

        function hideError() {
            if (!error || !errorText) return;
            error.classList.add('hidden');
            errorText.textContent = '';
        }

        function ensureAnyStaffOption() {
            if (staff.querySelector('option[value="' + anyValue + '"]')) return;
            const option = document.createElement('option');
            option.value = anyValue;
            option.textContent = text.anyStaff;
            staff.appendChild(option);
        }

        function normalizeStaffList() {
            currentStaff = Array.from(staff.options)
                .filter((option) => option.value && option.value !== anyValue)
                .map((option) => ({ id: option.value, name: option.textContent }));
        }

        function ensureTimeSlotCards() {
            if (slotButtons) return slotButtons;
            const host = document.createElement('div');
            host.className = 'vb-guided-slots';
            host.setAttribute('role', 'listbox');
            host.setAttribute('aria-label', 'Available times');
            time.parentNode.insertBefore(host, time);
            time.classList.add('vb-guided-native-time');
            slotButtons = host;
            return host;
        }

        function clearSlotCards() {
            const host = ensureTimeSlotCards();
            host.innerHTML = '';
        }

        function renderSlotCards(slots) {
            const host = ensureTimeSlotCards();
            host.innerHTML = '';

            const unique = new Map();
            slots.forEach((slot) => {
                const key = slot.start_time + '|' + slot.staff_id;
                if (!unique.has(key)) unique.set(key, slot);
            });

            const sorted = Array.from(unique.values()).sort((a, b) => {
                if (a.start_time === b.start_time) return String(a.staff_name || '').localeCompare(String(b.staff_name || ''));
                return a.start_time.localeCompare(b.start_time);
            });

            sorted.forEach((slot) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'vb-guided-slot';
                button.setAttribute('role', 'option');
                button.dataset.time = slot.start_time;
                button.dataset.staffId = slot.staff_id;
                button.dataset.staffName = slot.staff_name || '';
                button.innerHTML = '<strong></strong><span></span>';
                button.querySelector('strong').textContent = slot.label || slot.start_time;
                if (staff.value === anyValue) {
                    button.querySelector('span').textContent = slot.staff_name || text.anyStaff;
                }

                button.addEventListener('click', function () {
                    host.querySelectorAll('.vb-guided-slot[aria-selected="true"]').forEach((item) => item.setAttribute('aria-selected', 'false'));
                    button.setAttribute('aria-selected', 'true');

                    if (button.dataset.staffId) {
                        const option = Array.from(staff.options).find((item) => item.value === button.dataset.staffId);
                        if (option) staff.value = button.dataset.staffId;
                    }

                    time.value = button.dataset.time;
                    time.dispatchEvent(new Event('change', { bubbles: true }));
                });

                host.appendChild(button);
            });

            if (!sorted.length) {
                const empty = document.createElement('p');
                empty.className = 'vb-guided-slots-empty';
                empty.textContent = text.noSlots;
                host.appendChild(empty);
            }

            time.classList.add('vb-guided-native-time');
        }

        function buildSingleStaffSlots(payload, staffItem) {
            return (payload.data || []).map((slot) => ({
                start_time: slot.start_time,
                end_time: slot.end_time,
                label: slot.label || slot.start_time,
                staff_id: staffItem.id,
                staff_name: staffItem.name,
            }));
        }

        async function fetchSlotsForStaff(staffItem) {
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
            return buildSingleStaffSlots(payload, staffItem);
        }

        async function loadAnyStaffAvailability() {
            hideError();
            setLoading(text.checking);
            clearSlotCards();
            timeSection.classList.remove('hidden');
            timeHint.textContent = text.checking;

            try {
                normalizeStaffList();
                const results = [];
                for (const staffItem of currentStaff) {
                    const staffSlots = await fetchSlotsForStaff(staffItem);
                    results.push(...staffSlots);
                }

                renderSlotCards(results);
                timeHint.textContent = results.length ? text.chooseTime : text.noSlots;
            } catch (e) {
                showError(text.network);
                timeHint.textContent = text.network;
                renderSlotCards([]);
            } finally {
                clearLoading();
            }
        }

        service.addEventListener('change', function () {
            // The existing page listener populates the staff list first.
            window.setTimeout(function () {
                ensureAnyStaffOption();
                normalizeStaffList();
            }, 0);
        });

        staff.addEventListener('change', function () {
            if (staff.value !== anyValue) return;
            dateSection.classList.remove('hidden');
            const hint = document.getElementById('serviceHint');
            if (hint) {
                hint.textContent = text.anyStaffHint;
                hint.classList.remove('hidden');
            }
        });

        date.addEventListener('change', function (event) {
            if (staff.value !== anyValue) return;
            event.stopImmediatePropagation();
            loadAnyStaffAvailability();
        }, true);

        time.addEventListener('change', function () {
            const selected = slotButtons && slotButtons.querySelector('[data-time="' + CSS.escape(time.value) + '"][data-staff-id="' + CSS.escape(staff.value) + '"]');
            if (selected) selected.setAttribute('aria-selected', 'true');
        });

        // Re-attach the option after async staff loading from the original page script.
        const observer = new MutationObserver(function () {
            if (service.value && !staff.querySelector('option[value="' + anyValue + '"]')) {
                ensureAnyStaffOption();
                normalizeStaffList();
            }
        });
        observer.observe(staff, { childList: true });

        // Keep the hidden/native time select available for form submission and validation.
        if (time.closest('.vb2-field')) {
            time.closest('.vb2-field').classList.add('vb-guided-time-field');
        }
    });
})();
