<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [

            // ── General ───────────────────────────────────────────────────
            [
                'key'         => 'app_name',
                'value'       => 'Velora',
                'type'        => 'string',
                'group'       => 'general',
                'description' => 'اسم المنصة — يظهر في الإيميلات وصفحة الهبوط',
            ],
            [
                'key'         => 'app_url',
                'value'       => 'https://velora.com',
                'type'        => 'string',
                'group'       => 'general',
                'description' => 'الرابط الأساسي للمنصة بدون / في النهاية',
            ],
            [
                'key'         => 'app_logo_url',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'general',
                'description' => 'رابط شعار المنصة (URL)',
            ],
            [
                'key'         => 'registration_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'general',
                'description' => 'تفعيل / إيقاف صفحة التسجيل للعملاء الجدد',
            ],
            [
                'key'         => 'maintenance_mode',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'general',
                'description' => 'عند التفعيل تظهر صفحة "قيد الصيانة" للزوار',
            ],
            [
                'key'         => 'default_trial_days',
                'value'       => '14',
                'type'        => 'number',
                'group'       => 'general',
                'description' => 'عدد أيام الفترة التجريبية الافتراضية عند التسجيل',
            ],
            [
                'key'         => 'max_tenants',
                'value'       => '0',
                'type'        => 'number',
                'group'       => 'general',
                'description' => 'الحد الأقصى لعدد الشركات المسجّلة (0 = غير محدود)',
            ],

            // ── Geo ───────────────────────────────────────────────────────
            [
                'key'         => 'default_language',
                'value'       => 'en',
                'type'        => 'string',
                'group'       => 'geo',
                'description' => 'اللغة المستخدمة إذا لم تُعرَف دولة الزائر (en, ar, fr …)',
            ],
            [
                'key'         => 'default_currency',
                'value'       => 'USD',
                'type'        => 'string',
                'group'       => 'geo',
                'description' => 'العملة المستخدمة إذا لم تُعرَف دولة الزائر (USD, SAR, EUR …)',
            ],

            // ── Billing ───────────────────────────────────────────────────
            [
                'key'         => 'stripe_public_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'billing',
                'description' => 'يبدأ بـ pk_live_ أو pk_test_ — يُستخدم في واجهة Stripe.js',
            ],
            [
                'key'         => 'stripe_secret_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'billing',
                'description' => 'يبدأ بـ sk_live_ أو sk_test_ — لا تشاركه مع أحد',
            ],
            [
                'key'         => 'stripe_webhook_secret',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'billing',
                'description' => 'يبدأ بـ whsec_ — للتحقق من الـ events القادمة من Stripe',
            ],
            [
                'key'         => 'billing_currency',
                'value'       => 'USD',
                'type'        => 'string',
                'group'       => 'billing',
                'description' => 'العملة الافتراضية للفواتير عند عدم وجود سعر جغرافي',
            ],
            [
                'key'         => 'invoice_prefix',
                'value'       => 'INV-',
                'type'        => 'string',
                'group'       => 'billing',
                'description' => 'بادئة رقم الفاتورة (مثلاً: INV-2026-0001)',
            ],

            // ── Email ─────────────────────────────────────────────────────
            [
                'key'         => 'mail_from_address',
                'value'       => 'noreply@velora.com',
                'type'        => 'string',
                'group'       => 'email',
                'description' => 'البريد الإلكتروني الذي تُرسَل منه رسائل النظام',
            ],
            [
                'key'         => 'mail_from_name',
                'value'       => 'Velora',
                'type'        => 'string',
                'group'       => 'email',
                'description' => 'الاسم الذي يراه المستلم في حقل "من"',
            ],
            [
                'key'         => 'mail_driver',
                'value'       => 'smtp',
                'type'        => 'string',
                'group'       => 'email',
                'description' => 'طريقة إرسال البريد: smtp أو mailgun أو ses أو log',
            ],
            [
                'key'         => 'mail_host',
                'value'       => 'smtp.mailgun.org',
                'type'        => 'string',
                'group'       => 'email',
                'description' => 'عنوان سيرفر SMTP',
            ],
            [
                'key'         => 'mail_port',
                'value'       => '587',
                'type'        => 'number',
                'group'       => 'email',
                'description' => 'بورت SMTP — عادةً 587 أو 465',
            ],
            [
                'key'         => 'mail_username',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'email',
                'description' => 'اسم المستخدم لـ SMTP',
            ],
            [
                'key'         => 'mail_password',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'email',
                'description' => 'كلمة مرور SMTP — مخفية في الواجهة',
            ],

            // ── Notifications ─────────────────────────────────────────────
            [
                'key'         => 'notify_new_signup',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'notifications',
                'description' => 'إرسال إشعار للمدير عند تسجيل عميل جديد',
            ],
            [
                'key'         => 'notify_new_payment',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'notifications',
                'description' => 'إرسال إشعار للمدير عند استلام دفعة جديدة',
            ],
            [
                'key'         => 'notify_subscription_expired',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'notifications',
                'description' => 'إرسال إشعار للعميل قبل انتهاء اشتراكه',
            ],
            [
                'key'         => 'notify_days_before_expiry',
                'value'       => '7',
                'type'        => 'number',
                'group'       => 'notifications',
                'description' => 'عدد الأيام قبل انتهاء الاشتراك لإرسال التنبيه',
            ],
            [
                'key'         => 'admin_notification_email',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'notifications',
                'description' => 'البريد الذي تُرسَل إليه إشعارات الأدمن (يختلف عن mail_from_address)',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->upsert(
                $setting,
                ['key'],
                ['description'] // Only update description if key exists; don't override saved values
            );
        }
    }

    public function down(): void
    {
        $keys = [
            'app_name', 'app_url', 'app_logo_url', 'registration_enabled',
            'maintenance_mode', 'default_trial_days', 'max_tenants',
            'default_language', 'default_currency',
            'stripe_public_key', 'stripe_secret_key', 'stripe_webhook_secret',
            'billing_currency', 'invoice_prefix',
            'mail_from_address', 'mail_from_name', 'mail_driver', 'mail_host',
            'mail_port', 'mail_username', 'mail_password',
            'notify_new_signup', 'notify_new_payment', 'notify_subscription_expired',
            'notify_days_before_expiry', 'admin_notification_email',
        ];

        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};
