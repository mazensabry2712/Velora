// 🔧 سكريبت تصحيح أزرار الطابور
// افتح صفحة المواعيد (/admin/appointments) ثم افتح Console (F12) والصق هذا الكود

console.log('🚀 بدء فحص أزرار الطابور...\n');

// 1. فحص وجود CSRF Token
console.log('1️⃣ فحص CSRF Token:');
const metaTag = document.querySelector('meta[name="csrf-token"]');
if (metaTag) {
    console.log('✅ CSRF meta tag موجود');
    console.log('   Token:', metaTag.content.substring(0, 20) + '...');
} else {
    console.error('❌ CSRF meta tag غير موجود!');
}

// 2. فحص وجود الأزرار
console.log('\n2️⃣ فحص الأزرار:');
const addButtons = document.querySelectorAll('button[onclick*="addToQueue"]');
const removeButtons = document.querySelectorAll('button[onclick*="removeFromQueue"]');
console.log(`✅ عدد أزرار الإضافة: ${addButtons.length}`);
console.log(`✅ عدد أزرار الإزالة: ${removeButtons.length}`);

// 3. فحص وجود الدوال
console.log('\n3️⃣ فحص الدوال JavaScript:');
if (typeof addToQueue === 'function') {
    console.log('✅ دالة addToQueue موجودة');
} else {
    console.error('❌ دالة addToQueue غير موجودة!');
}

if (typeof removeFromQueue === 'function') {
    console.log('✅ دالة removeFromQueue موجودة');
} else {
    console.error('❌ دالة removeFromQueue غير موجودة!');
}

// 4. اختبار يدوي
console.log('\n4️⃣ اختبار يدوي:');
console.log('استخدم الأوامر التالية للاختبار:');
console.log('   testAddButton(رقم_الموعد)    // مثال: testAddButton(1)');
console.log('   testRemoveButton(رقم_الموعد)  // مثال: testRemoveButton(1)');

// دالة اختبار زر الإضافة
window.testAddButton = async function(appointmentId) {
    console.log(`\n🧪 اختبار إضافة موعد #${appointmentId}...`);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        console.log('📡 إرسال POST request...');

        const response = await fetch(`/admin/api/appointments/${appointmentId}/add-to-queue`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        console.log(`📥 Response Status: ${response.status}`);
        const data = await response.json();
        console.log('📄 Response Data:', data);

        if (data.success) {
            console.log('✅ نجح!');
        } else {
            console.error('❌ فشل:', data.message);
        }

        return data;
    } catch (error) {
        console.error('❌ خطأ:', error);
        return null;
    }
};

// دالة اختبار زر الإزالة
window.testRemoveButton = async function(appointmentId) {
    console.log(`\n🧪 اختبار إزالة موعد #${appointmentId}...`);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        console.log('📡 إرسال DELETE request...');

        const response = await fetch(`/admin/api/appointments/${appointmentId}/remove-from-queue`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });

        console.log(`📥 Response Status: ${response.status}`);
        const data = await response.json();
        console.log('📄 Response Data:', data);

        if (data.success) {
            console.log('✅ نجح!');
        } else {
            console.error('❌ فشل:', data.message);
        }

        return data;
    } catch (error) {
        console.error('❌ خطأ:', error);
        return null;
    }
};

// 5. فحص الـ onclick handlers
console.log('\n5️⃣ فحص onclick handlers:');
if (addButtons.length > 0) {
    const firstAddButton = addButtons[0];
    console.log('أول زر إضافة:');
    console.log('   onclick:', firstAddButton.getAttribute('onclick'));
}
if (removeButtons.length > 0) {
    const firstRemoveButton = removeButtons[0];
    console.log('أول زر إزالة:');
    console.log('   onclick:', firstRemoveButton.getAttribute('onclick'));
}

console.log('\n✨ انتهى الفحص. استخدم الأوامر أعلاه للاختبار اليدوي.');
