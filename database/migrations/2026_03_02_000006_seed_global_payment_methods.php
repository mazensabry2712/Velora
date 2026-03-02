<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [

            // ── Tap Payments — الشرق الأوسط وشمال أفريقيا ────────────────
            // السعودية، الإمارات، الكويت، البحرين، قطر، عُمان، مصر، الأردن
            [
                'key'         => 'tap_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'Tap Payments — يغطي معظم دول المنطقة العربية',
            ],
            [
                'key'         => 'tap_secret_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Tap Secret Key — من لوحة Tap Dashboard',
            ],
            [
                'key'         => 'tap_public_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Tap Public Key — للواجهة الأمامية',
            ],

            // ── PayTabs — الشرق الأوسط وشمال أفريقيا ─────────────────────
            [
                'key'         => 'paytabs_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'PayTabs — يغطي MENA ويدعم 168 عملة',
            ],
            [
                'key'         => 'paytabs_profile_id',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'PayTabs Profile ID — من لوحة التحكم',
            ],
            [
                'key'         => 'paytabs_server_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'PayTabs Server Key — لا تشاركه',
            ],
            [
                'key'         => 'paytabs_region',
                'value'       => 'SAU',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'منطقة PayTabs: SAU أو ARE أو EGY أو JOR أو GLOBAL',
            ],

            // ── Paymob — مصر والمغرب والإمارات وباكستان ──────────────────
            [
                'key'         => 'paymob_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'Paymob — يغطي مصر، الإمارات، المغرب، باكستان',
            ],
            [
                'key'         => 'paymob_api_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Paymob API Key — من لوحة Accept',
            ],
            [
                'key'         => 'paymob_integration_id',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Paymob Integration ID لبطاقات الائتمان',
            ],
            [
                'key'         => 'paymob_hmac_secret',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Paymob HMAC Secret للـ webhooks — لا تشاركه',
            ],

            // ── Flutterwave — أفريقيا (نيجيريا، غانا، كينيا، جنوب أفريقيا، إلخ) ─
            [
                'key'         => 'flutterwave_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'Flutterwave — يغطي 30+ دولة أفريقية',
            ],
            [
                'key'         => 'flutterwave_public_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Flutterwave Public Key — يبدأ بـ FLWPUBK_',
            ],
            [
                'key'         => 'flutterwave_secret_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Flutterwave Secret Key — يبدأ بـ FLWSECK_ — لا تشاركه',
            ],
            [
                'key'         => 'flutterwave_encryption_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Flutterwave Encryption Key — للتشفير',
            ],

            // ── Razorpay — الهند ──────────────────────────────────────────
            [
                'key'         => 'razorpay_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'Razorpay — البوابة الأكثر استخداماً في الهند',
            ],
            [
                'key'         => 'razorpay_key_id',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Razorpay Key ID — يبدأ بـ rzp_',
            ],
            [
                'key'         => 'razorpay_key_secret',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Razorpay Key Secret — لا تشاركه',
            ],
            [
                'key'         => 'razorpay_webhook_secret',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Razorpay Webhook Secret للتحقق من الـ events',
            ],

            // ── Mercado Pago — أمريكا اللاتينية (البرازيل، الأرجنتين، المكسيك، إلخ) ─
            [
                'key'         => 'mercadopago_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'Mercado Pago — يغطي البرازيل، الأرجنتين، المكسيك، كولومبيا، تشيلي',
            ],
            [
                'key'         => 'mercadopago_public_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Mercado Pago Public Key',
            ],
            [
                'key'         => 'mercadopago_access_token',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Mercado Pago Access Token — لا تشاركه',
            ],

            // ── 2Checkout (Verifone) — عالمي (200+ دولة) ─────────────────
            [
                'key'         => 'twocheckout_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => '2Checkout — بوابة عالمية تقبل 200+ عملة و45 طريقة دفع',
            ],
            [
                'key'         => 'twocheckout_merchant_code',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => '2Checkout Merchant Code',
            ],
            [
                'key'         => 'twocheckout_secret_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => '2Checkout Secret Key — لا تشاركه',
            ],

            // ── KNET — الكويت ─────────────────────────────────────────────
            [
                'key'         => 'knet_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'KNET — شبكة الدفع الوطنية في الكويت',
            ],
            [
                'key'         => 'knet_transport_id',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'KNET Transport ID من البنك',
            ],
            [
                'key'         => 'knet_password',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'KNET Password — لا تشاركه',
            ],

            // ── Benefit — البحرين ─────────────────────────────────────────
            [
                'key'         => 'benefit_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'Benefit Pay — محفظة الدفع الوطنية في البحرين',
            ],
            [
                'key'         => 'benefit_api_key',
                'value'       => '',
                'type'        => 'string',
                'group'       => 'payment_methods',
                'description' => 'Benefit Pay API Key — من بوابة Benefit',
            ],

            // ── Stripe IBAN / Bank Transfer — أوروبا ─────────────────────
            [
                'key'         => 'bank_transfer_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'payment_methods',
                'description' => 'تحويل بنكي مباشر (IBAN) عبر Stripe — مناسب لأوروبا',
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
            'tap_enabled', 'tap_secret_key', 'tap_public_key',
            'paytabs_enabled', 'paytabs_profile_id', 'paytabs_server_key', 'paytabs_region',
            'paymob_enabled', 'paymob_api_key', 'paymob_integration_id', 'paymob_hmac_secret',
            'flutterwave_enabled', 'flutterwave_public_key', 'flutterwave_secret_key', 'flutterwave_encryption_key',
            'razorpay_enabled', 'razorpay_key_id', 'razorpay_key_secret', 'razorpay_webhook_secret',
            'mercadopago_enabled', 'mercadopago_public_key', 'mercadopago_access_token',
            'twocheckout_enabled', 'twocheckout_merchant_code', 'twocheckout_secret_key',
            'knet_enabled', 'knet_transport_id', 'knet_password',
            'benefit_enabled', 'benefit_api_key',
            'bank_transfer_enabled',
        ];

        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};
