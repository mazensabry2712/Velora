<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed system_settings rows for the payment gateways that were present in the UI
 * (settings.blade.php getProviders / labels) but missing from the initial seed migration:
 *
 *   moyasar, paymob, tap, paytabs, knet, benefit, razorpay,
 *   flutterwave, mercadopago, twocheckout, bank_transfer
 */
return new class extends Migration
{
    public function up(): void
    {
        $settings = [

            // ── ميسر (Moyasar) — السعودية ─────────────────────────────────
            ['key' => 'moyasar_enabled',         'value' => '0',       'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر ميسر (السعودية)'],
            ['key' => 'moyasar_publishable_key', 'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Moyasar Publishable Key — يبدأ بـ pk_'],
            ['key' => 'moyasar_secret_key',      'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Moyasar Secret Key — لا تشاركه'],
            ['key' => 'moyasar_webhook_secret',  'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Moyasar Webhook Secret للتحقق من الإشعارات'],

            // ── بيموب (Paymob) — مصر والشرق الأوسط ──────────────────────
            ['key' => 'paymob_enabled',          'value' => '0',       'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر Paymob (مصر والشرق الأوسط)'],
            ['key' => 'paymob_api_key',          'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Paymob API Key من لوحة تحكم Paymob'],
            ['key' => 'paymob_integration_id',   'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Paymob Integration ID لطريقة الدفع المختارة'],
            ['key' => 'paymob_hmac_secret',      'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Paymob HMAC Secret للتحقق من إشعارات الدفع'],

            // ── تاب (Tap Payments) — الخليج ───────────────────────────────
            ['key' => 'tap_enabled',             'value' => '0',       'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر Tap Payments (الخليج)'],
            ['key' => 'tap_secret_key',          'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Tap Secret Key — لا تشاركه'],
            ['key' => 'tap_public_key',          'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Tap Public Key — يبدأ بـ pk_'],

            // ── بيتابز (PayTabs) — الخليج ─────────────────────────────────
            ['key' => 'paytabs_enabled',         'value' => '0',       'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر PayTabs (الخليج وشمال أفريقيا)'],
            ['key' => 'paytabs_profile_id',      'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'PayTabs Profile ID من لوحة التحكم'],
            ['key' => 'paytabs_server_key',      'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'PayTabs Server Key — لا تشاركه'],
            ['key' => 'paytabs_region',          'value' => 'SAU',     'type' => 'string',  'group' => 'payment_methods', 'description' => 'رمز المنطقة: SAU / ARE / EGY / JOR / ...'],

            // ── كي-نت (KNET) — الكويت ─────────────────────────────────────
            ['key' => 'knet_enabled',            'value' => '0',       'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر KNET (الكويت)'],
            ['key' => 'knet_transport_id',       'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'KNET Transport ID من KNET/NBK'],
            ['key' => 'knet_password',           'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'KNET Password — لا تشاركه'],

            // ── بنفت (Benefit Pay) — البحرين ──────────────────────────────
            ['key' => 'benefit_enabled',         'value' => '0',       'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر Benefit Pay (البحرين)'],
            ['key' => 'benefit_api_key',         'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Benefit Pay API Key — لا تشاركه'],

            // ── رازورباي (Razorpay) — الهند ───────────────────────────────
            ['key' => 'razorpay_enabled',        'value' => '0',       'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر Razorpay (الهند)'],
            ['key' => 'razorpay_key_id',         'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Razorpay Key ID — يبدأ بـ rzp_'],
            ['key' => 'razorpay_key_secret',     'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Razorpay Key Secret — لا تشاركه'],
            ['key' => 'razorpay_webhook_secret', 'value' => '',        'type' => 'string',  'group' => 'payment_methods', 'description' => 'Razorpay Webhook Secret للتحقق من الإشعارات'],

            // ── فلاترويف (Flutterwave) — أفريقيا ──────────────────────────
            ['key' => 'flutterwave_enabled',        'value' => '0',    'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر Flutterwave (أفريقيا)'],
            ['key' => 'flutterwave_public_key',     'value' => '',     'type' => 'string',  'group' => 'payment_methods', 'description' => 'Flutterwave Public Key — يبدأ بـ FLWPUBK'],
            ['key' => 'flutterwave_secret_key',     'value' => '',     'type' => 'string',  'group' => 'payment_methods', 'description' => 'Flutterwave Secret Key — لا تشاركه'],
            ['key' => 'flutterwave_encryption_key', 'value' => '',     'type' => 'string',  'group' => 'payment_methods', 'description' => 'Flutterwave Encryption Key للتحقق من المعاملات'],

            // ── ميركادو باغو (Mercado Pago) — أمريكا اللاتينية ────────────
            ['key' => 'mercadopago_enabled',        'value' => '0',    'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر Mercado Pago (أمريكا اللاتينية)'],
            ['key' => 'mercadopago_public_key',     'value' => '',     'type' => 'string',  'group' => 'payment_methods', 'description' => 'Mercado Pago Public Key'],
            ['key' => 'mercadopago_access_token',   'value' => '',     'type' => 'string',  'group' => 'payment_methods', 'description' => 'Mercado Pago Access Token — لا تشاركه'],

            // ── تو-تشيك-أوت (2Checkout) — عالمي ──────────────────────────
            ['key' => 'twocheckout_enabled',        'value' => '0',    'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر 2Checkout (عالمي)'],
            ['key' => 'twocheckout_merchant_code',  'value' => '',     'type' => 'string',  'group' => 'payment_methods', 'description' => 'كود التاجر في 2Checkout'],
            ['key' => 'twocheckout_secret_key',     'value' => '',     'type' => 'string',  'group' => 'payment_methods', 'description' => '2Checkout Secret Key — لا تشاركه'],

            // ── تحويل بنكي ───────────────────────────────────────────────
            ['key' => 'bank_transfer_enabled',      'value' => '0',    'type' => 'boolean', 'group' => 'payment_methods', 'description' => 'قبول الدفع عبر التحويل البنكي المباشر'],
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
            'moyasar_enabled', 'moyasar_publishable_key', 'moyasar_secret_key', 'moyasar_webhook_secret',
            'paymob_enabled', 'paymob_api_key', 'paymob_integration_id', 'paymob_hmac_secret',
            'tap_enabled', 'tap_secret_key', 'tap_public_key',
            'paytabs_enabled', 'paytabs_profile_id', 'paytabs_server_key', 'paytabs_region',
            'knet_enabled', 'knet_transport_id', 'knet_password',
            'benefit_enabled', 'benefit_api_key',
            'razorpay_enabled', 'razorpay_key_id', 'razorpay_key_secret', 'razorpay_webhook_secret',
            'flutterwave_enabled', 'flutterwave_public_key', 'flutterwave_secret_key', 'flutterwave_encryption_key',
            'mercadopago_enabled', 'mercadopago_public_key', 'mercadopago_access_token',
            'twocheckout_enabled', 'twocheckout_merchant_code', 'twocheckout_secret_key',
            'bank_transfer_enabled',
        ];

        DB::table('system_settings')->whereIn('key', $keys)->delete();
    }
};
