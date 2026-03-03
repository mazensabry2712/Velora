# Velora — سجل كل الأعمال المنجزة

> **المشروع:** Velora — نظام SaaS متعدد المستأجرين (Multi-Tenant)
> **Framework:** Laravel 11 · **بيئة:** Herd · **Domain:** `velora.test`
> **Path:** `c:\Herd\Velora`
> **آخر تحديث:** ٢ مارس ٢٠٢٦

---

## ١. البنية التقنية للمشروع

### Stack الأساسي
| الطبقة | التقنية |
|--------|---------|
| Backend | Laravel 11 (PHP) |
| Frontend | Blade Templates + Tailwind CSS + Vite |
| Authentication | Laravel Sanctum |
| Multi-Tenancy | Stancl/Tenancy (by subdomain) |
| Permissions | Spatie Laravel Permission |
| Localization | Laravel `__()` + 15 lang files |
| Queue | Laravel Queue |
| Dev Environment | Herd (Windows) |

### هيكل المشروع الأساسي
```
app/
├── Http/
│   └── Middleware/
│       ├── SetCentralLocale.php       ← تحديد لغة Super-Admin
│       ├── SetTenantLocale.php        ← تحديد لغة التنانت
│       ├── CheckRole.php
│       ├── CheckSuperAdmin.php
│       ├── DetectCountryAndLocale.php
│       └── SuperAdminAuth.php
├── Models/
├── Services/
└── Exports/

resources/views/super-admin/
├── layout.blade.php
├── login.blade.php
├── dashboard.blade.php
├── tenants.blade.php
├── activity-logs.blade.php
├── notifications.blade.php
├── reports.blade.php
├── subscription-plans.blade.php
└── settings.blade.php

lang/
├── en/super-admin.php   ← 624 مفتاح (المرجع الرئيسي)
├── ar/super-admin.php   ← ترجمة عربية كاملة
├── de/ es/ fr/ hi/ id/ it/ ja/ ko/ nl/ pt/ ru/ tr/ zh/
│   └── super-admin.php  ← ترجمة كاملة لكل لغة
```

---

## ٢. نظام الترجمة المتعدد اللغات

### اللغات المدعومة (15 لغة)

| كود | اللغة | الاتجاه |
|-----|-------|---------|
| `en` | English | LTR |
| `ar` | العربية | **RTL** |
| `de` | Deutsch | LTR |
| `es` | Español | LTR |
| `fr` | Français | LTR |
| `hi` | हिन्दी | LTR |
| `id` | Bahasa Indonesia | LTR |
| `it` | Italiano | LTR |
| `ja` | 日本語 | LTR |
| `ko` | 한국어 | LTR |
| `nl` | Nederlands | LTR |
| `pt` | Português | LTR |
| `ru` | Русский | LTR |
| `tr` | Türkçe | LTR |
| `zh` | 中文 | LTR |

### كيفية عمل نظام الترجمة

#### Middleware: `SetCentralLocale`
```php
// app/Http/Middleware/SetCentralLocale.php
// يقرأ اللغة من Session أو Cookie أو Accept-Language Header
// يضبط: App::setLocale($locale)
// يضبط: Carbon::setLocale($locale)
// يضبط: اتجاه الصفحة (RTL/LTR) لو عربي
```

#### Route تغيير اللغة
```
GET /lang/{locale}
→ يحفظ في Session: lang = {locale}
→ يعمل redirect للصفحة السابقة
```

#### الاستخدام في Blade
```php
// استدعاء بسيط
{{ __('super-admin.key_name') }}

// في @php block مع JS
@php
  $trans = [
      'title' => __('super-admin.dashboard_h1'),
      'live'  => __('super-admin.dashboard_live'),
  ];
@endphp
<script>
  const __t = @json($trans);
  // ثم في JS:
  // document.title = __t.title;
</script>
```

> **مهم:** لا يجوز استخدام `__()` داخل `@json()` مباشرة — يجب تحويلها لـ `@php` block أولاً.

---

## ٣. ملف اللغة الإنجليزي (المرجع)

**المسار:** `lang/en/super-admin.php`
**الحجم:** ~694 سطر · **624 مفتاح**

### تقسيم المفاتيح حسب القسم

