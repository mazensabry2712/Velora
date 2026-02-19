/**
 * Dark Mode Toggle Handler - Admin Dashboard Only
 * نظام التبديل بين الوضع النهاري والليلي - مخصص للداشبورد الإداري
 *
 * يستخدم localStorage key: 'adminDarkMode'
 * منفصل تماماً عن Dark Mode الخاص بصفحة الحجز العامة
 *
 * ملاحظة مهمة:
 * - يجب أن يكون هناك سكريبت inline في <head> لمنع وميض الوضع الفاتح
 * - هذا الملف يحتوي على الوظائف الإضافية فقط
 *
 * الاستخدام:
 * أضف هذا الملف في layouts/admin.blade.php قبل إغلاق </body>:
 * <script src="/js/dark-mode.js"></script>
 */

/**
 * تبديل وضع الدارك مود - الداشبورد
 */
function toggleDarkMode() {
    const wasDark = document.documentElement.classList.contains('dark');
    const isDark = document.documentElement.classList.toggle('dark');
    const value = String(isDark);

    localStorage.setItem('adminDarkMode', value);

    console.log('='.repeat(50));
    console.log('🔄 TOGGLE DARK MODE:');
    console.log('   Was Dark:', wasDark);
    console.log('   Now Dark:', isDark);
    console.log('   Saved to localStorage:', value);
    console.log('   Verify:', localStorage.getItem('adminDarkMode'));
    console.log('='.repeat(50));

    // تحديث أيقونة الزر إذا كانت موجودة
    const icon = document.getElementById('dark-mode-icon');
    if (icon) {
        icon.textContent = isDark ? '☀️' : '🌙';
    }
}

/**
 * تحديث أيقونة الزر عند تحميل الصفحة
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('📱 DOMContentLoaded - checking current state...');
    const savedMode = localStorage.getItem('adminDarkMode');
    const isDark = document.documentElement.classList.contains('dark');
    console.log('   localStorage adminDarkMode:', savedMode);
    console.log('   Current dark class:', isDark);

    const icon = document.getElementById('dark-mode-icon');
    if (icon) {
        icon.textContent = isDark ? '☀️' : '🌙';
        console.log('   Icon updated:', icon.textContent);
    }
});

/**
 * الاستماع لتغيير تفضيلات النظام
 */
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    const savedMode = localStorage.getItem('adminDarkMode');
    if (savedMode === null) {
        if (e.matches) {
            document.documentElement.classList.add('dark');
            const icon = document.getElementById('dark-mode-icon');
            if (icon) icon.textContent = '☀️';
        } else {
            document.documentElement.classList.remove('dark');
            const icon = document.getElementById('dark-mode-icon');
            if (icon) icon.textContent = '🌙';
        }
    }
});
