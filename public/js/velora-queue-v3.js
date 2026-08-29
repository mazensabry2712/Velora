(function () {
  'use strict';

  const form = document.getElementById('queueForm');
  if (!form) return;

  const lang = (document.documentElement.lang || 'en').toLowerCase();
  const t = lang === 'ar' ? {
    loading:'جارٍ تحميل الموعد...', notFound:'لم يتم العثور على الحجز.', failed:'تعذر تحميل حالة الحجز.',
    waiting:'في الانتظار', serving:'حان دورك تقريبًا', called:'تم استدعاؤك', completed:'مكتمل', cancelled:'ملغي',
    min:'دقيقة', people:'عميل', copied:'تم نسخ الرقم', noData:'لا توجد بيانات متاحة',
  } : {
    loading:'Loading your appointment...', notFound:'Appointment not found.', failed:'Unable to load appointment status.',
    waiting:'Waiting', serving:'Almost your turn', called:'Your turn', completed:'Completed', cancelled:'Cancelled',
    min:'min', people:'people', copied:'Reference copied', noData:'Not available',
  };

  const els = {
    lookup:document.getElementById('lookup'), result:document.getElementById('queueResult'), loading:document.getElementById('queueLoading'), error:document.getElementById('queueError'),
    number:document.getElementById('queueNumber'), reference:document.getElementById('queueReference'), ahead:document.getElementById('peopleAhead'), wait:document.getElementById('waitTime'),
    status:document.getElementById('queueStatusText'), dot:document.getElementById('queueStatusDot'), customer:document.getElementById('customerName'), service:document.getElementById('serviceName'),
    staff:document.getElementById('staffName'), date:document.getElementById('appointmentDate'), time:document.getElementById('appointmentTime'), duration:document.getElementById('duration'), refresh:document.getElementById('queueRefresh'), copy:document.getElementById('copyReference'),
  };

  let refreshTimer = null;
  let currentReference = new URLSearchParams(window.location.search).get('ref') || new URLSearchParams(window.location.search).get('queue_number') || '';

  function showLoading() { els.loading.classList.remove('hidden'); els.error.classList.add('hidden'); }
  function hideLoading() { els.loading.classList.add('hidden'); }
  function showError(message) { els.error.textContent = message; els.error.classList.remove('hidden'); els.result.classList.add('hidden'); hideLoading(); }

  function statusLabel(status) {
    return ({waiting:t.waiting,serving:t.serving,called:t.called,in_service:t.called,completed:t.completed,cancelled:t.cancelled})[status] || status || t.waiting;
  }

  function formatDate(value) {
    if (!value) return t.noData; const d = new Date(`${value}T00:00:00`); if (Number.isNaN(d.getTime())) return value;
    return new Intl.DateTimeFormat(lang === 'ar' ? 'ar-EG' : 'en-US',{weekday:'long',month:'short',day:'numeric',year:'numeric'}).format(d);
  }

  function formatTime(value) {
    if (!value) return t.noData; const parts = String(value).split(':'); if (parts.length < 2) return value; const d = new Date(); d.setHours(Number(parts[0]),Number(parts[1]),0,0);
    return new Intl.DateTimeFormat(lang === 'ar' ? 'ar-EG' : 'en-US',{hour:'numeric',minute:'2-digit'}).format(d);
  }

  function render(data) {
    els.result.classList.remove('hidden');
    els.number.textContent = data.queue_number || '—';
    els.reference.textContent = data.reference || currentReference || '—';
    els.ahead.textContent = data.people_ahead ?? 0;
    els.wait.textContent = data.estimated_wait_minutes ?? 0;
    els.status.textContent = statusLabel(data.status);
    els.customer.textContent = data.customer_name || t.noData;
    els.service.textContent = data.service || t.noData;
    els.staff.textContent = data.staff_name || t.noData;
    els.date.textContent = formatDate(data.appointment_date || data.queue_date);
    els.time.textContent = formatTime(data.appointment_time);
    els.duration.textContent = data.duration_minutes ? `${data.duration_minutes} ${t.min}` : t.noData;
    els.dot.style.color = data.status === 'completed' ? '#12b76a' : data.status === 'cancelled' ? '#d92d20' : '#12b76a';
  }

  async function lookup() {
    const identifier = (els.lookup.value || '').trim();
    if (!identifier) return;
    currentReference = identifier;
    const url = `/api/queue/status/${encodeURIComponent(identifier)}`;
    showLoading();
    try {
      const response = await fetch(url,{headers:{Accept:'application/json'},credentials:'same-origin'});
      const payload = await response.json();
      if (!response.ok || !payload.success || !payload.data) throw new Error(payload.message || t.notFound);
      render(payload.data); hideLoading();
      const newUrl = `${window.location.pathname}?ref=${encodeURIComponent(identifier)}`;
      window.history.replaceState({},'',newUrl);
      scheduleRefresh(payload.data.status);
    } catch (error) { showError(error.message || t.failed); }
  }

  function scheduleRefresh(status) {
    clearTimeout(refreshTimer);
    if (['waiting','serving','called','in_service'].includes(status)) refreshTimer = setTimeout(lookup,20000);
  }

  form.addEventListener('submit',function(event){event.preventDefault();lookup();});
  els.refresh?.addEventListener('click',lookup);
  els.copy?.addEventListener('click',async()=>{const value=els.reference.textContent.trim();if(!value||value==='—')return;try{await navigator.clipboard.writeText(value);const old=els.copy.textContent;els.copy.textContent=t.copied;setTimeout(()=>{els.copy.textContent=old;},1500);}catch(_){} });

  const saved=localStorage.getItem('queueTheme');
  function applyTheme(dark){document.documentElement.classList.toggle('dark',dark);const icon=document.getElementById('queueThemeIcon');if(icon)icon.textContent=dark?'☀️':'🌙';}
  document.getElementById('queueTheme')?.addEventListener('click',()=>{const next=!document.documentElement.classList.contains('dark');localStorage.setItem('queueTheme',String(next));applyTheme(next);});
  window.changeLanguage=function(value){window.location.href='/change-language/'+encodeURIComponent(value);};
  applyTheme(saved==='true'||(saved===null&&window.matchMedia('(prefers-color-scheme: dark)').matches));

  if (currentReference) { els.lookup.value=currentReference; lookup(); }
})();