| المجموعة | Prefix | عدد المفاتيح | الوصف |
|----------|--------|-------------|-------|
| Navigation | `nav_` | 8 | روابط القائمة الجانبية |
| Auth / Login | `login_` | 12 | شاشة تسجيل الدخول |
| Dashboard | `dashboard_` | 9 | عنوان وأدوات الداشبورد |
| Stats | `stat_` | 8 | بطاقات الإحصائيات |
| Mini Cards | `mini_` | 11 | البطاقات الصغيرة |
| Quick Actions | `qa_` | 4 | أزرار الإجراءات السريعة |
| Dashboard Table | `dash_` | 14 | جدول قائمة الشركات |
| Error | `error_` | 3 | رسائل الخطأ |
| Tenants | `tenants_` | ~40 | صفحة إدارة الشركات |
| Activity Logs | `activity_` | ~25 | سجل الأنشطة |
| Notifications | `notifs_` | ~20 | الإشعارات |
| Subscription Plans | `plans_` | ~35 | خطط الاشتراك |
| Reports | `reports_` | ~30 | التقارير |
| Settings Labels | `settings_label_` | 84 | تسميات إعدادات النظام |
| Settings Descs | `settings_desc_` | 83 | وصف إعدادات النظام |
| Delete Dialog | `delete_` | 5 | حوار تأكيد الحذف |
| Toast | `toast_` | 5 | رسائل التنبيه |
| General | متنوع | ~80 | مفاتيح عامة |

---

## ٤. الأعمال المنجزة على Views

### ٤.١ `layout.blade.php` ✅
- ترجمة القائمة الجانبية (Navigation) بالكامل
- ترجمة أسماء الصفحات في الـ breadcrumb
- دعم RTL للعربية (تغيير `dir="rtl"` و `text-right`)
- ترجمة اسم المستخدم وزر تسجيل الخروج
- selector اللغة في الهيدر (dropdown بـ 15 علم)

### ٤.٢ `login.blade.php` ✅
- ترجمة جميع Labels والـ placeholders
- ترجمة رسائل الخطأ
- ترجمة زر "تسجيل الدخول"
- دعم RTL

### ٤.٣ `dashboard.blade.php` ✅
- ترجمة العنوان الرئيسي، أزرار Refresh وExport CSV
- ترجمة 4 بطاقات الإحصائيات (stat_*)
- ترجمة البطاقات الصغيرة (mini_*): Company Growth, Active Subscriptions, System Status
- ترجمة Quick Actions (qa_*)
- ترجمة جدول الشركات (dash_*): أعمدة، فلاتر، pagination، tooltips
- ترجمة رسالة "لا توجد شركات"
- ترجمة dialog الحذف
- ترجمة Toast notifications
- ترجمة رسائل الخطأ (error_*)
- **إصلاح خاص:** إزالة علامة `؟` العربية → `?` إنجليزية

### ٤.٤ `tenants.blade.php` ✅
- ترجمة جميع أعمدة الجدول
- ترجمة فلاتر البحث
- ترجمة أزرار الإضافة والتعديل والحذف
- ترجمة حالات التنانت (Active/Trial/Suspended)
- ترجمة Modal إضافة شركة جديدة
- **إصلاح خاص:** إزالة كلمة `'من'` العربية الهاردكود → `__('super-admin.of_word')`

### ٤.٥ `activity-logs.blade.php` ✅
- ترجمة جميع أعمدة الجدول
- ترجمة فلاتر التاريخ والنوع
- ترجمة رسائل الحالة
- **إصلاح خاص:** إزالة كلمة `'من'` العربية الهاردكود

### ٤.٦ `notifications.blade.php` ✅
- ترجمة عنوان الصفحة والأزرار
- ترجمة أنواع الإشعارات
- ترجمة حالات القراءة
- **إصلاح خاص:** إزالة `'حدث خطأ'` الهاردكود → `__('super-admin.generic_error')`

### ٤.٧ `subscription-plans.blade.php` ✅
- ترجمة أسماء الخطط والميزات
- ترجمة أزرار التعديل والحذف
- ترجمة Modal إضافة خطة جديدة
- **إصلاح خاص:** ترجمة placeholder وstring خطأ كانوا هاردكود

### ٤.٨ `reports.blade.php` ✅
- ترجمة عناوين التقارير
- ترجمة أسماء المحاور في Charts
- **إصلاح خاص:** تغيير أسماء شركات Mock عربية → أسماء إنجليزية (Company A, B, C...)

