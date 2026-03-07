# تقرير كامل — مشروع Velora
## SaaS متعدد المستأجرين لإدارة المواعيد والطابور

**نوع المشروع:** Multi-Tenant SaaS  
**الغرض:** إدارة المواعيد والصفوف والفوترة للعيادات والصالونات والمشاغل  
**Framework:** Laravel 12  
**Tenancy Package:** Stancl/Tenancy v3.9  
**PHP:** 8.2+  
**قواعد البيانات:** MySQL (إنتاج) + SQLite (اختبارات)  
**Frontend:** Blade + Alpine.js + Tailwind CSS  
**تاريخ التقرير:** مارس 2026  
**المرحلة الحالية:** 🚀 Revenue-Building Mode

---

## الفهرس

1. [نظرة عامة على المشروع](#1-نظرة-عامة)
2. [البنية المعمارية الكاملة](#2-البنية-المعمارية-الكاملة)
3. [Dependencies — composer.json](#3-dependencies)
4. [قاعدة البيانات — المايغريشنز (68 migration)](#4-قاعدة-البيانات)
5. [الـ Models (46 model)](#5-الـ-models)
6. [الـ Controllers (50 controller)](#6-الـ-controllers)
7. [الـ Middleware (14 middleware)](#7-الـ-middleware)
8. [الـ Services (8 services)](#8-الـ-services)
9. [Domain Layer — Booking Engine](#9-domain-layer--booking-engine)
10. [Repository Pattern](#10-repository-pattern)
11. [الـ Jobs (6 jobs)](#11-الـ-jobs)
12. [الـ Mail Classes (11 mailable)](#12-الـ-mail-classes)
13. [Console Commands (6 commands)](#13-console-commands)
14. [الـ Routes (336 route)](#14-الـ-routes)
15. [الـ Views (Blade)](#15-الـ-views)
16. [نظام المصادقة والـ Tenancy](#16-نظام-المصادقة-والـ-tenancy)
17. [نظام الاشتراكات والفوترة (Stripe)](#17-نظام-الاشتراكات-والفوترة)
18. [نظام الـ Onboarding](#18-نظام-الـ-onboarding)
19. [نظام التذكيرات التلقائية](#19-نظام-التذكيرات-التلقائية)
20. [Analytics & Reporting](#20-analytics--reporting)
21. [الـ GDPR والخصوصية](#21-الـ-gdpr-والخصوصية)
22. [دعم متعدد اللغات (15 لغة)](#22-دعم-متعدد-اللغات)
23. [التسعير الجغرافي](#23-التسعير-الجغرافي)
24. [الاختبارات (191 test — 472 assertions)](#24-الاختبارات)
25. [Service Providers (3)](#25-service-providers)
26. [Observers — AppointmentObserver](#26-observers)
27. [Exports (3 classes)](#27-exports)
28. [Database Seeders & Factories](#28-database-seeders--factories)
29. [Config Files (12)](#29-config-files)
30. [الحالة التقنية الراهنة](#30-الحالة-التقنية-الراهنة)

---

## 1. نظرة عامة

Velora هو تطبيق SaaS متعدد المستأجرين (Multi-Tenant) يُمكّن أي نشاط تجاري (عيادة / صالون / مشغل) من إنشاء حسابه الخاص بقاعدة بيانات معزولة تماماً، وإدارة:

- **المواعيد** — حجز وتأكيد وإلغاء وإتمام مع تتبع كامل للحالات
- **الطابور** — إدارة الحضور المباشر (Walk-in) مع أولوية VIP
- **الموظفين** — ساعات عمل + استراحات + إجازات + عمولات + جدول التخصصات
- **الخدمات** — فئات + مدة + سعر + ربط بالموظفين والموارد
- **العملاء** — ملف كامل + تاريخ مواعيد + GDPR + حظر + Tags + تصنيف LTV
- **الفوترة** — فواتير PDF/Excel + اشتراكات Stripe + Grace Period
- **التقارير** — إحصاءات + Analytics + تصدير
- **البوابة العامة** — صفحة حجز خارجية يصل إليها العميل النهائي

---

## 2. البنية المعمارية الكاملة

### 2.1 نموذج Multi-Tenancy

```
Central Database (MySQL)
├── tenants
├── domains
├── subscription_plans
├── tenant_subscriptions
├── activity_logs
├── system_settings
├── system_notifications
├── upgrade_requests
├── usage_logs
├── country_settings
├── country_taxes
└── plan_prices

Per-Tenant Database (MySQL في الإنتاج / SQLite في الاختبارات)
└── [كل الجداول الخاصة بالنشاط التجاري]
```

**عزل 4 طبقات:**
| الطبقة | الـ Bootstrapper |
|--------|-----------------|
| Database | `DatabaseTenancyBootstrapper` |
| Cache | `CacheTenancyBootstrapper` |
| Storage | `FilesystemTenancyBootstrapper` |
| Queue | عبر `tenant_id` في الـ Job payload |

**التعريف بالمستأجر:**
- **Subdomain:** `salon.velora.test` → middleware `InitializeTenancyByDomain`
- **Token:** `X-Tenant-Token` header → middleware `InitializeTenancyByToken` للـ `/v1/` endpoints

### 2.2 طبقات الكود (Architecture Layers)

```
┌─────────────────────────────────────────────────────────────────┐
│  Routes: api.php + tenant.php + web.php + console.php           │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  Middleware Stack: Auth + Role + Subscription + Locale + Sec    │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  Controllers: Admin/ + SuperAdmin/ + Tenant/ + Web/ + Auth/     │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  Form Requests: Validation Layer (app/Http/Requests/)           │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  Repository Pattern: Interface → Eloquent Implementation        │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  Domain Layer: DTOs + Events + Services + Exceptions            │
│  (app/Domain/Booking/)                                          │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  Models (Eloquent): SoftDeletes + HasTranslations Trait         │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  Database: Central MySQL + Per-Tenant MySQL/SQLite              │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Dependencies

### composer.json — Production

| Package | الإصدار | الغرض |
|---------|---------|-------|
| `laravel/framework` | ^12.0 | الـ Framework الرئيسي |
| `stancl/tenancy` | ^3.9 | Multi-tenancy core |
| `laravel/sanctum` | ^4.2 | API token authentication |
| `stripe/stripe-php` | ^19.4 | معالجة المدفوعات |
| `barryvdh/laravel-dompdf` | ^3.1 | تصدير PDF |
| `maatwebsite/excel` | ^3.1 | تصدير Excel/CSV |
| `simplesoftwareio/simple-qrcode` | ^4.2 | توليد QR Code |

### composer.json — Dev

| Package | الغرض |
|---------|-------|
| `phpunit/phpunit` v11.5 | إطار الاختبارات |
| `fakerphp/faker` | توليد بيانات وهمية |
| `laravel/pint` | PHP code formatter |
| `laravel/sail` | Docker dev environment |
| `mockery/mockery` | Mocking في الاختبارات |
| `nunomaduro/collision` | عرض أفضل لأخطاء CLI |
| `laravel/pail` | Log streaming |

### package.json — Frontend

| Package | الغرض |
|---------|-------|
| Vite | Build tool |
| Tailwind CSS | Utility-first CSS framework |
| Alpine.js | (via CDN في بعض الـ views) |

---

## 4. قاعدة البيانات

### 4.1 المايغريشنز المركزية (Central — 24 migration)

| الملف | الجدول / الغرض |
|-------|---------------|
| `0001_01_01_000001_create_cache_table.php` | `cache` |
| `0001_01_01_000002_create_jobs_table.php` | `jobs` |
| `2019_09_15_000010_create_tenants_table.php` | `tenants` |
| `2019_09_15_000020_create_domains_table.php` | `domains` |
| `2026_01_27_012305_add_columns_to_tenants_table.php` | إضافة حقول للـ tenants |
| `2026_02_16_015409_add_rating_to_appointments_table.php` | — (central reference) |
| `2026_02_17_080459_create_subscription_plans_table.php` | `subscription_plans` |
| `2026_02_17_080508_create_tenant_subscriptions_table.php` | `tenant_subscriptions` |
| `2026_02_17_080515_create_activity_logs_table.php` | `activity_logs` |
| `2026_02_17_080522_create_system_settings_table.php` | `system_settings` |
| `2026_02_17_080549_create_system_notifications_table.php` | `system_notifications` |
| `2026_02_17_093309_create_upgrade_requests_table.php` | `upgrade_requests` |
| `2026_02_17_093409_create_usage_logs_table.php` | `usage_logs` |
| `2026_02_17_125009_add_available_languages_to_settings_table.php` | حقول لغات |
| `2026_03_01_000001_add_grace_period_to_tenant_subscriptions.php` | `grace_ends_at` |
| `2026_03_01_000002_add_stripe_fields_to_subscription_plans.php` | `stripe_price_id` |
| `2026_03_02_000001_create_country_settings_table.php` | `country_settings` |
| `2026_03_02_000002_create_plan_prices_and_country_taxes_tables.php` | `plan_prices` + `country_taxes` |
| `2026_03_02_000003_seed_geo_system_settings.php` | Seeder Geo settings |
| `2026_03_02_000004_seed_core_system_settings.php` | Seeder Core settings |
| `2026_03_02_000005_seed_payment_methods_settings.php` | Seeder Payment settings |
| `2026_03_02_000006_seed_global_payment_methods.php` | Seeder Global payments |
| `2026_03_04_100002_add_trial_tracking_to_tenant_subscriptions.php` | `converted_at` + `trial_tracking` |
| `2026_03_05_000001_add_trial_extended_to_tenant_subscriptions.php` | `trial_extended` |

### 4.2 المايغريشنز لكل مستأجر (Per-Tenant — 46 migration)

**الجداول الأساسية:**

| الملف | الجدول / الغرض |
|-------|---------------|
| `2026_01_27_012400_create_roles_table.php` | `roles` |
| `2026_01_27_012401_create_users_table.php` | `users` |
| `2026_01_27_012412_create_appointments_table.php` | `appointments` |
| `2026_01_27_012432_create_queues_table.php` | `queues` |
| `2026_01_27_012437_create_notifications_table.php` | `notifications` |
| `2026_01_27_012441_create_settings_table.php` | `settings` |
| `2026_01_27_012445_create_invoices_table.php` | `invoices` |
| `2026_01_27_012510_create_personal_access_tokens_table.php` | `personal_access_tokens` |

**التحسينات (Alters):**

| الملف | التغيير |
|-------|---------|
| `2026_01_30_090345_add_service_type_and_notes_to_appointments_table.php` | `service_type` + `notes` |
| `2026_01_30_091125_add_phone_to_users_table.php` | `phone` على `users` |
| `2026_01_31_015320_make_staff_id_nullable_in_appointments_table.php` | staff_id nullable |
| `2026_01_31_015546_change_queue_number_to_string_in_queues_table.php` | queue_number → string |
| `2026_02_01_033531_create_services_table.php` | `services` |
| `2026_02_01_033531_create_time_slots_table.php` | `time_slots` |
| `2026_02_01_033532_create_working_days_table.php` | `working_days` |
| `2026_02_01_035200_create_staff_schedules_table.php` | `staff_schedules` |
| `2026_02_03_000001_add_avatar_and_permissions_to_users_table.php` | avatar + permissions JSON |
| `2026_02_03_000002_create_images_table.php` | `images` |
| `2026_02_03_add_business_settings_to_settings_table.php` | حقول إعدادات الأعمال |
| `2026_02_05_150000_add_is_vip_to_queues_table.php` | `is_vip` |
| `2026_02_05_153155_add_specialization_to_users_table.php` | `specialization` |
| `2026_02_05_160000_add_service_id_to_appointments_table.php` | `service_id` |
| `2026_02_05_170000_add_notes_to_queues_table.php` | notes |
| `2026_02_15_082743_change_appointments_status_to_string.php` | تحويل enum → string |
| `2026_02_15_120000_add_queue_date_to_queues_table.php` | `queue_date` |
| `2026_02_16_015409_add_rating_to_appointments_table.php` | `rating` + `rating_comment` |
| `2026_02_17_125009_add_available_languages_to_settings_table.php` | `available_languages` |
| `2026_02_18_000001_create_cache_table.php` | tenant cache |
| `2026_02_18_000002_create_jobs_table.php` | tenant jobs |
| `2026_03_01_100000_add_is_read_and_appointment_id_to_notifications_table.php` | `is_read` + `appointment_id` |

**V2 — الـ Layer الجديد:**

| الملف | الجداول |
|-------|---------|
| `2026_03_02_010001_create_service_categories_table.php` | `service_categories` |
| `2026_03_02_010002_upgrade_services_table.php` | تحسين `services` |
| `2026_03_02_010003_create_resources_table.php` | `resources` + `service_resources` |
| `2026_03_02_010004_create_staff_table.php` | `staff` (Model جديد منفصل عن User) |
| `2026_03_02_010005_create_staff_availability_tables.php` | `staff_working_hours` + `staff_breaks` + `staff_time_off` |
| `2026_03_02_010006_create_customers_table.php` | `customers` (Model مستقل عن User) |
| `2026_03_02_010007_upgrade_appointments_table.php` | تحديثات على `appointments` |
| `2026_03_02_010008_create_booking_support_tables.php` | `business_rules` + `recurring_rules` + `appointment_status_history` + `staff_commissions` |
| `2026_03_02_010009_create_waiting_list_table.php` | `waiting_list` |
| `2026_03_02_010010_create_reminder_tables.php` | `reminder_rules` + `reminder_logs` |
| `2026_03_02_010011_create_analytics_tables.php` | `analytics_daily` + `staff_analytics_daily` + `booking_heatmap` + `service_analytics_daily` |
| `2026_03_02_010012_create_payment_tables.php` | `payment_transactions` + `invoice_items` |
| `2026_03_02_010013_create_push_and_gdpr_tables.php` | `push_tokens` + `gdpr_consents` |
| `2026_03_03_000001_add_is_vip_to_users_table.php` | `is_vip` على users |
| `2026_03_04_100001_add_onboarding_to_settings_table.php` | `onboarding_completed` |
| `2026_03_10_000001_add_fields_to_invoices_table.php` | حقول مالية على invoices |

---

## 5. الـ Models

### 5.1 Models المركزية (Central)

| الـ Model | الجدول | الغرض |
|----------|--------|-------|
| `Tenant` | `tenants` | المستأجر — يحتوي على بيانات JSON (name, email, token) |
| `SubscriptionPlan` | `subscription_plans` | خطط الاشتراك (اسم + سعر + حدود + Stripe price ID) |
| `TenantSubscription` | `tenant_subscriptions` | اشتراك مستأجر فعلي (status + trial_ends_at + grace_ends_at + converted_at) |
| `ActivityLog` | `activity_logs` | سجل الأنشطة المركزي |
| `SystemSetting` | `system_settings` | إعدادات النظام العامة |
| `SystemNotification` | `system_notifications` | إشعارات النظام |
| `UpgradeRequest` | `upgrade_requests` | طلبات ترقية الاشتراك |
| `UsageLog` | `usage_logs` | سجل استخدام المستأجرين |
| `CountrySetting` | `country_settings` | إعدادات الدول (عملة + timezone) |
| `CountryTax` | `country_taxes` | ضرائب لكل دولة |
| `PlanPrice` | `plan_prices` | أسعار مخصصة لكل خطة حسب الدولة |

### 5.2 Models خاصة بكل مستأجر (Per-Tenant)

**الكيانات الأساسية:**

| الـ Model | الجدول | الغرض |
|----------|--------|-------|
| `User` | `users` | المستخدمون (Admin + Staff V1 + Customer V1) — مع permissions JSON |
| `Role` | `roles` | الأدوار: Admin Tenant / Staff / Customer |
| `Customer` | `customers` | **V2** — كيان مستقل للعميل مع tags + GDPR + ltv_tier + referral + is_blocked |
| `Staff` | `staff` | **V2** — كيان مستقل للموظف مع commission_type + commission_value + SoftDeletes |
| `Service` | `services` | الخدمة — مع HasTranslations + category + duration + price |
| `ServiceCategory` | `service_categories` | فئات الخدمات مع ترتيب |
| `TimeSlot` | `time_slots` | الأوقات المتاحة في اليوم |
| `WorkingDay` | `working_days` | أيام العمل وساعاتها |

**المواعيد والطابور:**

| الـ Model | الجدول | الغرض |
|----------|--------|-------|
| `Appointment` | `appointments` | الموعد — مع State Machine كاملة + SoftDeletes + rating |
| `AppointmentStatusHistory` | `appointment_status_history` | سجل كل تغيير في حالة الموعد |
| `Queue` | `queues` | إدخال الطابور — مع is_vip + queue_date |
| `RecurringRule` | `recurring_rules` | قواعد تكرار المواعيد (يومي/أسبوعي/شهري) |
| `WaitingList` | `waiting_list` | قائمة الانتظار للأوقات المشغولة |

**الفوترة:**

| الـ Model | الجدول | الغرض |
|----------|--------|-------|
| `Invoice` | `invoices` | الفاتورة — مع computed attributes: subtotal + tax + discount + total |
| `InvoiceItem` | `invoice_items` | بنود الفاتورة |
| `PaymentTransaction` | `payment_transactions` | سجل المعاملات المالية (deposits + refunds) |

**الموارد والجداول:**

| الـ Model | الجدول | الغرض |
|----------|--------|-------|
| `Resource` | `resources` | موارد (قاعة / كرسي / معدة) مع service_resources pivot |
| `StaffWorkingHours` | `staff_working_hours` | ساعات عمل الموظف لكل يوم |
| `StaffBreak` | `staff_breaks` | فترات الاستراحة للموظف |
| `StaffTimeOff` | `staff_time_off` | إجازات الموظف |
| `StaffCommission` | `staff_commissions` | عمولات الموظف المحسوبة تلقائياً |
| `StaffSchedule` | `staff_schedules` | جدول الموظف V1 (legacy) |
| `Holiday` | `holidays` | العطلات — مع applies_to_all + pivot |

**الإعدادات والإشعارات:**

| الـ Model | الجدول | الغرض |
|----------|--------|-------|
| `Setting` | `settings` | إعدادات المستأجر (اسم + شعار + لغات + onboarding_completed) |
| `Notification` | `notifications` | إشعارات داخلية للمستأجر |

**Analytics:**

| الـ Model | الجدول | الغرض |
|----------|--------|-------|
| `AnalyticsDaily` | `analytics_daily` | إحصاءات يومية مجمّعة |
| `StaffAnalyticsDaily` | `staff_analytics_daily` | إحصاءات أداء الموظف اليومية |
| `BookingHeatmap` | `booking_heatmap` | خريطة حرارة الحجوزات حسب اليوم والساعة |
| `ServiceAnalyticsDaily` | `service_analytics_daily` | إحصاءات الخدمات اليومية |

**أخرى:**

| الـ Model | الجدول | الغرض |
|----------|--------|-------|
| `BusinessRule` | `business_rules` | قواعد الأعمال (min_advance_booking_hours / max_per_day / same_day_booking…) |
| `ReminderRule` | `reminder_rules` | قواعد التذكير التلقائي (متى + كيف) |
| `ReminderLog` | `reminder_logs` | سجل التذكيرات المُرسَلة |
| `PushToken` | `push_tokens` | رموز الـ Push Notifications |
| `GdprConsent` | `gdpr_consents` | سجل موافقات GDPR |
| `Image` | `images` | الصور المرفوعة |

**Traits مشتركة:**

| Trait | الغرض |
|-------|-------|
| `HasTranslations` | `$model->trans('name')` — يقرأ من JSON column حسب اللغة الحالية |

---

## 6. الـ Controllers

### 6.1 Auth Controllers — `app/Http/Controllers/Auth/`

| الكنترولر | الغرض |
|-----------|-------|
| `TenantAuthController` | تسجيل دخول المستأجر (Web Session + API Token) |
| `TenantRegistrationController` | تسجيل مستأجر جديد (ينشئ: subdomain + DB + migrations + roles + admin user + welcome email) |
| `SuperAdminAuthController` | تسجيل دخول السوبر أدمن بـ guard مستقل |

### 6.2 Admin Controllers — `app/Http/Controllers/Admin/`

| الكنترولر | المسؤوليات الرئيسية |
|-----------|-------------------|
| `AppointmentController` | CRUD + تغيير الحالة + إضافة/إزالة من الطابور + إرسال تذكير + bulk Action + QR Code + تقييم |
| `QueueController` | إضافة Walk-in + استدعاء التالي + serve + complete + priority VIP + نقل للغد |
| `ServiceController` | CRUD الخدمات + TimeSlots + Working Days |
| `StaffController` | CRUD موظفين + ربط الخدمات + جدول العمل |
| `StaffScheduleController` | ساعات عمل + استراحات + إجازات + عمولات لكل موظف |
| `DashboardController` | 13+ إحصاء + رسم بياني 7 أيام + أداء الموظفين |
| `OnboardingController` | Wizard 4 خطوات (settings / service / working-days / completion) |
| `SettingController` | حفظ الإعدادات + رفع الشعار |
| `CustomerController` | CRUD V2 + block/unblock + سجل مواعيد + tags + فلاتر |
| `HolidayController` | CRUD العطلات + applies_to_all |
| `InvoiceController` | CRUD + بنود + تحديث الحالة + PDF/CSV |
| `ReportController` | إحصاءات شاملة + تصدير |
| `AnalyticsController` | 5 endpoints: summary/daily/heatmap/staff/services |
| `ReminderRuleController` | CRUD قواعد التذكير + toggle + reorder |
| `ReminderLogController` | قراءة سجل التذكيرات + stats |
| `RecurringController` | إنشاء مواعيد متكررة + إلغاء السلسلة |
| `BusinessRuleController` | قراءة + تحديث قواعد الأعمال |
| `ServiceCategoryController` | CRUD فئات الخدمات + toggle + reorder |
| `ResourceController` | CRUD الموارد + attach/detach services |
| `CommissionsController` | index + summary + mark-paid + bulk-mark-paid |
| `PaymentTransactionController` | index + summary + show + mark-paid + refund |
| `GdprController` | موافقات + سحب + anonymize + تصدير بيانات العميل |

### 6.3 SuperAdmin Controllers — `app/Http/Controllers/SuperAdmin/`

| الكنترولر | المسؤوليات |
|-----------|-----------|
| `DashboardController` | إحصاءات عامة + **Revenue KPIs** (MRR/ARPU/Churn/Trial Funnel) |
| `TenantController` | CRUD مستأجرين + toggle + statistics + assignSubscription + resetAdminPassword |
| `SubscriptionPlanController` | CRUD خطط الاشتراك + toggle |
| `ActivityLogController` | قراءة سجل الأنشطة + statistics + clearOld |
| `SystemSettingController` | CRUD الإعدادات مجمّعة بمجموعات |
| `SystemNotificationController` | CRUD الإشعارات + إرسال |

### 6.4 Tenant API Controllers — `app/Http/Controllers/Tenant/`

| الكنترولر | المسؤوليات |
|-----------|-----------|
| `AppointmentController` | إنشاء موعد عبر الـ API (يستخدم BookingCreationService V2) |
| `QueueController` | حالة الطابور |
| `InvoiceController` | عرض الفواتير |
| `SettingController` | قراءة وتحديث إعدادات المستأجر عبر `/v1/settings` |
| `ReportController` | تقارير عبر الـ API |
| `NotificationController` | قراءة الإشعارات + mark as read |
| `PushTokenController` | تسجيل + حذف Push Tokens |
| `AnalyticsController` | analytics عبر الـ API |

### 6.5 Web Controllers — `app/Http/Controllers/Web/`

| الكنترولر | المسؤوليات |
|-----------|-----------|
| `AssistantController` | CRUD المساعدين + صلاحياتهم |
| `CustomerController` | CRUD العملاء V1 (User-based) |
| `ProfileController` | الملف الشخصي + رفع صورة + تغيير كلمة مرور + حذف الحساب |
| `QueueController` | صفحة الطابور + تصدير Excel |
| `SubscriptionController` | صفحة الاشتراك + Upgrade + Billing |
| `WaitingListController` | قائمة الانتظار (Admin API) |

### 6.6 Controllers في الجذر — `app/Http/Controllers/`

| الكنترولر | المسؤوليات |
|-----------|-----------|
| `LandingController` | Landing Page + Pricing + Signup + Find Account |
| `BillingController` | Stripe Checkout + Portal + Success Page + Expired Page |
| `StripeWebhookController` | معالجة جميع Stripe webhook events |
| `CountrySettingController` | CRUD إعدادات الدول |
| `PlanPriceController` | CRUD أسعار الخطط حسب الدولة |
| `SuperAdminController` | Base SA controller |

---

## 7. الـ Middleware

| Middleware | الغرض |
|-----------|-------|
| `InitializeTenancyByDomain` | تفعيل الـ Tenancy عبر الـ Subdomain |
| `InitializeTenancyByToken` | تفعيل الـ Tenancy عبر `X-Tenant-Token` header |
| `CheckRole` | التحقق من دور المستخدم (`role:Admin Tenant|Staff|Customer`) |
| `CheckSuperAdmin` | التحقق من أن المستخدم Super Admin |
| `SuperAdminAuth` | guard مستقل للـ Super Admin |
| `CheckSubscriptionLimits` | فحص حدود الاشتراك (users / appointments / storage) |
| `EnsureSubscriptionIsValid` | التحقق من صلاحية الاشتراك (active / trial / grace) |
| `SetTenantLocale` | ضبط لغة الـ Tenant من الـ Settings |
| `SetCentralLocale` | ضبط لغة المنطقة المركزية |
| `DetectCountryAndLocale` | اكتشاف البلد من `CF-IPCountry` header |
| `RedirectIfOnboardingIncomplete` | إعادة توجيه للـ Onboarding إذا لم يكتمل |
| `SecurityHeaders` | إضافة Security Headers (XSS, CSP, etc.) |
| `CheckTokenAbility` | التحقق من صلاحيات الـ Token |
| `ThrottleRequests` | تحديد معدل الطلبات |

---

## 8. الـ Services

### `app/Services/`

| Service | الغرض |
|---------|-------|
| `TenantRegistrationService` | كل منطق إنشاء مستأجر جديد في DB transaction واحدة |
| `StripeService` | Wrapper حول Stripe PHP SDK — Checkout + Portal + Webhook verification |
| `SubscriptionService` | تفاصيل الاشتراك الحالي + نسب الاستخدام + حدود الخطة |
| `GeoService` | اكتشاف البلد + جلب أسعار الخطط المناسبة |
| `RecurringAppointmentService` | توليد المواعيد المتكررة من القاعدة |
| `PushNotificationService` | إرسال Push Notifications عبر FCM / OneSignal |

### `app/Domain/Booking/Services/`

| Service | الغرض |
|---------|-------|
| `SlotEngine` | محرك المواعيد — يولّد الأوقات المتاحة مع فحص: ساعات العمل + استراحات + إجازات + حجوزات سابقة + عطلات + Resources + BusinessRules |
| `BookingCreationService` | إنشاء موعد جديد بـ Pessimistic Locking لمنع Double-booking |

---

## 9. Domain Layer — Booking Engine

**الموقع:** `app/Domain/Booking/`

### 9.1 DTOs (Data Transfer Objects)

| DTO | الغرض |
|-----|-------|
| `CreateBookingData` | بيانات إنشاء الموعد type-safe (tenantId + serviceId + staffId + date + time + customerId + resourceId + recurringId + depositAmount) |
| `SlotValidationResult` | نتيجة التحقق من الوقت مع سبب الرجوع (available: bool + reason: string) |
| `TimeSlot` | تمثيل فترة زمنية (start + end + staffId + isAvailable) |

### 9.2 Events

| Event | متى يُطلق |
|-------|----------|
| `AppointmentCreated` | مباشرة بعد إنشاء موعد جديد ناجح |
| `AppointmentStatusChanged` | عند كل تغيير في حالة الموعد |

### 9.3 Exceptions

| Exception | متى تُرمى |
|-----------|----------|
| `SlotUnavailableException` | عندما يكون الوقت المطلوب غير متاح (مشغول / إجازة / خارج ساعات العمل) |

### 9.4 منطق SlotEngine بالتفصيل

`SlotEngine::getAvailableSlots(date, serviceId, staffId, resourceId)`:
1. يجلب `WorkingDay` ويتحقق أن اليوم مفتوح
2. يتحقق من العطلات (`Holiday::isHoliday()`)
3. يجلب `StaffWorkingHours` + `StaffBreak` + `StaffTimeOff` للموظف
4. يولّد جميع الفترات الزمنية حسب مدة الخدمة
5. يزيل الأوقات المحجوزة مسبقاً
6. يتحقق من توفر الـ Resource إذا كان محدداً

`SlotEngine::validateSlot(date, time, serviceId, staffId, resourceId)`:
- خطوة 1: فحص يوم العمل
- خطوة 2: فحص العطلات
- خطوة 3: فحص ساعات عمل الموظف
- خطوة 4: فحص عدم وجود حجز موجود
- خطوة 5: فحص توفر الـ Resource
- خطوة 6: فحص BusinessRules (min_advance_hours / max_advance_days / same_day / max_per_customer_per_day)

`BookingCreationService::create(CreateBookingData)`:
```
1. validateSlot() → throw SlotUnavailableException if failed
2. DB::transaction with lockForUpdate() (Pessimistic Locking)
3. Re-check slot inside transaction (double-check)
4. Create Appointment record
5. Create PaymentTransaction (deposit) if deposit_amount > 0
6. Fire AppointmentCreated Event
7. Return Appointment
```

**State Machine للمواعيد:**
```
pending ──→ confirmed ──→ completed (terminal)
         ↘             ↘ cancelled (terminal)
           cancelled        no_show (terminal)
```
كل انتقال يُسجَّل في `appointment_status_history` مع (actor_id + from + to + timestamp).

---

## 10. Repository Pattern

**الموقع:** `app/Repositories/`

| Interface | Implementation | المسؤوليات |
|-----------|----------------|-----------|
| `AppointmentRepositoryInterface` | `AppointmentRepository` | paginate مع filters + getTodayStats + getWeeklyStats + create + findById + update + delete |
| `QueueRepositoryInterface` | `QueueRepository` | getByDate + getActive + callNext + getOverallStats |
| `StaffRepositoryInterface` | `StaffRepository` | all + findById + findWithRelations + create مع services + schedule + update + delete + getBySpecialization |

**الربط:** `RepositoryServiceProvider` يربط كل Interface بـ Concrete Implementation عبر Laravel IoC Container.

---

## 11. الـ Jobs

| Job | الغرض | Queue |
|-----|-------|-------|
| `SendAppointmentNotification` | إرسال إشعار موعد (email / قناة أخرى) | `emails` |
| `SendQueueNotification` | إرسال إشعار تحديث الطابور | `emails` |
| `NotifyWaitingListOnAvailability` | إخطار أول عميل في قائمة الانتظار عند إلغاء موعد | default |
| `GenerateNextRecurringAppointment` | توليد الموعد التالي في السلسلة المتكررة | default |
| `LinkTenantDomain` | ربط domain المستأجر بـ Herd (dev only) | default |
| `SendTrialNudge` | إرسال بريد Nudge للتجربة المجانية | `emails` |

---

## 12. الـ Mail Classes

| Mailable | متى يُرسَل | من يستقبله |
|----------|----------|-----------|
| `AppointmentBookedMail` | عند إنشاء/تأكيد موعد | العميل |
| `AppointmentReminderMail` | يدوياً عبر send-reminder API | العميل |
| `QueueUpdateMail` | عند تحديث حالة الطابور | العميل |
| `TrialReminderMail` | قبل انتهاء التجربة | الأدمن |
| `TrialNudgeMail` | أيام 1/3/7/12 من التجربة | الأدمن |
| `UpgradeApprovedMail` | عند موافقة SA على طلب الترقية | الأدمن |
| `UpgradeRejectedMail` | عند رفض SA لطلب الترقية | الأدمن |
| `WelcomeAssistantMail` | عند إنشاء مساعد جديد | المساعد (مع كلمة المرور) |
| `WelcomeTenantMail` | عند تسجيل مستأجر جديد | الأدمن (مع credentials) |
| `PaymentFailedMail` | عند فشل دفعة Stripe | الأدمن |
| `FounderAlertMail` | تنبيهات داخلية للمؤسس | المؤسس |

---

## 13. Console Commands

| Command | الغرض | Schedule |
|---------|-------|---------|
| `CheckSubscriptionStatus` | يتحقق من الاشتراكات المنتهية/المنتهية قريباً | `hourly()` |
| `SendTrialReminders` | يُرسل تذكيرات انتهاء التجربة المجانية | `dailyAt('09:00')` |
| `ProcessReminders` | يعالج التذكيرات التلقائية للمواعيد (±7 دقائق window) | `everyFifteenMinutes()` |
| `AggregateAnalytics` | يجمّع الإحصاءات اليومية في جداول Analytics | `dailyAt('00:30')` |
| `ProcessTrialNudges` | يُرسل Nudge emails حسب يوم التجربة | `dailyAt('09:00')` |
| `AddAvailableLanguagesToTenants` | migration helper لللغات | manual |

**الجدول الزمني الكامل (routes/console.php):**
```php
Schedule::command('subscriptions:check-status')->hourly();
Schedule::command('subscriptions:send-trial-reminders')->dailyAt('09:00');
Schedule::command('reminders:process')->everyFifteenMinutes();
Schedule::command('analytics:aggregate')->dailyAt('00:30');
Schedule::command('trial-nudges:process')->dailyAt('09:00');
```

---

## 14. الـ Routes

**الملفات:**
- `routes/api.php` — Central + SuperAdmin API routes
- `routes/tenant.php` — Tenant-specific routes (Admin panel + Public booking)
- `routes/web.php` — Landing page + Auth (Web)
- `routes/console.php` — Scheduled commands

**إجمالي الـ Routes: 336 route**

### 14.1 routes/api.php (Central)

**SuperAdmin endpoints تحت `/super-admin/api/`:**
- **Tenants:** CRUD + toggle + statistics + assign subscription + reset password
- **Plans:** CRUD + toggle
- **Activity Logs:** index + statistics + clearOld
- **System Settings:** grouped CRUD
- **System Notifications:** CRUD + send
- **Upgrade Requests:** index + show + approve/reject
- **Country Settings + Taxes:** CRUD
- **Plan Prices:** CRUD
- **Revenue KPIs:** `GET /dashboard/revenue-metrics`

**Stripe Webhooks:**
- `POST /webhooks/stripe`

### 14.2 routes/tenant.php (Per-Tenant)

**مجموعة `/admin/` (مطلوب تسجيل دخول + دور Admin/Staff):**

| المجموعة | عدد الـ Routes |
|---------|--------------|
| Dashboard | ~5 routes |
| Appointments (admin panel) | ~13 route |
| Queue (admin) | ~15 route |
| Staff | ~10 routes |
| Services + TimeSlots + WorkingDays | ~10 routes |
| Holidays | 5 routes |
| Settings | ~5 routes |
| Customers V2 | ~8 routes |
| Reports + Export | ~10 routes |
| Invoices | ~10 routes |
| Analytics | 5 routes |
| Reminder Rules + Logs | ~10 routes |
| Recurring Appointments | 3 routes |
| Business Rules | 4 routes |
| Service Categories | ~7 routes |
| Resources | ~8 routes |
| Commissions | 4 routes |
| Payment Transactions | 5 routes |
| GDPR | 5 routes |
| Onboarding | 5 routes |

**Public API (لا يحتاج تسجيل دخول):**
- `GET /api/booking/services`
- `GET /api/booking/available-timeslots`
- `GET /api/booking/workingdays`
- `GET /api/booking/staff/by-service/{serviceId}`
- `GET /api/booking/staff/{id}/schedule`
- `POST /api/appointments`
- `GET /api/queue/status/{queueNumber}`
- `GET /api/waiting-list/status`
- `POST /api/waiting-list/join`
- `POST /api/waiting-list/{id}/cancel`

**Tenant API V1 (Token-based `/v1/`):**
- `GET/POST /v1/auth/login` + `logout` + `profile`
- `GET/PUT /v1/settings`
- `GET /v1/push-tokens` + `POST` + `DELETE /{id}`
- `GET /v1/analytics/*`

**Billing:**
- `POST /billing/checkout`
- `POST /billing/portal`
- `GET /billing/expired` + `/billing/success`
- `GET /admin/subscription`
- `GET /admin/subscription/upgrade`
- `POST /admin/subscription/request-upgrade`

### 14.3 routes/web.php (Central Web)

- `GET /` — Landing Page
- `GET /pricing` — صفحة الأسعار
- `GET /signup` + `GET /find-account`
- `GET|POST /check-subdomain`
- `POST /register`
- `GET|POST /super-admin/login`
- `GET /super-admin/dashboard` (+ Blade views)

---

## 15. الـ Views

**الموقع:** `resources/views/`

```
resources/views/
├── admin/
│   ├── appointments/       ← صفحات إدارة المواعيد
│   ├── assistants/         ← صفحات إدارة المساعدين
│   ├── customers/          ← صفحات إدارة العملاء
│   ├── dashboard/          ← لوحة التحكم + مكوناتها
│   ├── onboarding/
│   │   └── wizard.blade.php ← Alpine.js CDN + Tailwind CDN — 4 خطوات
│   ├── profile/            ← صفحات الملف الشخصي
│   ├── queue/              ← صفحات الطابور
│   ├── reports/            ← صفحات التقارير
│   ├── settings/           ← صفحات الإعدادات
│   ├── staff/              ← صفحات الموظفين
│   ├── subscription/       ← صفحات الاشتراك + Upgrade
│   └── _old/               ← ⚠️ نسخ قديمة (assistants + dashboard) — تحتاج حذف
├── auth/
│   ├── login.blade.php     ← تسجيل دخول المستأجر
│   └── super-admin-login.blade.php
├── billing/
│   ├── expired.blade.php
│   └── success.blade.php
├── customer/               ← صفحات العميل العام (حجز)
├── emails/
│   ├── appointment-booked.blade.php
│   ├── appointment-reminder.blade.php
│   ├── founder-alert.blade.php
│   ├── payment-failed.blade.php
│   ├── queue-update.blade.php
│   ├── trial-nudge.blade.php
│   ├── trial-reminder.blade.php
│   ├── upgrade-approved.blade.php
│   ├── upgrade-rejected.blade.php
│   ├── welcome-assistant.blade.php
│   └── welcome-tenant.blade.php
├── landing/
│   ├── index.blade.php     ← Landing Page الرئيسية
│   └── pricing.blade.php   ← SAR 99/184/299 + ضمان 14 يوم
├── layouts/
│   ├── admin.blade.php     ← Layout لوحة الأدمن (+ View Composer لـ System Notifications)
│   └── app.blade.php       ← Layout عام
├── partials/               ← مكونات مشتركة
├── pdf/                    ← قوالب DomPDF (فواتير + مواعيد)
├── queue/                  ← صفحة حالة الطابور العامة
├── super-admin/
│   ├── layout.blade.php
│   ├── login.blade.php
│   ├── dashboard.blade.php
│   ├── kpis.blade.php       ← Revenue KPIs view
│   ├── tenants.blade.php
│   ├── activity-logs.blade.php
│   ├── settings.blade.php
│   ├── notifications.blade.php
│   ├── reports.blade.php
│   ├── subscription-plans.blade.php
│   ├── subscription-plans/ ← تفاصيل الخطط
│   ├── countries/          ← إدارة الدول
│   └── upgrade-requests/   ← طلبات الترقية
└── welcome.blade.php
```

**الـ Frontend Stack:**
- **Blade** — templating engine
- **Tailwind CSS** — via Vite build (`vite.config.js`)
- **Alpine.js** — reactivity خفيفة (CDN في Onboarding Wizard وبعض الصفحات)
- **Vite** — build tool
- **DomPDF** — تصدير PDF عبر `barryvdh/laravel-dompdf`

**View Composer (في `AppServiceProvider`):**
يُشغَّل مع كل `layouts.admin` view — يجلب آخر 5 إشعارات نظام من `system_notifications` (Central DB) ويمررها تلقائياً لجميع صفحات لوحة الأدمن.

---

## 16. نظام المصادقة والـ Tenancy

### التسجيل (Registration)

عند `POST /register`:
```
TenantRegistrationService::create()
├── إنشاء Tenant record
├── إنشاء Domain record (subdomain)
├── تشغيل Artisan migrate لقاعدة بيانات المستأجر
├── إنشاء الـ Roles (Admin Tenant / Staff / Customer)
├── إنشاء Setting record
├── إنشاء User (admin) مع Role
├── إنشاء TenantSubscription (trial 14 يوم)
├── إرسال WelcomeTenantMail
└── Dispatch LinkTenantDomain Job (dev only)
```

### المصادقة

| النوع | الطريقة | الحالة |
|-------|---------|--------|
| Web Session (Tenant) | `TenantAuthController@login` | ✅ |
| API Token (Tenant) | `POST /api/auth/login` → Sanctum Token | ✅ |
| Token-based V1 | `POST /v1/auth/login` → `X-Tenant-Token` context | ✅ |
| SuperAdmin | `SuperAdminAuthController@login` → `auth:web` guard مستقل | ✅ |

### صلاحيات المساعدين (Assistants)

المساعدون لهم `permissions` JSON array على الـ `users` table:
```
manage_appointments | manage_queue | manage_staff | manage_customers
view_reports | manage_settings | manage_assistants
```
الفحص يتم عبر middleware `CheckRole`.

---

## 17. نظام الاشتراكات والفوترة

### دورة حياة الاشتراك

```
Trial (14 يوم) ──→ Active ──→ Grace (3 أيام عند فشل الدفع) ──→ Expired
                          ↘ Cancelled
```

| الحقل | الجدول | الغرض |
|-------|--------|-------|
| `status` | `tenant_subscriptions` | trial / active / grace / cancelled / expired |
| `trial_ends_at` | `tenant_subscriptions` | نهاية فترة التجربة |
| `grace_ends_at` | `tenant_subscriptions` | نهاية فترة السماح عند فشل الدفع |
| `converted_at` | `tenant_subscriptions` | متى انتقل المستأجر من Trial إلى Paid |
| `trial_extended` | `tenant_subscriptions` | هل تم تمديد التجربة |
| `stripe_price_id` | `subscription_plans` | معرف Stripe للخطة |

### Stripe Webhooks المُعالَجة

| Event | الإجراء |
|-------|---------|
| `customer.subscription.created` | ✅ تحديث الاشتراك |
| `customer.subscription.updated` | ✅ تحديث الحالة |
| `customer.subscription.deleted` | ✅ إلغاء الاشتراك |
| `invoice.paid` | ✅ تجديد الاشتراك |
| `invoice.payment_failed` | ✅ وضع `grace` + ضبط `grace_ends_at` + إرسال `PaymentFailedMail` |
| `checkout.session.completed` | ✅ ضبط `converted_at` |

**التحقق من الـ Signature:** `StripeService::constructWebhookEvent()` يتحقق قبل أي معالجة.

---

## 18. نظام الـ Onboarding

**Wizard من 4 خطوات** يظهر عند أول دخول للـ Dashboard:

| الخطوة | المحتوى | الـ Route |
|--------|---------|----------|
| Step 1 | اسم النشاط + شعار + هاتف | `POST /admin/onboarding/settings` |
| Step 2 | إضافة خدمة واحدة | `POST /admin/onboarding/service` |
| Step 3 | أوقات العمل الأساسية | `POST /admin/onboarding/working-hours` |
| Step 4 | إتمام + رابط الحجز | `POST /admin/onboarding/complete` |

- Middleware `RedirectIfOnboardingIncomplete` → يوجّه لـ Wizard إذا `onboarding_completed = false`
- Wizard Blade View: Alpine.js CDN stepper

### نظام Trial Nudges

`ProcessTrialNudges` Command يُرسل `TrialNudgeMail` (قالب عربي + جدول SAR) في:
- اليوم **1** من التجربة
- اليوم **3**
- اليوم **7**
- اليوم **12**

### Aha Moment Tracking

`AppointmentObserver` يُفعَّل عند إتمام **5 مواعيد** ويُسجّل `aha_moment_at` في الـ `usage_logs`.

---

## 19. نظام التذكيرات التلقائية

Command `ProcessReminders` يعمل كل **15 دقيقة** ويتحقق:
1. يجلب `ReminderRule` records الفعّالة
2. يحسب الـ appointments التي يقترب موعدها (±7 دقائق window)
3. يُطلق `SendAppointmentNotification` Job لكل موعد مطابق
4. يُسجّل في `reminder_logs` لتجنب التكرار

**إدارة قواعد التذكير:**
- CRUD كامل عبر `ReminderRuleController`
- دعم toggle (تفعيل/تعطيل) + reorder
- سجل كامل قابل للقراءة عبر `ReminderLogController` مع stats

---

## 20. Analytics & Reporting

### Analytics API (5 endpoints)

| Endpoint | البيانات |
|----------|---------|
| `GET /admin/api/analytics/summary` | ملخص شامل |
| `GET /admin/api/analytics/daily` | يومي للفترة المحددة |
| `GET /admin/api/analytics/heatmap` | خريطة حرارة الأوقات |
| `GET /admin/api/analytics/staff` | أداء الموظفين |
| `GET /admin/api/analytics/services` | أداء الخدمات |

### التصدير

| النوع | الـ Export Class |
|-------|----------------|
| مواعيد Excel | `AppointmentsExport` |
| طابور Excel | `QueuesExport` |
| فواتير Excel | `InvoicesExport` |
| فاتورة PDF | DomPDF template |
| مواعيد CSV | direct stream |
| فواتير CSV | direct stream |

---

## 21. الـ GDPR والخصوصية

`GdprController` يُنفّذ توجيهات GDPR الأوروبية:

| الحق القانوني | الـ Route | التفصيل |
|----------------|----------|---------|
| Right to Access (Art.20) | `POST /gdpr/customers/{id}/export` | تصدير كل بيانات العميل |
| Right to Erasure (Art.17) | `POST /gdpr/customers/{id}/delete` | إخفاء هوية العميل (anonymize) |
| Consent Management | `POST /gdpr/customers/{id}/consents` | تسجيل الموافقة |
| Consent Withdrawal | `DELETE /gdpr/customers/{id}/consents/{id}` | سحب الموافقة |
| Consent List | `GET /gdpr/consents` | قائمة جميع الموافقات |

---

## 22. دعم متعدد اللغات

**15 لغة مدعومة:**

| AR | EN | FR | ES | DE | IT | PT | RU | ZH | JA | TR | HI | KO | NL | ID |
|----|----|----|----|----|----|----|----|----|----|----|----|----|----|----|
| عربي | إنجليزي | فرنسي | إسباني | ألماني | إيطالي | برتغالي | روسي | صيني | ياباني | تركي | هندي | كوري | هولندي | إندونيسي |

**RTL:** دعم كامل للعربية في جميع الـ views.

**`HasTranslations` Trait:**
```php
// في الـ Model:
$service->trans('name'); // يقرأ من JSON column حسب app()->getLocale()

// JSON column مثال:
{"ar": "قص الشعر", "en": "Haircut", "fr": "Coupe de cheveux"}
```

**ملفات الترجمة:** `lang/ar.json` + `lang/ar/` + (14 لغة أخرى).

---

## 23. التسعير الجغرافي

| المكون | الحالة |
|--------|--------|
| اكتشاف البلد | ✅ `CF-IPCountry` header من Cloudflare |
| أسعار مخصصة لكل دولة | ✅ `PlanPrice` model (plan_id + country_code + price + currency) |
| عملة مخصصة | ✅ `CountrySetting` (currency + currency_symbol + timezone) |
| ضريبة مخصصة | ✅ `CountryTax` (tax_rate + tax_name) |
| عرض السعر المحلي | ✅ `GeoService@getPlansForCountry()` في Landing + Pricing |
| أسعار السوق السعودي | SAR 99 / 184 / 299 شهرياً |

---

## 24. الاختبارات

### النتيجة الكاملة: **191 test ✅ — 472 assertions — 0 failures**

| ملف الاختبار | الـ Tests | الحالة | ما يُختبر |
|-------------|---------|--------|---------|
| `Feature/Admin/AppointmentControllerTest` | 15 | ✅ | store + show + update + destroy + quickStatus + addToQueue + removeFromQueue + sendReminder + bulkDayAction + QRCode + rate |
| `Feature/Admin/QueueControllerTest` | 15 | ✅ | addDirect + callNext + serve + complete + returnToWaiting + setPriority + get + update + remove + moveToNextDay |
| `Feature/Admin/ServiceControllerTest` | 12 | ✅ | CRUD + TimeSlots + WorkingDays |
| `Feature/Admin/SettingControllerTest` | 10 | ✅ | store + update + logo upload |
| `Feature/Admin/StaffControllerTest` | 13 | ✅ | store مع services + schedule + update + destroy + bySpecialization + validation |
| `Feature/Admin/DashboardControllerTest` | 5 | ✅ | 5 dashboard stats endpoints |
| `Feature/Requests/AppointmentRequestTest` | 11 | ✅ | validation rules للمواعيد |
| `Feature/Requests/StaffRequestTest` | 12 | ✅ | validation بـ PHP Attributes |
| `Feature/RepositoryServiceProviderTest` | 5 | ✅ | ربط الـ Interfaces بالـ Implementations |
| `Unit/Repositories/AppointmentRepositoryTest` | 15 | ✅ | paginate مع filters + stats + CRUD |
| `Unit/Repositories/QueueRepositoryTest` | 14 | ✅ | getByDate + getActive + callNext + stats |
| `Unit/Repositories/StaffRepositoryTest` | 20 | ✅ | all + find + create + update + delete + bySpecialization |
| `Feature/AppointmentQueueIntegrationTest` | 9 | ✅ | integration tests: appointment ↔ queue coupling |
| `Feature/AppointmentActionsTest` | 14 | ✅ | admin actions + queue management |
| `Feature/PublicBookingTest` | 9 | ✅ | public booking flow end-to-end |
| `Feature/PublicQueueTest` | 10 | ✅ | public queue status |
| `Feature/Admin/OnboardingControllerTest` | 9 | ✅ | 4 steps + validation + completion |
| `Feature/Billing/StripeWebhookControllerTest` | 7 | ✅ | signature + payment_failed + checkout.completed + unknown event |
| `ExampleTest` | 1 | ✅ | sanity check |
| **المجموع** | **191** | **✅** | |

### ما لا يُختبر حالياً

- SuperAdmin Controllers
- Holiday Controller
- Invoice Controller
- Waiting List Controller
- Profile Controller (Web)
- Assistant Controller (Web)
- Domain SlotEngine (Unit Tests)
- Domain BookingCreationService (Unit Tests)
- GeoService
- RecurringAppointmentService
- Analytics aggregation

### Base Test Classes

| Class | الغرض |
|-------|-------|
| `TestCase` | Base class — central DB (SQLite in-memory) |
| `TenantTestCase` | Extends TestCase — يُهيئ Tenant DB وينفذ tenant migrations |
| `CreatesApplication` | Trait لإنشاء الـ Application في الاختبارات |

### تقنيات الاختبار المستخدمة
- **PHPUnit 11.5** مع PHP Attributes (`#[Test]`, `#[Group]`)
- **RefreshDatabase** لعزل كل اختبار
- **Mockery** لـ mocking الـ Services (StripeService)
- **Mail::fake()** لاختبار الـ Emails بدون إرسال حقيقي
- **TenantTestCase** — يعزل كل اختبار في قاعدة بيانات مستأجر مؤقتة (SQLite)

---

## 25. Service Providers

**الموقع:** `app/Providers/`

> **تسجيل Providers:** يتم في `bootstrap/providers.php` (نمط Laravel 12 — لا يوجد في `config/app.php`)
> ```php
> return [
>     App\Providers\AppServiceProvider::class,
>     App\Providers\TenancyServiceProvider::class,
>     App\Providers\RepositoryServiceProvider::class,
> ];
> ```

| Provider | الغرض |
|---------|-------|
| `AppServiceProvider` | تسجيل `AppointmentObserver` + View Composer لـ System Notifications + تعيين مسار الترجمة لـ `lang/` |
| `RepositoryServiceProvider` | ربط الـ Repository Interfaces بالـ Eloquent Implementations في IoC Container |
| `TenancyServiceProvider` | إعداد جميع Tenancy Events, Bootstrappers, Middleware الـ Routing |

### TenancyServiceProvider — Events Pipeline

```
TenantCreated → CreateDatabase → MigrateDatabase
TenantDeleted → DeleteDatabase
DomainCreated → LinkTenantDomain (Job — dev only)
```

**Bootstrappers المفعّلة عند تهيئة كل Tenant:**
| الـ Bootstrapper | الغرض |
|----------------|-------|
| `DatabaseTenancyBootstrapper` | تبديل DB connection لقاعدة المستأجر |
| `CacheTenancyBootstrapper` | عزل الـ Cache بـ prefix خاص بالمستأجر |
| `FilesystemTenancyBootstrapper` | عزل التخزين (Storage paths) |

---

## 26. Observers

**الموقع:** `app/Observers/`

### AppointmentObserver

**التسجيل:** `AppServiceProvider::boot()` → `Appointment::observe(AppointmentObserver::class)`

**الحدث المُستمَع إليه:** `updated` — يتفاعل فقط عند تغيير عمود `status`.

| تغيير الحالة | الإجراء التلقائي |
|-------------|----------------|
| → `completed` | `createCommission()` — ينشئ `StaffCommission` record تلقائياً |
| → `completed` | `checkAhaMoment()` — يفحص إذا وصل لـ 5 مواعيد مكتملة ويُسجّل Aha Moment |
| → `confirmed` أو `completed` مع `recurring_id` | `GenerateNextRecurringAppointment::dispatch()` |
| → `cancelled` | `NotifyWaitingListOnAvailability::dispatch()` |

#### منطق createCommission() بالتفصيل
```
1. يجلب Staff Model عبر appointment->staffMember
2. يتحقق من وجود commission_type + commission_value
3. يتحقق من عدم وجود عمولة مسبقة للموعد ذاته
4. يحسب العمولة:
   - percentage: gross × (rate / 100)
   - fixed: قيمة ثابتة
5. ينشئ StaffCommission record بـ is_paid = false
6. يُسجّل Log::info
```

#### منطق checkAhaMoment() بالتفصيل
```
1. يحسب عدد المواعيد المكتملة في قاعدة المستأجر
2. إذا < 5: يخرج
3. يجلب TenantSubscription من Central DB
4. إذا aha_reached = true: يخرج (تجنب التكرار)
5. يُحدّث aha_reached = true + aha_reached_at = now()
6. يُسجّل UsageLog::log('aha_moment_reached')
```

---

## 27. Exports

**الموقع:** `app/Exports/`

| الـ Export Class | الـ Route | الصيغة |
|----------------|---------|--------|
| `AppointmentsExport` | `GET /admin/reports/export-appointments` | Excel (.xlsx) |
| `QueuesExport` | `GET /admin/queue/export-excel` | Excel (.xlsx) |
| `InvoicesExport` | `GET /reports/invoices/export-csv` | CSV |

جميعها تستخدم `maatwebsite/excel` package.

**تصدير PDF إضافي (بدون Export Class):**
- فاتورة واحدة PDF: `GET /reports/invoice/{id}/pdf` — يستخدم `barryvdh/laravel-dompdf` مباشرة
- مواعيد PDF: `GET /admin/reports/export-pdf` — DomPDF

---

## 28. Database Seeders & Factories

**الموقع:** `database/seeders/` و `database/factories/`

### Seeders (8)

| Seeder | الغرض |
|--------|-------|
| `DatabaseSeeder` | الـ Entry Point — يُشغّل `TenantSeeder` فقط (للـ central DB) |
| `TenantSeeder` | ينشئ tenant تجريبي للتطوير |
| `SubscriptionPlansSeeder` | ينشئ 3 خطط (Basic $29.99 / Professional $79.99 / Enterprise $199.99) + trial 14 يوم |
| `SuperAdminSeeder` | ينشئ حساب السوبر أدمن الافتراضي |
| `RoleSeeder` | ينشئ الأدوار الأساسية (Admin Tenant / Staff / Customer) |
| `RolesAndPermissionsSeeder` | seeder بديل مع صلاحيات مفصلة |
| `TenantUsersSeeder` | ينشئ مستخدمين تجريبيين داخل Tenant context |
| `WorkingDaysSeeder` | ينشئ أيام العمل الافتراضية (الأحد → الخميس) |

### Factories (1)

| Factory | الغرض |
|---------|-------|
| `UserFactory` | توليد بيانات مستخدمين وهمية في الاختبارات (Faker) |

---

## 29. Config Files

**الموقع:** `config/`

| الملف | أهم الإعدادات |
|-------|-------------|
| `app.php` | APP_NAME + timezone (Asia/Riyadh) + locale + providers list |
| `auth.php` | guards: web (session) + sanctum (token) + providers (users Eloquent) |
| `cache.php` | driver: redis في الإنتاج / array في الاختبارات |
| `database.php` | connections: mysql (central) + sqlite (tenant fallback للاختبارات) |
| `filesystems.php` | disks: local + public + tenant-root + tenant-public |
| `logging.php` | channels: stack + daily + slack |
| `mail.php` | driver: smtp + queue: emails |
| `queue.php` | driver: database + connections + queues: default + emails |
| `sanctum.php` | guard + token prefix + expiration |
| `services.php` | stripe.key + stripe.secret + stripe.webhook_secret |
| `session.php` | driver: database + lifetime + encrypt |
| `tenancy.php` | tenant_model + bootstrappers + central_domains + database config |

**ملاحظة مهمة على `tenancy.php`:**
- `central_domains`: تحتوي على domain المشروع الرئيسي (e.g. `velora.test`)
- `bootstrappers`: قائمة الـ 3 Bootstrappers (Database + Cache + Filesystem)
- `database > template_tenant_connection`: القالب المستخدم لإنشاء Tenant DB connections

---

## 30. ملفات التهيئة الجذرية

**الملفات الموجودة في Root Directory:**

| الملف | الغرض |
|-------|-------|
| `artisan` | Laravel CLI entry point — تشغيل Commands |
| `composer.json` | تعريف PHP Dependencies + Autoloading + Scripts |
| `composer.lock` | قفل إصدارات الـ PHP packages المثبتة |
| `package.json` | تعريف JS Dependencies (Vite, Tailwind, Alpine.js) |
| `package-lock.json` | قفل إصدارات الـ npm packages المثبتة |
| `phpunit.xml` | إعداد PHPUnit — Test Suites + ENV overrides للاختبار |
| `tailwind.config.js` | إعداد Tailwind CSS — Content paths + Dark Mode (class) |
| `vite.config.js` | إعداد Vite — Entrypoints + Hot Reload + Tailwind plugin |
| `README.md` | ملف التوثيق العام للمشروع |
| `PROJECT_REPORT.md` | تقرير المشروع الأولي |
| `PROJECT_REPORT_FULL.md` | هذا التقرير الشامل |
| `.env` | متغيرات البيئة الفعلية (غير مُضمَّنة في Git) |
| `.env.example` | نموذج متغيرات البيئة (يُستخدم للإعداد الأوّلي) |
| `.gitignore` | ملفات ومجلدات مستثناة من Git (vendor/, .env, storage/...) |
| `.gitattributes` | إعدادات معالجة ملفات Git (line endings, diff...) |
| `.editorconfig` | معايير تنسيق الكود عبر المحررات المختلفة |
| `.phpunit.result.cache` | ذاكرة تخزين مؤقت لنتائج PHPUnit (مُولَّد تلقائياً) |

### phpunit.xml — أبرز الإعدادات

```xml
<testsuites>
  <testsuite name="Unit">  → tests/Unit/  </testsuite>
  <testsuite name="Feature">  → tests/Feature/  </testsuite>
</testsuites>
<php>
  DB_CONNECTION=sqlite, DB_DATABASE=:memory:   <!-- قاعدة بيانات في الذاكرة -->
  TENANCY_CENTRAL_CONNECTION=sqlite
  QUEUE_CONNECTION=sync                        <!-- Jobs تُنفَّذ فوراً في الاختبار -->
  MAIL_MAILER=array                            <!-- لا ترسل emails حقيقية -->
</php>
```

### vite.config.js — Entrypoints

```
resources/css/app.css  →  compiled CSS
resources/js/app.js   →  compiled JS
plugins: laravel-vite + @tailwindcss/vite
server.watch.ignored: storage/framework/views/**
```

### tailwind.config.js — Content Scan

```
resources/**/*.blade.php
resources/**/*.js
resources/**/*.vue
darkMode: 'class'  →  Dark mode يُفعَّل عبر class="dark" على <html>
```

---

## 31. مجلد outFiles/ — الوثائق والسكريبتات الخارجية

**الموقع:** `outFiles/`  
**الغرض:** مجلد خارجي يحوي ملفات نُنتِجت خلال دورة تطوير المشروع — وثائق تقنية تاريخية، سكريبتات تشخيصية، وصفحات اختبار HTML يدوية. **هذه الملفات ليست جزءاً من كود التطبيق** ولا يتم تحميلها في الإنتاج.

---

### 31.1 outFiles/docs/ — 31 ملف توثيق

| الملف | المحتوى |
|-------|----------|
| `VELORA_MASTERPLAN.md` | خطة المشروع الرئيسية — المراحل والمتطلبات الكاملة |
| `DEPLOYMENT_GUIDE.md` | دليل نشر التطبيق على الإنتاج |
| `MULTI_LANGUAGE_SYSTEM.md` | توثيق نظام دعم 15 لغة |
| `SUPER_ADMIN_COMPLETE_GUIDE.md` | دليل شامل لنظام Super Admin |
| `SUPER_ADMIN_FINAL_REPORT.md` | تقرير نهائي عن تطوير Super Admin |
| `SUPER_ADMIN_QUICK_REFERENCE.md` | مرجع سريع لأوامر Super Admin |
| `SUPER_ADMIN_ROUTES_FIX.md` | توثيق إصلاح مشكلة Routes الخاصة بـ Super Admin |
| `SUPER_ADMIN_SETUP.md` | تعليمات إعداد Super Admin |
| `SUPER_ADMIN_TESTING_REPORT.md` | نتائج اختبار Super Admin |
| `APPOINTMENT_LOGIC.md` | توثيق منطق المواعيد |
| `APPOINTMENT_TESTING_GUIDE.md` | دليل اختبار المواعيد |
| `BUTTON_FIX_GUIDE.md` | توثيق إصلاح أزرار Queue |
| `COLOR_IMPLEMENTATION_COMPLETE.md` | توثيق تطبيق نظام الألوان |
| `COLOR_SYSTEM.md` | توثيق نظام الألوان الكامل |
| `DARK_MODE_ACTIVATION.md` | توثيق تفعيل Dark Mode |
| `DARK_MODE_CHECKLIST.md` | قائمة تدقيق Dark Mode |
| `DARK_MODE_ENHANCEMENTS.md` | تحسينات Dark Mode |
| `DARK_MODE_FLASH_FIX.md` | توثيق إصلاح مشكلة Flash في Dark Mode |
| `DARK_MODE_README.md` | دليل Dark Mode |
| `DASHBOARD_ENHANCEMENT_COMPLETE.md` | توثيق تحسينات لوحة التحكم |
| `DASHBOARD_FEATURES_COMPLETE.md` | ميزات لوحة التحكم المكتملة |
| `DASHBOARD_FIXES.md` | توثيق إصلاحات لوحة التحكم |
| `DASHBOARD_UPGRADE.md` | توثيق ترقية لوحة التحكم |
| `IMPLEMENTATION_SUCCESS.md` | تقارير نجاح التطبيق |
| `INTEGRATION_SUMMARY.md` | ملخص عمليات التكامل |
| `LOGIN_DESIGN_UPDATE.md` | توثيق تحديث تصميم صفحة Login |
| `PROJECT_STATUS_SUMMARY.md` | ملخص حالة المشروع |
| `PROJECT_WORK_LOG.md` | سجل عمل تطوير المشروع |
| `QUEUE_BUTTONS_DEBUG_GUIDE.md` | دليل تشخيص مشكلة Queue Buttons |
| `TESTING_INDEX.md` | فهرس الاختبارات |
| `TESTING_README.md` | دليل الاختبار العام |
| `WHAT_IS_MISSING.md` | قائمة ما كان مفقوداً وتم تنفيذه |

---

### 31.2 outFiles/scripts/ — 14 سكريبت تشخيصي وأدوات

| الملف | النوع | الغرض |
|-------|-------|--------|
| `add-subdomain.ps1` | PowerShell | إضافة subdomain واحد لـ Windows hosts |
| `add-subdomains.ps1` | PowerShell | إضافة مجموعة subdomains دفعة واحدة |
| `add_language_column.php` | PHP | ترحيل يدوي لإضافة عمود اللغة |
| `audit_all.php` | PHP | مراجعة شاملة لحالة جميع المستأجرين |
| `button-fix-script.js` | JavaScript | إصلاح مستهدف لأزرار Queue |
| `check_settings.php` | PHP | فحص صحة إعدادات Tenant Settings |
| `debug-queue-buttons.js` | JavaScript | تشخيص مشاكل Queue Buttons في المتصفح |
| `fix_dashboard_translations.php` | PHP | إصلاح مفاتيح الترجمة الناقصة في Dashboard |
| `fix_tenant_languages.php` | PHP | إصلاح تعيين اللغات للمستأجرين |
| `link-existing-tenants.php` | PHP | ربط المستأجرين القدامى بالبنية الجديدة |
| `test-dashboard-apis.php` | PHP | اختبار API endpoints لوحة التحكم |
| `test-stats.php` | PHP | اختبار إحصاءات النظام |
| `test-super-admin-routes.php` | PHP | اختبار Routes الخاصة بـ Super Admin |
| `test_apis.php` | PHP | اختبار API endpoints عام |

---

### 31.3 outFiles/tests/ — 13 صفحة اختبار HTML يدوية

| الملف | الغرض |
|-------|--------|
| `super-admin-test.html` | اختبار واجهة Super Admin |
| `test-dashboard-features.html` | اختبار ميزات لوحة التحكم |
| `test-queue-buttons.html` | اختبار أزرار Queue |
| `test-super-admin-routes.html` | اختبار Routes السوبر أدمن |
| `test-dashboard-components.html` | اختبار مكونات Dashboard |
| `dark-mode-test.html` | اختبار Dark Mode |
| `login-dark-test.html` | اختبار Dark Mode في صفحة Login |
| `test-dark-mode.html` | اختبار وضع الظلام العام |
| `simple-dark-test.html` | اختبار Dark Mode مبسط |
| `reset-dark-mode.html` | أداة إعادة تعيين Dark Mode |
| `reset-all-dark-mode.html` | أداة إعادة تعيين شاملة لـ Dark Mode |
| `button-test-diagnostic.html` | تشخيص أزرار |
| `TESTING_GUIDE.html` | دليل الاختبار اليدوي الشامل |

---

## 32. مجلد project_img/ — الصور الثابتة

**الموقع:** `project_img/`

| المجلد الفرعي | الغرض |
|--------------|--------|
| `avatars/` | صور الأفاتار الخاصة بالمستخدمين — مستخدمة في Storage (tenant-isolated) |

> **ملاحظة:** يُستخدم هذا المجلد كمسار تخزين ثابت لصور الأفاتار خلال بيئة التطوير. في الإنتاج يُفضَّل تخزين الملفات عبر `storage/app/` مع نظام تعدد المستأجرين (FilesystemTenancyBootstrapper).

---

## 33. مجلد public/ — نقطة دخول الويب

**الموقع:** `public/`  
**الغرض:** المجلد الوحيد المكشوف للويب (Document Root لـ Nginx/Apache/Herd)

| الملف / المجلد | الغرض |
|----------------|-------|
| `index.php` | نقطة الدخول الرئيسية — يُحمِّل `bootstrap/app.php` |
| `.htaccess` | إعادة كتابة الـ URLs لـ Apache (كل الطلبات → index.php) |
| `robots.txt` | تعليمات محركات البحث |
| `favicon.ico` | أيقونة الـ browser tab |
| `css/` | Assets CSS المبنية عبر Vite |
| `js/` | Assets JS المبنية عبر Vite |
| `project_img/` | Symlink إلى `../project_img/` لعرض صور الأفاتار مباشرة |

---

## 34. مجلد storage/ — التخزين الداخلي

**الموقع:** `storage/`  
**الغرض:** كل الملفات المُولَّدة تلقائياً (logs, cache, sessions, compiled views)

```
storage/
├── app/
│   ├── private/          ← ملفات التطبيق الخاصة (غير قابلة للوصول مباشرة)
│   └── public/           ← ملفات مرتبطة بـ public symlink
├── framework/
│   ├── cache/            ← ملفات الـ Cache المُخزَّنة (array/file driver)
│   ├── sessions/         ← ملفات جلسات المستخدمين
│   ├── testing/          ← ملفات مؤقتة خاصة بالاختبارات
│   └── views/            ← Blade templates مُصرَّفة (compiled)
├── logs/
│   └── laravel.log       ← سجل أخطاء وأحداث التطبيق
└── tenanttest-tenant-*/  ← مجلدات Storage معزولة لكل Tenant (FilesystemTenancyBootstrapper)
```

> كل مجلد بالاسم `tenanttest-tenant-{id}/` هو Storage معزول تلقائياً لمستأجر معين عند تفعيل `FilesystemTenancyBootstrapper`.

---

## 35. مجلد resources/lang/ — ترجمات PHP المنظَّمة

**الموقع:** `resources/lang/`  
**الفرق عن `lang/` الجذري:** مجلد `lang/` الجذري يحتوي على ملفات JSON للـ 15 لغة (ترجمات Blade `__('key')`). أما `resources/lang/` فيحتوي على ملفات PHP array لترجمات مُهيكَلة (booking, messages, notifications).

**الهيكل:**
```
resources/lang/
├── ar/
│   ├── booking.php       ← ترجمات نظام الحجز بالعربية
│   ├── messages.php      ← رسائل النظام بالعربية
│   └── notifications.php ← نصوص الإشعارات بالعربية
├── ar.json
├── en/
│   ├── booking.php       ← ترجمات نظام الحجز بالإنجليزية
│   ├── messages.php      ← رسائل النظام بالإنجليزية
│   └── notifications.php ← نصوص الإشعارات بالإنجليزية
└── en.json
```

> **ملاحظة عن `lang/` الجذري:** يحتوي على 15 لغة كل واحدة بمجلدها (ar, de, en, es, fr, hi, id, it, ja, ko, nl, pt, ru, tr, zh) مع ملف JSON شامل لكل لغة — هذا هو المصدر الرئيسي للترجمات في Blade views.

---

## 36. الحالة التقنية الراهنة

### الملف الحالي المفتوح في المحرر

**`tests/Feature/Billing/StripeWebhookControllerTest.php`**

هذا الملف يحتوي على **7 اختبارات integration** لـ Stripe Webhook Controller:

1. `missing_signature_header_returns_400` — فحص غياب الـ signature header
2. `invalid_stripe_signature_returns_400` — فحص الـ signature الخاطئ
3. `payment_failed_sends_email_and_sets_grace_period` — ✅ الأهم — يتحقق من:
   - تغيير status إلى `grace`
   - ضبط `grace_ends_at`
   - إرسال `PaymentFailedMail`
4. `payment_failed_without_tenant_id_metadata_returns_ok_silently` — edge case: لا tenant_id
5. `checkout_completed_stamps_converted_at` — يتحقق من ضبط `converted_at`
6. `checkout_completed_without_tenant_id_returns_ok` — edge case
7. `unknown_event_type_returns_ok_and_is_ignored` — أي event مجهول يُتجاهل بـ 200

### ما هو مكتمل ✅

- **كل الـ Core Features** شغّالة وجاهزة للإنتاج
- **0 bugs** معروفة
- **191 test تمر** بدون أي فشل
- **Stripe Webhooks** مُختبرة بالكامل
- **Trial System** (14 يوم + Nudges) جاهز
- **Onboarding Wizard** جاهز
- **Revenue KPIs API** جاهز في SuperAdmin

### ما يحتاج عمل 🔴

| المهمة | الأولوية | الجهد المتوقع |
|--------|---------|--------------|
| Trial Expiry Banner في Dashboard | 🔴 عالي | 3 ساعات |
| Stripe `payment_failed` email للـ tenant (تأكيد E2E) | 🔴 عالي | 2 ساعة |
| Grace Period Banner قبل القطع بـ 3 أيام | 🟡 متوسط | 2 ساعة |
| Service Categories UI في Blade | 🟡 متوسط | نصف يوم |
| Waiting List Blade View | 🟢 منخفض | 2 ساعة |
| حذف `resources/views/admin/_old/` | 🟢 منخفض | 10 دقائق |

### إحصائيات المشروع الكاملة

| المؤشر | القيمة |
|--------|-------|
| Routes المعرّفة + المكتملة | **336 route** |
| Controllers | **50 controller** |
| Models | **46 model** |
| Migrations إجمالية | **68 (24 central + 46 tenant)** |
| Mail Classes | **11 mailable** |
| Email Blade Templates | **11 template** |
| Form Requests | **6 request** |
| Jobs | **6 job** |
| Console Commands | **6 command** |
| Services | **8 (6 app + 2 domain)** |
| Domain DTOs | **3** |
| Domain Events | **2** |
| Domain Exceptions | **1** |
| Repository Interfaces | **3** |
| Repository Implementations | **3** |
| Observers | **1 (AppointmentObserver)** |
| Exports | **3 (Excel × 2 + CSV × 1)** |
| Service Providers | **3** |
| Seeders | **8** |
| Factories | **1 (UserFactory)** |
| Config Files | **12** |
| Middleware | **14** |
| Blade Views (dirs) | **~60+ view** |
| لغات مدعومة | **15 لغة** |
| اختبارات تمر | **191 test / 472 assertion** |
| تحذيرات PHPUnit | **0** |
| Bugs معروفة | **0** |
| ملفات الترجمة PHP (resources/lang) | **6 ملف (booking + messages + notifications × ar + en)** |
| ملفات التوثيق (outFiles/docs) | **31 ملف Markdown** |
| سكريبتات أدوات (outFiles/scripts) | **14 سكريبت (PHP + JS + PS1)** |
| صفحات اختبار يدوية (outFiles/tests) | **13 صفحة HTML** |
| ملفات Root Config | **6 (artisan, composer.json, package.json, phpunit.xml, tailwind.config.js, vite.config.js)** |
| ملفات Root أخرى | **7 (.env, .env.example, .gitignore, .gitattributes, .editorconfig, README.md, composer.lock)** |

---

*آخر تحديث للتقرير: مارس 2026*
