// 🔧 سكريبت الإصلاح السريع للأزرار
// استخدام: افتح Console (F12) في صفحة المواعيد والصق هذا الكود

console.clear();
console.log('%c🔧 بدء تشخيص أزرار الطابور...', 'color: #3b82f6; font-size: 16px; font-weight: bold');

// ============================================
// 1️⃣ فحص البيئة الأساسية
// ============================================
console.log('\n%c1️⃣ فحص البيئة:', 'color: #8b5cf6; font-weight: bold');

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfMeta ? csrfMeta.content : null;

console.log('   • CSRF Meta Tag:', csrfMeta ? '✅ موجود' : '❌ غير موجود');
console.log('   • CSRF Token:', csrfToken ? `✅ ${csrfToken.substring(0, 20)}...` : '❌ غير موجود');
console.log('   • Page URL:', window.location.href);
console.log('   • Is Arabic:', typeof isArabic !== 'undefined' ? isArabic : '❓ غير معرف');

// ============================================
// 2️⃣ فحص الدوال
// ============================================
console.log('\n%c2️⃣ فحص الدوال:', 'color: #8b5cf6; font-weight: bold');

const functions = {
    'addToQueue': typeof addToQueue,
    'removeFromQueue': typeof removeFromQueue,
    'csrfToken (variable)': typeof csrfToken
};

Object.entries(functions).forEach(([name, type]) => {
    const status = type === 'function' || (name.includes('variable') && type === 'string') ? '✅' : '❌';
    console.log(`   ${status} ${name}: ${type}`);
});

// ============================================
// 3️⃣ فحص الأزرار في الصفحة
// ============================================
console.log('\n%c3️⃣ فحص الأزرار:', 'color: #8b5cf6; font-weight: bold');

const addButtons = document.querySelectorAll('button[onclick*="addToQueue"]');
const removeButtons = document.querySelectorAll('button[onclick*="removeFromQueue"]');

console.log(`   • أزرار الإضافة: ${addButtons.length} ${addButtons.length > 0 ? '✅' : '⚠️'}`);
console.log(`   • أزرار الإزالة: ${removeButtons.length} ${removeButtons.length > 0 ? '✅' : '⚠️'}`);

if (addButtons.length > 0) {
    console.log('   • مثال على زر إضافة:');
    console.log('     - onclick:', addButtons[0].getAttribute('onclick'));
    console.log('     - visible:', addButtons[0].offsetParent !== null ? '✅' : '❌');
    console.log('     - enabled:', !addButtons[0].disabled ? '✅' : '❌');
}

if (removeButtons.length > 0) {
    console.log('   • مثال على زر إزالة:');
    console.log('     - onclick:', removeButtons[0].getAttribute('onclick'));
    console.log('     - visible:', removeButtons[0].offsetParent !== null ? '✅' : '❌');
    console.log('     - enabled:', !removeButtons[0].disabled ? '✅' : '❌');
}

// ============================================
// 4️⃣ فحص event listeners محتملة
// ============================================
console.log('\n%c4️⃣ فحص Event Listeners:', 'color: #8b5cf6; font-weight: bold');

let clickListenerCount = 0;
document.addEventListener('click', function testListener() {
    clickListenerCount++;
});
document.removeEventListener('click', testListener);

console.log(`   • Global click listeners: موجودة (عادي)`);
console.log(`   • ملاحظة: قد تتداخل بعض listeners مع الأزرار`);

// ============================================
// 5️⃣ اختبار يدوي
// ============================================
console.log('\n%c5️⃣ دوال الاختبار اليدوي:', 'color: #10b981; font-weight: bold; font-size: 14px');

// دالة اختبار بدون API
window.testButtonClick = function(buttonType, id) {
    console.log(`\n%c🧪 اختبار ${buttonType}:`, 'color: #f59e0b; font-weight: bold');
    console.log(`   ID: ${id}`);

    if (buttonType === 'add') {
        if (typeof addToQueue === 'function') {
            console.log('   ✅ استدعاء addToQueue...');
            // لا نستدعيها فعلياً، فقط نتحقق
            console.log('   💡 للاستدعاء الفعلي استخدم: addToQueue(' + id + ')');
        } else {
            console.log('   ❌ addToQueue غير معرفة!');
        }
    } else if (buttonType === 'remove') {
        if (typeof removeFromQueue === 'function') {
            console.log('   ✅ استدعاء removeFromQueue...');
            console.log('   💡 للاستدعاء الفعلي استخدم: removeFromQueue(' + id + ')');
        } else {
            console.log('   ❌ removeFromQueue غير معرفة!');
        }
    }
};

