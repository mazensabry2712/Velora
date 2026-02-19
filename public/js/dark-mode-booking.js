/**
 * Dark Mode Toggle Handler - Booking Page Only
 * نظام التبديل بين الوضع النهاري والليلي - مخصص لصفحة الحجز العامة
 * 
 * يستخدم localStorage key: 'bookingDarkMode'
 * منفصل تماماً عن Dark Mode الخاص بالداشبورد
 */

/**
 * تبديل وضع الدارك مود - صفحة الحجز
 */
function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('bookingDarkMode', String(isDark));

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
    const savedMode = localStorage.getItem('bookingDarkMode');
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
