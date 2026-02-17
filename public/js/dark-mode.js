/**
 * Dark Mode Toggle Handler
 * نظام التبديل بين الوضع النهاري والليلي
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
 * تبديل وضع الدارك مود
 */
function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);

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
    const icon = document.getElementById('dark-mode-icon');
    if (icon) {
        const isDark = document.documentElement.classList.contains('dark');
        icon.textContent = isDark ? '☀️' : '🌙';
    }
});

/**
 * الاستماع لتغيير تفضيلات النظام
 */
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (!localStorage.getItem('darkMode')) {
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