// دالة اختبار API مباشر
window.testQueueAPI = async function(action, appointmentId) {
    console.log(`\n%c🌐 اختبار API: ${action}`, 'color: #06b6d4; font-weight: bold');

    const endpoint = action === 'add'
        ? `/admin/api/appointments/${appointmentId}/add-to-queue`
        : `/admin/api/appointments/${appointmentId}/remove-from-queue`;

    const method = action === 'add' ? 'POST' : 'DELETE';

    console.log(`   📡 ${method} ${endpoint}`);

    try {
        const response = await fetch(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        console.log(`   📥 Response Status: ${response.status} ${response.statusText}`);

        const data = await response.json();
        console.log(`   📄 Response Data:`, data);

        if (response.ok && data.success) {
            console.log(`   %c✅ نجح!`, 'color: #10b981; font-weight: bold');
        } else {
            console.log(`   %c❌ فشل: ${data.message || 'Unknown error'}`, 'color: #ef4444; font-weight: bold');
        }

        return data;
    } catch (error) {
        console.log(`   %c❌ خطأ: ${error.message}`, 'color: #ef4444; font-weight: bold');
        return null;
    }
};

// دالة اكتشاف المشاكل
window.diagnoseButtons = function() {
    console.log('\n%c🔍 تشخيص شامل:', 'color: #ef4444; font-weight: bold; font-size: 14px');

    const issues = [];

    if (!csrfToken) issues.push('❌ CSRF Token غير موجود');
    if (typeof addToQueue !== 'function') issues.push('❌ addToQueue غير معرفة');
    if (typeof removeFromQueue !== 'function') issues.push('❌ removeFromQueue غير معرفة');
    if (addButtons.length === 0 && removeButtons.length === 0) issues.push('⚠️ لا توجد أزرار في الصفحة');

    if (issues.length === 0) {
        console.log('   %c✅ كل شيء يبدو سليماً!', 'color: #10b981; font-weight: bold');
        console.log('\n   💡 إذا كانت الأزرار لا تعمل:');
        console.log('      1. جرب Hard Refresh: Ctrl + Shift + R');
        console.log('      2. افتح في Incognito Mode');
        console.log('      3. تحقق من Browser Extensions (AdBlock, etc.)');
    } else {
        console.log('   %c⚠️ مشاكل محتملة:', 'color: #f59e0b; font-weight: bold');
        issues.forEach(issue => console.log(`      ${issue}`));
    }
};

// ============================================
// ✅ النتيجة النهائية
// ============================================
console.log('\n%c✅ انتهى الفحص!', 'color: #10b981; font-size: 16px; font-weight: bold');
console.log('\n%c📋 الأوامر المتاحة:', 'color: #3b82f6; font-weight: bold');
console.log('%c   testButtonClick("add", 1)     ', 'color: #6366f1; background: #f0f0f0; padding: 2px 5px') + ' - اختبار زر الإضافة';
console.log('%c   testButtonClick("remove", 2)  ', 'color: #6366f1; background: #f0f0f0; padding: 2px 5px') + ' - اختبار زر الإزالة';
console.log('%c   testQueueAPI("add", 1)        ', 'color: #6366f1; background: #f0f0f0; padding: 2px 5px') + ' - اختبار API للإضافة (فعلي)';
console.log('%c   testQueueAPI("remove", 2)     ', 'color: #6366f1; background: #f0f0f0; padding: 2px 5px') + ' - اختبار API للإزالة (فعلي)');
console.log('%c   diagnoseButtons()             ', 'color: #6366f1; background: #f0f0f0; padding: 2px 5px') + ' - تشخيص المشاكل');
console.log('%c   addToQueue(ID)                ', 'color: #10b981; background: #f0f0f0; padding: 2px 5px') + ' - استدعاء فعلي لإضافة موعد';
console.log('%c   removeFromQueue(ID)           ', 'color: #ef4444; background: #f0f0f0; padding: 2px 5px') + ' - استدعاء فعلي لإزالة موعد');

console.log('\n💡 نصيحة: ابدأ بتشخيص المشاكل:');
console.log('%c   diagnoseButtons()', 'color: #fff; background: #3b82f6; padding: 5px 10px; border-radius: 4px; font-weight: bold');

// تشغيل التشخيص تلقائياً
setTimeout(() => {
    diagnoseButtons();
}, 100);
