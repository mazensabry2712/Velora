(function () {
    'use strict';

    const STORAGE_KEY = 'bookingDarkMode';
    const ANY_STAFF = '__any__';
    const form = document.getElementById('bookingForm');
    if (!form) return;

    document.body.classList.add('vb-final');

    const service = document.getElementById('service_id');
    const staff = document.getElementById('staff_id');
    const date = document.getElementById('appointment_date');
    const time = document.getElementById('appointment_time');
    const submit = document.getElementById('submitBtn');
    const loading = document.getElementById('loadingBanner');
    const error = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const step1 = form.querySelector('.vb2-step[data-step="1"]');
    const step2 = form.querySelector('.vb2-step[data-step="2"]');
    const step3 = form.querySelector('.vb2-step[data-step="3"]');
    const step4 = form.querySelector('.vb2-step[data-step="4"]');
    const step6 = form.querySelector('.vb2-step[data-step="6"]');
    const submitWrap = form.querySelector('.vb2-submit-wrap');

    if (!service || !staff || !date || !time || !submit || !step1 || !step2 || !step3 || !step4) return;

    const lang = (document.documentElement.lang || 'en').toLowerCase();
    const rtl = document.documentElement.dir === 'rtl' || lang === 'ar';
    const t = {
        en: {
            service: 'Service', staff: 'Staff', date: 'Date & Time', details: 'Details',
            back: 'Back', next: 'Continue', any: 'Any available specialist', available: 'Available times',
            chooseService: 'Choose a service', chooseStaff: 'Choose a specialist', chooseDate: 'Choose date & time',
            detailsTitle: 'Your details', serviceHelp: 'Start with what you would like to book.',
            staffHelp: 'Pick someone specific or let us find the first available option.',
            dateHelp: 'We only show appointments that are actually available.', detailsHelp: 'Tell us who the appointment is for.',
            review: 'Review your appointment', loading: 'Checking available times...', noSlots: 'No times are available for the selected date.',
            noStaff: 'No staff members are currently available for this service.', booking: 'Booking...', confirm: 'Confirm appointment',
        },
        ar: {
            service: 'الخدمة', staff: 'الموظف', date: 'التاريخ والوقت', details: 'البيانات',
            back: 'رجوع', next: 'متابعة', any: 'أي موظف متاح', available: 'المواعيد المتاحة',
            chooseService: 'اختر الخدمة', chooseStaff: 'اختر المتخصص', chooseDate: 'اختر التاريخ والوقت',
            detailsTitle: 'بيانات العميل', serviceHelp: 'ابدأ باختيار الخدمة التي تريد حجزها.',
            staffHelp: 'اختر موظفًا محددًا أو اتركنا نختار أول موعد متاح.',
            dateHelp: 'نحن نعرض فقط المواعيد المتاحة فعليًا.', detailsHelp: 'أخبرنا لمن الحجز.',
            review: 'مراجعة الحجز', loading: 'جارٍ التحقق من المواعيد المتاحة...', noSlots: 'لا توجد مواعيد متاحة في هذا اليوم.',
            noStaff: 'لا يوجد موظفون متاحون لهذه الخدمة حاليًا.', booking: 'جارٍ الحجز...', confirm: 'تأكيد الحجز',
        },
    }[lang] || {
        service: 'Service', staff: 'Staff', date: 'Date & Time', details: 'Details', back: 'Back', next: 'Continue',
        any: 'Any available specialist', available: 'Available times', chooseService: 'Choose a service',
        chooseStaff: 'Choose a specialist', chooseDate: 'Choose date & time', detailsTitle: 'Your details',
        serviceHelp: 'Start with what you would like to book.', staffHelp: 'Pick someone specific or let us find the first available option.',
        dateHelp: 'We only show appointments that are actually available.', detailsHelp: 'Tell us who the appointment is for.', review: 'Review your appointment',
        loading: 'Checking available times...', noSlots: 'No times are available for the selected date.', noStaff: 'No staff members are currently available for this service.', booking: 'Booking...', confirm: 'Confirm appointment',
    };

    function applyDark(isDark) {
        document.documentElement.classList.toggle('dark', isDark);
        const icon = document.getElementById('dark-mode-icon');
        if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    }

    window.toggleDarkMode = function () {
        const isDark = !document.documentElement.classList.contains('dark');
        localStorage.setItem(STORAGE_KEY, String(isDark));
        applyDark(isDark);
    };

    function normalizeLogo() {
        document.querySelectorAll('.vb2-logo').forEach((image) => {
            image.addEventListener('error', () => {
                image.src = '/logo-bais.png';
                image.removeAttribute('onerror');
            }, { once: true });
        });
    }

    function setLoading(message) { if (loading) { loading.textContent = message; loading.classList.remove('hidden'); } }
    function clearLoading() { loading?.classList.add('hidden'); }
    function showError(message) { if (error && errorText) { errorText.textContent = message; error.classList.remove('hidden'); } }
    function hideError() { error?.classList.add('hidden'); if (errorText) errorText.textContent = ''; }

    function setActivePanel(number) {
        [step1, step2, step3, step4].forEach((panel) => panel.classList.toggle('vb-final-active', panel.dataset.stepPanel === String(number)));
    }

    function setProgress(key) {
        const number = { service: 1, staff: 2, date: 3, details: 4 }[key] || 1;
        document.querySelectorAll('.vb-final-progress-item').forEach((item) => {
            const n = Number(item.dataset.progress);
            item.classList.toggle('is-active', n === number);
            item.classList.toggle('is-complete', n < number);
        });
    }

    function currentChoice(select) { return select.value ? (select.selectedOptions[0]?.textContent?.trim() || '—') : '—'; }

    function injectProgress() {
        if (document.querySelector('.vb-final-progress')) return;
        const progress = document.createElement('nav');
        progress.className = 'vb-final-progress';
        progress.setAttribute('aria-label', lang === 'ar' ? 'تقدم الحجز' : 'Booking progress');
        [[1,t.service],[2,t.staff],[3,t.date],[4,t.details]].forEach(([n,label], i, all) => {
            const item = document.createElement('div'); item.className = 'vb-final-progress-item' + (n === 1 ? ' is-active' : ''); item.dataset.progress = n;
            item.innerHTML = `<span>${n}</span><strong></strong>`; item.querySelector('strong').textContent = label; progress.appendChild(item);
            if (i < all.length - 1) { const line = document.createElement('div'); line.className = 'vb-final-progress-line'; progress.appendChild(line); }
        });
        document.querySelector('.vb2-card')?.querySelector('.vb2-card-head')?.insertAdjacentElement('afterend', progress);
    }

    function injectSummary() {
        if (document.querySelector('.vb-final-summary')) return;
        const aside = document.createElement('aside'); aside.className = 'vb-final-summary';
        aside.innerHTML = '<div class="vb-final-summary-eyebrow"><span class="vb-final-summary-dot"></span><span class="vb-summary-live"></span></div><h2></h2><p class="vb-final-summary-copy"></p><div class="vb-final-summary-row"><span></span><strong id="vbSummaryService">—</strong></div><div class="vb-final-summary-row"><span></span><strong id="vbSummaryStaff">—</strong></div><div class="vb-final-summary-row"><span></span><strong id="vbSummaryDate">—</strong></div><div class="vb-final-summary-row"><span></span><strong id="vbSummaryTime">—</strong></div><a class="vb-final-summary-link" href="/queue/status"><span></span><span aria-hidden="true">→</span></a>';
        aside.querySelector('.vb-summary-live').textContent = lang === 'ar' ? 'الحجز متاح الآن' : 'Live availability';
        aside.querySelector('h2').textContent = lang === 'ar' ? 'موعدك' : 'Your appointment';
        aside.querySelector('.vb-final-summary-copy').textContent = lang === 'ar' ? 'اختياراتك ستظل ظاهرة هنا أثناء الحجز.' : 'Your selections stay visible here as you book.';
        aside.querySelectorAll('.vb-final-summary-row span').forEach((n,i)=>n.textContent=(lang==='ar'?['الخدمة','الموظف','التاريخ','الوقت']:['Service','Staff','Date','Time'])[i]);
        aside.querySelector('.vb-final-summary-link span').textContent = lang === 'ar' ? 'لديك حجز بالفعل؟ تتبع موعدك' : 'Already booked? Track your appointment';
        document.querySelector('.vb2-card')?.appendChild(aside);
    }

    function updateSummary() {
        injectSummary();
        document.getElementById('vbSummaryService').textContent = currentChoice(service);
        document.getElementById('vbSummaryStaff').textContent = staff.value === ANY_STAFF ? t.any : currentChoice(staff);
        document.getElementById('vbSummaryDate').textContent = date.value || '—';
        document.getElementById('vbSummaryTime').textContent = currentChoice(time);
        document.getElementById('reviewService')?.replaceChildren(document.createTextNode(currentChoice(service)));
        document.getElementById('reviewStaff')?.replaceChildren(document.createTextNode(staff.value === ANY_STAFF ? t.any : currentChoice(staff)));
        document.getElementById('reviewDate')?.replaceChildren(document.createTextNode(date.value || '—'));
        document.getElementById('reviewTime')?.replaceChildren(document.createTextNode(currentChoice(time)));
    }

    function addPanelHead(panel, number, title, help) {
        if (panel.querySelector('.vb-final-panel-head')) return;
        const head = document.createElement('div'); head.className='vb-final-panel-head'; head.innerHTML='<span class="vb-final-step-number"></span><div><h2></h2><p></p></div>';
        head.querySelector('.vb-final-step-number').textContent=number; head.querySelector('h2').textContent=title; head.querySelector('p').textContent=help; panel.prepend(head);
    }

    function moveAndNormalizePanels() {
        step6?.querySelector('.vb2-field')?.let?.(() => {});
        const notes = step6?.querySelector('#notes')?.closest('.vb2-field');
        if (notes && step1 && !step1.contains(notes)) step1.querySelector('.vb2-fields')?.insertAdjacentElement('afterend', notes);
        step6?.remove();

        addPanelHead(step2, 1, t.chooseService, t.serviceHelp);
        addPanelHead(step3, 2, t.chooseStaff, t.staffHelp);
        addPanelHead(step4, 3, t.chooseDate, t.dateHelp);
        addPanelHead(step1, 4, t.detailsTitle, t.detailsHelp);

        [step2, step3, step4, step1].forEach((node) => form.appendChild(node));
        if (submitWrap) {
            form.appendChild(submitWrap);
            const oldNote = submitWrap.querySelector('.vb2-submit-note'); oldNote?.remove();
        }
        step2.dataset.stepPanel='1'; step3.dataset.stepPanel='2'; step4.dataset.stepPanel='3'; step1.dataset.stepPanel='4';
        step1.querySelector('.vb2-step-head')?.remove(); step2.querySelector('.vb2-step-head')?.remove(); step3.querySelector('.vb2-step-head')?.remove(); step4.querySelector('.vb2-step-head')?.remove();
        submit.classList.remove('hidden');
    }

    function ensureOptionContainers() {
        let serviceCards = document.getElementById('serviceCards');
        if (!serviceCards) { serviceCards=document.createElement('div'); serviceCards.id='serviceCards'; serviceCards.className='vb-final-option-grid'; step2.querySelector('.vb-final-panel-head').insertAdjacentElement('afterend',serviceCards); }
        let staffCards = document.getElementById('staffCards');
        if (!staffCards) { staffCards=document.createElement('div'); staffCards.id='staffCards'; staffCards.className='vb-final-option-grid'; step3.querySelector('.vb-final-panel-head').insertAdjacentElement('afterend',staffCards); }

        const renderServices=()=>{
            const options=Array.from(service.options).filter(o=>o.value); serviceCards.replaceChildren();
            if(!options.length){const empty=document.createElement('div');empty.className='vb-final-empty';empty.textContent=service.options[0]?.textContent?.trim()||'No services available';serviceCards.appendChild(empty);return;}
            options.forEach(option=>{
                const button=document.createElement('button');button.type='button';button.className='vb-final-option';button.innerHTML='<strong></strong><span></span>';
                const title=option.textContent.trim();const m=title.match(/^(.*?)(?:\s*\((\d+)\s*min\))?$/i);button.querySelector('strong').textContent=m?.[1]?.trim()||title;button.querySelector('span').textContent=m?.[2]?`${m[2]} min`:(lang==='ar'?'متاح للحجز':'Available online');button.classList.toggle('is-selected',option.selected);
                button.addEventListener('click',()=>{service.value=option.value;service.dispatchEvent(new Event('change',{bubbles:true}));renderServices();});serviceCards.appendChild(button);
            });
        };
        const renderStaff=()=>{
            const options=Array.from(staff.options).filter(o=>o.value&&o.value!==ANY_STAFF);staffCards.replaceChildren();if(!options.length)return;
            const choices=[{value:ANY_STAFF,title:t.any,sub:lang==='ar'?'نختار لك أول موظف وموعد متاح.':'We will find the first available specialist and time.',wide:true},...options.map(o=>({value:o.value,title:o.textContent.trim(),sub:lang==='ar'?'متخصص متاح':'Available specialist'}))];
            choices.forEach(choice=>{const b=document.createElement('button');b.type='button';b.className='vb-final-option'+(choice.wide?' vb-final-option-wide':'');b.innerHTML='<strong></strong><span></span>';b.querySelector('strong').textContent=choice.title;b.querySelector('span').textContent=choice.sub;b.classList.toggle('is-selected',staff.value===choice.value);b.addEventListener('click',()=>{staff.value=choice.value;renderStaff();revealDateStep();});staffCards.appendChild(b);});
        };
        const observer=new MutationObserver(()=>{renderServices();renderStaff();});observer.observe(service,{childList:true});observer.observe(staff,{childList:true});renderServices();renderStaff();window.__veloraRenderBookingChoices=()=>{renderServices();renderStaff();};
    }

    function addActions(){
        const add=(panel,id,next,number)=>{if(!panel||panel.querySelector('.vb-final-actions'))return;const wrap=document.createElement('div');wrap.className='vb-final-actions';wrap.innerHTML=`${number>1?`<button type="button" class="vb-final-btn secondary" data-back>${t.back}</button>`:''}<button type="button" class="vb-final-btn primary" id="${id}" disabled>${t.next}<span aria-hidden="true"> ${rtl?'←':'→'}</span></button>`;wrap.querySelector('[data-back]')?.addEventListener('click',()=>setActivePanel(number-1));wrap.querySelector('.primary').addEventListener('click',next);panel.appendChild(wrap);};
        add(step2,'serviceContinue',selectStaffStep,1);add(step3,'staffContinue',revealDateStep,2);add(step4,'dateContinue',revealDetailsStep,3);
        if(!step1.querySelector('.vb-final-review')){const review=document.createElement('div');review.className='vb-final-review';review.innerHTML='<div class="vb-final-review-title"></div><div class="vb-final-review-row"><span></span><strong id="reviewService">—</strong></div><div class="vb-final-review-row"><span></span><strong id="reviewStaff">—</strong></div><div class="vb-final-review-row"><span></span><strong id="reviewDate">—</strong></div><div class="vb-final-review-row"><span></span><strong id="reviewTime">—</strong></div>';review.querySelector('.vb-final-review-title').textContent=t.review;review.querySelectorAll('.vb-final-review-row span').forEach((n,i)=>n.textContent=(lang==='ar'?['الخدمة','الموظف','التاريخ','الوقت']:['Service','Staff','Date','Time'])[i]);step1.querySelector('.vb2-fields')?.insertAdjacentElement('afterend',review);}
    }

    function syncButtons(){
        document.getElementById('serviceContinue')?.toggleAttribute('disabled',!service.value);
        document.getElementById('staffContinue')?.toggleAttribute('disabled',!staff.value);
        document.getElementById('dateContinue')?.toggleAttribute('disabled',!(date.value&&time.value));
        submit.disabled=!(time.value&&document.getElementById('name')?.value&&document.getElementById('email')?.value&&document.getElementById('phone')?.value);
        updateSummary();
    }

    function selectStaffStep(){if(!service.value)return;setActivePanel(2);setProgress('staff');syncButtons();}
    function revealDateStep(){if(!staff.value)return;setActivePanel(3);setProgress('date');syncButtons();date.focus();}
    function revealDetailsStep(){if(!date.value||!time.value)return;setActivePanel(4);setProgress('details');syncButtons();document.getElementById('name')?.focus();}

    async function fetchSlots(staffId, staffName){
        const params=new URLSearchParams({date:date.value,staff_id:staffId,service_id:service.value,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone||'Africa/Cairo'});
        const response=await fetch('/api/booking/available-timeslots?'+params.toString(),{headers:{Accept:'application/json'},credentials:'same-origin'});const payload=await response.json();if(!response.ok||!payload.success)return[];return(payload.data||[]).map(slot=>({...slot,staff_id:staffId,staff_name:staffName}));
    }

    async function loadSlots(){
        if(!date.value||!staff.value||!service.value)return;const grid=document.getElementById('slotGrid');if(!grid)return;setLoading(t.loading);
        try{let slots=[];if(staff.value===ANY_STAFF){const candidates=Array.from(staff.options).filter(o=>o.value&&o.value!==ANY_STAFF).map(o=>({id:o.value,name:o.textContent.trim()}));const results=await Promise.all(candidates.map(x=>fetchSlots(x.id,x.name).catch(()=>[])));slots=results.flat().sort((a,b)=>a.start_time.localeCompare(b.start_time)||a.staff_name.localeCompare(b.staff_name));}else slots=await fetchSlots(staff.value,currentChoice(staff));
            grid.replaceChildren();
            if(!slots.length){const empty=document.createElement('div');empty.className='vb-final-empty';empty.textContent=t.noSlots;grid.appendChild(empty);}else slots.forEach(slot=>{const b=document.createElement('button');b.type='button';b.className='vb-final-slot';b.innerHTML='<strong></strong><span></span>';b.querySelector('strong').textContent=slot.label||slot.start_time;b.querySelector('span').textContent=staff.value===ANY_STAFF?slot.staff_name:(lang==='ar'?'متاح':'Available');b.addEventListener('click',()=>{time.value=slot.start_time;if(staff.value===ANY_STAFF&&slot.staff_id)staff.value=String(slot.staff_id);grid.querySelectorAll('.vb-final-slot').forEach(x=>x.classList.toggle('is-selected',x===b));revealDetailsStep();});grid.appendChild(b);});
            syncButtons();
        }catch(_){grid.replaceChildren();const empty=document.createElement('div');empty.className='vb-final-empty';empty.textContent=lang==='ar'?'تعذر تحميل المواعيد المتاحة.':'Unable to load available times.';grid.appendChild(empty);}finally{clearLoading();}
    }

    function buildSlotUI(){
        if(document.getElementById('timeSectionFinal'))return;const block=document.createElement('div');block.id='timeSectionFinal';block.innerHTML='<div class="vb-final-slot-heading"><div><strong></strong><span></span></div></div><div id="slotGrid" class="vb-final-slot-grid" role="listbox"></div>';block.querySelector('strong').textContent=t.available;block.querySelector('span').textContent=lang==='ar'?'اختر الوقت المناسب لك.':'Pick the time that works best for you.';time.insertAdjacentElement('afterend',block);time.parentElement?.classList.add('vb-final-fallback');
    }

    async function submitBooking(){
        hideError();if(!form.reportValidity()){syncButtons();return;}submit.disabled=true;submit.textContent=t.booking;
        const data=new FormData(form);const body={customer_name:data.get('name'),customer_email:data.get('email'),customer_phone:data.get('phone'),appointment_date:data.get('appointment_date'),appointment_time:data.get('appointment_time'),staff_id:data.get('staff_id'),service_id:data.get('service_id'),notes:data.get('notes')||null,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone||'Africa/Cairo'};
        try{const response=await fetch('/api/appointments',{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},credentials:'same-origin',body:JSON.stringify(body)});const payload=await response.json();if(!response.ok||!payload.success){let message=payload.message||(lang==='ar'?'حدث خطأ أثناء الحجز.':'An error occurred while booking.');if(payload.errors){const messages=Object.values(payload.errors).flat();if(messages.length)message=messages.join(' ');}if(payload.reason)message+=` ${payload.reason}`;throw new Error(message);}const reference=payload.data?.appointment?.public_reference;if(!reference)throw new Error(lang==='ar'?'تم الحجز ولكن لم يتم استلام رمز المتابعة.':'Booking succeeded but no tracking reference was returned.');sessionStorage.setItem('veloraBookingReference',reference);window.location.assign('/queue/status?ref='+encodeURIComponent(reference));}catch(err){showError(err.message||'Unable to complete the booking.');submit.disabled=false;submit.textContent=t.confirm+' '+(rtl?'←':'→');}
    }

    function bindEvents(){
        service.addEventListener('change',(e)=>{e.stopImmediatePropagation();hideError();ensureOptionContainers();syncButtons();selectStaffStep();loadStaff();},true);
        staff.addEventListener('change',(e)=>{e.stopImmediatePropagation();hideError();ensureOptionContainers();syncButtons();revealDateStep();},true);
        date.addEventListener('change',(e)=>{e.stopImmediatePropagation();hideError();syncButtons();loadSlots();},true);
        time.addEventListener('change',(e)=>{e.stopImmediatePropagation();syncButtons();if(time.value)revealDetailsStep();},true);
        form.addEventListener('input',(e)=>{if(['name','email','phone','notes'].includes(e.target?.id))syncButtons();},true);
        form.addEventListener('submit',async(e)=>{e.preventDefault();e.stopImmediatePropagation();await submitBooking();},true);
    }

    async function loadStaff(){
        if(!service.value)return;setLoading(lang==='ar'?'جارٍ تحميل الموظفين...':'Loading specialists...');staff.innerHTML=`<option value="">${t.staffPlaceholder|| (lang==='ar'?'اختر الموظف':'Select Staff Member')}</option>`;
        try{const response=await fetch(`/api/booking/staff/by-service/${encodeURIComponent(service.value)}`,{headers:{Accept:'application/json'},credentials:'same-origin'});const payload=await response.json();if(!response.ok||!payload.success)throw new Error();if(!payload.data.length){showError(t.noStaff);setActivePanel(2);return;}payload.data.forEach(item=>{const option=document.createElement('option');option.value=item.id;option.textContent=item.name;staff.appendChild(option);});const any=document.createElement('option');any.value=ANY_STAFF;any.textContent=t.any;staff.appendChild(any);window.__veloraRenderBookingChoices?.();setActivePanel(2);setProgress('staff');}catch(_){showError(lang==='ar'?'تعذر تحميل الموظفين.':'Unable to load specialists.');}finally{clearLoading();syncButtons();}
    }

    function init(){
        const saved=localStorage.getItem(STORAGE_KEY);applyDark(saved==='true'||(saved===null&&window.matchMedia('(prefers-color-scheme: dark)').matches));normalizeLogo();
        document.getElementById('successMessage')?.remove();
        moveAndNormalizePanels();injectProgress();injectSummary();ensureOptionContainers();buildSlotUI();addActions();bindEvents();
        setActivePanel(1);setProgress('service');syncButtons();window.addEventListener('load',()=>{window.__veloraRenderBookingChoices?.();syncButtons();});
    }

    init();
})();
