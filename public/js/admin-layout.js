// ── Scroll Progress ──────────────────────────────────────────────
window.addEventListener('scroll', () => {
    const el = document.getElementById('admin-scroll-progress');
    if (!el) return;
    const pct = window.scrollY / (document.documentElement.scrollHeight - window.innerHeight);
    el.style.transform = `scaleX(${Math.min(pct, 1)})`;
}, { passive: true });

// ── Back to Top ──────────────────────────────────────────────────
window.addEventListener('scroll', () => {
    const btn = document.getElementById('admin-back-to-top');
    if (!btn) return;
    if (window.scrollY > 300) {
        btn.style.opacity = '1';
        btn.style.transform = 'translateY(0)';
        btn.style.pointerEvents = 'auto';
    } else {
        btn.style.opacity = '0';
        btn.style.transform = 'translateY(16px)';
        btn.style.pointerEvents = 'none';
    }
}, { passive: true });

// ── Dark Mode Toggle ─────────────────────────────────────────────
function toggleAdminDark() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('adminDarkMode', isDark);
}
// Legacy alias used by some pages
function toggleDarkMode() { toggleAdminDark(); }
function changeLanguage(lang) { window.location.href = '/change-language/' + lang; }

// ── Toast ────────────────────────────────────────────────────────
function showToast(message, type = 'success', duration = 3500) {
    const icons = {
        success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
        error:   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
        info:    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    };
    const colors = { success: 'bg-emerald-500', error: 'bg-red-500', info: 'bg-blue-500', warning: 'bg-amber-500' };
    const toast = document.createElement('div');
    toast.className = `toast-enter pointer-events-auto flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-2xl text-white font-medium text-sm ${colors[type] || colors.success}`;
    toast.innerHTML = `
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icons[type] || icons.success}</svg>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ms-auto opacity-70 hover:opacity-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>`;
    document.getElementById('admin-toast-container').appendChild(toast);
    setTimeout(() => {
        toast.classList.replace('toast-enter', 'toast-exit');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ── Counter Animation ────────────────────────────────────────────
function animateCounter(el, target, duration = 800) {
    const startTime = performance.now();
    (function update(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(target * ease);
        if (progress < 1) requestAnimationFrame(update);
    })(startTime);
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-counter]').forEach(el => {
        animateCounter(el, parseInt(el.dataset.counter, 10) || 0);
    });
});
