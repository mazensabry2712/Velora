(function () {
  'use strict';

  const form = document.getElementById('bookingForm');
  if (!form) return;
  const lang = (document.documentElement.lang || 'en').toLowerCase();
  const rtl = document.documentElement.dir === 'rtl';
  const ANY = '__any__';
  const text = lang === 'ar' ? {
    services:'جارٍ تحميل الخدمات...', staff:'جارٍ تحميل المتخصصين...', slots:'جارٍ البحث عن المواعيد المتاحة...',
    noServices:'لا توجد خدمات متاحة للحجز الإلكتروني حاليًا.', noStaff:'لا يوجد متخصصون متاحون لهذه الخدمة حاليًا.', noSlots:'لا توجد مواعيد متاحة في هذا اليوم.',
    any:'أي متخصص متاح', available:'متاح للحجز', minutes:'دقيقة', back:'رجوع', confirm:'تأكيد الحجز', booking:'جارٍ تأكيد الحجز...',
    network:'تعذر تحميل البيانات. حاول مرة أخرى.', bookingError:'تعذر إتمام الحجز. حاول مرة أخرى.', track:'لديك حجز بالفعل؟ تتبع موعدك'
  } : {
    services:'Loading services...', staff:'Loading specialists...', slots:'Finding available times...',
    noServices:'No online-bookable services are available right now.', noStaff:'No specialists are currently available for this service.', noSlots:'No times are available for this day.',
    any:'Any available specialist', available:'Available online', minutes:'min', back:'Back', confirm:'Confirm appointment', booking:'Confirming appointment...',
    network:'Unable to load the information. Please try again.', bookingError:'Unable to complete the booking. Please try again.', track:'Already booked? Track your appointment'
  };
  const e = {
    service:document.getElementById('service_id'), staff:document.getElementById('staff_id'), date:document.getElementById('appointment_date'), time:document.getElementById('appointment_time'),
    serviceCards:document.getElementById('serviceCards'), staffCards:document.getElementById('staffCards'), dateChoices:document.getElementById('dateChoices'), timeOptions:document.getElementById('timeOptions'),
    submit:document.getElementById('submitBtn'), loading:document.getElementById('loadingBanner'), error:document.getElementById('errorMessage'), errorText:document.getElementById('errorText'),
    sumService:document.getElementById('summaryService'), sumStaff:document.getElementById('summaryStaff'), sumDate:document.getElementById('summaryDate'), sumTime:document.getElementById('summaryTime'),
    reviewService:document.getElementById('reviewService'), reviewStaff:document.getElementById('reviewStaff'), reviewDate:document.getElementById('reviewDate'), reviewTime:document.getElementById('reviewTime')
  };
  const steps = [...document.querySelectorAll('.booking-step')];
  const progress = [...document.querySelectorAll('.booking-progress-item')];
  if (!e.service || !e.staff || !e.date || !e.time || !e.submit || steps.length !== 4) return;

  const showLoading = (m) => { e.loading.textContent = m; e.loading.classList.remove('hidden'); };
  const hideLoading = () => e.loading.classList.add('hidden');
  const showError = (m) => { e.errorText.textContent = m; e.error.classList.remove('hidden'); };
  const clearError = () => { e.error.classList.add('hidden'); e.errorText.textContent = ''; };
  const dateLabel = (value) => value ? new Intl.DateTimeFormat(lang === 'ar' ? 'ar-EG' : 'en-US', { weekday:'short', month:'short', day:'numeric' }).format(new Date(`${value}T00:00:00`)) : '—';
  const setStep = (n) => { steps.forEach((s) => s.classList.toggle('active', Number(s.dataset.step) === n)); progress.forEach((p) => { const x = Number(p.dataset.step); p.classList.toggle('active', x === n); p.classList.toggle('done', x < n); }); };
  const sync = () => {
    const vals = { service:e.service.value ? e.service.selectedOptions[0]?.textContent.trim() : '—', staff:e.staff.value === ANY ? text.any : (e.staff.value ? e.staff.selectedOptions[0]?.textContent.trim() : '—'), date:dateLabel(e.date.value), time:e.time.value ? e.time.selectedOptions[0]?.textContent.trim() : '—' };
    [e.sumService,e.reviewService].forEach((x) => x && (x.textContent = vals.service));
    [e.sumStaff,e.reviewStaff].forEach((x) => x && (x.textContent = vals.staff));
    [e.sumDate,e.reviewDate].forEach((x) => x && (x.textContent = vals.date));
    [e.sumTime,e.reviewTime].forEach((x) => x && (x.textContent = vals.time));
    const detailsReady = Boolean(e.date.value && e.time.value && document.getElementById('name')?.value.trim() && document.getElementById('phone')?.value.trim() && document.getElementById('email')?.value.trim());
    e.submit.disabled = !detailsReady;
  };

  function renderServices() {
    e.serviceCards.replaceChildren();
    const options = [...e.service.options].filter((o) => o.value);
    if (!options.length) { const x=document.createElement('div'); x.className='booking-empty'; x.textContent=text.noServices; e.serviceCards.appendChild(x); return; }
    options.forEach((o) => { const b=document.createElement('button'); b.type='button'; b.className='booking-choice'; b.setAttribute('aria-pressed',String(o.selected)); const raw=o.textContent.trim(); b.innerHTML='<strong></strong><span></span>'; b.querySelector('strong').textContent=raw.replace(/\s*\(\d+\s*min\)$/i,''); b.querySelector('span').textContent=(raw.match(/\((\d+)\s*min\)/i)?.[1] ? `${raw.match(/\((\d+)\s*min\)/i)[1]} ${text.minutes}` : text.available); b.addEventListener('click',()=>{e.service.value=o.value;e.service.dispatchEvent(new Event('change',{bubbles:true}));});e.serviceCards.appendChild(b); });
  }

  function renderStaff() {
    e.staffCards.replaceChildren();
    const options=[...e.staff.options].filter((o)=>o.value&&o.value!==ANY);
    if(!options.length)return;
    const choices=[{value:ANY,title:text.any,sub:lang==='ar'?'نختار لك أول موعد متاح.':'We find the earliest suitable time.',wide:true},...options.map((o)=>({value:o.value,title:o.textContent.trim(),sub:lang==='ar'?'متخصص متاح':'Available specialist'}))];
    choices.forEach((c)=>{const b=document.createElement('button');b.type='button';b.className='booking-choice'+(c.wide?' booking-choice-wide':'')+(e.staff.value===c.value?' selected':'');b.innerHTML='<strong></strong><span></span>';b.querySelector('strong').textContent=c.title;b.querySelector('span').textContent=c.sub;b.addEventListener('click',()=>{e.staff.value=c.value;renderStaff();clearError();buildDates();setStep(3);sync();});e.staffCards.appendChild(b);});
  }

  async function loadServices(){
    showLoading(text.services);
    try{const r=await fetch('/api/booking/services',{headers:{Accept:'application/json'},credentials:'same-origin'});const p=await r.json();if(!r.ok||!p.success)throw new Error();e.service.innerHTML=`<option value="">${lang==='ar'?'اختر الخدمة':'Choose a service'}</option>`;(p.data||[]).forEach((item)=>{const o=document.createElement('option');o.value=item.id;o.textContent=item.duration_minutes?`${item.name_localized||item.name} (${item.duration_minutes} ${text.minutes})`:(item.name_localized||item.name);e.service.appendChild(o);});renderServices();if(!p.data?.length)showError(text.noServices);}catch(_){showError(text.network);}finally{hideLoading();sync();}
  }

  async function loadStaff(){
    clearError();showLoading(text.staff);
    try{const r=await fetch(`/api/booking/staff/by-service/${encodeURIComponent(e.service.value)}`,{headers:{Accept:'application/json'},credentials:'same-origin'});const p=await r.json();if(!r.ok||!p.success)throw new Error();e.staff.innerHTML=`<option value="">${lang==='ar'?'اختر المتخصص':'Choose a specialist'}</option>`;(p.data||[]).forEach((item)=>{const o=document.createElement('option');o.value=item.id;o.textContent=item.name;e.staff.appendChild(o);});if(!p.data?.length){showError(text.noStaff);return;}const any=document.createElement('option');any.value=ANY;any.textContent=text.any;e.staff.appendChild(any);renderStaff();setStep(2);sync();}catch(_){showError(text.network);}finally{hideLoading();}
  }

  function buildDates(){
    e.dateChoices.replaceChildren();e.date.innerHTML='';
    const now=new Date();now.setHours(0,0,0,0);
    for(let i=0;i<7;i++){
      const day=new Date(now);day.setDate(now.getDate()+i);const value=day.toISOString().slice(0,10);
      const opt=document.createElement('option');opt.value=value;opt.textContent=dateLabel(value);e.date.appendChild(opt);
      const b=document.createElement('button');b.type='button';b.className='booking-date';b.innerHTML='<small></small><strong></strong><span></span>';b.querySelector('small').textContent=new Intl.DateTimeFormat(lang==='ar'?'ar-EG':'en-US',{weekday:'short'}).format(day);b.querySelector('strong').textContent=String(day.getDate());b.querySelector('span').textContent=new Intl.DateTimeFormat(lang==='ar'?'ar-EG':'en-US',{month:'short'}).format(day);b.addEventListener('click',()=>{e.dateChoices.querySelectorAll('.booking-date').forEach((x)=>x.classList.remove('selected'));b.classList.add('selected');e.date.value=value;e.time.value='';loadSlots();});e.dateChoices.appendChild(b);
    }
    e.date.value='';e.timeOptions.replaceChildren();const empty=document.createElement('div');empty.className='booking-empty';empty.textContent=text.selectDate;e.timeOptions.appendChild(empty);sync();
  }

  async function getSlots(staffId,staffName){const params=new URLSearchParams({date:e.date.value,staff_id:staffId,service_id:e.service.value,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone||'Africa/Cairo'});const r=await fetch(`/api/booking/available-timeslots?${params}`,{headers:{Accept:'application/json'},credentials:'same-origin'});const p=await r.json();if(!r.ok||!p.success)return[];return(p.data||[]).map((s)=>({...s,staff_id:staffId,staff_name:staffName}));}

  async function loadSlots(){
    if(!e.date.value||!e.staff.value||!e.service.value)return;clearError();showLoading(text.slots);e.timeOptions.replaceChildren();e.time.innerHTML='';
    try{let slots=[];if(e.staff.value===ANY){const candidates=[...e.staff.options].filter((o)=>o.value&&o.value!==ANY).map((o)=>({id:o.value,name:o.textContent.trim()}));const results=await Promise.all(candidates.map((c)=>getSlots(c.id,c.name).catch(()=>[])));slots=results.flat().sort((a,b)=>a.start_time.localeCompare(b.start_time)||a.staff_name.localeCompare(b.staff_name));}else slots=await getSlots(e.staff.value,e.staff.selectedOptions[0]?.textContent.trim());
      if(!slots.length){const empty=document.createElement('div');empty.className='booking-empty';empty.textContent=text.noSlots;e.timeOptions.appendChild(empty);return;}
      slots.forEach((slot)=>{const b=document.createElement('button');b.type='button';b.className='booking-slot';b.innerHTML='<strong></strong><span></span>';b.querySelector('strong').textContent=slot.label||slot.start_time;b.querySelector('span').textContent=e.staff.value===ANY?slot.staff_name:text.available;b.addEventListener('click',()=>{e.time.innerHTML='';const o=document.createElement('option');o.value=slot.start_time;o.textContent=slot.label||slot.start_time;o.selected=true;e.time.appendChild(o);if(e.staff.value===ANY){e.staff.value=String(slot.staff_id);renderStaff();}e.timeOptions.querySelectorAll('.booking-slot').forEach((x)=>x.classList.remove('selected'));b.classList.add('selected');sync();setStep(4);document.getElementById('name')?.focus();});e.timeOptions.appendChild(b);});sync();
    }catch(_){showError(text.network);}finally{hideLoading();}
  }

  function validate(){const name=document.getElementById('name'),phone=document.getElementById('phone'),email=document.getElementById('email');if(!e.service.value||!e.staff.value||e.staff.value===ANY||!e.date.value||!e.time.value||!name?.value.trim()||!phone?.value.trim()||!email?.value.trim()||!email.checkValidity()){showError(lang==='ar'?'أكمل بيانات الحجز قبل التأكيد.':'Please complete your booking details before confirming.');return false;}return true;}

  async function submitBooking(ev){ev.preventDefault();clearError();if(!validate())return;e.submit.disabled=true;e.submit.textContent=text.booking;const data=new FormData(form);try{const r=await fetch('/api/appointments',{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},credentials:'same-origin',body:JSON.stringify({customer_name:data.get('name'),customer_email:data.get('email'),customer_phone:data.get('phone'),appointment_date:data.get('appointment_date'),appointment_time:data.get('appointment_time'),staff_id:data.get('staff_id'),service_id:data.get('service_id'),notes:data.get('notes')||null,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone||'Africa/Cairo'})});const p=await r.json();if(!r.ok||!p.success){let msg=p.message||text.bookingError;if(p.errors)msg=Object.values(p.errors).flat().join(' ');throw new Error(msg);}const ref=p.data?.appointment?.public_reference;if(!ref)throw new Error(text.bookingError);window.location.href=`/queue/status?ref=${encodeURIComponent(ref)}`;}catch(err){showError(err.message||text.bookingError);e.submit.disabled=false;e.submit.textContent=text.confirm;}}

  e.service.addEventListener('change',loadStaff);
  e.submit.addEventListener('click',submitBooking);
  form.querySelectorAll('input:not(.booking-hidden-field),textarea').forEach((field)=>field.addEventListener('input',sync));
  document.querySelectorAll('[data-back-to]').forEach((button)=>button.addEventListener('click',()=>setStep(Number(button.dataset.backTo))));
  document.getElementById('dark-mode-toggle')?.addEventListener('click',()=>{const dark=!document.documentElement.classList.contains('dark');localStorage.setItem('bookingTheme',String(dark));applyTheme(dark);});
  function applyTheme(dark){document.documentElement.classList.toggle('dark',dark);const icon=document.getElementById('darkModeIcon');if(icon)icon.textContent=dark?'☀️':'🌙';}
  window.changeLanguage=(value)=>{window.location.href='/change-language/'+encodeURIComponent(value);};
  const savedTheme=localStorage.getItem('bookingTheme');applyTheme(savedTheme==='true'||(savedTheme===null&&window.matchMedia('(prefers-color-scheme: dark)').matches));
  buildDates();sync();loadServices();
})();
