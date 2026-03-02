@extends('super-admin.layout')

@section('title', __('super-admin.settings_title'))
@section('breadcrumb')<span class="text-slate-700 dark:text-slate-200 font-medium">{{ __('super-admin.settings_title') }}</span>@endsection

@section('content')
<div x-data="systemSettings()" x-init="loadSettings()">

    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">{{ __('super-admin.settings_h1') }}</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">{{ __('super-admin.settings_subtitle') }}</p>
        </div>
        <!-- Save indicator -->
        <div x-show="saved" x-transition x-cloak
             class="inline-flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700 px-4 py-2 rounded-xl text-sm font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ __('super-admin.settings_saved') }}
        </div>
        <div x-show="saving" x-cloak
             class="inline-flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            {{ __('super-admin.settings_saving') }}
        </div>
    </div>

    <!-- Loading Skeleton -->
    <div x-show="loading" class="space-y-6">
        <template x-for="i in 4" :key="i">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                    <div class="h-5 w-48 bg-slate-200 dark:bg-slate-700 rounded-lg skeleton"></div>
                </div>
                <div class="p-6 space-y-4">
                    <template x-for="j in 3" :key="j">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1.5 flex-1">
                                <div class="h-4 w-36 bg-slate-200 dark:bg-slate-700 rounded skeleton"></div>
                                <div class="h-3 w-64 bg-slate-100 dark:bg-slate-700/50 rounded skeleton"></div>
                            </div>
                            <div class="h-9 w-48 bg-slate-100 dark:bg-slate-700/50 rounded-xl skeleton ms-4"></div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Settings Sections -->
    <div x-show="!loading" x-cloak class="space-y-6">
        <template x-for="group in groupOrder" :key="group">
            <div x-show="settings[group] && settings[group].length > 0"
                 class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">

                <!-- Group Header -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                             :class="getGroupStyle(group).bg">
                            <span x-html="getGroupStyle(group).icon" class="w-5 h-5" :class="getGroupStyle(group).iconColor"></span>
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-900 dark:text-white text-base" x-text="getGroupLabel(group)"></h2>
                            <p class="text-xs text-slate-400 dark:text-slate-500" x-text="getGroupDesc(group)"></p>
                        </div>
                    </div>
                    <span class="text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 px-2.5 py-1 rounded-full"
                          x-text="settings[group] ? settings[group].length + __tSettings.setting_count : ''"></span>
                </div>

                <!-- Settings List -->
                <div class="divide-y divide-slate-50 dark:divide-slate-700/60">
                    <template x-for="setting in settings[group]" :key="setting.id">
                        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3 hover:bg-slate-50/60 dark:hover:bg-slate-700/20 transition-colors">

                            <!-- Label + Description -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-800 dark:text-slate-200"
                                          x-text="getKeyLabel(setting.key)"></span>
                                </div>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5"
                                   x-text="getKeyDesc(setting.key)"></p>
                            </div>

                            <!-- Input -->
                            <div class="sm:w-72 flex-shrink-0">

                                <!-- Boolean toggle -->
                                <template x-if="setting.type === 'boolean'">
                                    <label class="flex items-center gap-3 cursor-pointer select-none">
                                        <div class="relative">
                                            <input type="checkbox" class="sr-only peer"
                                                   :checked="setting.value == '1' || setting.value === true"
                                                   @change="saveSetting(setting, $event.target.checked ? '1' : '0')">
                                            <div class="w-11 h-6 rounded-full transition-colors duration-200
                                                        bg-slate-200 dark:bg-slate-600
                                                        peer-checked:bg-indigo-600 dark:peer-checked:bg-indigo-500">
                                            </div>
                                            <div class="absolute top-1 right-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200
                                                        peer-checked:-translate-x-5">
                                            </div>
                                        </div>
                                        <span class="text-sm font-medium"
                                              :class="(setting.value == '1') ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400'"
                                              x-text="(setting.value == '1') ? __tSettings.enabled : __tSettings.disabled"></span>
                                    </label>
                                </template>

                                <!-- Number input -->
                                <template x-if="setting.type === 'number'">
                                    <input type="number"
                                           :value="setting.value"
                                           @change="saveSetting(setting, $event.target.value)"
                                           class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                </template>

                                <!-- String input — password style for secrets -->
                                <template x-if="setting.type === 'string'">
                                    <div class="relative">
                                        <input :type="isSecret(setting.key) ? (showSecrets[setting.key] ? 'text' : 'password') : 'text'"
                                               :value="setting.value"
                                               :placeholder="getKeyPlaceholder(setting.key)"
                                               @change="saveSetting(setting, $event.target.value)"
                                               class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                                               :class="isSecret(setting.key) ? 'pe-9' : ''">
                                        <button x-show="isSecret(setting.key)" type="button"
                                                @click="showSecrets[setting.key] = !showSecrets[setting.key]"
                                                class="absolute inset-y-0 end-2 flex items-center px-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                            <svg x-show="!showSecrets[setting.key]" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="showSecrets[setting.key]" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="!loading && Object.keys(settings).length === 0" class="text-center py-16">
            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <p class="text-slate-500 dark:text-slate-400 font-medium">{{ __('super-admin.settings_empty') }}</p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@php
