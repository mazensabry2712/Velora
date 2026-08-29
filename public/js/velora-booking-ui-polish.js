(function () {
    'use strict';

    const form = document.getElementById('bookingForm');
    if (!form) return;

    const lang = (document.documentElement.lang || 'en').toLowerCase();
    const rtl = document.documentElement.dir === 'rtl' || lang === 'ar';
    const ANY = '__any__';
    const cfg = window.veloraBookingConfig || {};
    const t = lang === 'ar' ? {
        service:'الخدمة', staff:'الموظف', dateTime:'التاريخ والوقت', details:'البيانات',
        chooseService:'اختر الخدمة', chooseStaff:'اختر المتخصص', chooseDateTime:'اختر التاريخ والوقت', detailsTitle:'بيانات العميل',
        serviceHelp:'ابدأ باختيار الخدمة التي تريد حجزها.', staffHelp:'اختر موظفًا محددًا أو اتركنا نختار أول موعد متاح.',
        dateHelp:'نحن نعرض فقط المواعيد المتاحة فعليًا.', detailsHelp:'أدخل بياناتك لإتمام الحجز.',
        any:'أي موظف متاح', available:'المواعيد المتاحة', availableHelp:'اختر الموعد المناسب لك.',
        next:'متابعة', back:'رجوع', booking:'جارٍ الحجز...', confirm:'تأكيد الحجز',
        loadingServices:'جارٍ تحميل الخدمات...', loadingStaff:'جارٍ تحميل الموظفين...', loadingSlots:'جارٍ التحقق من المواعيد...',
        noServices:'لا توجد خدمات متاحة للحجز الإلكتروني حاليًا.', noStaff:'لا يوجد موظفون متاحون لهذه الخدمة حاليًا.',
        noSlots:'لا توجد مواعيد متاحة في هذا اليوم.', network:'تعذر إتمام الطلب. حاول مرة أخرى.',
        online:'متاح للحجز', specialist:'متخصص متاح', guest:'زائر', track:'لديك حجز بالفعل؟ تتبع موعدك',
    } : {
        service:'Service', staff:'Staff', dateTime:'Date & Time', details:'Details',
        chooseService:'Choose a service', chooseStaff:'Choose a specialist', chooseDateTime:'Choose date & time', detailsTitle:'Your details',
        serviceHelp:'Start with what you would like to book.', staffHelp:'Pick someone specific or let us find the first available option.',
        dateHelp:'We only show appointments that are actually available.', detailsHelp:'Enter your details to complete the booking.',
        any:'Any available specialist', available:'Available times', availableHelp:'Pick the time that works best for you.',
        next:'Continue', back:'Back', booking:'Booking...', confirm:'Confirm appointment',
        loadingServices:'Loading services...', loadingStaff:'Loading specialists...', loadingSlots:'Checking available times...',
        noServices:'No online-bookable services are available right now.', noStaff:'No staff members are currently available for this service.',
        noSlots:'No times are available for the selected date.', network:'Unable to complete the request. Please try again.',
        online:'Available online', specialist:'Available specialist', guest:'Guest', track:'Already booked? Track your appointment',
    };

    const service = document.getElementById('service_id');
    const staff = document.getElementById('staff_id');
    const date = document.getElementById('appointment_date');
    const time = document.getElementById('appointment_time');
    const submit = document.getElementById('submitBtn');
    const loading = document.getElementById('loadingBanner');
    const errorBox = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const stepService = form.querySelector('[data-step-panel="1"]');
    const stepStaff = form.querySelector('[data-step-panel="2"]');
    const stepDate = form.querySelector('[data-step-panel="3"]');
    const stepDetails = form.querySelector('[data-step-panel="4"]');
    const serviceCards = document.getElementById('serviceCards');
    const staffCards = document.getElementById('staffCards');
    const slotGrid = document.getElementById('vbFinalSlots');

    if (!service || !staff || !date || !time || !submit || !stepService || !stepStaff || !stepDate || !stepDetails) return;

    document.body.classList.add('vb-final');

    function applyDark(isDark) {
        document.documentElement.classList.toggle('dark', isDark);
        const icon = document.getElementById('dark-mode-icon');
        if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    }
    window.toggleDarkMode = function () {
        const next = !document.documentElement.classList.contains('dark');
        localStorage.setItem('bookingDarkMode', String(next));
        applyDark(next);
    };
    window.changeLanguage = function (value) { window.location.href = '/change-language/' + encodeURIComponent(value); };

    const savedTheme = localStorage.getItem('bookingDarkMode');
    applyDark(savedTheme === 'true' || (savedTheme === null && window.matchMedia('(prefers-color-scheme: dark)').matches));

    function setLoading(message) {
        if (!loading) return;
        loading.textContent = message;
        loading.classList.remove('hidden');
    }
    function clearLoading() { loading?.classList.add('hidden'); }
    function showError(message) { if (errorBox && errorText) { errorText.textContent = message; errorBox.classList.remove('hidden'); } }
    function clearError() { errorBox?.classList.add('hidden'); if (errorText) errorText.textContent = ''; }

    function setStep(n) {
        [stepService, stepStaff, stepDate, stepDetails].forEach((panel) => {
            panel.classList.toggle('vb-final-v2-active', panel.dataset.stepPanel === String(n));
            panel.hidden = panel.dataset.stepPanel !== String(n);
        });
        document.querySelectorAll('.vb-final-v2-progress-item').forEach((item) => {
            const number = Number(item.dataset.step);
            item.classList.toggle('is-active', number === n);
            item.classList.toggle('is-complete', number < n);
        });
        window.scrollTo({ top: Math.max(0, form.getBoundingClientRect().top + window.scrollY - 110), behavior: 'smooth' });
    }

    function currentLabel(select, fallback = '—') { return select.value ? (select.selectedOptions[0]?.textContent?.trim() || fallback) : fallback; }

    function updateSummary() {
        const values = {
            service: service.value ? currentLabel(service) : '—',
            staff: staff.value === ANY ? t.any : (staff.value ? currentLabel(staff) : '—'),
            date: date.value || '—',
            time: time.value ? currentLabel(time) : '—',
        };
        document.getElementById('vbSummaryService')?.replaceChildren(document.createTextNode(values.service));
        document.getElementById('vbSummaryStaff')?.replaceChildren(document.createTextNode(values.staff));
        document.getElementById('vbSummaryDate')?.replaceChildren(document.createTextNode(values.date));
        document.getElementById('vbSummaryTime')?.replaceChildren(document.createTextNode(values.time));
        document.getElementById('reviewService')?.replaceChildren(document.createTextNode(values.service));
        document.getElementById('reviewStaff')?.replaceChildren(document.createTextNode(values.staff));
        document.getElementById('reviewDate')?.replaceChildren(document.createTextNode(values.date));
        document.getElementById('reviewTime')?.replaceChildren(document.createTextNode(values.time));
    }

    function syncButtons() {
        document.getElementById('serviceContinue')?.toggleAttribute('disabled', !service.value);
        document.getElementById('staffContinue')?.toggleAttribute('disabled', !staff.value);
        document.getElementById('dateContinue')?.toggleAttribute('disabled', !(date.value && time.value));
        const detailsReady = Boolean(date.value && time.value && document.getElementById('name')?.value && document.getElementById('phone')?.value && document.getElementById('email')?.value);
        submit.disabled = !detailsReady;
        submit.textContent = t.confirm;
        updateSummary();
    }

    function renderServices() {
        serviceCards?.replaceChildren();
        const options = Array.from(service.options).filter((option) => option.value);
        if (!options.length) {
            const empty = document.createElement('div'); empty.className = 'vb-final-v2-empty'; empty.textContent = service.options[0]?.textContent?.trim() || t.noServices; serviceCards?.appendChild(empty); return;
        }
        options.forEach((option) => {
            const card = document.createElement('button'); card.type = 'button'; card.className = 'vb-final-v2-choice' + (option.selected ? ' is-selected' : '');
            const raw = option.textContent.trim();
            const match = raw.match(/^(.*?)(?:\s*\((\d+)\s*min\))?$/i);
            card.innerHTML = '<strong></strong><span></span>';
            card.querySelector('strong').textContent = match?.[1]?.trim() || raw;
            card.querySelector('span').textContent = match?.[2] ? `${match[2]} min` : t.online;
            card.addEventListener('click', () => { service.value = option.value; service.dispatchEvent(new Event('change', { bubbles: true })); });
            serviceCards?.appendChild(card);
        });
    }

    function renderStaff() {
        staffCards?.replaceChildren();
        const options = Array.from(staff.options).filter((option) => option.value && option.value !== ANY);
        if (!options.length) return;
        const choices = [{ value: ANY, title: t.any, subtitle: lang === 'ar' ? 'نختار لك أول موظف وموعد متاح.' : 'We find the first available specialist and time.', wide: true }, ...options.map((option) => ({ value: option.value, title: option.textContent.trim(), subtitle: t.specialist }))];
        choices.forEach((choice) => {
            const card = document.createElement('button'); card.type = 'button'; card.className = 'vb-final-v2-choice' + (choice.wide ? ' vb-final-option-wide' : '') + (staff.value === choice.value ? ' is-selected' : '');
            card.innerHTML = '<strong></strong><span></span>'; card.querySelector('strong').textContent = choice.title; card.querySelector('span').textContent = choice.subtitle;
            card.addEventListener('click', () => { staff.value = choice.value; renderStaff(); clearError(); setStep(3); updateSummary(); date.focus(); });
            staffCards?.appendChild(card);
        });
    }

    async function loadServices() {
        setLoading(cfg.translations?.loadingServices || 'Loading services...');
        try {
            const response = await fetch('/api/booking/services', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || t.network);
            service.innerHTML = `<option value="">${cfg.translations?.selectService || (lang === 'ar' ? 'اختر الخدمة' : 'Select Service')}</option>`;
            (payload.data || []).forEach((item) => {
                const option = document.createElement('option'); option.value = item.id;
                const name = item.name_localized || (lang === 'ar' && item.name_ar ? item.name_ar : item.name);
                const duration = item.duration_minutes || item.duration;
                option.textContent = duration ? `${name} (${duration} min)` : name;
                service.appendChild(option);
            });
            renderServices();
            if (!payload.data?.length) showError(t.noServices);
        } catch (_) { showError(t.network); }
        finally { clearLoading(); syncButtons(); }
    }

    async function loadStaff() {
        setLoading(t.loadingStaff);
        try {
            const response = await fetch(`/api/booking/staff/by-service/${encodeURIComponent(service.value)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error();
            staff.innerHTML = `<option value="">${t.chooseStaff}</option>`;
            (payload.data || []).forEach((item) => { const option = document.createElement('option'); option.value = item.id; option.textContent = item.name; staff.appendChild(option); });
            if (!payload.data?.length) { showError(t.noStaff); setStep(2); return; }
            const any = document.createElement('option'); any.value = ANY; any.textContent = t.any; staff.appendChild(any);
            renderStaff(); setStep(2); updateSummary();
        } catch (_) { showError(t.network); }
        finally { clearLoading(); syncButtons(); }
    }

    async function getSlotsFor(staffId, staffName) {
        const params = new URLSearchParams({ date: date.value, staff_id: staffId, service_id: service.value, timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'Africa/Cairo' });
        const response = await fetch(`/api/booking/available-timeslots?${params}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const payload = await response.json();
        if (!response.ok || !payload.success) return [];
        return (payload.data || []).map((slot) => ({ ...slot, staff_id: staffId, staff_name: staffName }));
    }

    async function loadSlots() {
        if (!date.value || !staff.value || !service.value || !slotGrid) return;
        clearError(); setLoading(t.loadingSlots); slotGrid.replaceChildren();
        try {
            let slots = [];
            if (staff.value === ANY) {
                const candidates = Array.from(staff.options).filter((option) => option.value && option.value !== ANY).map((option) => ({ id: option.value, name: option.textContent.trim() }));
                const results = await Promise.all(candidates.map((item) => getSlotsFor(item.id, item.name).catch(() => [])));
                slots = results.flat().sort((a, b) => a.start_time.localeCompare(b.start_time) || a.staff_name.localeCompare(b.staff_name));
            } else slots = await getSlotsFor(staff.value, currentLabel(staff));

            if (!slots.length) { const empty = document.createElement('div'); empty.className = 'vb-final-v2-empty'; empty.textContent = t.noSlots; slotGrid.appendChild(empty); }
            else slots.forEach((slot) => {
                const button = document.createElement('button'); button.type = 'button'; button.className = 'vb-final-v2-slot';
                button.innerHTML = '<strong></strong><span></span>'; button.querySelector('strong').textContent = slot.label || slot.start_time; button.querySelector('span').textContent = staff.value === ANY ? slot.staff_name : t.specialist;
                button.addEventListener('click', () => { time.innerHTML = ''; const option = document.createElement('option'); option.value = slot.start_time; option.textContent = slot.label || slot.start_time; option.selected = true; time.appendChild(option); if (staff.value === ANY) staff.value = String(slot.staff_id); slotGrid.querySelectorAll('.vb-final-v2-slot').forEach((item) => item.classList.toggle('is-selected', item === button)); updateSummary(); syncButtons(); setStep(4); document.getElementById('name')?.focus(); });
                slotGrid.appendChild(button);
            });
            document.getElementById('timeSectionFinal')?.removeAttribute('hidden');
            syncButtons();
        } catch (_) { const empty = document.createElement('div'); empty.className = 'vb-final-v2-empty'; empty.textContent = t.network; slotGrid.appendChild(empty); }
        finally { clearLoading(); }
    }

    async function submitBooking() {
        clearError(); if (!form.reportValidity()) { syncButtons(); return; }
        submit.disabled = true; submit.textContent = t.booking;
        const data = new FormData(form);
        try {
            const response = await fetch(cfg.bookingApi || '/api/appointments', { method:'POST', headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, credentials:'same-origin', body:JSON.stringify({
                customer_name:data.get('name'), customer_email:data.get('email'), customer_phone:data.get('phone'), appointment_date:data.get('appointment_date'), appointment_time:data.get('appointment_time'), staff_id:data.get('staff_id'), service_id:data.get('service_id'), notes:data.get('notes') || null, timezone:Intl.DateTimeFormat().resolvedOptions().timeZone || 'Africa/Cairo',
            }) });
            const payload = await response.json();
            if (!response.ok || !payload.success) { let message=payload.message||t.network; if(payload.errors) message=Object.values(payload.errors).flat().join(' '); if(payload.reason) message += ` ${payload.reason}`; throw new Error(message); }
            const reference = payload.data?.appointment?.public_reference;
            if (!reference) throw new Error(lang === 'ar' ? 'تم الحجز ولكن لم يتم استلام رمز المتابعة.' : 'Booking succeeded but no tracking reference was returned.');
            sessionStorage.setItem('veloraBookingReference', reference);
            window.location.assign(`${cfg.queueStatusUrl || '/queue/status'}?ref=${encodeURIComponent(reference)}`);
        } catch (e) { showError(e.message || t.network); submit.disabled = false; submit.textContent = t.confirm; }
    }

    function bind() {
        service.addEventListener('change', () => { clearError(); loadStaff(); });
        staff.addEventListener('change', () => { clearError(); setStep(3); syncButtons(); });
        date.addEventListener('change', () => { clearError(); loadSlots(); });
        time.addEventListener('change', () => { syncButtons(); if (time.value) setStep(4); });
        form.addEventListener('input', (event) => { if (['name','email','phone','notes'].includes(event.target?.id)) syncButtons(); });
        form.addEventListener('submit', (event) => { event.preventDefault(); submitBooking(); });
        form.querySelectorAll('[data-back]').forEach((button) => button.addEventListener('click', () => setStep(Number(button.dataset.back))));
        document.getElementById('serviceContinue')?.addEventListener('click', () => { if (service.value) setStep(2); });
        document.getElementById('staffContinue')?.addEventListener('click', () => { if (staff.value) setStep(3); });
        document.getElementById('dateContinue')?.addEventListener('click', () => { if (date.value && time.value) setStep(4); });
    }

    setStep(1);
    updateSummary();
    bind();
    loadServices();
})();
