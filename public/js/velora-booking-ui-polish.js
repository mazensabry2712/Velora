(function () {
    'use strict';

    const form = document.getElementById('bookingForm');
    if (!form) return;

    const lang = (document.documentElement.lang || 'en').toLowerCase();
    const rtl = document.documentElement.dir === 'rtl' || lang === 'ar';
    const t = lang === 'ar' ? {
        back: 'رجوع', next: 'متابعة', review: 'مراجعة الحجز', service: 'الخدمة', staff: 'الموظف', date: 'التاريخ', time: 'الوقت',
        live: 'الحجز متاح الآن', summary: 'موعدك', summaryCopy: 'اختياراتك ستظل ظاهرة هنا أثناء الحجز.', tracking: 'لديك حجز بالفعل؟ تتبع موعدك',
    } : {
        back: 'Back', next: 'Continue', review: 'Review your appointment', service: 'Service', staff: 'Staff', date: 'Date', time: 'Time',
        live: 'Live availability', summary: 'Your appointment', summaryCopy: 'Your selections stay visible here as you book.', tracking: 'Already booked? Track your appointment',
    };

    const panels = {
        service: form.querySelector('.vb2-step[data-step="2"]'),
        staff: form.querySelector('.vb2-step[data-step="3"]'),
        date: form.querySelector('.vb2-step[data-step="4"]'),
        details: form.querySelector('.vb2-step[data-step="1"]'),
        notes: form.querySelector('.vb2-step[data-step="6"]'),
    };
    const service = document.getElementById('service_id');
    const staff = document.getElementById('staff_id');
    const date = document.getElementById('appointment_date');
    const time = document.getElementById('appointment_time');
    const submit = document.getElementById('submitBtn');

    if (!service || !staff || !date || !time || !submit || !panels.service || !panels.staff || !panels.date || !panels.details) return;

    document.body.classList.add('vb-final-v2');
    document.getElementById('successMessage')?.remove();
    document.querySelector('.vb2-card-head')?.setAttribute('hidden', 'hidden');

    function panelOrder() {
        return [panels.service, panels.staff, panels.date, panels.details].filter(Boolean);
    }

    function normalizePanels() {
        const notes = panels.notes?.querySelector('#notes')?.closest('.vb2-field');
        if (notes && !panels.details.contains(notes)) {
            panels.details.querySelector('.vb2-fields')?.appendChild(notes);
        }
        panels.notes?.remove();
        panelOrder().forEach((panel, index) => {
            panel.dataset.stepPanel = String(index + 1);
            form.appendChild(panel);
        });
        form.querySelector('.vb2-step-head')?.remove();
        panelOrder().forEach((panel) => panel.querySelector('.vb2-step-head')?.remove());
    }

    function progress() {
        if (document.querySelector('.vb-final-v2-progress')) return;
        const nav = document.createElement('nav');
        nav.className = 'vb-final-v2-progress';
        nav.setAttribute('aria-label', lang === 'ar' ? 'تقدم الحجز' : 'Booking progress');
        [[1,t.service],[2,t.staff],[3,lang === 'ar' ? 'التاريخ والوقت' : 'Date & Time'],[4,lang === 'ar' ? 'البيانات' : 'Details']].forEach(([n,label], i, all) => {
            const item = document.createElement('span');
            item.className = 'vb-final-v2-progress-item' + (n === 1 ? ' is-active' : '');
            item.dataset.step = String(n);
            item.innerHTML = `<b>${n}</b><em></em>`;
            item.querySelector('em').textContent = label;
            nav.appendChild(item);
            if (i < all.length - 1) { const line = document.createElement('i'); line.className = 'vb-final-v2-progress-line'; nav.appendChild(line); }
        });
        const card = document.querySelector('.vb2-card');
        card?.insertBefore(nav, form);
    }

    function setActive(n) {
        panelOrder().forEach((panel) => panel.classList.toggle('vb-final-v2-active', panel.dataset.stepPanel === String(n)));
        document.querySelectorAll('.vb-final-v2-progress-item').forEach((item) => {
            const x = Number(item.dataset.step); item.classList.toggle('is-active', x === n); item.classList.toggle('is-complete', x < n);
        });
    }

    function summary() {
        if (document.querySelector('.vb-final-v2-summary')) return;
        const box = document.createElement('aside');
        box.className = 'vb-final-v2-summary';
        box.innerHTML = '<div class="vb-final-v2-live"><span></span><em></em></div><h2></h2><p></p><dl><div><dt></dt><dd id="vbSumService">—</dd></div><div><dt></dt><dd id="vbSumStaff">—</dd></div><div><dt></dt><dd id="vbSumDate">—</dd></div><div><dt></dt><dd id="vbSumTime">—</dd></div></dl><a href="/queue/status"></a>';
        box.querySelector('.vb-final-v2-live em').textContent = t.live;
        box.querySelector('h2').textContent = t.summary;
        box.querySelector('p').textContent = t.summaryCopy;
        box.querySelectorAll('dt').forEach((el,i)=>el.textContent=[t.service,t.staff,t.date,t.time][i]);
        box.querySelector('a').textContent = t.tracking;
        document.querySelector('.vb2-card')?.appendChild(box);
    }

    function updateSummary() {
        summary();
        document.getElementById('vbSumService').textContent = service.value ? service.selectedOptions[0].textContent.trim() : '—';
        document.getElementById('vbSumStaff').textContent = staff.value === '__any__' ? (lang === 'ar' ? 'أي موظف متاح' : 'Any available specialist') : (staff.value ? staff.selectedOptions[0].textContent.trim() : '—');
        document.getElementById('vbSumDate').textContent = date.value || '—';
        document.getElementById('vbSumTime').textContent = time.value ? time.selectedOptions[0].textContent.trim() : '—';
        document.getElementById('reviewService')?.replaceChildren(document.createTextNode(document.getElementById('vbSumService').textContent));
        document.getElementById('reviewStaff')?.replaceChildren(document.createTextNode(document.getElementById('vbSumStaff').textContent));
        document.getElementById('reviewDate')?.replaceChildren(document.createTextNode(date.value || '—'));
        document.getElementById('reviewTime')?.replaceChildren(document.createTextNode(time.value ? time.selectedOptions[0].textContent.trim() : '—'));
    }

    function addReview() {
        if (panels.details.querySelector('.vb-final-v2-review')) return;
        const review = document.createElement('section');
        review.className = 'vb-final-v2-review';
        review.innerHTML = '<h3></h3><div><span></span><strong id="reviewService">—</strong></div><div><span></span><strong id="reviewStaff">—</strong></div><div><span></span><strong id="reviewDate">—</strong></div><div><span></span><strong id="reviewTime">—</strong></div>';
        review.querySelector('h3').textContent = t.review;
        review.querySelectorAll('div span').forEach((node,i)=>node.textContent=[t.service,t.staff,t.date,t.time][i]);
        panels.details.querySelector('.vb2-fields')?.insertAdjacentElement('afterend', review);
    }

    function actions() {
        const add = (panel, id, next, number) => {
            if (!panel || panel.querySelector('.vb-final-v2-actions')) return;
            const wrap = document.createElement('div'); wrap.className = 'vb-final-v2-actions';
            wrap.innerHTML = `${number > 1 ? `<button type="button" class="vb-final-v2-btn secondary" data-back>${t.back}</button>` : ''}<button type="button" class="vb-final-v2-btn primary" id="${id}" disabled>${t.next}<span> ${rtl ? '←' : '→'}</span></button>`;
            wrap.querySelector('[data-back]')?.addEventListener('click', () => setActive(number - 1));
            wrap.querySelector('.primary').addEventListener('click', next);
            panel.appendChild(wrap);
        };
        add(panels.service,'serviceContinue',()=>setActive(2),1);
        add(panels.staff,'staffContinue',()=>setActive(3),2);
        add(panels.date,'dateContinue',()=>setActive(4),3);
    }

    function sync() {
        document.getElementById('serviceContinue')?.toggleAttribute('disabled', !service.value);
        document.getElementById('staffContinue')?.toggleAttribute('disabled', !staff.value);
        document.getElementById('dateContinue')?.toggleAttribute('disabled', !(date.value && time.value));
        const ready = Boolean(time.value && document.getElementById('name')?.value && document.getElementById('email')?.value && document.getElementById('phone')?.value);
        submit.disabled = !ready;
        submit.textContent = t.confirm || (lang === 'ar' ? 'تأكيد الحجز' : 'Confirm appointment');
        updateSummary();
    }

    function wireCards() {
        const serviceCards = document.getElementById('serviceCards') || (() => { const x=document.createElement('div');x.id='serviceCards';x.className='vb-final-v2-grid';panels.service.querySelector('.vb-final-v2-panel-head')?.insertAdjacentElement('afterend',x);return x;})();
        const staffCards = document.getElementById('staffCards') || (() => { const x=document.createElement('div');x.id='staffCards';x.className='vb-final-v2-grid';panels.staff.querySelector('.vb-final-v2-panel-head')?.insertAdjacentElement('afterend',x);return x;})();
        const renderServices=()=>{const opts=Array.from(service.options).filter(o=>o.value);serviceCards.replaceChildren(...opts.map(o=>{const b=document.createElement('button');b.type='button';b.className='vb-final-v2-choice'+(o.selected?' is-selected':'');b.innerHTML='<strong></strong><span></span>';b.querySelector('strong').textContent=o.textContent.trim();b.querySelector('span').textContent=lang==='ar'?'متاح للحجز':'Available online';b.onclick=()=>{service.value=o.value;service.dispatchEvent(new Event('change',{bubbles:true}));};return b;}));};
        const renderStaff=()=>{const opts=Array.from(staff.options).filter(o=>o.value&&o.value!=='__any__');staffCards.replaceChildren();const all=[{value:'__any__',title:t.any,sub:lang==='ar'?'نختار لك أول موعد متاح.':'We find the first available specialist.'},...opts.map(o=>({value:o.value,title:o.textContent.trim(),sub:lang==='ar'?'متخصص متاح':'Available specialist'}))];all.forEach(c=>{const b=document.createElement('button');b.type='button';b.className='vb-final-v2-choice'+(staff.value===c.value?' is-selected':'');b.innerHTML='<strong></strong><span></span>';b.querySelector('strong').textContent=c.title;b.querySelector('span').textContent=c.sub;b.onclick=()=>{staff.value=c.value;renderStaff();setActive(3);sync();};staffCards.appendChild(b);});};
        const obs=new MutationObserver(()=>{renderServices();renderStaff();sync();});obs.observe(service,{childList:true});obs.observe(staff,{childList:true});renderServices();renderStaff();
    }

    async function loadStaff(){
        if(!service.value)return;setLoading(lang==='ar'?'جارٍ تحميل الموظفين...':'Loading specialists...');
        try{const r=await fetch(`/api/booking/staff/by-service/${encodeURIComponent(service.value)}`,{headers:{Accept:'application/json'},credentials:'same-origin'});const p=await r.json();if(!r.ok||!p.success)throw new Error();staff.innerHTML=`<option value="">${lang==='ar'?'اختر الموظف':'Select Staff Member'}</option>`;(p.data||[]).forEach(x=>{const o=document.createElement('option');o.value=x.id;o.textContent=x.name;staff.appendChild(o);});const any=document.createElement('option');any.value=ANY_STAFF;any.textContent=t.any;staff.appendChild(any);wireCards();setActive(2);setProgress('staff');sync();}catch(_){showError(lang==='ar'?'تعذر تحميل الموظفين.':'Unable to load specialists.');}finally{clearLoading();}
    }

    function setLoading(message){ if(window.loadingBanner){window.loadingBanner.textContent=message;window.loadingBanner.classList.remove('hidden');} else document.getElementById('loadingBanner')?.classList.remove('hidden'); }
    function clearLoading(){document.getElementById('loadingBanner')?.classList.add('hidden');}
    function showError(message){const e=document.getElementById('errorMessage'),tgt=document.getElementById('errorText');if(e&&tgt){tgt.textContent=message;e.classList.remove('hidden');}}

    function setProgress(key){const map={service:1,staff:2,date:3,details:4};document.querySelectorAll('.vb-final-v2-progress-item').forEach(x=>{const n=Number(x.dataset.step);x.classList.toggle('is-active',n===map[key]);x.classList.toggle('is-complete',n<map[key]);});}

    async function loadSlots(){
        if(!date.value||!staff.value||!service.value)return;const grid=document.getElementById('vbFinalSlots');if(!grid)return;setLoading(t.loading||'Checking available times...');
        try{let slots=[];if(staff.value===ANY_STAFF){const candidates=Array.from(staff.options).filter(o=>o.value&&o.value!==ANY_STAFF).map(o=>({id:o.value,name:o.textContent.trim()}));const results=await Promise.all(candidates.map(x=>fetchSlots(x.id,x.name).catch(()=>[])));slots=results.flat().sort((a,b)=>a.start_time.localeCompare(b.start_time)||a.staff_name.localeCompare(b.staff_name));}else slots=await fetchSlots(staff.value,currentChoice(staff));
            grid.replaceChildren();if(!slots.length){const empty=document.createElement('div');empty.className='vb-final-v2-empty';empty.textContent=t.noSlots||'No times are available.';grid.appendChild(empty);}else slots.forEach(slot=>{const b=document.createElement('button');b.type='button';b.className='vb-final-v2-slot';b.innerHTML='<strong></strong><span></span>';b.querySelector('strong').textContent=slot.label||slot.start_time;b.querySelector('span').textContent=staff.value===ANY_STAFF?slot.staff_name:(lang==='ar'?'متاح':'Available');b.onclick=()=>{time.value=slot.start_time;if(staff.value===ANY_STAFF&&slot.staff_id)staff.value=String(slot.staff_id);grid.querySelectorAll('.vb-final-v2-slot').forEach(x=>x.classList.toggle('is-selected',x===b));setActive(4);setProgress('details');sync();document.getElementById('name')?.focus();};grid.appendChild(b);});sync();}catch(_){grid.replaceChildren();const empty=document.createElement('div');empty.className='vb-final-v2-empty';empty.textContent=lang==='ar'?'تعذر تحميل المواعيد.':'Unable to load available times.';grid.appendChild(empty);}finally{clearLoading();}
    }

    async function fetchSlots(staffId, staffName){const params=new URLSearchParams({date:date.value,staff_id:staffId,service_id:service.value,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone||'Africa/Cairo'});const r=await fetch('/api/booking/available-timeslots?'+params.toString(),{headers:{Accept:'application/json'},credentials:'same-origin'});const p=await r.json();if(!r.ok||!p.success)return[];return(p.data||[]).map(x=>({...x,staff_id:staffId,staff_name:staffName}));}
    function currentChoice(s){return s.value?s.selectedOptions[0]?.textContent?.trim()||'—':'—';}

    function buildDateTimeUI(){
        if(document.getElementById('vbFinalSlots'))return;
        const inputBlock=date.closest('.vb2-field');inputBlock?.classList.add('vb-final-v2-date');
        const section=document.createElement('div');section.id='vbFinalSlots';section.className='vb-final-v2-slot-grid';
        const label=document.createElement('div');label.className='vb-final-v2-slot-heading';label.innerHTML='<div><strong></strong><span></span></div>';label.querySelector('strong').textContent=t.available;label.querySelector('span').textContent=lang==='ar'?'اختر الوقت المناسب لك.':'Choose a time that works for you.';
        step4.appendChild(label);step4.appendChild(section);time.parentElement?.classList.add('vb-final-fallback');
    }

    function bind(){
        service.addEventListener('change',(e)=>{e.stopImmediatePropagation();wireCards();hideError();sync();if(service.value)loadStaff();},true);
        staff.addEventListener('change',(e)=>{e.stopImmediatePropagation();sync();if(staff.value){setActive(3);setProgress('date');}},true);
        date.addEventListener('change',(e)=>{e.stopImmediatePropagation();hideError();setProgress('date');loadSlots();},true);
        time.addEventListener('change',(e)=>{e.stopImmediatePropagation();if(time.value)setActive(4);sync();},true);
        form.addEventListener('input',(e)=>{if(['name','email','phone','notes'].includes(e.target?.id))sync();},true);
        form.addEventListener('submit',async(e)=>{e.preventDefault();e.stopImmediatePropagation();await submitBooking();},true);
    }

    async function submitBooking(){
        hideError();if(!form.reportValidity()){sync();return;}submit.disabled=true;submit.textContent=t.booking|| (lang==='ar'?'جارٍ الحجز...':'Booking...');const data=new FormData(form);const body={customer_name:data.get('name'),customer_email:data.get('email'),customer_phone:data.get('phone'),appointment_date:data.get('appointment_date'),appointment_time:data.get('appointment_time'),staff_id:data.get('staff_id'),service_id:data.get('service_id'),notes:data.get('notes')||null,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone||'Africa/Cairo'};
        try{const r=await fetch('/api/appointments',{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},credentials:'same-origin',body:JSON.stringify(body)});const p=await r.json();if(!r.ok||!p.success){let msg=p.message||'Unable to complete the booking.';if(p.errors)msg=Object.values(p.errors).flat().join(' ');if(p.reason)msg+=' '+p.reason;throw new Error(msg);}const ref=p.data?.appointment?.public_reference;if(!ref)throw new Error(lang==='ar'?'تم الحجز لكن لم يتم إنشاء رمز المتابعة.':'Booking succeeded but no tracking reference was returned.');sessionStorage.setItem('veloraBookingReference',ref);window.location.assign('/queue/status?ref='+encodeURIComponent(ref));}catch(err){showError(err.message||'Unable to complete the booking.');submit.disabled=false;sync();}
    }

    function hideError(){document.getElementById('errorMessage')?.classList.add('hidden');}

    function init(){
        normalizeLogo();normalizePanels();progress();summary();
        [step2,step3,step4,step1].forEach(p=>p?.querySelectorAll('.vb2-step-head').forEach(x=>x.remove()));
        const headMap=[[step2,1,t.chooseService,t.serviceHelp],[step3,2,t.chooseStaff,t.staffHelp],[step4,3,t.chooseDate,t.dateHelp],[step1,4,t.detailsTitle,t.detailsHelp]];
        headMap.forEach(([panel,n,title,help])=>{const h=document.createElement('div');h.className='vb-final-v2-panel-head';h.innerHTML='<span></span><div><h2></h2><p></p></div>';h.querySelector('span').textContent=n;h.querySelector('h2').textContent=title;h.querySelector('p').textContent=help;panel.prepend(h);});
        addReview();actions();buildDateTimeUI();wireCards();bind();setActive(1);setProgress('service');sync();
    }

    init();
})();