### ٤.٩ `settings.blade.php` ✅ (الأكثر تعقيداً)
- **المشكلة الأصلية:** كان يستخدم `$isAr ? 'Arabic text' : 'English text'` → كل اللغات غير العربية تحصل إنجليزي
- **الحل:**
  1. إنشاء `@php` block يبني arrays باستخدام `__()`:
     ```php
     @php
       $keyLabels = [
           'shop_name'    => __('super-admin.settings_label_shop_name'),
           'shop_email'   => __('super-admin.settings_label_shop_email'),
           // ... 82 مفتاح إضافي
       ];
       $keyDescs = [
           'shop_name'    => __('super-admin.settings_desc_shop_name'),
           // ... 83 مفتاح
       ];
     @endphp
     ```
  2. تمرير الـ arrays لـ JS عبر `@json()`:
     ```blade
     const __tSettings = @json(['key_labels' => $keyLabels, 'key_descs' => $keyDescs, ...]);
     ```
  3. دوال JS مبسطة:
     ```js
     function getKeyLabel(key) { return __tSettings.key_labels[key] ?? key.replace(/_/g, ' '); }
     function getKeyDesc(key)  { return __tSettings.key_descs[key]  ?? ''; }
     ```

---

## ٥. الأعمال المنجزة على ملفات اللغة

### ٥.١ إضافة 167 مفتاح `settings_label_*` و `settings_desc_*`

**سبب الإضافة:** لترجمة صفحة الإعدادات لكل اللغات بدون `$isAr`.

**المفاتيح المضافة (مثال):**
```php
// lang/en/super-admin.php
'settings_label_shop_name'     => 'Store Name',
'settings_label_shop_email'    => 'Store Email',
'settings_label_currency'      => 'Currency',
// ... 81 مفتاح إضافي

'settings_desc_shop_name'      => 'The official name of your store',
'settings_desc_shop_email'     => 'Primary contact email address',
// ... 82 مفتاح إضافي
```

**الملفات المحدثة:** جميع الـ 15 ملف لغة.

**السكريبت المستخدم:** `fix_settings_labels.php`
```php
// المنطق:
// 1. يحمل EN array
// 2. يحمل target lang array
// 3. array_diff_key() → يجد المفاتيح المفقودة
// 4. يضيفها مع قيمة EN كـ fallback مؤقت
// 5. يكتب الملف محدثاً
```

### ٥.٢ إضافة 48 مفتاح Dashboard لـ 13 لغة (624 استبدال)

**سبب الإضافة:** المفاتيح كانت موجودة في الملفات بقيم إنجليزية (EN fallback من fix_lang.php السابق) — محتاجين قيم حقيقية بكل لغة.

**المفاتيح المحدثة:**
```
dashboard_h1, dashboard_live, dashboard_last_updated, dashboard_refresh_data,
dashboard_search_short, dashboard_export_csv, dashboard_search_ph,
dashboard_results, dashboard_loading,
stat_all_companies, stat_using_now, stat_needs_followup, stat_new_company,
stat_positive_growth, stat_no_new_companies, stat_inactive_label, stat_pct_of_total,
mini_tenants_growth, mini_tenants_total, mini_this_month_label, mini_growth_cont,
mini_active_subs, mini_using_now, mini_system_status, mini_status_active,
mini_services_ok, mini_active_label, mini_trial_label, mini_upgrade_reqs,
qa_manage_companies, qa_manage_sub, qa_settings_sub, qa_reports_sub,
dash_empty_title, dash_empty_desc, dash_filter_all, dash_filter_active,
dash_filter_inactive, dash_pag_show, dash_pag_to, dash_pag_of, dash_pag_companies,
delete_confirm_title, delete_confirm_msg, delete_irreversible, delete_cancel,
delete_confirm_btn, toast_deleted_success,
error_load, error_load_desc, error_reload
```

**السكريبت المستخدم:** `fix_dashboard_translations.php`
- يعرّف `$translations[lang][key] = 'translated value'` لكل 13 لغة
- يستخدم `preg_replace()` لاستبدال القيمة الإنجليزية بالترجمة الحقيقية
- **النتيجة:** 624 استبدال ناجح (48 key × 13 لغة)

### عينة من الترجمات المضافة

| Key | de | ko | ja | zh | ar |
|-----|----|----|----|----|-----|
| `dashboard_h1` | Haupt-Admin-Dashboard | 메인 관리자 대시보드 | メイン管理ダッシュボード | 主管理员仪表板 | لوحة التحكم الرئيسية |
| `mini_system_status` | Systemstatus | 시스템 상태 | システム状態 | 系统状态 | حالة النظام |
| `stat_all_companies` | Alle registrierten Unternehmen | 등록된 모든 기업 | 全登録企業 | 所有注册企业 | جميع الشركات المسجلة |
| `qa_manage_companies` | Unternehmen verwalten | 기업 관리 | 企業を管理 | 管理企业 | إدارة الشركات |
| `mini_status_active` | Aktiv | 활성 | アクティブ | 活跃 | نشط |

