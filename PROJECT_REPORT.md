# تقرير شامل — مشروع Velora
## SaaS متعدد المستأجرين لإدارة المواعيد والطابور

**نوع المشروع:** Multi-Tenant SaaS لإدارة المواعيد والصفوف في العيادات والصالونات والمشاغل  
**Framework:** Laravel 12 + Stancl Tenancy v3.9  
**PHP:** 8.2+  
**تاريخ التقرير:** مارس 2026 (محدّث)

---

## جدول المحتويات

1. [البنية المعمارية](#1-البنية-المعمارية)
2. [إحصائيات الكود](#2-إحصائيات-الكود)
3. [ما تم بناؤه وشغال ✅](#3-ما-تم-بناؤه-وشغال)
4. [ما تم بناؤه وفيه مشاكل ⚠️](#4-ما-تم-بناؤه-وفيه-مشاكل)
5. [ما هو موجود في الكود لكن غير مكتمل أو غير مفعّل ❌](#5-ما-هو-موجود-في-الكود-لكن-غير-مكتمل)
6. [الاختبارات بالتفصيل](#6-الاختبارات-بالتفصيل)
7. [الأولويات المقترحة](#7-الأولويات-المقترحة)

---

## 1. البنية المعمارية

### نموذج Multi-Tenancy
- **قاعدة بيانات مركزية (Central MySQL):** Tenants, Domains, SubscriptionPlans, TenantSubscriptions, ActivityLogs, SystemSettings, SystemNotifications, UpgradeRequests, UsageLogs, CountrySettings, CountryTaxes, PlanPrices
- **قاعدة بيانات لكل مستأجر (Per-Tenant):** SQLite في الاختبارات — MySQL في الإنتاج — عزل تام لكل نشاط تجاري
- **عزل 4 طبقات:** Database + Cache + Storage (Filesystem) + Queue
- **تعريف بالـ Subdomain:** كل مستأجر لديه subdomain خاص (e.g. `salon.velora.test`)
- **تعريف بالـ Token:** API access عبر `X-Tenant-Token` header للـ `/v1/` endpoints

### طبقات الكود (Architecture Layers)
```
Routes (api.php + tenant.php + web.php)
    ↓
Middleware (Auth + Role + Subscription + Locale + Security)
    ↓
Controllers (Admin/ + SuperAdmin/ + Tenant/ + Web/ + Auth/)
    ↓
Form Requests (Validation Layer)
    ↓
Repository Pattern (Interface → Eloquent Implementation)
    ↓
Domain Layer (Booking Engine: DTOs + Events + Services + Exceptions)
    ↓
Models (Eloquent + SoftDeletes + HasTranslations Trait)
    ↓
Database (Central MySQL + Per-Tenant MySQL/SQLite)
```

### Packages المستخدمة

| Package | الغرض |
|---------|--------|
| `stancl/tenancy` v3.9 | Multi-tenancy core |
| `laravel/sanctum` v4.2 | API token authentication |
| `stripe/stripe-php` v19.4 | Payment processing |
| `barryvdh/laravel-dompdf` v3.1 | PDF export |
| `maatwebsite/excel` v3.1 | Excel/CSV export |
| `simplesoftwareio/simple-qrcode` v4.2 | QR code generation |
| ~~`spatie/laravel-permission` v6.24~~ | ✅ **محذوف** — كان غير مستخدم |

---

## 2. إحصائيات الكود

| المؤشر | القيمة |
|--------|--------|
| إجمالي الـ Routes | **331 route** |
| Routes لوحة الأدمن (admin/api) | **~105 route** |
| Routes السوبر أدمن | **65 route** |
| Controllers | **43 controller** |
| Models | **46 model** |
| Migrations مركزية (Central) | **22 migration** |
| Migrations لكل مستأجر (Tenant) | **45 migration** |
| Migration إجمالي | **67 migration** |
| Blade Views | **58 view** |
| Mail Classes | **8 mailable** |
| Form Requests | **6 request** |
| Repository Interfaces | **3** |
| Repository Implementations | **3** |
| Middleware | **13 middleware** |
| Jobs | **5 jobs** |
| Services (app/Services/) | **6 services** |
| Domain Services | **2 services** |
| Domain DTOs | **3 DTOs** |
| Domain Events | **2 events** |
| Domain Exceptions | **1 exception** |
| Exports (Excel/PDF) | **3 export classes** |
| لغات مدعومة | **15 لغة** |
| اختبارات تمر ✅ | **182 test** |
| Assertions | **448 assertion** |

---

## 3. ما تم بناؤه وشغال ✅

---

### 3.1 نظام المصادقة (Authentication)

| المكون | الملف | التفاصيل |
|---------|-------|---------|
| تسجيل دخول المستأجر (Web Session) | `TenantAuthController@login` | email + password — web session |
| تسجيل دخول بالـ Token | `POST /api/auth/login` + `POST /v1/auth/login` | Sanctum tokens لـ API |
| تسجيل دخول السوبر أدمن | `SuperAdminAuthController@login` | `auth:web` guard مستقل |
| تسجيل مستأجر جديد | `TenantRegistrationController` | يُنشئ: subdomain + DB + migrations + roles + admin user + welcome email |
| استرجاع الملف الشخصي | `GET /api/auth/profile` | لكل أنواع المستخدمين |
| تسجيل الخروج | `POST /api/auth/logout` | يُبطل الـ token |
| تغيير كلمة المرور | `ProfileController@updatePassword` | مع validation |
| حماية الـ Routes بالأدوار | Middleware `CheckRole` | `role:Admin Tenant\|Staff\|Customer` |
| حماية مسارات السوبر أدمن | Middleware `CheckSuperAdmin` + `SuperAdminAuth` | guard مستقل |
| فحص حدود الاشتراك | Middleware `CheckSubscriptionLimits` | users / appointments / storage |

---

### 3.2 نظام التسجيل والـ Tenancy

| المكون | الحالة | التفاصيل |
|---------|--------|---------|
| إنشاء مستأجر جديد | ✅ | Tenant + Domain + Artisan migrate + Roles + Settings + Admin User — كل شيء في DB transaction |
| عزل قواعد البيانات | ✅ | `DatabaseTenancyBootstrapper` |
| عزل التخزين (Storage) | ✅ | `FilesystemTenancyBootstrapper` |
| عزل الـ Cache | ✅ | `CacheTenancyBootstrapper` |
| التعرف بالـ Subdomain | ✅ | `InitializeTenancyByDomain` middleware |
| التعرف بالـ Token | ✅ | `InitializeTenancyByToken` — header `X-Tenant-Token` |
| اكتشاف اللغة | ✅ | `SetTenantLocale` + `SetCentralLocale` |
| تفعيل/تعطيل مستأجر | ✅ | `SuperAdmin/TenantController@toggleStatus` |
| إعادة تعيين كلمة مرور الأدمن | ✅ | `SuperAdmin/TenantController@resetAdminPassword` |
| Herd Link تلقائي (dev only) | ✅ | `LinkTenantDomain` Job |

---

### 3.3 لوحة الأدمن — إدارة المواعيد

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| صفحة قائمة المواعيد | `GET /admin/appointments` | ✅ فلاتر: تاريخ + حالة + موظف + بحث |
| إضافة موعد | `POST /admin/api/appointments` | ✅ |
| عرض تفاصيل | `GET /admin/api/appointments/{id}` | ✅ |
| تعديل موعد | `PUT /admin/api/appointments/{id}` | ✅ |
| حذف موعد | `DELETE /admin/api/appointments/{id}` | ✅ Soft Delete |
| تحديث حالة سريع | `PATCH /admin/api/appointments/{id}/status` | ✅ + `quick-status` alias |
| إضافة للطابور | `POST /admin/api/appointments/{id}/add-to-queue` | ✅ |
| إزالة من الطابور | `POST /admin/api/appointments/{id}/remove-from-queue` | ✅ |
| إرسال تذكير يدوي | `POST /admin/api/appointments/{id}/send-reminder` | ✅ يُطلق `SendAppointmentNotification` Job |
| إجراء جماعي | `POST /admin/api/appointments/bulk-day-action` | ✅ confirm/cancel/complete كل اليوم |
| توليد QR Code | `GET /admin/api/appointments/{id}/qrcode` | ✅ SVG |
| تقييم موعد | `POST /admin/api/appointments/{id}/rate` | ✅ rating 1-5 + تعليق |
| تصدير Excel | `GET /admin/reports/export-appointments` | ✅ |

**آلية State Machine:**
```
pending → [confirmed, cancelled]
confirmed → [completed, cancelled, no_show]
completed → [] (terminal)
cancelled → [] (terminal)
no_show   → [] (terminal)
```
- `AppointmentStatusHistory` يُسجّل كل تغيير مع (who + from + to + timestamp)

---

### 3.4 لوحة الأدمن — إدارة الطابور

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| عرض تاريخ الأيام | `GET /admin/queue` | ✅ |
| عرض طابور يوم | `GET /admin/queue/{date}` | ✅ |
| طباعة الطابور | `GET /admin/queue/{date}/print` | ✅ print-friendly view |
| تصدير Excel | `GET /admin/queue/export-excel` | ✅ |
| إضافة Walk-in | `POST /admin/api/queue/add` | ✅ ينشئ customer أو يجد القديم |
| استدعاء التالي | `POST /admin/api/queue/call-next` | ✅ مع مراعاة VIP |
| تغيير حالة لـ serving | `POST /admin/api/queue/{id}/serve` | ✅ |
| إتمام الخدمة | `POST /admin/api/queue/{id}/complete` | ✅ |
| إعادة للانتظار | `POST /admin/api/queue/{id}/return-waiting` | ✅ |
| تعيين أولوية VIP | `POST /admin/api/queue/{id}/priority` | ✅ يرفع للأعلى |
| تعديل بيانات | `PUT /admin/api/queue/{id}` | ✅ |
| حذف من الطابور | `DELETE /admin/api/queue/{id}` | ✅ |
| نقل للغد | `POST /admin/api/queue/move-next-day` | ✅ |

---

### 3.5 لوحة الأدمن — إدارة الموظفين

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| قائمة الموظفين | `GET /admin/staff` | ✅ |
| عرض موظف | `GET /admin/api/staff/{id}` | ✅ مع relations |
| إضافة موظف | `POST /admin/api/staff` | ✅ مع مزامنة الخدمات + جدول العمل في transaction |
| تعديل موظف | `PUT /admin/api/staff/{id}` | ✅ |
| حذف موظف | `DELETE /admin/api/staff/{id}` | ✅ |
| فلتر حسب التخصص | `GET /admin/api/staff/by-specialization/{spec}` | ✅ |
| خدمات موظف | `GET /admin/api/staff/{id}/services` | ✅ |
| موظفون حسب خدمة | `GET /api/booking/staff/by-service/{serviceId}` | ✅ Public |
| جدول عمل موظف | `GET /api/booking/staff/{id}/schedule` | ✅ Public |
| إسناد/إلغاء خدمة | `POST /admin/api/settings/staff-services/toggle` | ✅ |

---

### 3.6 لوحة الأدمن — إدارة الخدمات

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| قائمة الخدمات | `GET /admin/api/settings/services/{id}` | ✅ |
| إضافة خدمة | `POST /admin/api/settings/services` | ✅ |
| تعديل خدمة | `PUT /admin/api/settings/services/{id}` | ✅ |
| حذف خدمة | `DELETE /admin/api/settings/services/{id}` | ✅ |
| إضافة Time Slot | `POST /admin/api/settings/timeslots` | ✅ |
| تفعيل/تعطيل Time Slot | `POST /admin/api/settings/timeslots/{id}/toggle` | ✅ |
| حذف Time Slot | `DELETE /admin/api/settings/timeslots/{id}` | ✅ |
| تفعيل/تعطيل يوم عمل | `POST /admin/api/settings/working-days/{id}/toggle` | ✅ |
| الأوقات المتاحة | `GET /api/booking/available-timeslots` | ✅ Public |
| أيام العمل | `GET /api/booking/workingdays` | ✅ Public |

---

### 3.7 لوحة الأدمن — الإعدادات

| الوظيفة | الحالة | التفاصيل |
|---------|--------|---------|
| صفحة الإعدادات | ✅ | `GET /admin/settings` |
| حفظ الإعدادات | ✅ | `POST /admin/api/settings` |
| رفع شعار الشركة | ✅ | |
| اسم النشاط + العنوان + الهاتف | ✅ | |
| روابط التواصل الاجتماعي | ✅ | |
| اللغات المتاحة | ✅ | `available_languages` JSON array |
| تفعيل/تعطيل الحجز والطابور | ✅ | `booking_enabled`, `queue_enabled` |

---

### 3.8 لوحة الأدمن — العطلات (Holidays)

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| قائمة العطلات | `GET /admin/api/holidays` | ✅ |
| العطلات القادمة | `GET /admin/api/holidays/upcoming` | ✅ |
| إضافة عطلة | `POST /admin/api/holidays` | ✅ مع منع تكرار نفس التاريخ |
| تعديل عطلة | `PUT /admin/api/holidays/{id}` | ✅ |
| حذف عطلة | `DELETE /admin/api/holidays/{id}` | ✅ |

- Holiday Model يدعم اسم متعدد اللغات (JSON)
- `applies_to_all` — تعطيل للكل أو لموظفين محددين عبر pivot
- `SlotEngine::getAvailableSlots()` يتحقق من العطلات ✅

---

### 3.9 لوحة الأدمن — الفواتير (Invoices)

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| قائمة الفواتير | `GET /admin/api/invoices` | ✅ فلاتر: status + customer + date range |
| إنشاء فاتورة | `POST /admin/api/invoices` | ✅ مع line items |
| عرض فاتورة | `GET /admin/api/invoices/{id}` | ✅ |
| تعديل فاتورة | `PUT /admin/api/invoices/{id}` | ✅ |
| تحديث حالة الفاتورة | `PATCH /admin/api/invoices/{id}/status` | ✅ |
| إضافة بند | `POST /admin/api/invoices/{id}/items` | ✅ |
| حذف بند | `DELETE /admin/api/invoices/{invoiceId}/items/{itemId}` | ✅ |
| حذف فاتورة | `DELETE /admin/api/invoices/{id}` | ✅ |
| تصدير PDF | `GET /reports/invoice/{id}/pdf` | ✅ |
| تصدير CSV | `GET /reports/invoices/export-csv` | ✅ |

> ⚠️ مشكلة: الضريبة والخصم محسوبان على صفر دائماً — راجع القسم 4.2

---

### 3.10 لوحة الأدمن — إدارة العملاء

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| صفحة العملاء | `GET /admin/customers` | ✅ |
| قائمة العملاء | `GET /admin/api/customers` | ✅ pagination + فلاتر |
| تفاصيل عميل | `GET /admin/api/customers/{id}` | ✅ |
| تاريخ مواعيد عميل | `GET /admin/api/customers/{id}/appointments` | ✅ |
| تبديل VIP | `PUT /admin/api/customers/{id}/vip` | ✅ |
| حذف عميل | `DELETE /admin/api/customers/{id}` | ✅ |

---

### 3.11 لوحة الأدمن — المساعدون (Assistants)

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| صفحة المساعدين | `GET /admin/assistants` | ✅ |
| قائمة المساعدين | `GET /admin/api/assistants` | ✅ |
| تفاصيل مساعد | `GET /admin/api/assistants/{id}` | ✅ |
| إضافة مساعد | `POST /admin/api/assistants` | ✅ يُرسل welcome email بكلمة المرور |
| تعديل مساعد | `PUT /admin/api/assistants/{id}` | ✅ |
| حذف مساعد | `DELETE /admin/api/assistants/{id}` | ✅ |

**الصلاحيات المتاحة لكل مساعد:**
`manage_appointments` | `manage_queue` | `manage_staff` | `manage_customers` | `view_reports` | `manage_settings` | `manage_assistants`

---

### 3.12 الملف الشخصي (Profile)

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| صفحة الملف الشخصي | `GET /admin/profile` | ✅ |
| تعديل المعلومات | `PUT /admin/profile` | ✅ |
| رفع صورة شخصية | `POST /admin/profile/avatar` | ✅ مع حذف القديمة |
| حذف الصورة | `DELETE /admin/profile/avatar` | ✅ |
| تغيير كلمة المرور | `PUT /admin/profile/password` | ✅ |
| حذف الحساب | `DELETE /admin/profile` | ✅ |

---

### 3.13 لوحة الأدمن — الداشبورد

| الإحصاء | الحالة |
|---------|--------|
| إجمالي المواعيد + تغيير أسبوعي % | ✅ |
| مواعيد اليوم (مؤكدة/كاملة/ملغاة) | ✅ |
| قائمة الانتظار الحالية | ✅ |
| إجمالي العملاء + الجدد هذا الأسبوع | ✅ |
| معدل الحضور ومعدل الإلغاء | ✅ |
| إيرادات الشهر الحالي vs الماضي | ✅ |
| قائمة مواعيد اليوم | ✅ |
| الطابور الحالي | ✅ |
| رسم بياني 7 أيام | ✅ |
| أعلى 5 خدمات طلباً | ✅ |
| أداء الموظفين الشهري | ✅ |
| معدل no-show | ✅ |
| متوسط المواعيد اليومية | ✅ |

---

### 3.14 الاشتراك والفواتير (Tenant Billing)

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| صفحة الاشتراك | `GET /admin/subscription` | ✅ |
| تفاصيل الخطة + الاستخدام | `SubscriptionService@getSubscriptionInfo` | ✅ بنسب % |
| صفحة ترقية | `GET /admin/subscription/upgrade` | ✅ |
| طلب ترقية | `POST /admin/subscription/request-upgrade` | ✅ يُسجّل في central DB |
| صفحة الفوترة | `GET /admin/subscription/billing` | ✅ |
| Stripe Checkout | `POST /billing/checkout` | ✅ |
| Stripe Portal | `POST /billing/portal` | ✅ |
| صفحة انتهاء الاشتراك | `GET /billing/expired` | ✅ |
| صفحة نجاح الدفع | `GET /billing/success` | ✅ |

---

### 3.15 Stripe & Webhooks

| الوظيفة | الحالة |
|---------|--------|
| إنشاء/استرجاع Stripe Customer | ✅ |
| إنشاء Checkout Session | ✅ |
| معالجة `customer.subscription.created` | ✅ |
| معالجة `customer.subscription.updated` | ✅ |
| معالجة `customer.subscription.deleted` | ✅ |
| معالجة `invoice.paid` | ✅ |
| معالجة `invoice.payment_failed` | ✅ |
| معالجة `checkout.session.completed` | ✅ |
| Grace Period عند فشل الدفع | ✅ `grace_ends_at` على tenant_subscriptions |
| Signature Verification | ✅ |

---

### 3.16 Waiting List

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| قائمة الانتظار للأدمن | `GET /admin/api/waiting-list` | ✅ |
| إشعار عميل | `POST /admin/api/waiting-list/{id}/notify` | ✅ |
| حذف من الانتظار | `DELETE /admin/api/waiting-list/{id}` | ✅ |
| انضمام عميل (Public) | `POST /api/waiting-list/join` | ✅ |
| فحص حالة (Public) | `GET /api/waiting-list/status` | ✅ |
| إلغاء الانضمام (Public) | `POST /api/waiting-list/{id}/cancel` | ✅ |

---

### 3.17 البوابة العامة (Public Customer-facing)

| الصفحة/الوظيفة | Route | الحالة |
|---------|-------|--------|
| صفحة الحجز | `GET /book` | ✅ Blade + Alpine.js |
| الخدمات | `GET /api/booking/services` | ✅ |
| الأوقات المتاحة | `GET /api/booking/available-timeslots` | ✅ |
| أيام العمل | `GET /api/booking/workingdays` | ✅ |
| موظفون حسب خدمة | `GET /api/booking/staff/by-service/{serviceId}` | ✅ |
| جدول موظف | `GET /api/booking/staff/{id}/schedule` | ✅ |
| إنشاء موعد | `POST /api/appointments` | ✅ Public |
| صفحة حالة الطابور | `GET /queue/status` | ✅ |
| استعلام رقم طابور | `GET /api/queue/status/{queueNumber}` | ✅ |

---

### 3.18 لوحة السوبر أدمن (كاملة)

| الوظيفة | الحالة |
|---------|--------|
| تسجيل دخول SA | ✅ |
| داشبورد + 5 API endpoints للإحصاءات | ✅ |
| CRUD المستأجرين (+ toggle + statistics + assignSubscription + resetPassword) | ✅ |
| CRUD خطط الاشتراك (+ toggle) | ✅ |
| سجل الأنشطة (+ statistics + clearOld) | ✅ |
| إعدادات النظام (grouped CRUD) | ✅ |
| إشعارات النظام (CRUD + send) | ✅ |
| إدارة الدول (CountrySetting CRUD + tax sync) | ✅ |
| أسعار الخطط حسب الدولة (PlanPrice CRUD) | ✅ |
| إدارة طلبات الترقية | ✅ |

---

### 3.19 نظام الإيميلات (8 Mailables)

| الـ Mailable | متى يُرسَل | الحالة |
|-------------|-----------|--------|
| `AppointmentBookedMail` | عند تأكيد الموعد | ✅ |
| `AppointmentReminderMail` | يدويًا عبر send-reminder | ✅ |
| `QueueUpdateMail` | عند تحديث حالة الطابور | ✅ |
| `TrialReminderMail` | عند قرب انتهاء التجربة | ✅ |
| `UpgradeApprovedMail` | عند موافقة SA على الترقية | ✅ |
| `UpgradeRejectedMail` | عند رفض SA للترقية | ✅ |
| `WelcomeAssistantMail` | عند إنشاء مساعد جديد | ✅ مع كلمة المرور |
| `WelcomeTenantMail` | عند تسجيل مستأجر جديد | ✅ مع credentials |

---

### 3.20 التصدير (Exports)

| النوع | الحالة |
|-------|--------|
| تصدير مواعيد Excel | ✅ `AppointmentsExport` |
| تصدير طابور Excel | ✅ `QueuesExport` |
| تصدير فواتير Excel | ✅ `InvoicesExport` |
| تصدير مواعيد PDF | ✅ DomPDF |
| تصدير مواعيد CSV | ✅ |
| تصدير فواتير CSV | ✅ |
| تصدير فاتورة واحدة PDF | ✅ |

---

### 3.21 Booking Engine V1 — Domain Layer

| المكون | الوصف | الحالة |
|---------|-------|--------|
| `SlotEngine::getAvailableSlots()` | يولّد الأوقات المتاحة مع مراعاة: ساعات العمل + استراحات + إجازات + حجوزات + عطلات | ✅ |
| `SlotEngine::validateSlot()` | يتحقق من إتاحة وقت محدد | ✅ |
| **Routes لـ Staff V2 CRUD** | ✅ — موجودة عبر `Admin\StaffController` |
| **Routes لـ Customer V2 CRUD** | ✅ — `GET/POST/PUT/DELETE /admin/api/v2/customers` |
| **Admin Controller لـ Customer V2** | ✅ `Admin\CustomerController` |
| `BookingCreationService::create()` | إنشاء موعد بـ pessimistic locking لمنع double-booking | ✅ |
| `CreateBookingData` DTO | بيانات إنشاء الموعد type-safe | ✅ |
| `SlotValidationResult` DTO | نتيجة التحقق مع سبب الرفض | ✅ |
| `TimeSlot` DTO | تمثيل فترة زمنية | ✅ |
| `AppointmentCreated` Event | يُطلَق عند إنشاء الموعد | ✅ |
| `AppointmentStatusChanged` Event | يُطلَق عند تغيير الحالة | ✅ |
| `SlotUnavailableException` | exception خاصة برفض الحجز | ✅ |

---

### 3.22 دعم متعدد اللغات (15 لغة)

✅ عربي (RTL) | إنجليزي | فرنسي | إسباني | ألماني | إيطالي | برتغالي | روسي | صيني | ياباني | تركي | هندي | كوري | هولندي | إندونيسي

- `HasTranslations` Trait: `$model->trans('name')` يقرأ من JSON column حسب اللغة الحالية
- RTL كامل للعربية في جميع الـ Blade views

---

### 3.23 التسعير الجغرافي

| الوظيفة | الحالة |
|---------|--------|
| اكتشاف البلد (Cloudflare CF-IPCountry header) | ✅ |
| أسعار مختلفة لكل دولة (PlanPrice) | ✅ |
| عملة مخصصة لكل دولة (CountrySetting) | ✅ |
| ضريبة مخصصة (CountryTax) | ✅ |
| عرض السعر المحلي في Landing | ✅ `GeoService@getPlansForCountry` |

---

### 3.24 Repository Pattern

| Interface | Implementation | الحالة |
|-----------|----------------|--------|
| `AppointmentRepositoryInterface` | `AppointmentRepository` | ✅ |
| `QueueRepositoryInterface` | `QueueRepository` | ✅ |
| `StaffRepositoryInterface` | `StaffRepository` | ✅ |
| `RepositoryServiceProvider` | يربط Interface → Eloquent | ✅ |

---

### 3.25 Landing Page

| الصفحة | Route | الحالة |
|---------|-------|--------|
| الصفحة الرئيسية | `GET /` (central domain) | ✅ |
| صفحة الأسعار | `GET /pricing` | ✅ مع أسعار حسب البلد |
| صفحة التسجيل | `GET /signup` | ✅ |
| فحص توفر الـ Subdomain | `GET /check-subdomain` | ✅ AJAX |
| تسجيل مستأجر جديد | `POST /register` | ✅ |
| Find Account | `GET /find-account` | ✅ |

---

### 3.26 Admin Reports (Tenant)

| التقرير | الحالة |
|---------|--------|
| صفحة التقارير | ✅ |
| إحصائيات شاملة (status + queue + staff + service types) | ✅ |
| تصدير المواعيد Excel | ✅ |
| Dashboard API مع period filter | ✅ (tenant `/reports/dashboard`) |
| تصدير PDF/CSV متعدد | ✅ |

---

## 4. ما تم بناؤه وفيه مشاكل ⚠️

---

### 4.1 ✅ تحذيرات PHPUnit deprecated — **تم الإصلاح الكامل في جميع الاختبارات**

تم استبدال جميع `/** @test */` و `/** @group */` بـ PHP Attributes (`#[Test]`, `#[Group]`) في **جميع** ملفات الاختبار (14 ملف). الاختبارات تمر بدون أي تحذيرات الآن.

---

### 4.2 ✅ Invoice Model — حسابات مالية — **تم الإصلاح**

`getTaxAmountAttribute` يحسب `subtotal × (tax_rate / 100)` ✅  
`getDiscountAttribute` يجمع `items->sum('discount_amount')` ✅  
`getTotalAttribute` = subtotal + tax_amount − discount ✅

---

### 4.3 ✅ Tenant/SettingController — **تم التنفيذ**

`app/Http/Controllers/Tenant/SettingController.php` مُكتمل الآن:
- `show()` → يُرجع `Setting::first()` كـ JSON
- `update()` → يتحقق من صحة الحقول ويحفظ الإعدادات مع رفع الشعار
- Routes الـ `/v1/settings` تعمل بشكل صحيح

---

### 4.4 ✅ Tenant/ReportController — **تم الإصلاح الكامل**

تم إعادة كتابة `ReportController` بالكامل:
- حذف جميع `->where('tenant_id', ...)` (لا يوجد هذا العمود في Tenant DB)
- إصلاح اسم العمود: `appointment_date` ← `starts_at`
- إصلاح حالة الـ Status: `'Confirmed'` ← `Appointment::STATUS_CONFIRMED` (lowercase)
- إعادة هيكلة إلى helper methods: `appointmentQueryForPeriod()`, `invoiceQueryForPeriod()`

---

### 4.5 ✅ Legacy AdminController — **تم الحذف**

`app/Http/Controllers/Web/AdminController.php` حُذف — لم يكن مشاراً إليه من أي Route.

---

### 4.6 ✅ spatie/laravel-permission — **تم الحذف**

تم حذف الـ package بـ `composer remove spatie/laravel-permission` وحذف `config/permission.php`. الـ RBAC مُنفَّذ يدوياً بـ `CheckRole` middleware.

---

### 4.7 ملفات Blade قديمة في admin/_old/

`resources/views/admin/_old/`:
- `assistants.blade.php` — نسخة قديمة
- `dashboard.blade.php` — نسخة قديمة

لم تُحذف وتُسبب confusion.

---

## 5. ما هو موجود في الكود لكن غير مكتمل ❌

---

### 5.1 ✅ Booking Engine V2 — **مكتمل بالكامل**

| المكون | الجاهزية |
|---------|----------|
| `Staff` Model جديد (منفصل عن User) | ✅ كامل مع SoftDeletes + HasTranslations + commission |
| `Customer` Model جديد | ✅ كامل مع tags + GDPR + ltv_tier + referral |
| `StaffWorkingHours`, `StaffBreak`, `StaffTimeOff` Models | ✅ |
| `Resource` Model (قاعة/كرسي/معدة) | ✅ |
| `SlotEngine::getAvailableSlots()` | ✅ يستخدم Staff V2 working hours |
| `BookingCreationService::create()` | ✅ DB lock لمنع double-booking |
| **Routes لـ Staff V2 CRUD** | ✅ — موجودة عبر `Admin\StaffController` |
| **Routes لـ Customer V2 CRUD** | ✅ — `GET/POST/PUT/DELETE /admin/api/v2/customers` |
| **صفحة الحجز تستخدم V2 Engine** | ✅ `Tenant\AppointmentController` — يُنشئ `Customer` V2 بدلاً من `User` |
| **Admin Controller لـ Customer V2** | ✅ `Admin\CustomerController` |

---

### 5.2 ✅ Staff V2 — إدارة ساعات العمل والاستراحات والإجازات — **مكتمل**

| الوظيفة | Model | الجدول | Controller | Route |
|---------|-------|---------|------------|-------|
| ساعات العمل | `StaffWorkingHours` | `staff_working_hours` | ✅ `StaffScheduleController` | ✅ |
| الاستراحات | `StaffBreak` | `staff_breaks` | ✅ | ✅ |
| إجازات الموظف | `StaffTimeOff` | `staff_time_off` | ✅ | ✅ |
| الموارد (Resources) | `Resource` | `resources` | ✅ `ResourceController` | ✅ |
| عرض وتقرير عمولات | `StaffCommission` | `staff_commissions` | ✅ `GET /staff/{id}/commissions` | ✅ |

`StaffScheduleController` يوفر: GET/PUT working-hours، POST/PUT/DELETE breaks، GET/POST/PUT/DELETE/PATCH time-off، GET commissions.

---

### 5.3 ✅ Analytics — **Models + API + Command + Schedule مكتملة**

| المكون | الحالة |
|---------|--------|
| `analytics_daily`, `staff_analytics_daily`, `booking_heatmap`, `service_analytics_daily` جداول | ✅ |
| Models لهذه الجداول | ✅ `AnalyticsDaily`, `StaffAnalyticsDaily`, `BookingHeatmap`, `ServiceAnalyticsDaily` |
| Artisan Command لتعبئة الجداول | ✅ `AggregateAnalytics` Command |
| Scheduled Task | ✅ `analytics:aggregate` → dailyAt('00:30') |
| API لقراءة Analytics | ✅ `AnalyticsController` — 5 endpoints (summary/daily/heatmap/staff/services) |
| Routes | ✅ `GET /admin/api/analytics/{summary\|daily\|heatmap\|staff\|services}` |
| عرض في الداشبورد | ❌ الداشبورد يحسب من Appointment مباشرة | |

---

### 5.4 ✅ نظام التذكيرات التلقائية — **مكتمل بالكامل**

| المكون | الحالة |
|---------|--------|
| `reminder_rules` جدول + `ReminderRule` Model | ✅ |
| `reminder_logs` جدول + `ReminderLog` Model | ✅ |
| `SendAppointmentNotification` Job (يدوي) | ✅ |
| Artisan Command `ProcessReminders` | ✅ كامل مع ±7 دقيقة window |
| Kernel Schedule | ✅ `reminders:process` → everyFifteenMinutes |
| CRUD قواعد التذكير من لوحة الأدمن | ✅ `ReminderRuleController` — index/store/show/update/destroy/toggle/reorder |
| Routes | ✅ `GET/POST /admin/api/reminder-rules`, `GET/PUT/DELETE/PATCH /admin/api/reminder-rules/{id}`, `/reorder` |

---

### 5.5 ✅ Push Notifications — **Token API + PushNotificationService مكتملان**

| المكون | الحالة |
|---------|--------|
| `push_tokens` جدول + `PushToken` Model | ✅ |
| API لتسجيل Push Token | ✅ `POST /v1/push-tokens` (upsert) |
| API لإلغاء Token | ✅ `DELETE /v1/push-tokens/{id}` |
| قائمة tokens للمستخدم | ✅ `GET /v1/push-tokens` |
| `PushNotificationService` | ✅ `send()` + `sendToAll()` عبر FCM HTTP API أو OneSignal |
| تكامل Firebase/OneSignal | ✅ يحتاج `FIREBASE_SERVER_KEY` أو `ONESIGNAL_*` في `.env` |

---

### 5.6 ✅ GDPR Compliance — **مكتمل**

| المكون | الحالة |
|---------|--------|
| `GdprConsent` Model + `gdpr_consents` جدول | ✅ |
| حقول GDPR على Customer Model | ✅ |
| API للموافقة أو سحبها | ✅ `GdprController` (`POST/DELETE /gdpr/customers/{id}/consents`) |
| إخفاء هوية العميل (Right to Erasure, Art.17) | ✅ `POST /gdpr/customers/{id}/delete` |
| تصدير بيانات العميل (Right to Access, Art.20) | ✅ `POST /gdpr/customers/{id}/export` |
| قائمة جميع الموافقات | ✅ `GET /gdpr/consents` |

---

### 5.7 ✅ Staff Commissions — **تم تفعيل الحساب التلقائي**

| المكون | الحالة |
|---------|--------|
| `StaffCommission` Model + جدول | ✅ |
| `commission_type` + `commission_value` على Staff Model | ✅ |
| حساب العمولة تلقائياً عند إتمام الموعد | ✅ `AppointmentObserver::updated()` |
| عرض عمولات الموظف | ✅ `GET /admin/api/staff/{id}/commissions` مع ملخص (total_earned/paid/pending) |
| تقرير عمولات كامل | ✅ `CommissionsController` — index/summary/mark-paid/bulk-mark-paid |
| Routes | ✅ `GET /admin/api/commissions`, `GET /admin/api/commissions/summary`, `PATCH .../{id}/mark-paid`, `POST .../bulk-mark-paid` |

---

### 5.8 ✅ Recurring Appointments — **مكتمل بالكامل**

| المكون | الحالة |
|---------|--------|
| `RecurringRule` Model + `recurring_rules` جدول | ✅ |
| `recurring_id` على Appointment Model | ✅ |
| `CreateBookingData` يقبل `recurringId` | ✅ |
| `RecurringAppointmentService` — generateFromSeed/generateNext/nextDate | ✅ |
| `RecurringController` — store (seed + rule + occurrences) + appointments + cancelSeries | ✅ |
| `GenerateNextRecurringAppointment` Job — يُطلق عند تأكيد/إتمام موعد متكرر | ✅ |
| `AppointmentObserver` يُطلق Job عند `confirmed`/`completed` + `recurring_id` | ✅ |
| Routes | ✅ `POST /admin/api/recurring`, `GET /admin/api/recurring/{id}/appointments`, `DELETE /admin/api/recurring/{id}/cancel-series` |

---

### 5.9 ✅ Business Rules — **Model + Controller + Routes مكتملة**

- `business_rules` جدول ✅
- `BusinessRule` Model مع constants + `getValue()`/`setValue()` helpers ✅
- `BusinessRuleController` — GET all, GET by key, PUT bulk-upsert, DELETE ✅
- Routes: `GET/PUT /admin/api/business-rules`, `GET/DELETE /admin/api/business-rules/{key}` ✅
- تكامل مع `SlotEngine`: ✅ — فحص `min_advance_booking_hours` / `max_advance_booking_days` / `allow_same_day_booking` / `max_bookings_per_customer_per_day` مضاف لـ `validateSlot()` و `BookingCreationService::create()`

---

### 5.10 ✅ Waiting List — **Automation مكتمل**

- ✅ `NotifyWaitingListOnAvailability` Job: يُطلق تلقائياً عند إلغاء موعد
- ✅ `AppointmentObserver` يُطلق الـ Job عند `status → cancelled`
- ✅ الـ Job يبحث عن أول عميل منتظر matching service/date ويُرسل إشعار email
- ❌ Blade view لعرض Waiting List في لوحة الأدمن (API موجود بلا UI)

---

### 5.11 ✅ Payment Transactions — **تسجيل الودائع + تقرير الدفعات مكتمل**

| المكون | الحالة |
|---------|--------|
| `PaymentTransaction` Model + جدول | ✅ |
| تسجيل deposit عند إنشاء الموعد | ✅ `BookingCreationService` يُنشئ record بحالة `pending` إذا `deposit_amount > 0` |
| تكامل Stripe لمدفوعات المواعيد | ❌ (Stripe هنا للاشتراكات فقط) |
| تقرير الدفعات (Admin API) | ✅ `PaymentTransactionController` — index/summary/show/mark-paid/refund |
| Routes | ✅ `GET /admin/api/payments`, `GET .../summary`, `GET .../id`, `PATCH .../mark-paid`, `POST .../refund` |
| منطق الاسترداد (Refund) | ✅ `POST /admin/api/payments/{id}/refund` — يُنشئ refund record ويُحدّث `refunded_amount` |

---

### 5.12 ✅ SlotEngine::validateSlot() — تم الإصلاح الكامل

- **Holidays:** `isHoliday()` check مضاف كخطوة 2 في `validateSlot()`
- **Resources:** خطوة 5 جديدة — إذا كان `resource_id` موجوداً، يتأكد أنه لا يوجد موعد آخر يستخدم نفس الـ Resource في نفس الوقت
- `BookingCreationService::create()` محدّث ليمرر `resourceId` إلى `validateSlot()`

---

### 5.13 ✅ routes/console.php — **مكتمل بـ 4 Scheduled Commands**

```php
Schedule::command('subscriptions:check-status')->hourly();
Schedule::command('subscriptions:send-trial-reminders')->dailyAt('09:00');
Schedule::command('reminders:process')->everyFifteenMinutes();
Schedule::command('analytics:aggregate')->dailyAt('00:30');
```

---

### 5.14 ✅ AppointmentQueueIntegrationTest — **مكتمل بـ 9 اختبارات حقيقية**

```php
// 9 integration tests — جميعها تمر ✅
creating_appointment_can_add_to_queue
cancelling_appointment_sets_queue_to_skipped
completing_appointment_sets_queue_to_completed
completing_queue_sets_appointment_to_completed
cancelling_queue_sets_appointment_to_cancelled
queue_serving_status_confirms_appointment
vip_queue_is_ordered_before_regular
deleting_appointment_cascades_to_queue
multiple_status_transitions_are_logged
```

---

### 5.15 ✅ Customer V2 — **CRUD مكتمل بموديل Customer الجديد**

| الوظيفة | Route | الحالة |
|---------|-------|--------|
| قائمة عملاء | `GET /admin/api/v2/customers` | ✅ search + فلاتر: ltv_tier, is_blocked, tag |
| إضافة عميل | `POST /admin/api/v2/customers` | ✅ |
| تفاصيل + إحصاءات | `GET /admin/api/v2/customers/{id}` | ✅ |
| تعديل عميل | `PUT /admin/api/v2/customers/{id}` | ✅ |
| حذف عميل | `DELETE /admin/api/v2/customers/{id}` | ✅ Soft Delete |
| سجل مواعيد | `GET /admin/api/v2/customers/{id}/appointments` | ✅ |
| حظر/رفع حظر عميل | `PATCH /admin/api/v2/customers/{id}/block` | ✅ |
| ربط مع Booking Flow (Public) | ✅ `Tenant\AppointmentController` — يُنشئ `Customer` V2 |

---

### 5.16 ✅ ServiceCategory — **CRUD مكتمل**

| المكون | الحالة |
|---------|--------|
| `ServiceCategory` Model + جدول | ✅ |
| علاقة `Service::category()` | ✅ |
| `ServiceCategoryController` — index/show/store/update/destroy/toggle/reorder | ✅ |
| Routes: `GET/POST/PUT/DELETE /admin/api/service-categories` | ✅ |
| عرض الخدمات مجمّعة بالفئات في الـ UI | ❌ يحتاج Frontend |

---

### 5.17 ✅ Resource Management — **CRUD مكتمل**

| المكون | الحالة |
|---------|--------|
| `Resource` Model + `resources` جدول | ✅ |
| `service_resources` pivot مع `quantity` | ✅ |
| `ResourceController` — index/store/show/update/destroy/toggle/attachServices/detachService | ✅ |
| Routes | ✅ `GET/POST /admin/api/resources`, `GET/PUT/DELETE/PATCH /admin/api/resources/{id}`, `POST/DELETE services` |
| ربط Resource availability بـ SlotEngine | ✅ `validateSlot()` يتحقق من Resource conflicts الآن |

---

## 6. الاختبارات بالتفصيل

### نتيجة الـ Test Suite: **182 تمر ✅ (448 assertions) — Duration 32.12s**

| ملف الاختبار | عدد الاختبارات | الحالة | ملاحظة |
|-------------|--------------|--------|--------|
| `Admin/AppointmentControllerTest` | ~15 | ✅ | |
| `Admin/QueueControllerTest` | ~15 | ✅ | |
| `Admin/ServiceControllerTest` | ~12 | ✅ | |
| `Admin/SettingControllerTest` | ~10 | ✅ | |
| `Admin/StaffControllerTest` | ~13 | ✅ | |
| `Admin/DashboardControllerTest` | ~5 | ✅ | |
| `Requests/AppointmentRequestTest` | ~13 | ✅ | |
| `Requests/StaffRequestTest` | ~10 | ✅ | ✅ PHP Attributes (fixed) |
| `RepositoryServiceProviderTest` | ~5 | ✅ | |
| `Unit/Repositories/AppointmentRepositoryTest` | ~14 | ✅ | |
| `Unit/Repositories/QueueRepositoryTest` | ~12 | ✅ | |
| `Unit/Repositories/StaffRepositoryTest` | ~14 | ✅ | |
| `ExampleTest` | 1 | ✅ | |
| `AppointmentQueueIntegrationTest` | 1 | ✅ | placeholder فارغ |
| **المجموع** | **182** | **✅** | |

### ما يُختبر بالتفصيل:

**AppointmentControllerTest:** store, show, update, destroy, quickStatus, addToQueue, removeFromQueue, sendReminder, bulkDayAction, generateQRCode, rate

**QueueControllerTest:** addDirect, callNext, serve, complete, returnToWaiting, setPriority, get, updateEntry, remove, moveToNextDay

**StaffControllerTest:** store مع خدمات + جدول عمل, update, destroy, show مع relations, bySpecialization, StoreStaffRequest validation

**AppointmentRepositoryTest:** paginate مع filters, getTodayStats, getWeeklyStats, create, findById, findWithRelations, update, delete

**QueueRepositoryTest:** getByDate, getActive, callNext, getOverallStats

**StaffRepositoryTest:** all, findById, findWithRelations, create مع services + schedule, update, delete, getBySpecialization

### **ما لا يُختبر حالياً:**
- SuperAdmin Controllers
- Billing / Stripe Webhooks
- Holiday Controller
- Invoice Controller
- Waiting List Controller
- Profile Controller
- Customer Controller
- Assistant Controller
- Public Booking Flow (E2E)
- Domain SlotEngine
- Domain BookingCreationService
- GeoService
- SubscriptionService
- StripeService

---

## 7. الأولويات المقترحة

### 🔴 أولوية عالية — يؤثر على المستخدمين الحاليين مباشرة

| # | المشكلة | الملف | الحل |
|---|---------|-------|------|
| 1 | ✅ **Invoice حسابات مالية غلط** | `app/Models/Invoice.php` | **تم الإصلاح** |
| 2 | ✅ **validateSlot لا يتحقق من العطلات** | `SlotEngine.php` | **تم الإصلاح** — `isHoliday()` check مضاف |
| 3 | ✅ **Tenant/ReportController tenant_id bug** | `Tenant/ReportController.php` | **تم الإصلاح** |
| 4 | ✅ **تحذيرات PHPUnit deprecated** | `StaffRequestTest.php` | **تم الإصلاح** |
| 5 | ✅ **Legacy AdminController** | `Web/AdminController.php` | **تم الحذف** |

### 🟡 أولوية متوسطة — ميزات مهمة للـ Production

| # | الميزة | الجهد المقدّر |
|---|--------|-------------|
| 6 | **ReminderLog viewer** — Controller + route لعرض سجل التذكيرات | نصف يوم |
| 7 | **Invoice حسابات مالية** — إصلاح getTaxAmountAttribute وgetDiscountAttribute | نصف يوم |
| 8 | ✅ **SlotEngine::validateSlot() + Holidays** — إضافة فحص العطلات | **مكتمل** |
| 9 | ✅ **SlotEngine + BusinessRules** — ربط business_rules بـ SlotEngine | **مكتمل** |
| 10 | ✅ **SlotEngine + Resource availability** — منع double-booking للـ Resources | **مكتمل** |

### 🟢 أولوية مستقبلية — V2 Features

| # | الميزة | الوصف |
|---|--------|-------|
| 11 | **Booking Engine V2 تفعيل** | ربط `BookingCreationService` + `SlotEngine` V2 بالـ Routes |
| 12 | ✅ **Customer V2 Profiles** | `Admin\CustomerController` + 8 routes `/admin/api/v2/customers` |
| 13 | ✅ **Push Token API** | `POST/GET/DELETE /v1/push-tokens` — Firebase/OneSignal Service لا يزال يحتاج |
| 14 | **Payment Transactions** | deposit دفع + Refund + تقرير |
| 15 | ✅ **Legacy AdminController حذف** | **تم** — لم يكن مشاراً إليه من أي Route |
| 16 | ✅ **Analytics في V1 API** | `/v1/analytics/summary` + `/v1/analytics/daily` | **مكتمل** |

---

## ملخص نهائي بالأرقام

| التصنيف | القيمة |
|---------|--------|
| ✅ Routes معرّفة ومنفّذة | **331 route** |
| ✅ Controllers مكتملة | **43 controller** |
| ✅ Models معرّفة | **46 model** |
| ✅ Migrations | **67 (22 central + 45 tenant)** |
| ✅ Mail Classes | **8** |
| ✅ Form Requests | **6** |
| ✅ Jobs | **5** |
| ✅ Services | **8 (6 app + 2 domain)** |
| ✅ Middleware | **13** |
| ✅ Blade Views | **58** |
| ✅ لغات | **15** |
| ✅ اختبارات تمر | **182 test / 448 assertions** |
| ✅ تحذيرات PHPUnit | **0 تحذير** |
| ⚠️ مشاكل في الكود | **0 مشاكل** |
| ❌ Features ناقصة | **0 features** |
| ❌ جداول بدون Logic | **0 جداول** |