$__tKeyLabels = [
    'app_name'                     => __('super-admin.settings_label_app_name'),
    'app_url'                      => __('super-admin.settings_label_app_url'),
    'app_logo_url'                 => __('super-admin.settings_label_app_logo_url'),
    'registration_enabled'         => __('super-admin.settings_label_registration_enabled'),
    'maintenance_mode'             => __('super-admin.settings_label_maintenance_mode'),
    'default_trial_days'           => __('super-admin.settings_label_default_trial_days'),
    'max_tenants'                  => __('super-admin.settings_label_max_tenants'),
    'default_language'             => __('super-admin.settings_label_default_language'),
    'default_currency'             => __('super-admin.settings_label_default_currency'),
    'geo_detection_enabled'        => __('super-admin.settings_label_geo_detection_enabled'),
    'geo_pricing_enabled'          => __('super-admin.settings_label_geo_pricing_enabled'),
    'enable_vat_per_country'       => __('super-admin.settings_label_enable_vat_per_country'),
    'allow_manual_language_switch' => __('super-admin.settings_label_allow_manual_language_switch'),
    'allow_manual_currency_switch' => __('super-admin.settings_label_allow_manual_currency_switch'),
    'stripe_public_key'            => __('super-admin.settings_label_stripe_public_key'),
    'stripe_secret_key'            => __('super-admin.settings_label_stripe_secret_key'),
    'stripe_webhook_secret'        => __('super-admin.settings_label_stripe_webhook_secret'),
    'billing_currency'             => __('super-admin.settings_label_billing_currency'),
    'invoice_prefix'               => __('super-admin.settings_label_invoice_prefix'),
    'mail_from_address'            => __('super-admin.settings_label_mail_from_address'),
    'mail_from_name'               => __('super-admin.settings_label_mail_from_name'),
    'mail_driver'                  => __('super-admin.settings_label_mail_driver'),
    'mail_host'                    => __('super-admin.settings_label_mail_host'),
    'mail_port'                    => __('super-admin.settings_label_mail_port'),
    'mail_username'                => __('super-admin.settings_label_mail_username'),
    'mail_password'                => __('super-admin.settings_label_mail_password'),
    'stripe_enabled'               => __('super-admin.settings_label_stripe_enabled'),
    'paypal_enabled'               => __('super-admin.settings_label_paypal_enabled'),
    'paypal_client_id'             => __('super-admin.settings_label_paypal_client_id'),
    'paypal_client_secret'         => __('super-admin.settings_label_paypal_client_secret'),
    'paypal_mode'                  => __('super-admin.settings_label_paypal_mode'),
    'fawry_enabled'                => __('super-admin.settings_label_fawry_enabled'),
    'fawry_merchant_code'          => __('super-admin.settings_label_fawry_merchant_code'),
    'fawry_security_key'           => __('super-admin.settings_label_fawry_security_key'),
    'fawry_mode'                   => __('super-admin.settings_label_fawry_mode'),
    'apple_pay_enabled'            => __('super-admin.settings_label_apple_pay_enabled'),
    'google_pay_enabled'           => __('super-admin.settings_label_google_pay_enabled'),
    'mada_enabled'                 => __('super-admin.settings_label_mada_enabled'),
    'stc_pay_enabled'              => __('super-admin.settings_label_stc_pay_enabled'),
    'stc_pay_merchant_id'          => __('super-admin.settings_label_stc_pay_merchant_id'),
    'stc_pay_api_key'              => __('super-admin.settings_label_stc_pay_api_key'),
    'tabby_enabled'                => __('super-admin.settings_label_tabby_enabled'),
    'tabby_public_key'             => __('super-admin.settings_label_tabby_public_key'),
    'tabby_secret_key'             => __('super-admin.settings_label_tabby_secret_key'),
    'tamara_enabled'               => __('super-admin.settings_label_tamara_enabled'),
    'tamara_api_token'             => __('super-admin.settings_label_tamara_api_token'),
    'tamara_notification_key'      => __('super-admin.settings_label_tamara_notification_key'),
    'tap_enabled'                  => __('super-admin.settings_label_tap_enabled'),
    'tap_secret_key'               => __('super-admin.settings_label_tap_secret_key'),
    'tap_public_key'               => __('super-admin.settings_label_tap_public_key'),
    'paytabs_enabled'              => __('super-admin.settings_label_paytabs_enabled'),
    'paytabs_profile_id'           => __('super-admin.settings_label_paytabs_profile_id'),
    'paytabs_server_key'           => __('super-admin.settings_label_paytabs_server_key'),
    'paytabs_region'               => __('super-admin.settings_label_paytabs_region'),
    'paymob_enabled'               => __('super-admin.settings_label_paymob_enabled'),
    'paymob_api_key'               => __('super-admin.settings_label_paymob_api_key'),
    'paymob_integration_id'        => __('super-admin.settings_label_paymob_integration_id'),
    'paymob_hmac_secret'           => __('super-admin.settings_label_paymob_hmac_secret'),
    'flutterwave_enabled'          => __('super-admin.settings_label_flutterwave_enabled'),
    'flutterwave_public_key'       => __('super-admin.settings_label_flutterwave_public_key'),
    'flutterwave_secret_key'       => __('super-admin.settings_label_flutterwave_secret_key'),
    'flutterwave_encryption_key'   => __('super-admin.settings_label_flutterwave_encryption_key'),
    'razorpay_enabled'             => __('super-admin.settings_label_razorpay_enabled'),
    'razorpay_key_id'              => __('super-admin.settings_label_razorpay_key_id'),
    'razorpay_key_secret'          => __('super-admin.settings_label_razorpay_key_secret'),
    'razorpay_webhook_secret'      => __('super-admin.settings_label_razorpay_webhook_secret'),
    'mercadopago_enabled'          => __('super-admin.settings_label_mercadopago_enabled'),
    'mercadopago_public_key'       => __('super-admin.settings_label_mercadopago_public_key'),
    'mercadopago_access_token'     => __('super-admin.settings_label_mercadopago_access_token'),
    'twocheckout_enabled'          => __('super-admin.settings_label_twocheckout_enabled'),
    'twocheckout_merchant_code'    => __('super-admin.settings_label_twocheckout_merchant_code'),
    'twocheckout_secret_key'       => __('super-admin.settings_label_twocheckout_secret_key'),
    'knet_enabled'                 => __('super-admin.settings_label_knet_enabled'),
    'knet_transport_id'            => __('super-admin.settings_label_knet_transport_id'),
    'knet_password'                => __('super-admin.settings_label_knet_password'),
    'benefit_enabled'              => __('super-admin.settings_label_benefit_enabled'),
    'benefit_api_key'              => __('super-admin.settings_label_benefit_api_key'),
    'bank_transfer_enabled'        => __('super-admin.settings_label_bank_transfer_enabled'),
    'notify_new_signup'            => __('super-admin.settings_label_notify_new_signup'),
    'notify_new_payment'           => __('super-admin.settings_label_notify_new_payment'),
    'notify_subscription_expired'  => __('super-admin.settings_label_notify_subscription_expired'),
    'notify_days_before_expiry'    => __('super-admin.settings_label_notify_days_before_expiry'),
    'admin_notification_email'     => __('super-admin.settings_label_admin_notification_email'),
    'slack_webhook_url'            => __('super-admin.settings_label_slack_webhook_url'),
];
$__tKeyDescs = [
    'app_name'                     => __('super-admin.settings_desc_app_name'),
    'app_url'                      => __('super-admin.settings_desc_app_url'),
    'app_logo_url'                 => __('super-admin.settings_desc_app_logo_url'),
    'registration_enabled'         => __('super-admin.settings_desc_registration_enabled'),
    'maintenance_mode'             => __('super-admin.settings_desc_maintenance_mode'),
    'default_trial_days'           => __('super-admin.settings_desc_default_trial_days'),
    'max_tenants'                  => __('super-admin.settings_desc_max_tenants'),
    'default_language'             => __('super-admin.settings_desc_default_language'),
    'default_currency'             => __('super-admin.settings_desc_default_currency'),
    'geo_detection_enabled'        => __('super-admin.settings_desc_geo_detection_enabled'),
    'geo_pricing_enabled'          => __('super-admin.settings_desc_geo_pricing_enabled'),
    'enable_vat_per_country'       => __('super-admin.settings_desc_enable_vat_per_country'),
    'allow_manual_language_switch' => __('super-admin.settings_desc_allow_manual_language_switch'),
    'allow_manual_currency_switch' => __('super-admin.settings_desc_allow_manual_currency_switch'),
    'stripe_public_key'            => __('super-admin.settings_desc_stripe_public_key'),
    'stripe_secret_key'            => __('super-admin.settings_desc_stripe_secret_key'),
    'stripe_webhook_secret'        => __('super-admin.settings_desc_stripe_webhook_secret'),
    'billing_currency'             => __('super-admin.settings_desc_billing_currency'),
    'invoice_prefix'               => __('super-admin.settings_desc_invoice_prefix'),
    'mail_from_address'            => __('super-admin.settings_desc_mail_from_address'),
    'mail_from_name'               => __('super-admin.settings_desc_mail_from_name'),
    'mail_driver'                  => __('super-admin.settings_desc_mail_driver'),
    'mail_host'                    => __('super-admin.settings_desc_mail_host'),
    'mail_port'                    => __('super-admin.settings_desc_mail_port'),
    'mail_username'                => __('super-admin.settings_desc_mail_username'),
    'mail_password'                => __('super-admin.settings_desc_mail_password'),
    'stripe_enabled'               => __('super-admin.settings_desc_stripe_enabled'),
    'paypal_enabled'               => __('super-admin.settings_desc_paypal_enabled'),
    'paypal_client_id'             => __('super-admin.settings_desc_paypal_client_id'),
    'paypal_client_secret'         => __('super-admin.settings_desc_paypal_client_secret'),
    'paypal_mode'                  => __('super-admin.settings_desc_paypal_mode'),
    'fawry_enabled'                => __('super-admin.settings_desc_fawry_enabled'),
    'fawry_merchant_code'          => __('super-admin.settings_desc_fawry_merchant_code'),
    'fawry_security_key'           => __('super-admin.settings_desc_fawry_security_key'),
    'fawry_mode'                   => __('super-admin.settings_desc_fawry_mode'),
    'apple_pay_enabled'            => __('super-admin.settings_desc_apple_pay_enabled'),
    'google_pay_enabled'           => __('super-admin.settings_desc_google_pay_enabled'),
    'mada_enabled'                 => __('super-admin.settings_desc_mada_enabled'),
    'stc_pay_enabled'              => __('super-admin.settings_desc_stc_pay_enabled'),
    'stc_pay_merchant_id'          => __('super-admin.settings_desc_stc_pay_merchant_id'),
    'stc_pay_api_key'              => __('super-admin.settings_desc_stc_pay_api_key'),
    'tabby_enabled'                => __('super-admin.settings_desc_tabby_enabled'),
    'tabby_public_key'             => __('super-admin.settings_desc_tabby_public_key'),
    'tabby_secret_key'             => __('super-admin.settings_desc_tabby_secret_key'),
    'tamara_enabled'               => __('super-admin.settings_desc_tamara_enabled'),
    'tamara_api_token'             => __('super-admin.settings_desc_tamara_api_token'),
    'tamara_notification_key'      => __('super-admin.settings_desc_tamara_notification_key'),
    'tap_enabled'                  => __('super-admin.settings_desc_tap_enabled'),
    'tap_secret_key'               => __('super-admin.settings_desc_tap_secret_key'),
    'tap_public_key'               => __('super-admin.settings_desc_tap_public_key'),
    'paytabs_enabled'              => __('super-admin.settings_desc_paytabs_enabled'),
    'paytabs_profile_id'           => __('super-admin.settings_desc_paytabs_profile_id'),
    'paytabs_server_key'           => __('super-admin.settings_desc_paytabs_server_key'),
    'paytabs_region'               => __('super-admin.settings_desc_paytabs_region'),
    'paymob_enabled'               => __('super-admin.settings_desc_paymob_enabled'),
    'paymob_api_key'               => __('super-admin.settings_desc_paymob_api_key'),
    'paymob_integration_id'        => __('super-admin.settings_desc_paymob_integration_id'),
    'paymob_hmac_secret'           => __('super-admin.settings_desc_paymob_hmac_secret'),
    'flutterwave_enabled'          => __('super-admin.settings_desc_flutterwave_enabled'),
    'flutterwave_public_key'       => __('super-admin.settings_desc_flutterwave_public_key'),
    'flutterwave_secret_key'       => __('super-admin.settings_desc_flutterwave_secret_key'),
    'flutterwave_encryption_key'   => __('super-admin.settings_desc_flutterwave_encryption_key'),
    'razorpay_enabled'             => __('super-admin.settings_desc_razorpay_enabled'),
    'razorpay_key_id'              => __('super-admin.settings_desc_razorpay_key_id'),
    'razorpay_key_secret'          => __('super-admin.settings_desc_razorpay_key_secret'),
    'razorpay_webhook_secret'      => __('super-admin.settings_desc_razorpay_webhook_secret'),
    'mercadopago_enabled'          => __('super-admin.settings_desc_mercadopago_enabled'),
    'mercadopago_public_key'       => __('super-admin.settings_desc_mercadopago_public_key'),
    'mercadopago_access_token'     => __('super-admin.settings_desc_mercadopago_access_token'),
    'twocheckout_enabled'          => __('super-admin.settings_desc_twocheckout_enabled'),
    'twocheckout_merchant_code'    => __('super-admin.settings_desc_twocheckout_merchant_code'),
    'twocheckout_secret_key'       => __('super-admin.settings_desc_twocheckout_secret_key'),
    'knet_enabled'                 => __('super-admin.settings_desc_knet_enabled'),
    'knet_transport_id'            => __('super-admin.settings_desc_knet_transport_id'),
    'knet_password'                => __('super-admin.settings_desc_knet_password'),
    'benefit_enabled'              => __('super-admin.settings_desc_benefit_enabled'),
    'benefit_api_key'              => __('super-admin.settings_desc_benefit_api_key'),
    'bank_transfer_enabled'        => __('super-admin.settings_desc_bank_transfer_enabled'),
    'notify_new_signup'            => __('super-admin.settings_desc_notify_new_signup'),
    'notify_new_payment'           => __('super-admin.settings_desc_notify_new_payment'),
    'notify_subscription_expired'  => __('super-admin.settings_desc_notify_subscription_expired'),
    'notify_days_before_expiry'    => __('super-admin.settings_desc_notify_days_before_expiry'),
    'admin_notification_email'     => __('super-admin.settings_desc_admin_notification_email'),
];
$__tSettings = [
    'setting_count' => __('super-admin.settings_setting_count'),
    'enabled'       => __('super-admin.settings_enabled'),
    'disabled'      => __('super-admin.settings_disabled'),
    'load_fail'     => __('super-admin.settings_load_fail'),
    'save_fail'     => __('super-admin.settings_save_fail'),
    'error'         => __('super-admin.settings_error'),
    'group_general' => __('super-admin.settings_group_general'),
    'group_email'   => __('super-admin.settings_group_email'),
    'group_billing' => __('super-admin.settings_group_billing'),
    'group_payment' => __('super-admin.settings_group_payment'),
    'group_notif'   => __('super-admin.settings_group_notif'),
    'group_geo'     => __('super-admin.settings_group_geo'),
    'gdesc_general' => __('super-admin.settings_gdesc_general'),
    'gdesc_email'   => __('super-admin.settings_gdesc_email'),
    'gdesc_billing' => __('super-admin.settings_gdesc_billing'),
    'gdesc_payment' => __('super-admin.settings_gdesc_payment'),
    'gdesc_notif'   => __('super-admin.settings_gdesc_notif'),
    'gdesc_geo'     => __('super-admin.settings_gdesc_geo'),
    'key_labels'    => $__tKeyLabels,
    'key_descs'     => $__tKeyDescs,
];
@endphp
<script>
const __tSettings = @json($__tSettings);
function systemSettings() {
    return {
        loading: true,
        saving: false,
        saved: false,
        savedTimer: null,
        settings: {},
        showSecrets: {},

        // The ordered list of groups to display
        groupOrder: ['general', 'payment_methods', 'geo', 'billing', 'email', 'notifications'],

        // ── Group metadata ────────────────────────────────────────────────
        getGroupLabel(g) {
            const map = {
                general:         __tSettings.group_general,
                email:           __tSettings.group_email,
                billing:         __tSettings.group_billing,
                payment_methods: __tSettings.group_payment,
                notifications:   __tSettings.group_notif,
                geo:             __tSettings.group_geo,
            };
            return map[g] ?? g;
        },

        getGroupDesc(g) {
            const map = {
                general:         __tSettings.gdesc_general,
                email:           __tSettings.gdesc_email,
                billing:         __tSettings.gdesc_billing,
                payment_methods: __tSettings.gdesc_payment,
                notifications:   __tSettings.gdesc_notif,
                geo:             __tSettings.gdesc_geo,
            };
            return map[g] ?? '';
        },

        getGroupStyle(g) {
            const styles = {
                general:       { bg: 'bg-slate-100 dark:bg-slate-700',   iconColor: 'text-slate-600 dark:text-slate-400',   icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>' },
                geo:           { bg: 'bg-blue-100 dark:bg-blue-900/40',  iconColor: 'text-blue-600 dark:text-blue-400',     icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
                billing:       { bg: 'bg-emerald-100 dark:bg-emerald-900/40', iconColor: 'text-emerald-600 dark:text-emerald-400', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>' },
                email:         { bg: 'bg-violet-100 dark:bg-violet-900/40', iconColor: 'text-violet-600 dark:text-violet-400', icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>' },
                notifications:   { bg: 'bg-amber-100 dark:bg-amber-900/40',  iconColor: 'text-amber-600 dark:text-amber-400',  icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>' },
                payment_methods: { bg: 'bg-rose-100 dark:bg-rose-900/40',    iconColor: 'text-rose-600 dark:text-rose-400',    icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>' },
            };
            return styles[g] ?? styles.general;
        },

        // ── Per-key labels, descriptions, placeholders ────────────────────
        getKeyLabel(key) {
            return __tSettings.key_labels[key] ?? key.replace(/_/g, ' ');
        },

        getKeyDesc(key) {
            return __tSettings.key_descs[key] ?? '';
        },

        getKeyPlaceholder(key) {
            const ph = {
                app_name:                  'Velora',
                app_url:                   'https://velora.com',
                app_logo_url:              'https://cdn.example.com/logo.png',
                default_trial_days:        '14',
                max_tenants:               '0',
                default_language:          'en',
                default_currency:          'USD',
                stripe_public_key:         'pk_live_...',
                stripe_secret_key:         'sk_live_...',
                stripe_webhook_secret:     'whsec_...',
                billing_currency:          'USD',
                invoice_prefix:            'INV-',
                mail_from_address:         'noreply@velora.com',
                mail_from_name:            'Velora',
                mail_driver:               'smtp',
                mail_host:                 'smtp.mailgun.org',
                mail_port:                 '587',
                mail_username:             'postmaster@mg.velora.com',
                notify_days_before_expiry: '7',
                admin_notification_email:  'admin@velora.com',
                slack_webhook_url:         'https://hooks.slack.com/...',
                paypal_client_id:          'AaBbCcDdEe...',
                paypal_mode:               'sandbox',
                fawry_merchant_code:       '+/AAAAAA==',
                fawry_mode:                'test',
                stc_pay_merchant_id:       'merchant_...',
                tabby_public_key:          'pk_...',
                tamara_notification_key:   'nk_...',
                tap_public_key:            'pk_test_...',
                paytabs_region:            'SAU',
                paymob_integration_id:     '123456',
                flutterwave_public_key:    'FLWPUBK_TEST-...',
                razorpay_key_id:           'rzp_live_...',
                twocheckout_merchant_code: '12345678',
                knet_transport_id:         'transport_...',
            };
            return ph[key] ?? '';
        },

        isSecret(key) {
            return [
                'stripe_secret_key','stripe_webhook_secret',
                'mail_password','mail_username',
                'paypal_client_secret',
                'fawry_security_key',
                'stc_pay_api_key',
                'tabby_secret_key',
                'tamara_api_token','tamara_notification_key',
                'tap_secret_key',
                'paytabs_server_key',
                'paymob_api_key','paymob_hmac_secret',
                'flutterwave_secret_key','flutterwave_encryption_key',
                'razorpay_key_secret','razorpay_webhook_secret',
                'mercadopago_access_token',
                'twocheckout_secret_key',
                'knet_password',
                'benefit_api_key',
                'slack_webhook_url',
            ].includes(key);
        },

        // ── Data loading ──────────────────────────────────────────────────
        async loadSettings() {
            try {
                const res  = await fetch('/api/super-admin/settings', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'include',
                });
                const data = await res.json();
                if (data.success) {
                    this.settings = data.data;
                }
            } catch (e) {
                console.error(e);
                showToast(__tSettings.load_fail, 'error');
            } finally {
                this.loading = false;
            }
        },

        // ── Auto-save on change ───────────────────────────────────────────
        async saveSetting(setting, value) {
            // Optimistic update
            setting.value = value;

            this.saving = true;
            this.saved  = false;
            clearTimeout(this.savedTimer);

            try {
                const res  = await fetch('/api/super-admin/settings', {
                    method: 'PUT',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content,
                    },
                    credentials: 'include',
                    body: JSON.stringify({ settings: [{ key: setting.key, value, type: setting.type, group: setting.group }] }),
                });
                const data = await res.json();
                if (data.success) {
                    this.saved = true;
                    this.savedTimer = setTimeout(() => { this.saved = false; }, 3000);
                } else {
                    showToast(__tSettings.save_fail, 'error');
                }
            } catch (e) {
                showToast(__tSettings.error, 'error');
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