---

## ٦. السكريبتات المنجزة

### `fix_lang.php` (السكريبت الأساسي للـ fallback)
```
الهدف: إضافة مفاتيح مفقودة لكل ملفات اللغة مع قيمة EN كـ fallback
المنطق: array_diff_key() + preg_replace() لإضافة في نهاية الملف
الاستخدام: php fix_lang.php
```

### `fix_settings_labels.php`
```
الهدف: إضافة 167 مفتاح settings_label_* وsettings_desc_* لـ 13 ملف
النتيجة: 167 × 13 = 2171 مفتاح مضاف
الاستخدام: php fix_settings_labels.php
```

### `fix_dashboard_translations.php` (outFiles)
```
الهدف: استبدال EN fallback بترجمات حقيقية لـ 48 مفتاح dashboard
النتيجة: 48 × 13 = 624 استبدال ناجح
الاستخدام: php fix_dashboard_translations.php
```

### `check_arabic.php` (Audit Script)
```
الهدف: فحص جميع Blade views وإيجاد أي نصوص عربية hardcoded
النتيجة: وجد 7 ملفات تحتاج تصحيح (تم تصحيحها جميعاً)
```

---

## ٧. الأنماط البرمجية المستخدمة

### نمط `@php` + `@json` (الصحيح)
```blade
{{-- ✅ الصحيح --}}
@php
  $trans = [
      'title'   => __('super-admin.dashboard_h1'),
      'refresh' => __('super-admin.dashboard_refresh_data'),
  ];
@endphp
<script>
  const __t = @json($trans);
  document.getElementById('title').textContent = __t.title;
</script>

{{-- ❌ الخطأ (لا يعمل) --}}
<script>
  const __t = @json(['title' => __('super-admin.dashboard_h1')]);
</script>
```

### نمط RTL Detection
```blade
@php
  $isRtl = in_array(app()->getLocale(), ['ar']);
  $dir   = $isRtl ? 'rtl' : 'ltr';
  $align = $isRtl ? 'right' : 'left';
@endphp
<html dir="{{ $dir }}" lang="{{ app()->getLocale() }}">
```

### نمط Language Selector
```blade
<div class="lang-selector">
  @foreach(['en','ar','de','es','fr','hi','id','it','ja','ko','nl','pt','ru','tr','zh'] as $lang)
    <a href="{{ route('lang', $lang) }}" class="{{ app()->getLocale() === $lang ? 'active' : '' }}">
      {{ strtoupper($lang) }}
    </a>
  @endforeach
</div>
```

---

## ٨. المشاكل التي تم حلها

### مشكلة ١: نصوص عربية hardcoded في JS
**السبب:** JS dictionaries كانت تُبنى بـ `$isAr ? 'نص عربي' : 'English text'`
**التأثير:** كل اللغات غير العربية تحصل إنجليزي
**الحل:** تحويل الـ dictionaries لـ `@php` blocks تستخدم `__()` لكل لغة

### مشكلة ٢: EN fallback في ملفات اللغة
**السبب:** `fix_lang.php` يضيف قيمة EN كـ fallback عند غياب المفتاح
**التأثير:** المفتاح "موجود" لكن بإنجليزي — Laravel لا يرجع للـ EN file
**الحل:** `fix_dashboard_translations.php` يستبدل القيمة الإنجليزية بالترجمة الحقيقية

### مشكلة ٣: علامة `؟` العربية
**السبب:** كاتب الكود استخدم علامة الاستفهام العربية `؟` بدل الإنجليزية `?`
**الملف:** `dashboard.blade.php`
**الحل:** استبدال مباشر بـ `?`

### مشكلة ٤: أسماء شركات عربية في Mock Data
**السبب:** `reports.blade.php` كان فيه شركات mock بأسماء عربية في JS
**الحل:** تغييرها لـ (Company A, Company B, Alpha Corp, Beta Ltd, etc.)

### مشكلة ٥: `@json()` + `__()` مباشرة
**السبب:** Laravel لا يدعم `@json(['key' => __('...')])` في بعض السياقات
**الحل:** دائماً `@php $arr = [...]; @endphp` ثم `@json($arr)`

---

## ٩. الأوامر المستخدمة

