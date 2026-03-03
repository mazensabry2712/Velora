# نظام اللغات المتعددة (Multi-Language System)

## 📋 نظرة عامة
تم تطوير نظام لغات متعدد يتيح للأدمن اختيار اللغات المتاحة للعملاء من صفحة الإعدادات.

## 🌍 اللغات المدعومة
- 🇬🇧 **English** (en)
- 🇸🇦 **العربية** (ar)
- 🇫🇷 **Français** (fr)
- 🇪🇸 **Español** (es)
- 🇩🇪 **Deutsch** (de)
- 🇮🇹 **Italiano** (it)
- 🇵🇹 **Português** (pt)
- 🇷🇺 **Русский** (ru)
- 🇨🇳 **中文** (zh)
- 🇯🇵 **日本語** (ja)

## ⚙️ كيفية الإعداد

### للأدمن:
1. اذهب إلى: `http://demo.booking-saas.test/admin/settings`
2. ابحث عن قسم "Available Languages"
3. اختر اللغات المطلوبة (checkbox)
4. احفظ الإعدادات

### للعملاء:
- Language Switcher يظهر تلقائياً في صفحة الحجز
- يعرض فقط اللغات التي اختارها الأدمن
- إذا لغة واحدة فقط: لن يظهر Language Switcher

## 📁 الملفات المعدلة

### 1. Database
- **Migration**: `2026_02_17_125009_add_available_languages_to_settings_table.php`
- **Field**: `available_languages` (JSON)

### 2. Models
- **App\Models\Setting**: أضيف حقل `available_languages` للـ fillable و cast

### 3. Controllers
- **App\Http\Controllers\Web\AdminController@saveSettings**: 
  - Validation للغات المتاحة
  - حفظ اللغات المختارة
  
- **App\Http\Controllers\Web\CustomerController@booking**:
  - قراءة اللغات المتاحة من Settings
  - تمرير المتغير `$availableLanguages` للـ View

### 4. Middleware
- **App\Http\Middleware\SetTenantLocale**:
  - يقرأ اللغات المتاحة من Settings
  - يسمح فقط باللغات المتاحة
  - Fallback إلى اللغة الافتراضية

### 5. Routes
- **routes/web.php**: 
  - `/change-language/{lang}` يتحقق من اللغات المتاحة من Settings

### 6. Views
- **resources/views/admin/settings/index.blade.php**:
  - قسم جديد "Available Languages"
  - عرض جميع اللغات مع أعلامها
  - Checkboxes لاختيار اللغات
  
- **resources/views/customer/booking.blade.php**:
  - Language Switcher ديناميكي
  - يعرض فقط اللغات المتاحة
  - يخفي Switcher إذا لغة واحدة فقط

### 7. Language Files
- **lang/fr/**, **lang/es/**, **lang/de/**, **lang/it/**, **lang/pt/**, **lang/ru/**, **lang/zh/**, **lang/ja/**
  - مجلدات جاهزة لإضافة الترجمات

## 🔄 آلية العمل

### 1. حفظ الإعدادات
```
Admin Settings → available_languages (JSON) → Database
```

### 2. عرض صفحة الحجز
```
CustomerController → Read Settings → Pass $availableLanguages → View
```

### 3. تغيير اللغة
```
User clicks language → /change-language/{lang} → Check if allowed → Set session → Redirect back
```

### 4. Middleware
```
Every Request → SetTenantLocale → Check available languages → Set app locale
```

## 🧪 الاختبار

### السيناريو 1: اختيار 3 لغات
1. Admin يختار: العربية + الإنجليزية + الفرنسية
2. صفحة الحجز تعرض 3 أزرار فقط: عربي | EN | FR
3. العميل يمكنه التبديل بينهم

### السيناريو 2: لغة واحدة فقط
1. Admin يختار: العربية فقط
2. صفحة الحجز لا تعرض Language Switcher
3. الموقع يعمل بالعربية فقط

### السيناريو 3: Default (لو Settings فاضي)
1. إذا لم يُحدد أي لغات في Settings
2. يتم استخدام: العربية + الإنجليزية كـ default

## ✅ المميزات

1. **ديناميكي**: الأدمن يتحكم بالكامل في اللغات المتاحة
2. **آمن**: التحقق من اللغات المسموحة في كل مكان
3. **سهل الاستخدام**: UI بسيط مع أعلام وأسماء اللغات
4. **مرن**: دعم 10 لغات مختلفة
5. **Fallback**: يعمل حتى لو Settings فاضي

## 🔧 التطوير المستقبلي

لإضافة لغة جديدة:
1. أضف اللغة في `$allLanguages` في settings/index.blade.php
2. أضف الـ code في validation في AdminController
3. أنشئ مجلد في `lang/` بملفات الترجمة
4. أضف الـ label في booking.blade.php

## 📝 ملاحظات

- اللغات المتاحة يتم حفظها كـ JSON Array في Database
- Middleware يتحقق من اللغات المتاحة في كل طلب
- Session تحفظ اللغة المختارة للمستخدم
- يمكن إضافة لغات جديدة بسهولة

---

**تم التطوير والاختبار**: 17 فبراير 2026
**الحالة**: ✅ جاهز للإنتاج
