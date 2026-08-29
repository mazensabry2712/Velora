(function () {
  'use strict';

  const form = document.getElementById('bookingForm');
  if (!form) return;

  const lang = (document.documentElement.lang || 'en').toLowerCase();
  const rtl = document.documentElement.dir === 'rtl';
  const ANY = '__any__';
  const text = lang === 'ar' ? {
    loading: 'جارٍ التحميل...', services: 'جارٍ تحميل الخدمات...', staff: 'جارٍ تحميل المتخصصين...', slots: 'جارٍ البحث عن المواعيد المتاحة...',
    noServices: 'لا توجد خدمات متاحة للحجز الإلكتروني حاليًا.', noStaff: 'لا يوجد متخصصون متاحون لهذه الخدمة حاليًا.', noSlots: 'لا توجد مواعيد متاحة في هذا اليوم.',
    any: 'أي متخصص متاح', available: 'متاح للحجز', minutes: 'دقيقة', continue: 'متابعة', back: 'رجوع', confirm: 'تأكيد الحجز', booking: 'جارٍ تأكيد الحجز...',
    network: 'تعذر تحميل البيانات. حاول مرة أخرى.', bookingError: 'تعذر إتمام الحجز. حاول مرة أخرى.', guest: 'زائر', selectDate: 'اختر يومًا لعرض المواعيد.', track: 'لديك حجز بالفعل؟ تتبع موعدك',
  } : {
    loading: 'Loading...', services: 'Loading services...', staff: 'Loading specialists...', slots: 'Finding available times...',
    noServices: 'No online-bookable services are available right now.', noStaff: 'No specialists are currently available for this service.', noSlots: 'No times are available for this day.',
    any: 'Any available specialist', available: 'Available online', minutes: 'min', continue: 'Continue', back: 'Back', confirm: 'Confirm appointment', booking: 'Confirming appointment...',
    network: 'Unable to load the information. Please try again.', bookingError: 'Unable to complete the booking. Please try again.', guest: 'Guest', selectDate: 'Choose a day to see available times.', track: 'Already booked? Track your appointment',
  };

  const els = {
    service: document.getElementById('service_id'), staff: document.getElementById('staff_id'), date: document.getElementById('appointment_date'),
    time: document.getElementById('appointment_time'), serviceCards: document.getElementById('serviceCards'), staffCards: document.getElementById('staffCards'),
    dateChoices: document.getElementById('dateChoices'), timeOptions: document.getElementById('timeOptions'), submit: document.getElementById('submitBtn'),
    loading: document.getElementById('loadingBanner'), error: document.getElementById('errorMessage'), errorText: document.getElementById('errorText'),
    summaryService: document.getElementById('summaryService'), summaryStaff: document.getElementById('summaryStaff'), summaryDate: document.getElementById('summaryDate'), summaryTime: document.getElementById('summaryTime'),
    reviewService: document.getElementById('reviewService'), reviewStaff: document.getElementById('reviewStaff'), reviewDate: document.getElementById('reviewDate'), reviewTime: document.getElementById('reviewTime'),
  };
  const steps = [...document.querySelectorAll('.booking-step')];
  const progress = [...document.querySelectorAll('.booking-progress-item')];
  const dateStep = document.getElementById('bookingStepDate');
  const detailsStep = document.getElementById('bookingStepDetails');
  if (!els.service || !els.staff || !els.date || !els.time || !els.submit || steps.length !== 4) return;

  function setLoading(message) { els.loading.textContent = message; els.loading.classList.remove('hidden'); }
  function clearLoading() { els.loading.classList.add('hidden'); }
  function error(message) { els.errorText.textContent = message; els.error.classList.remove('hidden'); }
  function clearError() { els.error.classList.add('hidden'); els.errorText.textContent = ''; }

  function setStep(number) {
    steps.forEach((step) => step.classList.toggle('active', Number(step.dataset.step) === number));
    progress.forEach((item) => { const n = Number(item.dataset.step); item.classList.toggle('active', n === number); item.classList.toggle('done', n < number); });
    window.scrollTo({ top: Math.max(0, form.getBoundingClientRect().top + window.scrollY - 96), behavior: 'smooth' });
  }

  function formatDate(date) {
    return new Intl.DateTimeFormat(lang === 'ar' ? 'ar-EG' : 'en-US', { weekday: 'short', month: 'short', day: 'numeric' }).format(date);
  }

  function updateSummary() {
    const values = {
      service: els.service.value ? els.service.selectedOptions[0]?.textContent.trim() : '—',
      staff: els.staff.value === ANY ? text.any : (els.staff.value ? els.staff.selectedOptions[0]?.textContent.trim() : '—'),
      date: els.date.value ? formatDate(new Date(`${els.date.value}T00:00:00`)) : '—',
      time: els.time.value ? els.time.selectedOptions[0]?.textContent.trim() : '—',
    };
    [els.summaryService, els.reviewService].forEach((e) => e && (e.textContent = values.service));
    [els.summaryStaff, els.reviewStaff].forEach((e) => e && (e.textContent = values.staff));
    [els.summaryDate, els.reviewDate].forEach((e) => e && (e.textContent = values.date));
    [els.summaryTime, els.reviewTime].forEach((e) => e && (e.textContent = values.time));
  }

  function sync() {
    const ready = Boolean(els.date.value && els.time.value && document.getElementById('name')?.value.trim() && document.getElementById('phone')?.value.trim() && document.getElementById('email')?.value.trim());
    els.submit.disabled = !ready;
    updateSummary();
  }

  function renderServices() {
    els.serviceCards.replaceChildren();
    const options = [...els.service.options].filter((option) => option.value);
    if (!options.length) { const empty = document.createElement('div'); empty.className = 'booking-empty'; empty.textContent = text.noServices; els.serviceCards.appendChild(empty); return; }
    options.forEach((option) => {
      const button = document.createElement('button'); button.type = 'button'; button.className = 'booking-choice'; button.setAttribute('aria-pressed', String(option.selected));
      const raw = option.textContent.trim(); const match = raw.match(/^(.*?)(?:\s*\((\d+)\s*min\))?$/i);
      button.innerHTML = '<strong></strong><span></span>'; button.querySelector('strong').textContent = match?.[1]?.trim() || raw; button.querySelector('span').textContent = match?.[2] ? `${match[2]} ${text.minutes}` : text.available;
      button.addEventListener('click', () => { els.service.value = option.value; els.service.dispatchEvent(new Event('change', { bubbles: true })); });
      els.serviceCards.appendChild(button);
    });
  }

  function renderStaff() {
    els.staffCards.replaceChildren();
    const options = [...els.staff.options].filter((option) => option.value && option.value !== ANY);
    if (!options.length) return;
    const choices = [{ value: ANY, title: text.any, sub: lang === 'ar' ? 'نختار لك أول موعد متاح.' : 'We find the earliest suitable time.' }, ...options.map((o) => ({ value: o.value, title: o.textContent.trim(), sub: lang === 'ar' ? 'متخصص متاح' : 'Available specialist' }))];
    choices.forEach((choice) => {
      const button = document.createElement('button'); button.type = 'button'; button.className = 'booking-choice' + (choice.value === ANY ? ' booking-choice-wide' : '') + (els.staff.value === choice.value ? ' selected' : '');
      button.innerHTML = '<strong></strong><span></span>'; button.querySelector('strong').textContent = choice.title; button.querySelector('span').textContent = choice.sub;
      button.addEventListener('click', () => { els.staff.value = choice.value; renderStaff(); clearError(); buildDates(); setStep(3); sync(); });
      els.staffCards.appendChild(button);
    });
  }

  async function loadServices() {
    setLoading(text.services);
    try {
      const r = await fetch('/api/booking/services', { headers: { Accept: 'application/json' }, credentials: 'same-origin' }); const p = await r.json();
      if (!r.ok || !p.success) throw new Error();
      els.service.innerHTML = `<option value="">${lang === 'ar' ? 'اختر الخدمة' : 'Choose a service'}</option>`;
      (p.data || []).forEach((item) => { const o = document.createElement('option'); o.value = item.id; o.textContent = item.duration_minutes ? `${item.name_localized || item.name} (${item.duration_minutes} ${text.minutes})` : (item.name_localized || item.name); els.service.appendChild(o); });
      renderServices();
      if (!p.data?.length) error(text.noServices);
    } catch (_) { error(text.network); }
    finally { clearLoading(); sync(); }
  }

  async function loadStaff() {
    clearError(); setLoading(text.staff);
    try {
      const r = await fetch(`/api/booking/staff/by-service/${encodeURIComponent(els.service.value)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }); const p = await r.json();
      if (!r.ok || !p.success) throw new Error();
      els.staff.innerHTML = `<option value="">${lang === 'ar' ? 'اختر المتخصص' : 'Choose a specialist'}</option>`;
      (p.data || []).forEach((item) => { const o = document.createElement('option'); o.value = item.id; o.textContent = item.name; els.staff.appendChild(o); });
      if (!p.data?.length) { error(text.noStaff); return; }
      const any = document.createElement('option'); any.value = ANY; any.textContent = text.any; els.staff.appendChild(any); renderStaff(); setStep(2); sync();
    } catch (_) { error(text.network); }
    finally { clearLoading(); }
  }

  function buildDates() {
    els.dateChoices.replaceChildren();
    const now = new Date(); now.setHours(0,0,0,0);
    for (let i = 0; i < 7; i++) {
      const day = new Date(now); day.setDate(now.getDate() + i); const value = day.toISOString().slice(0,10);
      const button = document.createElement('button'); button.type = 'button'; button.className = 'booking-date'; button.innerHTML = '<small></small><strong></strong><span></span>';
      button.querySelector('small').textContent = new Intl.DateTimeFormat(lang === 'ar' ? 'ar-EG' : 'en-US', { weekday: 'short' }).format(day);
      button.querySelector('strong').textContent = String(day.getDate());
      button.querySelector('span').textContent = new Intl.DateTimeFormat(lang === 'ar' ? 'ar-EG' : 'en-US', { month: 'short' }).format(day);
      button.addEventListener('click', () => { els.dateChoices.querySelectorAll('.booking-date').forEach((x) => x.classList.remove('selected')); button.classList.add('selected'); els.date.value = value; els.time.value = ''; loadSlots(); });
      els.dateChoices.appendChild(button);
    }
  }

  async function getSlots(staffId, staffName) {
    const params = new URLSearchParams({ date: els.date.value, staff_id: staffId, service_id: els.service.value, timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'Africa/Cairo' });
    const r = await fetch(`/api/booking/available-timeslots?${params}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }); const p = await r.json();
    if (!r.ok || !p.success) return []; return (p.data || []).map((slot) => ({ ...slot, staff_id: staffId, staff_name: staffName }));
  }

  async function loadSlots() {
    clearError(); setLoading(text.slots); els.timeOptions.replaceChildren();
    try {
      let slots = [];
      if (els.staff.value === ANY) {
        const candidates = [...els.staff.options].filter((o) => o.value && o.value !== ANY).map((o) => ({ id: o.value, name: o.textContent.trim() }));
        const results = await Promise.all(candidates.map((c) => getSlots(c.id, c.name).catch(() => []))); slots = results.flat().sort((a,b) => a.start_time.localeCompare(b.start_time));
      } else slots = await getSlots(els.staff.value, els.staff.selectedOptions[0]?.textContent.trim());
      if (!slots.length) { const empty = document.createElement('div'); empty.className = 'booking-empty'; empty.textContent = text.noSlots; els.timeOptions.appendChild(empty); return; }
      slots.forEach((slot) => {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'booking-slot'; button.innerHTML = '<strong></strong><span></span>'; button.querySelector('strong').textContent = slot.label || slot.start_time; button.querySelector('span').textContent = els.staff.value === ANY ? slot.staff_name : text.available;
        button.addEventListener('click', () => { els.time.innerHTML = ''; const o = document.createElement('option'); o.value = slot.start_time; o.textContent = slot.label || slot.start_time; o.selected = true; els.time.appendChild(o); if (els.staff.value === ANY) { els.staff.value = String(slot.staff_id); renderStaff(); } els.timeOptions.querySelectorAll('.booking-slot').forEach((x) => x.classList.remove('selected')); button.classList.add('selected'); sync(); });
        els.timeOptions.appendChild(button);
      });
      sync();
    } catch (_) { error(text.network); }
    finally { clearLoading(); }
  }

  function validateDetails() {
    const fields = ['name','phone','email'].map((id) => document.getElementById(id));
    if (fields.some((field) => !field?.value.trim())) return false;
    if (!els.date.value || !els.time.value || !els.service.value || !els.staff.value || els.staff.value === ANY) return false;
    return fields[2].checkValidity();
  }

  async function submitBooking(event) {
    event.preventDefault(); clearError();
    if (!validateDetails()) { error(lang === 'ar' ? 'أكمل بيانات الحجز قبل التأكيد.' : 'Please complete your booking details before confirming.'); return; }
    els.submit.disabled = true; els.submit.textContent = text.booking;
    const data = new FormData(form);
    try {
      const r = await fetch('/api/appointments', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, credentials: 'same-origin', body: JSON.stringify({
        customer_name: data.get('name'), customer_email: data.get('email'), customer_phone: data.get('phone'), appointment_date: data.get('appointment_date'), appointment_time: data.get('appointment_time'), staff_id: data.get('staff_id'), service_id: data.get('service_id'), notes: data.get('notes') || null, timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'Africa/Cairo'
      }) });
      const p = await r.json();
      if (!r.ok || !p.success) { let message = p.message || text.bookingError; if (p.errors) message = Object.values(p.errors).flat().join(' '); throw new Error(message); }
      const ref = p.data?.appointment?.public_reference;
      if (!ref) throw new Error(text.bookingError);
      window.location.href = `/queue/status?ref=${encodeURIComponent(ref)}`;
    } catch (e) { error(e.message || text.bookingError); els.submit.disabled = false; els.submit.textContent = text.confirm; }
  }

  document.querySelectorAll('[data-next]').forEach((button) => button.addEventListener('click', () => setStep(Number(button.dataset.next))));
  document.querySelectorAll('[data-back]').forEach((button) => button.addEventListener('click', () => setStep(Number(button.dataset.back))));
  els.service.addEventListener('change', loadStaff);
  els.submit.addEventListener('click', submitBooking);
  form.querySelectorAll('input, textarea').forEach((field) => field.addEventListener('input', sync));

  const savedTheme = localStorage.getItem('bookingTheme');
  function applyTheme(dark) { document.documentElement.classList.toggle('dark', dark); const icon = document.getElementById('darkModeIcon'); if (icon) icon.textContent = dark ? '☀️' : '🌙'; }
  window.toggleDarkMode = function () { const next = !document.documentElement.classList.contains('dark'); localStorage.setItem('bookingTheme', String(next)); applyTheme(next); };
  window.changeLanguage = function (value) { window.location.href = '/change-language/' + encodeURIComponent(value); };
  applyTheme(savedTheme === 'true' || (savedTheme === null && window.matchMedia('(prefers-color-scheme: dark)').matches));

  buildDates(); updateSummary(); sync(); loadServices();
})();
