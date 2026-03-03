# SUPER ADMIN DASHBOARD - QUICK REFERENCE

## 🔐 بيانات الدخول

### Super Admin
```
URL: http://booking-saas.test/super-admin/login
Email: superadmin@bookingsaas.com
Password: SuperAdmin@123
```

---

## 🛠️ الأوامر المهمة

### إنشاء Super Admin جديد
```bash
php artisan db:seed --class=SuperAdminSeeder
```

### عرض جميع Tenants
```bash
php artisan tenants:list
```

### تشغيل Migrations لـ Tenant معين
```bash
php artisan tenants:migrate --tenants=<tenant-id>
```

### مسح Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 📍 الـ Routes الرئيسية

### Web Routes
- `GET /super-admin/login` - صفحة تسجيل الدخول
- `POST /super-admin/login` - تسجيل الدخول
- `POST /super-admin/logout` - تسجيل الخروج
- `GET /super-admin/dashboard` - لوحة التحكم الرئيسية
- `GET /super-admin/tenants` - إدارة الشركات

### API Routes
- `POST /api/super-admin/auth/login` - API Login
- `POST /api/super-admin/auth/logout` - API Logout
- `GET /api/super-admin/auth/profile` - Super Admin Profile
- `GET /api/super-admin/dashboard` - Dashboard Stats
- `GET /api/super-admin/tenants` - Get All Tenants
- `POST /api/super-admin/tenants` - Create Tenant
- `GET /api/super-admin/tenants/{id}` - Get Tenant
- `PUT /api/super-admin/tenants/{id}` - Update Tenant
- `DELETE /api/super-admin/tenants/{id}` - Delete Tenant
- `POST /api/super-admin/tenants/{id}/toggle-status` - Activate/Deactivate

---

## 🎨 الصفحات

### 1. Login Page
**الموقع**: `resources/views/super-admin/login.blade.php`
**المميزات**:
- دعم RTL
- Dark Mode Ready
- Remember Me
- Error Messages

### 2. Dashboard
**الموقع**: `resources/views/super-admin/dashboard.blade.php`
**المميزات**:
- إحصائيات النظام
- إحصائيات Tenants
- قائمة Tenants الحديثة

### 3. Tenants Management
**الموقع**: `resources/views/super-admin/tenants.blade.php`
**المميزات**:
- عرض جميع Tenants
- إضافة Tenant جديد
- تعديل Tenant
- حذف Tenant
- عرض Tenant credentials

---

## 📊 الـ Models

### User Model
```php
// Helper Methods
$user->isSuperAdmin(); // Check if Super Admin
$user->isAdminTenant(); // Check if Admin Tenant
$user->isStaff(); // Check if Staff
$user->isCustomer(); // Check if Customer

// Relationships
$user->role; // Role relationship
$user->appointments; // User's appointments
$user->notifications; // User's notifications
```

### Tenant Model
```php
// Attributes (stored in 'data' JSON column)
$tenant->name; // Tenant name
$tenant->active; // Active status
$tenant->email; // Tenant email

// Relationships
$tenant->domains; // Tenant domains
$tenant->users; // Tenant users (in tenant DB)
```

### Role Model
```php
// Default Roles
- Super Admin (ID: 5) - للتحكم في النظام بالكامل
- Admin Tenant (ID: 1) - مدير الشركة
- Staff (ID: 2) - موظف
- Assistant (ID: 3) - مساعد
- Customer (ID: 4) - عميل
```

---

## 🔒 الـ Middleware

### CheckSuperAdmin
**الملف**: `app/Http/Middleware/CheckSuperAdmin.php`
**الوظيفة**: التحقق من صلاحيات Super Admin

**الاستخدام**:
```php
Route::middleware(['auth', 'super.admin'])->group(function () {
    // Routes protected by Super Admin middleware
});
```

**السلوك**:
- إذا لم يكن مسجل دخول → Redirect to login
- إذا لم يكن Super Admin → 403 Forbidden
- إذا API request → JSON response

---

## 🗄️ Database Structure

### Central Database
**الجداول الرئيسية**:
- `tenants` - بيانات الشركات
- `domains` - نطاقات الشركات
- `users` - مستخدمي النظام (Super Admins فقط)
- `roles` - الأدوار
- `migrations` - سجل Migrations

### Tenant Database
**الاسم**: `tenant<uuid-without-dashes>`
**الجداول**:
- `users` - مستخدمي الشركة
- `roles` - أدوار الشركة
- `appointments` - المواعيد
- `queues` - قوائم الانتظار
- `invoices` - الفواتير
- `notifications` - الإشعارات
- `settings` - إعدادات الشركة

---

## 🐛 استكشاف الأخطاء

### مشكلة: Route Not Found
```bash
# الحل
php artisan route:clear
php artisan config:clear
```

### مشكلة: 403 Forbidden
```bash
# تأكد من:
1. المستخدم مسجل دخول
2. المستخدم لديه role_id = 5 (Super Admin)
3. CheckSuperAdmin middleware مضاف للـ route
```

### مشكلة: Tenant Database Not Created
```bash
# الحل اليدوي
php artisan tenants:migrate --tenants=<tenant-id>

# أو من TenantController سيتم automatically
```

### مشكلة: CSRF Token Mismatch
```bash
# الحل
- تأكد من @csrf في الـ form
- امسح cache المتصفح
- php artisan config:clear
```

---

## 📦 الـ Controllers

### SuperAdminAuthController
**المسؤوليات**:
- Web Login/Logout
- API Login/Logout
- Get Profile

**الـ Methods**:
```php
webLogin(Request $request) // Web login
webLogout(Request $request) // Web logout
login(Request $request) // API login
logout(Request $request) // API logout
profile(Request $request) // Get profile
```

### TenantController
**المسؤوليات**:
- CRUD operations لـ Tenants
- Auto-credential generation
- Database creation

**الـ Methods**:
```php
index() // List all tenants
store(Request $request) // Create tenant
show(string $id) // Show tenant
update(Request $request, string $id) // Update tenant
destroy(string $id) // Delete tenant
toggleStatus(string $id) // Activate/Deactivate
statistics(string $id) // Get statistics
```

### DashboardController
**المسؤوليات**:
- System statistics
- Tenants overview

---

## 🎯 Best Practices

### 1. Security
- ✅ استخدام HTTPS في Production
- ✅ تفعيل Rate Limiting
- ✅ إضافة 2FA للـ Super Admin
- ✅ تسجيل جميع Actions

### 2. Performance
- ✅ استخدام Caching للـ statistics
- ✅ Queue للـ Email notifications
- ✅ Database indexing

### 3. Maintenance
- ✅ Backup منتظم لـ Central DB
- ✅ Backup لكل Tenant DB
- ✅ Monitoring للـ disk space
- ✅ Log rotation

---

## 📞 Support

للمساعدة أو الإبلاغ عن مشاكل:
- GitHub Issues
- Documentation: SUPER_ADMIN_SETUP.md
- Testing Report: SUPER_ADMIN_TESTING_REPORT.md

---

**آخر تحديث**: 17 فبراير 2026
