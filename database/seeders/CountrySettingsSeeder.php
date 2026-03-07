<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CountrySettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Country Settings ──────────────────────────────────────────────────
        // Covers all 15 language folders in lang/ (ar, de, en, es, fr, hi, id, it, ja, ko, nl, pt, ru, tr, zh)
        // Primary country per language is listed first, then secondary countries for same language.
        $countries = [
            // Arabic (ar)
            ['country_code' => 'SA', 'country_name' => 'المملكة العربية السعودية', 'default_language' => 'ar', 'default_currency' => 'SAR', 'is_active' => true],
            ['country_code' => 'AE', 'country_name' => 'الإمارات العربية المتحدة',  'default_language' => 'ar', 'default_currency' => 'AED', 'is_active' => true],
            ['country_code' => 'EG', 'country_name' => 'مصر',                        'default_language' => 'ar', 'default_currency' => 'EGP', 'is_active' => true],
            ['country_code' => 'KW', 'country_name' => 'الكويت',                     'default_language' => 'ar', 'default_currency' => 'KWD', 'is_active' => true],
            ['country_code' => 'QA', 'country_name' => 'قطر',                        'default_language' => 'ar', 'default_currency' => 'QAR', 'is_active' => true],
            ['country_code' => 'BH', 'country_name' => 'البحرين',                    'default_language' => 'ar', 'default_currency' => 'BHD', 'is_active' => true],
            ['country_code' => 'OM', 'country_name' => 'سلطنة عُمان',               'default_language' => 'ar', 'default_currency' => 'OMR', 'is_active' => true],
            ['country_code' => 'JO', 'country_name' => 'الأردن',                     'default_language' => 'ar', 'default_currency' => 'JOD', 'is_active' => true],
            ['country_code' => 'IQ', 'country_name' => 'العراق',                     'default_language' => 'ar', 'default_currency' => 'IQD', 'is_active' => true],
            ['country_code' => 'LB', 'country_name' => 'لبنان',                      'default_language' => 'ar', 'default_currency' => 'USD', 'is_active' => true],

            // German (de)
            ['country_code' => 'DE', 'country_name' => 'ألمانيا',           'default_language' => 'de', 'default_currency' => 'EUR', 'is_active' => true],
            ['country_code' => 'AT', 'country_name' => 'النمسا',             'default_language' => 'de', 'default_currency' => 'EUR', 'is_active' => true],
            ['country_code' => 'CH', 'country_name' => 'سويسرا',             'default_language' => 'de', 'default_currency' => 'CHF', 'is_active' => true],

            // English (en)
            ['country_code' => 'US', 'country_name' => 'الولايات المتحدة',   'default_language' => 'en', 'default_currency' => 'USD', 'is_active' => true],
            ['country_code' => 'GB', 'country_name' => 'المملكة المتحدة',    'default_language' => 'en', 'default_currency' => 'GBP', 'is_active' => true],
            ['country_code' => 'AU', 'country_name' => 'أستراليا',           'default_language' => 'en', 'default_currency' => 'AUD', 'is_active' => true],
            ['country_code' => 'CA', 'country_name' => 'كندا',               'default_language' => 'en', 'default_currency' => 'CAD', 'is_active' => true],
            ['country_code' => 'NZ', 'country_name' => 'نيوزيلندا',          'default_language' => 'en', 'default_currency' => 'NZD', 'is_active' => true],
            ['country_code' => 'ZA', 'country_name' => 'جنوب أفريقيا',       'default_language' => 'en', 'default_currency' => 'ZAR', 'is_active' => true],
            ['country_code' => 'NG', 'country_name' => 'نيجيريا',            'default_language' => 'en', 'default_currency' => 'NGN', 'is_active' => true],
            ['country_code' => 'PK', 'country_name' => 'باكستان',            'default_language' => 'en', 'default_currency' => 'PKR', 'is_active' => true],
            ['country_code' => 'PH', 'country_name' => 'الفلبين',            'default_language' => 'en', 'default_currency' => 'PHP', 'is_active' => true],
            ['country_code' => 'SG', 'country_name' => 'سنغافورة',           'default_language' => 'en', 'default_currency' => 'SGD', 'is_active' => true],

            // Spanish (es)
            ['country_code' => 'ES', 'country_name' => 'إسبانيا',            'default_language' => 'es', 'default_currency' => 'EUR', 'is_active' => true],
            ['country_code' => 'MX', 'country_name' => 'المكسيك',            'default_language' => 'es', 'default_currency' => 'MXN', 'is_active' => true],
            ['country_code' => 'AR', 'country_name' => 'الأرجنتين',          'default_language' => 'es', 'default_currency' => 'ARS', 'is_active' => true],
            ['country_code' => 'CO', 'country_name' => 'كولومبيا',           'default_language' => 'es', 'default_currency' => 'COP', 'is_active' => true],
            ['country_code' => 'CL', 'country_name' => 'تشيلي',              'default_language' => 'es', 'default_currency' => 'CLP', 'is_active' => true],

            // French (fr)
            ['country_code' => 'FR', 'country_name' => 'فرنسا',              'default_language' => 'fr', 'default_currency' => 'EUR', 'is_active' => true],
            ['country_code' => 'BE', 'country_name' => 'بلجيكا',             'default_language' => 'fr', 'default_currency' => 'EUR', 'is_active' => true],
            ['country_code' => 'MA', 'country_name' => 'المغرب',             'default_language' => 'fr', 'default_currency' => 'MAD', 'is_active' => true],
            ['country_code' => 'TN', 'country_name' => 'تونس',               'default_language' => 'ar', 'default_currency' => 'TND', 'is_active' => true],

            // Hindi (hi)
            ['country_code' => 'IN', 'country_name' => 'الهند',              'default_language' => 'hi', 'default_currency' => 'INR', 'is_active' => true],

            // Indonesian (id)
            ['country_code' => 'ID', 'country_name' => 'إندونيسيا',         'default_language' => 'id', 'default_currency' => 'IDR', 'is_active' => true],

            // Italian (it)
            ['country_code' => 'IT', 'country_name' => 'إيطاليا',            'default_language' => 'it', 'default_currency' => 'EUR', 'is_active' => true],

            // Japanese (ja)
            ['country_code' => 'JP', 'country_name' => 'اليابان',            'default_language' => 'ja', 'default_currency' => 'JPY', 'is_active' => true],

            // Korean (ko)
            ['country_code' => 'KR', 'country_name' => 'كوريا الجنوبية',    'default_language' => 'ko', 'default_currency' => 'KRW', 'is_active' => true],

            // Dutch (nl)
            ['country_code' => 'NL', 'country_name' => 'هولندا',             'default_language' => 'nl', 'default_currency' => 'EUR', 'is_active' => true],

            // Portuguese (pt)
            ['country_code' => 'BR', 'country_name' => 'البرازيل',           'default_language' => 'pt', 'default_currency' => 'BRL', 'is_active' => true],
            ['country_code' => 'PT', 'country_name' => 'البرتغال',           'default_language' => 'pt', 'default_currency' => 'EUR', 'is_active' => true],

            // Russian (ru)
            ['country_code' => 'RU', 'country_name' => 'روسيا',              'default_language' => 'ru', 'default_currency' => 'RUB', 'is_active' => true],
            ['country_code' => 'BY', 'country_name' => 'بيلاروسيا',          'default_language' => 'ru', 'default_currency' => 'BYN', 'is_active' => true],
            ['country_code' => 'KZ', 'country_name' => 'كازاخستان',          'default_language' => 'ru', 'default_currency' => 'KZT', 'is_active' => true],

            // Turkish (tr)
            ['country_code' => 'TR', 'country_name' => 'تركيا',              'default_language' => 'tr', 'default_currency' => 'TRY', 'is_active' => true],

            // Chinese (zh)
            ['country_code' => 'CN', 'country_name' => 'الصين',              'default_language' => 'zh', 'default_currency' => 'CNY', 'is_active' => true],
            ['country_code' => 'TW', 'country_name' => 'تايوان',             'default_language' => 'zh', 'default_currency' => 'TWD', 'is_active' => true],
            ['country_code' => 'HK', 'country_name' => 'هونغ كونغ',          'default_language' => 'zh', 'default_currency' => 'HKD', 'is_active' => true],
        ];

        $now = now();

        foreach ($countries as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }
        unset($row);

        DB::table('country_settings')->upsert(
            $countries,
            ['country_code'],
            ['country_name', 'default_language', 'default_currency', 'is_active', 'updated_at']
        );

        // ─── Country Taxes ─────────────────────────────────────────────────────
        $taxes = [
            // Gulf / Arabic countries
            ['country_code' => 'SA', 'tax_name' => 'VAT', 'tax_percentage' => 15.00, 'is_active' => true],
            ['country_code' => 'AE', 'tax_name' => 'VAT', 'tax_percentage' => 5.00,  'is_active' => true],
            ['country_code' => 'EG', 'tax_name' => 'VAT', 'tax_percentage' => 14.00, 'is_active' => true],
            ['country_code' => 'KW', 'tax_name' => 'VAT', 'tax_percentage' => 0.00,  'is_active' => false],
            ['country_code' => 'QA', 'tax_name' => 'VAT', 'tax_percentage' => 0.00,  'is_active' => false],
            ['country_code' => 'BH', 'tax_name' => 'VAT', 'tax_percentage' => 10.00, 'is_active' => true],
            ['country_code' => 'OM', 'tax_name' => 'VAT', 'tax_percentage' => 5.00,  'is_active' => true],
            ['country_code' => 'JO', 'tax_name' => 'GST', 'tax_percentage' => 16.00, 'is_active' => true],
            ['country_code' => 'IQ', 'tax_name' => 'GST', 'tax_percentage' => 15.00, 'is_active' => true],
            ['country_code' => 'LB', 'tax_name' => 'VAT', 'tax_percentage' => 11.00, 'is_active' => true],
            ['country_code' => 'MA', 'tax_name' => 'TVA', 'tax_percentage' => 20.00, 'is_active' => true],
            ['country_code' => 'TN', 'tax_name' => 'TVA', 'tax_percentage' => 19.00, 'is_active' => true],

            // Europe
            ['country_code' => 'DE', 'tax_name' => 'MwSt', 'tax_percentage' => 19.00, 'is_active' => true],
            ['country_code' => 'AT', 'tax_name' => 'MwSt', 'tax_percentage' => 20.00, 'is_active' => true],
            ['country_code' => 'CH', 'tax_name' => 'MWST', 'tax_percentage' => 8.10,  'is_active' => true],
            ['country_code' => 'GB', 'tax_name' => 'VAT',  'tax_percentage' => 20.00, 'is_active' => true],
            ['country_code' => 'FR', 'tax_name' => 'TVA',  'tax_percentage' => 20.00, 'is_active' => true],
            ['country_code' => 'BE', 'tax_name' => 'BTW',  'tax_percentage' => 21.00, 'is_active' => true],
            ['country_code' => 'ES', 'tax_name' => 'IVA',  'tax_percentage' => 21.00, 'is_active' => true],
            ['country_code' => 'IT', 'tax_name' => 'IVA',  'tax_percentage' => 22.00, 'is_active' => true],
            ['country_code' => 'NL', 'tax_name' => 'BTW',  'tax_percentage' => 21.00, 'is_active' => true],
            ['country_code' => 'PT', 'tax_name' => 'IVA',  'tax_percentage' => 23.00, 'is_active' => true],

            // Americas
            ['country_code' => 'US', 'tax_name' => 'Tax',  'tax_percentage' => 0.00,  'is_active' => false],
            ['country_code' => 'CA', 'tax_name' => 'GST',  'tax_percentage' => 5.00,  'is_active' => true],
            ['country_code' => 'MX', 'tax_name' => 'IVA',  'tax_percentage' => 16.00, 'is_active' => true],
            ['country_code' => 'BR', 'tax_name' => 'ICMS', 'tax_percentage' => 12.00, 'is_active' => true],
            ['country_code' => 'AR', 'tax_name' => 'IVA',  'tax_percentage' => 21.00, 'is_active' => true],
            ['country_code' => 'CO', 'tax_name' => 'IVA',  'tax_percentage' => 19.00, 'is_active' => true],
            ['country_code' => 'CL', 'tax_name' => 'IVA',  'tax_percentage' => 19.00, 'is_active' => true],

            // Asia-Pacific
            ['country_code' => 'IN', 'tax_name' => 'GST',  'tax_percentage' => 18.00, 'is_active' => true],
            ['country_code' => 'ID', 'tax_name' => 'PPN',  'tax_percentage' => 11.00, 'is_active' => true],
            ['country_code' => 'JP', 'tax_name' => 'CT',   'tax_percentage' => 10.00, 'is_active' => true],
            ['country_code' => 'KR', 'tax_name' => 'VAT',  'tax_percentage' => 10.00, 'is_active' => true],
            ['country_code' => 'CN', 'tax_name' => 'VAT',  'tax_percentage' => 13.00, 'is_active' => true],
            ['country_code' => 'TW', 'tax_name' => 'VAT',  'tax_percentage' => 5.00,  'is_active' => true],
            ['country_code' => 'HK', 'tax_name' => 'Tax',  'tax_percentage' => 0.00,  'is_active' => false],
            ['country_code' => 'SG', 'tax_name' => 'GST',  'tax_percentage' => 9.00,  'is_active' => true],
            ['country_code' => 'AU', 'tax_name' => 'GST',  'tax_percentage' => 10.00, 'is_active' => true],
            ['country_code' => 'NZ', 'tax_name' => 'GST',  'tax_percentage' => 15.00, 'is_active' => true],
            ['country_code' => 'PH', 'tax_name' => 'VAT',  'tax_percentage' => 12.00, 'is_active' => true],
            ['country_code' => 'PK', 'tax_name' => 'GST',  'tax_percentage' => 17.00, 'is_active' => true],
            ['country_code' => 'KZ', 'tax_name' => 'VAT',  'tax_percentage' => 12.00, 'is_active' => true],

            // Other
            ['country_code' => 'ZA', 'tax_name' => 'VAT',  'tax_percentage' => 15.00, 'is_active' => true],
            ['country_code' => 'NG', 'tax_name' => 'VAT',  'tax_percentage' => 7.50,  'is_active' => true],
            ['country_code' => 'RU', 'tax_name' => 'НДС',  'tax_percentage' => 20.00, 'is_active' => true],
            ['country_code' => 'BY', 'tax_name' => 'НДС',  'tax_percentage' => 20.00, 'is_active' => true],
            ['country_code' => 'TR', 'tax_name' => 'KDV',  'tax_percentage' => 20.00, 'is_active' => true],
        ];

        foreach ($taxes as &$tax) {
            $tax['created_at'] = $now;
            $tax['updated_at'] = $now;
        }
        unset($tax);

        DB::table('country_taxes')->upsert(
            $taxes,
            ['country_code'],
            ['tax_name', 'tax_percentage', 'is_active', 'updated_at']
        );

        // Clear all country-related caches
        Cache::flush();

        $this->command->info('✅ Inserted/updated ' . count($countries) . ' countries and ' . count($taxes) . ' tax records.');
    }
}
