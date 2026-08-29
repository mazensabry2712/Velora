(function () {
    'use strict';

    const STORAGE_KEY = 'bookingDarkMode';
    const ANY_STAFF = '__any__';

    const root = document.body;
    const form = document.getElementById('bookingForm');
    if (!form) return;

    root.classList.add('vb-final');

    const service = document.getElementById('service_id');
    const staff = document.getElementById('staff_id');
    const date = document.getElementById('appointment_date');
    const time = document.getElementById('appointment_time');
    const submit = document.getElementById('submitBtn');
    const step1 = form.querySelector('.vb2-step[data-step="1"]');
    const step2 = form.querySelector('.vb2-step[data-step="2"]');
    const step3 = form.querySelector('.vb2-step[data-step="3"]');
    const step4 = form.querySelector('.vb2-step[data-step="4"]');
    const step6 = form.querySelector('.vb2-step[data-step="6"]');
    const submitWrap = form.querySelector('.vb2-submit-wrap');

    if (!service || !staff || !date || !time || !submit) return;

    const lang = (document.documentElement.lang || 'en').toLowerCase();
    const rtl = document.documentElement.dir === 'rtl' || lang === 'ar';
    const labels = {
        en: { service: 'Service', staff: 'Staff', date: 'Date & Time', details: 'Details', back: 'Back', continue: 'Continue', any: 'Any available specialist', available: 'Available times', chooseService: 'Choose a service', chooseStaff: 'Choose a specialist', chooseDate: 'Choose date & time', detailsTitle: 'Your details', serviceHelp: 'Start with what you would like to book.', staffHelp: 'Pick someone specific or let us find the first available option.', dateHelp: 'We only show appointments that are actually available.', detailsHelp: 'Tell us who the appointment is for.', review: 'Review your appointment', loading: 'Checking available times...', noSlots: 'No times are available for the selected date.', noServices: 'No online-bookable services are available right now.', noStaff: 'No staff members are currently available for this service.', booking: 'Booking...', confirm: 'Confirm appointment', servicePlaceholder: 'Select Service Type', staffPlaceholder: 'Select Staff Member' },
        ar: { service: 'الخدمة', staff: 'الموظف', date: 'التاريخ والوقت', details: 'البيانات', back: 'رجوع', continue: 'متابعة', any: 'أي موظف متاح', available: 'المواعيد المتاحة', chooseService: 'اختر الخدمة', chooseStaff: 'اختر المتخصص', chooseDate: 'اختر التاريخ والوقت', detailsTitle: 'بيانات العميل', serviceHelp: 'ابدأ باختيار الخدمة التي تريد حجزها.', staffHelp: 'اختر موظفًا محددًا أو اتركنا نختار أول موعد متاح.', dateHelp: 'نحن نعرض فقط المواعيد المتاحة فعليًا.', detailsHelp: 'أخبرنا لمن الحجز.', review: 'مراجعة الحجز', loading: 'جارٍ التحقق من المواعيد المتاحة...', noSlots: 'لا توجد مواعيد متاحة في هذا اليوم.', noServices: 'لا توجد خدمات متاحة للحجز الإلكتروني حاليًا.', noStaff: 'لا يوجد موظفون متاحون لهذه الخدمة حاليًا.', booking: 'جارٍ الحجز...', confirm: 'تأكيد الحجز', servicePlaceholder: 'اختر الخدمة', staffPlaceholder: 'اختر الموظف' },
    };
    const t = labels[lang] || labels.en;

    const applyDark = (isDark) => {
        document.documentElement.classList.toggle('dark', isDark);
        const icon = document.getElementById('dark-mode-icon');
        if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    };

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

    function setLoading(message) {
        if (!loading) return;
        loading.textContent = message;
        loading.classList.remove('hidden');
    }

    function clearLoading() { loading?.classList.add('hidden'); }
    function showError(message) { if (error && errorText) { errorText.textContent = message; error.classList.remove('hidden'); } }
    function hideError() { error?.classList.add('hidden'); if (errorText) errorText.textContent = ''; }

    const loading = document.getElementById('loadingBanner');
    const error = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');

    function setStepNumbers() {
        if (step2) step2.dataset.stepPanel = '1';
        if (step3) step3.dataset.stepPanel = '2';
        if (step4) step4.dataset.stepPanel = '3';
        if (step1) step1.dataset.stepPanel = '4';
    }

    function setActivePanel(stepNumber) {
        [step1, step2, step3, step4].forEach((panel) => {
            if (!panel) return;
            panel.classList.toggle('vb-final-active', panel.dataset.stepPanel === String(stepNumber));
        });
    }

    function injectProgress() {
        if (document.querySelector('.vb-final-progress')) return;
        const progress = document.createElement('nav');
        progress.className = 'vb-final-progress';
        progress.setAttribute('aria-label', lang === 'ar' ? 'تقدم الحجز' : 'Booking progress');
        [[1,t.service],[2,t.staff],[3,t.date],[4,t.details]].forEach(([number,label], index, arr) => {
            const item = document.createElement('div');
            item.className = 'vb-final-progress-item' + (number === 1 ? ' is-active' : '');
            item.dataset.progress = String(number);
            item.innerHTML = `<span>${number}</span><strong></strong>`;
            item.querySelector('strong').textContent = label;
            progress.appendChild(item);
            if (index < arr.length - 1) { const line = document.createElement('div'); line.className = 'vb-final-progress-line'; progress.appendChild(line); }
        });
        const card = document.querySelector('.vb2-card');
        const head = card?.querySelector('.vb2-card-head');
        if (card && head) head.insertAdjacentElement('afterend', progress);
    }

    function setProgress(step) {
        const number = { service:1, staff:2, date:3, details:4 }[step] || 1;
        document.querySelectorAll('.vb-final-progress-item').forEach((item) => {
            const n = Number(item.dataset.progress);
            item.classList.toggle('is-active', n === number);
            item.classList.toggle('is-complete', n < number);
        });
    }

    function injectSummary() {
        if (document.querySelector('.vb-final-summary')) return document.querySelector('.vb-final-summary');
        const aside = document.createElement('aside');
        aside.className = 'vb-final-summary';
        aside.innerHTML = '<div class="vb-final-summary-eyebrow"><span class="vb-final-summary-dot"></span><span></span></div><h2></h2><p class="vb-final-summary-copy"></p><div class="vb-final-summary-row"><span></span><strong id="vbSummaryService">—</strong></div><div class="vb-final-summary-row"><span></span><strong id="vbSummaryStaff">—</strong></div><div class="vb-final-summary-row"><span></span><strong id="vbSummaryDate">—</strong></div><div class="vb-final-summary-row"><span></span><strong id="vbSummaryTime">—</strong></div><a class="vb-final-summary-link" href="/queue/status"><span></span><span aria-hidden="true">→</span></a>';
        aside.querySelectorAll('.vb-final-summary-row span').forEach((node, i) => node.textContent = (lang === 'ar' ? ['الخدمة','الموظف','التاريخ','الوقت'] : ['Service','Staff','Date','Time'])[i]);
        aside.querySelector('.vb-final-summary-eyebrow span:last-child').textContent = lang === 'ar' ? 'الحجز متاح الآن' : 'Live availability';
        aside.querySelector('h2').textContent = lang === 'ar' ? 'موعدك' : 'Your appointment';
        aside.querySelector('.vb-final-summary-copy').textContent = lang === 'ar' ? 'ستظهر اختياراتك هنا أثناء إتمام الحجز.' : 'Your selections will stay visible here as you book.';
        aside.querySelector('.vb-final-summary-link span').textContent = lang === 'ar' ? 'لديك حجز بالفعل؟ تتبع موعدك' : 'Already booked? Track your appointment';
        document.querySelector('.vb2-card')?.appendChild(aside);
        return aside;
    }

    function currentChoice(select) { return select?.value ? select.selectedOptions[0]?.textContent?.trim() || '—' : '—'; }
    function updateSummary() {
        const summary = injectSummary();
        summary.querySelector('#vbSummaryService').textContent = currentChoice(service);
        summary.querySelector('#vbSummaryStaff').textContent = staff.value === ANY_STAFF ? t.any : currentChoice(staff);
        summary.querySelector('#vbSummaryDate').textContent = date.value || '—';
        summary.querySelector('#vbSummaryTime').textContent = currentChoice(time);
        document.getElementById('reviewService')?.replaceChildren(document.createTextNode(currentChoice(service)));
        document.getElementById('reviewStaff')?.replaceChildren(document.createTextNode(staff.value === ANY_STAFF ? t.any : currentChoice(staff)));
        document.getElementById('reviewDate')?.replaceChildren(document.createTextNode(date.value || '—'));
        document.getElementById('reviewTime')?.replaceChildren(document.createTextNode(currentChoice(time)));
    }

    function injectPanelStructure() {
        [step1, step2, step3, step4].forEach((node) => node?.querySelector('.vb2-step-head')?.remove());
        if (step6) step6.remove();
        if (step2 && step3 && step4 && step1) [step2, step3, step4, step1].forEach((node) => form.appendChild(node));
        if (submitWrap) form.appendChild(submitWrap);
        setStepNumbers();
    }

    function ensureOptionContainers() {
        let serviceCards = document.getElementById('serviceCards');
        if (!serviceCards) {
            serviceCards = document.createElement('div');
            serviceCards.id = 'serviceCards'; serviceCards.className = 'vb-final-option-grid';
            step2?.querySelector('.vb-final-panel-head')?.insertAdjacentElement('afterend', serviceCards);
        }
        let staffCards = document.getElementById('staffCards');
        if (!staffCards) {
            staffCards = document.createElement('div'); staffCards.id = 'staffCards'; staffCards.className = 'vb-final-option-grid';
            step3?.querySelector('.vb-final-panel-head')?.insertAdjacentElement('afterend', staffCards);
        }
        const serviceFallback = service.parentElement; const staffFallback = staff.parentElement;
        serviceFallback?.classList.add('vb-final-fallback'); staffFallback?.classList.add('vb-final-fallback');

        const renderServices = () => {
            const options = Array.from(service.options).filter((o) => o.value);
            serviceCards.replaceChildren();
            if (!options.length) { if (service.options[0]?.textContent) { const empty=document.createElement('div'); empty.className='vb-final-empty'; empty.textContent=service.options[0].textContent.trim(); serviceCards.appendChild(empty); } return; }
            options.forEach((option) => {
                const button = document.createElement('button'); button.type='button'; button.className='vb-final-option';
                const raw=option.textContent.trim(); const match=raw.match(/^(.*?)(?:\s*\((\d+)\s*min\))?$/i);
                button.innerHTML='<strong></strong><span></span>'; button.querySelector('strong').textContent=match?.[1]?.trim()||raw; button.querySelector('span').textContent=match?.[2]?`${match[2]} min`: (lang==='ar'?'متاح للحجز':'Available online');
                button.classList.toggle('is-selected', option.selected);
                button.addEventListener('click', () => { service.value=option.value; service.dispatchEvent(new Event('change',{bubbles:true})); renderServices(); });
                serviceCards.appendChild(button);
            });
        };

        const renderStaff = () => {
            const options = Array.from(staff.options).filter((o) => o.value && o.value !== ANY_STAFF);
            staffCards.replaceChildren();
            if (!options.length) return;
            const choices = [{ value:ANY_STAFF, title:t.any, sub:lang==='ar'?'نختار لك أول موظف وموعد متاح.':'We will find the first available specialist and time.', wide:true }, ...options.map((o)=>({value:o.value,title:o.textContent.trim(),sub:lang==='ar'?'متخصص متاح':'Available specialist'}))];
            choices.forEach((choice) => {
                const button=document.createElement('button'); button.type='button'; button.className='vb-final-option'+(choice.wide?' vb-final-option-wide':'');
                button.innerHTML='<strong></strong><span></span>'; button.querySelector('strong').textContent=choice.title; button.querySelector('span').textContent=choice.sub;
                button.classList.toggle('is-selected', staff.value===choice.value);
                button.addEventListener('click',()=>{staff.value=choice.value; renderStaff(); revealDateStep();});
                staffCards.appendChild(button);
            });
        };
        const observer = new MutationObserver(()=>{ renderServices(); renderStaff(); });
        observer.observe(service,{childList:true}); observer.observe(staff,{childList:true});
        renderServices(); renderStaff();
        window.__veloraRenderBookingChoices = () => { renderServices(); renderStaff(); };
    }

    function addActions() {
        const makeActions=(panel,id,next,number)=>{
            if(!panel || panel.querySelector('.vb-final-actions')) return;
            const actions=document.createElement('div'); actions.className='vb-final-actions';
            actions.innerHTML=`${number>1?`<button type="button" class="vb-final-btn secondary" data-back-step>${t.back}</button>`:''}<button type="button" class="vb-final-btn primary" id="${id}" disabled>${t.continue}<span aria-hidden="true"> ${rtl?'←':'→'}</span></button>`;
            actions.querySelector('[data-back-step]')?.addEventListener('click',()=>setActivePanel(number-1));
            actions.querySelector('.primary').addEventListener('click',next);
            panel.appendChild(actions);
        };
        makeActions(step2,'serviceContinue',selectStaffStep,1);
        makeActions(step3,'staffContinue',revealDateStep,2);
        makeActions(step4,'dateContinue',revealDetailsStep,3);
        if(step1 && !step1.querySelector('.vb-final-review')){
            const review=document.createElement('div'); review.className='vb-final-review'; review.innerHTML='<div class="vb-final-review-title"></div><div class="vb-final-review-row"><span></span><strong id="reviewService">—</strong></div><div class="vb-final-review-row"><span></span><strong id="reviewStaff">—</strong></div><div class="vb-final-review-row"><span></span><strong id="reviewDate">—</strong></div><div class="vb-final-review-row"><span></span><strong id="reviewTime">—</strong></div>';
            review.querySelector('.vb-final-review-title').textContent=t.review;
            review.querySelectorAll('.vb-final-review-row span').forEach((node,i)=>node.textContent=(lang==='ar'?['الخدمة','الموظف','التاريخ','الوقت']:['Service','Staff','Date','Time'])[i]);
            step1.querySelector('.vb2-fields')?.insertAdjacentElement('afterend',review);
        }
    }

    function syncButtons(){
        const serviceReady=Boolean(service.value); const staffReady=Boolean(staff.value); const timeReady=Boolean(time.value);
        document.getElementById('serviceContinue')?.toggleAttribute('disabled',!serviceReady);
        document.getElementById('staffContinue')?.toggleAttribute('disabled',!staffReady);
        document.getElementById('dateContinue')?.toggleAttribute('disabled',!(date.value&&timeReady));
        submit.disabled=!(timeReady && document.getElementById('name')?.value && document.getElementById('email')?.value && document.getElementById('phone')?.value);
        submit.textContent=t.confirm+' '+(rtl?'←':'→');
        updateSummary();
    }

    function selectStaffStep(){ if(!service.value)return; setActivePanel(2); setProgress('staff'); syncButtons(); }
    function revealDateStep(){ if(!staff.value)return; setActivePanel(3); setProgress('date'); syncButtons(); date.focus(); }
    function revealDetailsStep(){ if(!date.value||!time.value)return; setActivePanel(4); setProgress('details'); syncButtons(); document.getElementById('name')?.focus(); }

    async function fetchSlots(staffId, staffName){
        const params=new URLSearchParams({date:date.value,staff_id:staffId,service_id:service.value,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone||'Africa/Cairo'});
        const response=await fetch('/api/booking/available-timeslots?'+params.toString(),{headers:{Accept:'application/json'},credentials:'same-origin'});
        const payload=await response.json(); if(!response.ok||!payload.success)return[];
        return (payload.data||[]).map(slot=>({...slot,staff_id:staffId,staff_name:staffName}));
    }

    async function loadSlots(){
        if(!date.value||!staff.value||!service.value)return;
        const grid=document.getElementById('slotGrid'); if(!grid)return;
        setLoading(staff.value===ANY_STAFF?(lang==='ar'?'جارٍ البحث في كل الموظفين...':'Checking all specialists...'):t.loading);
        try{
            let slots=[];
            if(staff.value===ANY_STAFF){
                const candidates=Array.from(staff.options).filter(o=>o.value&&o.value!==ANY_STAFF).map(o=>({id:o.value,name:o.textContent.trim()}));
                const results=await Promise.all(candidates.map(item=>fetchSlots(item.id,item.name).catch(()=>[])));
                slots=results.flat().sort((a,b)=>a.start_time.localeCompare(b.start_time)||a.staff_name.localeCompare(b.staff_name));
            }else slots=await fetchSlots(staff.value,currentChoice(staff));
            grid.replaceChildren();
            if(!slots.length){const empty=document.createElement('div');empty.className='vb-final-empty';empty.textContent=t.noSlots;grid.appendChild(empty);}else{
                slots.forEach(slot=>{
                    const button=document.createElement('button');button.type='button';button.className='vb-final-slot';button.innerHTML='<strong></strong><span></span>';button.querySelector('strong').textContent=slot.label||slot.start_time;button.querySelector('span').textContent=staff.value===ANY_STAFF?slot.staff_name:(lang==='ar'?'متاح':'Available');
                    button.addEventListener('click',()=>{time.value=slot.start_time;if(staff.value===ANY_STAFF&&slot.staff_id)staff.value=String(slot.staff_id);grid.querySelectorAll('.vb-final-slot').forEach(item=>item.classList.toggle('is-selected',item===button));revealDetailsStep();});
                    grid.appendChild(button);
                });
            }
            syncButtons();
        }catch(_){grid.replaceChildren();const empty=document.createElement('div');empty.className='vb-final-empty';empty.textContent=lang==='ar'?'تعذر تحميل المواعيد المتاحة.':'Unable to load available times.';grid.appendChild(empty);}finally{clearLoading();}
    }

    function buildSlotUI(){
        if(!step4||document.getElementById('timeSectionFinal'))return;
        const block=document.createElement('div');block.id='timeSectionFinal';block.innerHTML='<div class="vb-final-slot-heading"><div><strong></strong><span></span></div></div><div id="slotGrid" class="vb-final-slot-grid" role="listbox"></div>';
        block.querySelector('strong').textContent=t.available;block.querySelector('span').textContent=lang==='ar'?'اختر الوقت المناسب لك.':'Pick the time that works best for you.';
        time.insertAdjacentElement('afterend',block);time.parentElement?.classList.add('vb-final-fallback');
    }

    async function submitBooking(){
        hideError();
        if(!form.reportValidity()){syncButtons();return;}
        submit.disabled=true;submit.textContent=t.booking;
        const data=new FormData(form); const body={customer_name:data.get('name'),customer_email:data.get('email'),customer_phone:data.get('phone'),appointment_date:data.get('appointment_date'),appointment_time:data.get('appointment_time'),staff_id:data.get('staff_id'),service_id:data.get('service_id'),notes:data.get('notes')||null,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone||'Africa/Cairo'};
        try{
            const response=await fetch('/api/appointments',{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},credentials:'same-origin',body:JSON.stringify(body)});
            const payload=await response.json();
            if(!response.ok||!payload.success){let message=payload.message||(lang==='ar'?'حدث خطأ أثناء الحجز.':'An error occurred while booking.');if(payload.errors){const messages=Object.values(payload.errors).flat();if(messages.length)message=messages.join(' ');}if(payload.reason)message+=` ${payload.reason}`;throw new Error(message);}
            const reference=payload.data?.appointment?.public_reference;
            if(!reference)throw new Error(lang==='ar'?'تم الحجز ولكن لم يتم استلام رمز المتابعة.':'The booking was created but no tracking reference was returned.');
            sessionStorage.setItem('veloraBookingReference',reference);
            window.location.assign('/queue/status?ref='+encodeURIComponent(reference));
        }catch(err){showError(err.message||'Unable to complete the booking.');submit.disabled=false;submit.textContent=t.confirm+' '+(rtl?'←':'→');}
    }

    function bindCaptureEvents(){
        service.addEventListener('change',(event)=>{event.stopImmediatePropagation();hideError();syncButtons();selectStaffStep();},true);
        staff.addEventListener('change',(event)=>{event.stopImmediatePropagation();hideError();syncButtons();revealDateStep();},true);
        date.addEventListener('change',(event)=>{event.stopImmediatePropagation();hideError();syncButtons();loadSlots();},true);
        time.addEventListener('change',(event)=>{event.stopImmediatePropagation();if(time.value)revealDetailsStep();syncButtons();},true);
        form.addEventListener('input',(event)=>{if(['name','email','phone','notes'].includes(event.target?.id))syncButtons();},true);
        form.addEventListener('submit',async(event)=>{event.preventDefault();event.stopImmediatePropagation();await submitBooking();},true);
    }

    function init(){
        const saved=localStorage.getItem(STORAGE_KEY);applyDark(saved==='true'||(saved===null&&window.matchMedia('(prefers-color-scheme: dark)').matches));normalizeLogo();
        document.getElementById('successMessage')?.remove();
        injectPanelStructure();injectProgress();ensureOptionContainers();injectSummary();buildSlotUI();addActions();bindCaptureEvents();
        setActivePanel(1);setProgress('service');syncButtons();
        window.addEventListener('load',()=>{window.__veloraRenderBookingChoices?.();syncButtons();});
    }

    init();
})();
