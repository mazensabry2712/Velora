/**
 * Dark Mode Toggle Handler - Queue Display Page Only
 * نظام التبديل بين الوضع النهاري والليلي - مخصص لصفحة عرض الطابور
 * 
 * يستخدم localStorage key: 'queueDarkMode'
 * منفصل عن Dark Mode الخاص بالحجز والداشبورد
 * مثالي لشاشات العرض الكبيرة في المحلات
 */

/**
 * تبديل وضع الدارك مود - صفحة الطابور
 */
function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('queueDarkMode', String(isDark));

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
    const savedMode = localStorage.getItem('queueDarkMode');
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
