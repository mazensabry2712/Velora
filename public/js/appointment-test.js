/**
 * 🧪 Appointment Actions Quick Test Script
 *
 * كيفية الاستخدام:
 * 1. افتح صفحة المواعيد: /admin/appointments
 * 2. افتح Developer Tools (F12)
 * 3. انسخ والصق هذا الكود في Console واضغط Enter
 * 4. اتبع التعليمات
 */

console.log('%c🧪 بدء اختبار إجراءات المواعيد...', 'color: #10b981; font-size: 16px; font-weight: bold;');

// اختبار 1: التحقق من وجود الدوال
console.log('\n📋 اختبار 1: التحقق من وجود الدوال المطلوبة');
const functions = {
    'addToQueue': typeof addToQueue === 'function',
    'removeFromQueue': typeof removeFromQueue === 'function',
    'updateStatus': typeof updateStatus === 'function',
    'showToast': typeof showToast === 'function'
};

Object.entries(functions).forEach(([name, exists]) => {
    console.log(exists ? '✅' : '❌', name, '→', exists ? 'موجودة' : 'غير موجودة');
});

// اختبار 2: التحقق من عناصر DOM
console.log('\n📋 اختبار 2: التحقق من العناصر في الصفحة');

const elements = {
    'جدول المواعيد': document.querySelector('table'),
    'عمود رقم الطابور': document.querySelector('th') && Array.from(document.querySelectorAll('th')).some(th => th.textContent.includes('رقم الطابور') || th.textContent.includes('Queue')),
    'عمود الإجراءات': document.querySelector('th') && Array.from(document.querySelectorAll('th')).some(th => th.textContent.includes('الإجراءات') || th.textContent.includes('Actions')),
    'أزرار الإضافة': document.querySelectorAll('[onclick*="addToQueue"]').length,
    'أزرار الإزالة': document.querySelectorAll('[onclick*="removeFromQueue"]').length,
    'أزرار تحديث الحالة': document.querySelectorAll('[onclick*="updateStatus"]').length
};

Object.entries(elements).forEach(([name, value]) => {
    if (typeof value === 'boolean') {
        console.log(value ? '✅' : '❌', name, '→', value ? 'موجود' : 'غير موجود');
    } else if (typeof value === 'number') {
        console.log(value > 0 ? '✅' : '❌', name, '→', value, value > 0 ? 'موجود' : 'غير موجود');
    } else {
        console.log(value ? '✅' : '❌', name, '→', value ? 'موجود' : 'غير موجود');
    }
});

// اختبار 3: التحقق من CSRF Token
console.log('\n📋 اختبار 3: التحقق من CSRF Token');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
console.log(csrfToken ? '✅' : '❌', 'CSRF Token →', csrfToken ? 'موجود' : 'غير موجود');

// اختبار 4: اختبار الترجمة
console.log('\n📋 اختبار 4: التحقق من الترجمة');
console.log('اللغة الحالية:', isArabic ? 'العربية' : 'الإنجليزية');

// اختبار 5: إحصائيات المواعيد
console.log('\n📋 اختبار 5: فحص الإحصائيات');
const stats = {
    'مواعيد اليوم': document.querySelector('.stat-card') || 'غير موجود',
    'في الطابور': Array.from(document.querySelectorAll('p')).find(p => p.textContent.includes('في الطابور') || p.textContent.includes('In Queue'))
};

Object.entries(stats).forEach(([name, element]) => {
    console.log(element !== 'غير موجود' && element ? '✅' : '❌', name, '→', element !== 'غير موجود' && element ? 'موجود' : 'غير موجود');
});

// دوال الاختبار التفاعلي
console.log('\n\n🎯 دوال الاختبار المتاحة:');
console.log('%c══════════════════════════════════════', 'color: #3b82f6;');

// دالة لاختبار إضافة موعد للطابور
window.testAddToQueue = function(appointmentId) {
    if (!appointmentId) {
        console.log('%c❌ يجب تحديد رقم الموعد', 'color: #ef4444; font-weight: bold;');
        console.log('الاستخدام: testAddToQueue(123)');
        return;
    }

    console.log(`%c🔄 اختبار إضافة الموعد #${appointmentId} للطابور...`, 'color: #3b82f6;');

    fetch(`/admin/api/appointments/${appointmentId}/add-to-queue`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('%c✅ نجح!', 'color: #10b981; font-weight: bold;', result.message);
            console.log('البيانات:', result.data);
        } else {
            console.log('%c❌ فشل!', 'color: #ef4444; font-weight: bold;', result.message);
        }
    })
    .catch(error => {
        console.log('%c❌ خطأ:', 'color: #ef4444; font-weight: bold;', error.message);
    });
};

// دالة لاختبار إزالة موعد من الطابور
window.testRemoveFromQueue = function(appointmentId) {
    if (!appointmentId) {
        console.log('%c❌ يجب تحديد رقم الموعد', 'color: #ef4444; font-weight: bold;');
        console.log('الاستخدام: testRemoveFromQueue(123)');
        return;
    }

    console.log(`%c🔄 اختبار إزالة الموعد #${appointmentId} من الطابور...`, 'color: #3b82f6;');

    fetch(`/admin/api/appointments/${appointmentId}/remove-from-queue`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('%c✅ نجح!', 'color: #10b981; font-weight: bold;', result.message);
        } else {
            console.log('%c❌ فشل!', 'color: #ef4444; font-weight: bold;', result.message);
        }
    })
    .catch(error => {
        console.log('%c❌ خطأ:', 'color: #ef4444; font-weight: bold;', error.message);
    });
};