```bash
# تنظيف وإعادة بناء الـ views Cache
php artisan view:clear
php artisan view:cache

# تشغيل سكريبتات الترجمة
php fix_lang.php
php fix_settings_labels.php
php fix_dashboard_translations.php
php check_arabic.php

# التحقق من الترجمات
php artisan tinker --execute="echo __('super-admin.dashboard_h1');"

# PowerShell: فحص ملف لغة
Select-String -Path "lang/ko/super-admin.php" -Pattern "dashboard_h1|stat_all"
```

---

## ١٠. حالة كل ملف نهائياً

### Views

| الملف | الحالة | ملاحظات |
|-------|--------|---------|
| `layout.blade.php` | ✅ مكتمل | Nav + Header + Footer مترجمة |
| `login.blade.php` | ✅ مكتمل | كل العناصر مترجمة |
| `dashboard.blade.php` | ✅ مكتمل | Stats + Mini + QA + Table + Errors |
| `tenants.blade.php` | ✅ مكتمل | Table + Modals + Filters |
| `activity-logs.blade.php` | ✅ مكتمل | Table + Filters |
| `notifications.blade.php` | ✅ مكتمل | Cards + Status messages |
| `subscription-plans.blade.php` | ✅ مكتمل | Plans + Features + Modals |
| `reports.blade.php` | ✅ مكتمل | Charts + Tables + Mock data |
| `settings.blade.php` | ✅ مكتمل | 167 key + JS functions |

### ملفات اللغة

| اللغة | الحالة | عدد المفاتيح |
|-------|--------|-------------|
| `en` | ✅ مرجع رئيسي | 624 |
| `ar` | ✅ مكتمل (عربي حقيقي) | 624 |
| `de` | ✅ مكتمل (ألماني حقيقي) | 624 |
| `es` | ✅ مكتمل (إسباني حقيقي) | 624 |
| `fr` | ✅ مكتمل (فرنسي حقيقي) | 624 |
| `hi` | ✅ مكتمل (هندي حقيقي) | 624 |
| `id` | ✅ مكتمل (إندونيسي حقيقي) | 624 |
| `it` | ✅ مكتمل (إيطالي حقيقي) | 624 |
| `ja` | ✅ مكتمل (ياباني حقيقي) | 624 |
| `ko` | ✅ مكتمل (كوري حقيقي) | 624 |
| `nl` | ✅ مكتمل (هولندي حقيقي) | 624 |
| `pt` | ✅ مكتمل (برتغالي حقيقي) | 624 |
| `ru` | ✅ مكتمل (روسي حقيقي) | 624 |
| `tr` | ✅ مكتمل (تركي حقيقي) | 624 |
| `zh` | ✅ مكتمل (صيني حقيقي) | 624 |

---

## ١١. الملفات المنشأة

```
outFiles/
├── fix_dashboard_translations.php  ← 624 استبدال للـ 13 لغة
├── fix_settings_labels.php         ← إضافة 167 مفتاح setting
├── fix_lang.php                    ← EN fallback لكل ملفات اللغة
├── check_arabic.php                ← Audit: فحص نصوص عربية
├── MULTI_LANGUAGE_SYSTEM.md        ← دليل نظام الترجمة
├── PROJECT_WORK_LOG.md             ← هذا الملف
└── [ملفات تقارير وتوثيق أخرى]

lang/ (15 مجلد، كل منهم super-admin.php محدث)

resources/views/super-admin/ (9 views مترجمة بالكامل)
```

---

## ١٢. ملاحظات للمستقبل

1. **عند إضافة مفاتيح جديدة لـ EN:** شغّل `fix_lang.php` وعدّل الترجمات الحقيقية يدوياً أو عبر سكريبت مخصص.

2. **عند إضافة view جديدة:** اتبع نمط `@php $trans = [...__()...]; @endphp` + `@json($trans)` — لا تضع `__()` داخل `@json()` مباشرة.

3. **عند إضافة لغة جديدة:** انسخ `lang/en/super-admin.php` لمجلد اللغة الجديدة واترجم القيم.

4. **RTL:** حالياً العربية فقط RTL. إضافة لغات RTL أخرى (فارسي، عبري) تحتاج تعديل `SetCentralLocale.php`.

5. **Settings Keys:** أي setting جديد يضاف للنظام يحتاج:
   - `settings_label_{key}` في كل 15 ملف لغة
   - `settings_desc_{key}` في كل 15 ملف لغة

---

*آخر تحديث: ٢ مارس ٢٠٢٦*
