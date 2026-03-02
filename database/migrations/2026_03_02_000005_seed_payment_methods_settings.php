<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [

            // ── بطاقات الائتمان عبر Stripe ────────────────────────────────
            [
                'key'         => 'stripe_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'قبول بطاقات Visa و Mastercard عبر Stripe',
            ],

            // ── PayPal ────────────────────────────────────────────────────
            [
                'key'         => 'paypal_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'قبول الدفع عبر PayPal',
            ],
            [
                'key'         => 'paypal_client_id',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'PayPal Client ID من لوحة PayPal Developer',
            ],
            [
                'key'         => 'paypal_client_secret',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'PayPal Client Secret — لا تشاركه مع أحد',
            ],
            [
                'key'         => 'paypal_mode',
                'value'       => 'sandbox',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'sandbox للتجربة — live للإنتاج',
            ],

            // ── فوري (Fawry) — مصر ────────────────────────────────────────
            [
                'key'         => 'fawry_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'قبول الدفع عبر فوري (متاح في مصر)',
            ],
            [
                'key'         => 'fawry_merchant_code',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'كود التاجر من بوابة Fawry',
            ],
            [
                'key'         => 'fawry_security_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'المفتاح الأمني من بوابة Fawry — لا تشاركه',
            ],
            [
                'key'         => 'fawry_mode',
                'value'       => 'test',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'test للتجربة — live للإنتاج',
            ],

            // ── Apple Pay (عبر Stripe) ────────────────────────────────────
            [
                'key'         => 'apple_pay_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'تفعيل Apple Pay على صفحة الدفع (يتطلب Stripe + دومين موثّق)',
            ],

            // ── Google Pay (عبر Stripe) ───────────────────────────────────
            [
                'key'         => 'google_pay_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'تفعيل Google Pay على صفحة الدفع (عبر Stripe)',
            ],

            // ── مدى (Mada) — السعودية عبر Stripe ─────────────────────────
            [
                'key'         => 'mada_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'قبول بطاقات مدى السعودية عبر Stripe (يتطلب حساب Stripe KSA)',
            ],

            // ── STC Pay — السعودية ────────────────────────────────────────
            [
                'key'         => 'stc_pay_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'قبول الدفع عبر STC Pay (السعودية)',
            ],
            [
                'key'         => 'stc_pay_merchant_id',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Merchant ID من بوابة STC Pay',
            ],
            [
                'key'         => 'stc_pay_api_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'API Key من بوابة STC Pay — لا تشاركه',
            ],

            // ── تابي (Tabby) — التقسيط GCC ───────────────────────────────
            [
                'key'         => 'tabby_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'قبول التقسيط عبر تابي (السعودية، الإمارات، الكويت)',
            ],
            [
                'key'         => 'tabby_public_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Tabby Public Key — يبدأ بـ pk_',
            ],
            [
                'key'         => 'tabby_secret_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Tabby Secret Key — لا تشاركه',
            ],

            // ── تمارا (Tamara) — التقسيط السعودية ───────────────────────
            [
                'key'         => 'tamara_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'قبول التقسيط عبر تمارا (السعودية)',
            ],
            [
                'key'         => 'tamara_api_token',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Tamara API Token — لا تشاركه',
            ],
            [
                'key'         => 'tamara_notification_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Tamara Notification Key للـ webhooks',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->upsert(
                $setting,
                ['key'],
                ['description']
            );
        }
    }

    public function down(): void
    {
        $keys = [
            'stripe_enabled',
            'paypal_enabled', 'paypal_client_id', 'paypal_client_secret', 'paypal_mode',
            'fawry_enabled', 'fawry_merchant_code', 'fawry_security_key', 'fawry_mode',
            'apple_pay_enabled', 'google_pay_enabled', 'mada_enabled',
            'stc_pay_enabled', 'stc_pay_merchant_id', 'stc_pay_api_key',
            'tabby_enabled', 'tabby_public_key', 'tabby_secret_key',
            'tamara_enabled', 'tamara_api_token', 'tamara_notification_key',
        ];

        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};