// دالة لاختبار تحديث حالة الموعد
window.testUpdateStatus = function(appointmentId, status) {
    if (!appointmentId || !status) {
        console.log('%c❌ يجب تحديد رقم الموعد والحالة', 'color: #ef4444; font-weight: bold;');
        console.log('الاستخدام: testUpdateStatus(123, "confirmed")');
        console.log('الحالات المتاحة: pending, confirmed, cancelled, completed');
        return;
    }

    const validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!validStatuses.includes(status)) {
        console.log('%c❌ حالة غير صحيحة', 'color: #ef4444; font-weight: bold;');
        console.log('الحالات المتاحة:', validStatuses.join(', '));
        return;
    }

    console.log(`%c🔄 اختبار تحديث الموعد #${appointmentId} إلى حالة "${status}"...`, 'color: #3b82f6;');

    fetch(`/admin/api/appointments/${appointmentId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ status })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            console.log('%c✅ نجح!', 'color: #10b981; font-weight: bold;', result.message);
            console.log('البيانات:', result.data);
            if (result.data.queue) {
                console.log('حالة الطابور:', result.data.queue.status);
            }
        } else {
            console.log('%c❌ فشل!', 'color: #ef4444; font-weight: bold;', result.message);
        }
    })
    .catch(error => {
        console.log('%c❌ خطأ:', 'color: #ef4444; font-weight: bold;', error.message);
    });
};

// دالة للحصول على معلومات موعد
window.getAppointmentInfo = function(appointmentId) {
    const row = document.getElementById(`row-${appointmentId}`);
    if (!row) {
        console.log(`%c❌ الموعد #${appointmentId} غير موجود في الصفحة الحالية`, 'color: #ef4444; font-weight: bold;');
        return;
    }

    console.log(`%c📋 معلومات الموعد #${appointmentId}:`, 'color: #3b82f6; font-weight: bold;');

    const cells = row.querySelectorAll('td');
    const hasQueue = row.textContent.includes('#') && !row.textContent.includes('لا يوجد');
    const hasAddButton = row.querySelector('[onclick*="addToQueue"]');
    const hasRemoveButton = row.querySelector('[onclick*="removeFromQueue"]');

    console.log('في الطابور:', hasQueue ? 'نعم' : 'لا');
    console.log('زر الإضافة:', hasAddButton ? 'موجود' : 'غير موجود');
    console.log('زر الإزالة:', hasRemoveButton ? 'موجود' : 'غير موجود');
};

// دالة لاختبار سيناريو كامل
window.testFullScenario = async function(appointmentId) {
    if (!appointmentId) {
        console.log('%c❌ يجب تحديد رقم الموعد', 'color: #ef4444; font-weight: bold;');
        console.log('الاستخدام: testFullScenario(123)');
        return;
    }

    console.log('%c🎬 بدء السيناريو الكامل...', 'color: #8b5cf6; font-size: 14px; font-weight: bold;');
    console.log('══════════════════════════════════════');

    // الخطوة 1: إضافة للطابور
    console.log('\n%c1️⃣ إضافة للطابور...', 'color: #3b82f6;');
    await fetch(`/admin/api/appointments/${appointmentId}/add-to-queue`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(r => r.json())
    .then(result => console.log(result.success ? '✅' : '❌', result.message));

    await new Promise(r => setTimeout(r, 1000));

    // الخطوة 2: تحديث الحالة إلى مؤكد
    console.log('\n%c2️⃣ تحديث الحالة إلى مؤكد...', 'color: #3b82f6;');
    await fetch(`/admin/api/appointments/${appointmentId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ status: 'confirmed' })
    })
    .then(r => r.json())
    .then(result => console.log(result.success ? '✅' : '❌', result.message));

    await new Promise(r => setTimeout(r, 1000));

    // الخطوة 3: تحديث الحالة إلى مكتمل
    console.log('\n%c3️⃣ تحديث الحالة إلى مكتمل...', 'color: #3b82f6;');
    await fetch(`/admin/api/appointments/${appointmentId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ status: 'completed' })
    })
    .then(r => r.json())
    .then(result => {
        console.log(result.success ? '✅' : '❌', result.message);
        if (result.data && result.data.queue) {
            console.log('حالة الطابور النهائية:', result.data.queue.status);
        }
    });

    console.log('\n%c✅ اكتمل السيناريو!', 'color: #10b981; font-size: 14px; font-weight: bold;');
};

// عرض الإرشادات
console.log('\n%c📚 الدوال المتاحة:', 'color: #8b5cf6; font-weight: bold;');
console.log('%c══════════════════════════════════════', 'color: #3b82f6;');
console.log('%c• testAddToQueue(appointmentId)', 'color: #06b6d4;', '- اختبار إضافة موعد للطابور');
console.log('%c• testRemoveFromQueue(appointmentId)', 'color: #06b6d4;', '- اختبار إزالة موعد من الطابور');
console.log('%c• testUpdateStatus(appointmentId, status)', 'color: #06b6d4;', '- اختبار تحديث حالة الموعد');
console.log('%c• getAppointmentInfo(appointmentId)', 'color: #06b6d4;', '- الحصول على معلومات الموعد');
console.log('%c• testFullScenario(appointmentId)', 'color: #06b6d4;', '- اختبار سيناريو كامل');
console.log('%c══════════════════════════════════════', 'color: #3b82f6;');

console.log('\n%c💡 مثال:', 'color: #f59e0b; font-weight: bold;');
console.log('%ctestAddToQueue(1)', 'color: #06b6d4; font-family: monospace;');
console.log('%ctestUpdateStatus(1, "confirmed")', 'color: #06b6d4; font-family: monospace;');
console.log('%ctestFullScenario(1)', 'color: #06b6d4; font-family: monospace;');

console.log('\n%c✅ جاهز للاختبار!', 'color: #10b981; font-size: 16px; font-weight: bold;');
