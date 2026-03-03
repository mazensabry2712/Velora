# نظام الألوان الجديد - Color System

## 🎨 **نظام الألوان المطبق**

تم تحديث النظام بالكامل إلى نظام ألوان **Slate + Indigo** المريح للعين والمتوافق مع Dark Mode المستقبلي.

---

## **الألوان الأساسية**

### **Primary Color** (اللون الرئيسي)
- **القديم:** `blue-600` (#3B82F6)
- **الجديد:** `indigo-600` (#4F46E5)
- **Dark Mode:** `indigo-500` (#6366F1)

### **Background** (الخلفيات)
- **القديم:** `gray-50`, `gray-100`
- **الجديد:** `slate-50`, `slate-100`
- **Dark Mode:** `slate-900`, `slate-800`

### **Text Colors** (الخطوط)
- **Primary:** `slate-900` / `slate-100` (dark)
- **Secondary:** `slate-600` / `slate-300` (dark)
- **Muted:** `slate-500` / `slate-400` (dark)

### **Borders** (الحدود)
- **القديم:** `gray-200`, `gray-300`
- **الجديد:** `slate-200`, `slate-300`
- **Dark Mode:** `slate-700`, `slate-600`

---

## **ألوان الحالات (Status Colors)**

### **✅ Confirmed** (مؤكد)
```css
/* Light Mode */
bg-emerald-100 text-emerald-800
border-emerald-200

/* Dark Mode */
dark:bg-emerald-900 dark:text-emerald-300
dark:border-emerald-700
```

### **⏳ Pending** (قيد الانتظار)
```css
/* Light Mode */
bg-amber-100 text-amber-800
border-amber-200

/* Dark Mode */
dark:bg-amber-900 dark:text-amber-300
dark:border-amber-700
```

### **❌ Cancelled** (ملغي)
```css
/* Light Mode */
bg-red-100 text-red-800
border-red-200

/* Dark Mode */
dark:bg-red-900 dark:text-red-300
dark:border-red-700
```

### **✔️ Completed** (مكتمل)
```css
/* Light Mode */
bg-cyan-100 text-cyan-800
border-cyan-200

/* Dark Mode */
dark:bg-cyan-900 dark:text-cyan-300
dark:border-cyan-700
```

---

## **الأزرار (Buttons)**

### **Primary Button**
```html
<button class="bg-indigo-600 dark:bg-indigo-500 text-white hover:bg-indigo-700 dark:hover:bg-indigo-600">
    زر أساسي
</button>
```

### **Secondary Button**
```html
<button class="bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600">
    زر ثانوي
</button>
```

### **Success Button**
```html
<button class="bg-emerald-600 dark:bg-emerald-500 text-white hover:bg-emerald-700 dark:hover:bg-emerald-600">
    زر نجاح
</button>
```

---

## **الـ Cards والـ Containers**

### **White Card**
```html
<div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm">
    محتوى الكارد
</div>
```

### **Gradient Card**
```html
<div class="bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-700 dark:to-purple-700">
    محتوى بخلفية متدرجة
</div>
```

---

## **Navigation Links**

### **Active State**
```css
text-indigo-600 dark:text-indigo-400 
bg-indigo-50 dark:bg-indigo-900/30
```

### **Inactive State**
```css
text-slate-600 dark:text-slate-300 
hover:text-slate-900 dark:hover:text-slate-100 
hover:bg-slate-50 dark:hover:bg-slate-700/50
```

---

## **الـ Modals والـ Overlays**

### **Modal Overlay**
```css
bg-slate-900 bg-opacity-50
backdrop-blur-sm
```

### **Modal Content**
```css
bg-white dark:bg-slate-800
border border-slate-200 dark:border-slate-700
```

---

## **Custom Scrollbar**

```css
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9; /* slate-100 */
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1; /* slate-300 */
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8; /* slate-400 */
}
```

---

## **الملفات المحدثة**

✅ `resources/views/admin/appointments/index.blade.php`
✅ `resources/views/layouts/admin.blade.php`
✅ `resources/views/admin/queue/index.blade.php`

---

## **للمطورين - إضافة Dark Mode لاحقاً**

### خطوات تفعيل Dark Mode:

1. **إضافة Toggle في الـ Navigation:**
```html
<button onclick="toggleDarkMode()" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700">
    🌙 / ☀️
</button>
```

2. **JavaScript للتبديل:**
```javascript
function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
}

// تحميل الإعداد المحفوظ
if (localStorage.getItem('darkMode') === 'true') {
    document.documentElement.classList.add('dark');
}
```

3. **إضافة في tailwind.config.js:**
```javascript
module.exports = {
  darkMode: 'class',
  // ... باقي الإعدادات
}
```

---

## **المميزات**

✅ **مريح للعين** - ألوان Slate أقل إجهاداً من Gray
✅ **احترافي** - مظهر Enterprise متناسق
✅ **جاهز للـ Dark Mode** - جميع العناصر تحتوي على `dark:` variants
✅ **تباين جيد** - يحافظ على سهولة القراءة
✅ **متوافق مع WCAG** - معايير Accessibility

---

## **ملاحظات مهمة**

- جميع الأزرار والعناصر تدعم Dark Mode الآن
- استخدم `dark:` prefix عند إضافة عناصر جديدة
- الألوان القديمة (blue/gray) تم استبدالها بـ (indigo/slate)
- Status colors أصبحت أكثر وضوحاً (emerald/amber/cyan)

---

**تاريخ التحديث:** {{ now()->format('Y-m-d') }}
**الإصدار:** 2.0
