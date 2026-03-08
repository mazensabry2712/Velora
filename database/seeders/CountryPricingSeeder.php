<?php

namespace Database\Seeders;

use App\Models\CountryPricing;
use Illuminate\Database\Seeder;

class CountryPricingSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            // ── Fallback ──────────────────────────────────────────────────────
            [
                'country_code'    => 'GLOBAL',
                'country_name'    => 'Global (Default)',
                'price'           => 39.00,
                'currency'        => 'USD',
                'payment_methods' => ['stripe', 'paypal'],
            ],

            // ── Americas ──────────────────────────────────────────────────────
            [
                'country_code'    => 'US',
                'country_name'    => 'United States',
                'price'           => 39.00,
                'currency'        => 'USD',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'CA',
                'country_name'    => 'Canada',
                'price'           => 49.00,
                'currency'        => 'USD',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'BR',
                'country_name'    => 'Brazil',
                'price'           => 149.00,
                'currency'        => 'BRL',
                'payment_methods' => ['stripe', 'pagseguro'],
            ],
            [
                'country_code'    => 'MX',
                'country_name'    => 'Mexico',
                'price'           => 599.00,
                'currency'        => 'MXN',
                'payment_methods' => ['stripe', 'paypal'],
            ],

            // ── Europe ────────────────────────────────────────────────────────
            [
                'country_code'    => 'GB',
                'country_name'    => 'United Kingdom',
                'price'           => 29.00,
                'currency'        => 'GBP',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'FR',
                'country_name'    => 'France',
                'price'           => 35.00,
                'currency'        => 'EUR',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'DE',
                'country_name'    => 'Germany',
                'price'           => 35.00,
                'currency'        => 'EUR',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'ES',
                'country_name'    => 'Spain',
                'price'           => 35.00,
                'currency'        => 'EUR',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'IT',
                'country_name'    => 'Italy',
                'price'           => 35.00,
                'currency'        => 'EUR',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'NL',
                'country_name'    => 'Netherlands',
                'price'           => 35.00,
                'currency'        => 'EUR',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'RU',
                'country_name'    => 'Russia',
                'price'           => 2490.00,
                'currency'        => 'RUB',
                'payment_methods' => ['stripe'],
            ],
            [
                'country_code'    => 'TR',
                'country_name'    => 'Turkey',
                'price'           => 999.00,
                'currency'        => 'TRY',
                'payment_methods' => ['stripe', 'iyzico'],
            ],

            // ── Middle East ───────────────────────────────────────────────────
            [
                'country_code'    => 'SA',
                'country_name'    => 'Saudi Arabia',
                'price'           => 99.00,
                'currency'        => 'SAR',
                'payment_methods' => ['stripe', 'mada', 'moyasar'],
            ],
            [
                'country_code'    => 'AE',
                'country_name'    => 'United Arab Emirates',
                'price'           => 139.00,
                'currency'        => 'AED',
                'payment_methods' => ['stripe', 'telr', 'tap'],
            ],
            [
                'country_code'    => 'EG',
                'country_name'    => 'Egypt',
                'price'           => 199.00,
                'currency'        => 'EGP',
                'payment_methods' => ['stripe', 'fawry', 'paymob'],
            ],
            [
                'country_code'    => 'KW',
                'country_name'    => 'Kuwait',
                'price'           => 12.00,
                'currency'        => 'KWD',
                'payment_methods' => ['stripe', 'tap'],
            ],
            [
                'country_code'    => 'QA',
                'country_name'    => 'Qatar',
                'price'           => 139.00,
                'currency'        => 'QAR',
                'payment_methods' => ['stripe', 'tap'],
            ],
            [
                'country_code'    => 'BH',
                'country_name'    => 'Bahrain',
                'price'           => 15.00,
                'currency'        => 'BHD',
                'payment_methods' => ['stripe', 'tap'],
            ],
            [
                'country_code'    => 'OM',
                'country_name'    => 'Oman',
                'price'           => 15.00,
                'currency'        => 'OMR',
                'payment_methods' => ['stripe', 'tap'],
            ],
            [
                'country_code'    => 'MA',
                'country_name'    => 'Morocco',
                'price'           => 349.00,
                'currency'        => 'MAD',
                'payment_methods' => ['stripe', 'paypal'],
            ],

            // ── Asia-Pacific ──────────────────────────────────────────────────
            [
                'country_code'    => 'IN',
                'country_name'    => 'India',
                'price'           => 799.00,
                'currency'        => 'INR',
                'payment_methods' => ['razorpay', 'stripe'],
            ],
            [
                'country_code'    => 'PK',
                'country_name'    => 'Pakistan',
                'price'           => 1499.00,
                'currency'        => 'PKR',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'ID',
                'country_name'    => 'Indonesia',
                'price'           => 499000.00,
                'currency'        => 'IDR',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'JP',
                'country_name'    => 'Japan',
                'price'           => 4900.00,
                'currency'        => 'JPY',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'KR',
                'country_name'    => 'South Korea',
                'price'           => 42000.00,
                'currency'        => 'KRW',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'CN',
                'country_name'    => 'China',
                'price'           => 259.00,
                'currency'        => 'CNY',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'AU',
                'country_name'    => 'Australia',
                'price'           => 55.00,
                'currency'        => 'USD',
                'payment_methods' => ['stripe', 'paypal'],
            ],

            // ── Africa ────────────────────────────────────────────────────────
            [
                'country_code'    => 'NG',
                'country_name'    => 'Nigeria',
                'price'           => 14999.00,
                'currency'        => 'NGN',
                'payment_methods' => ['stripe', 'paypal'],
            ],
            [
                'country_code'    => 'ZA',
                'country_name'    => 'South Africa',
                'price'           => 599.00,
                'currency'        => 'USD',
                'payment_methods' => ['stripe', 'paypal'],
            ],
        ];

        foreach ($entries as $entry) {
            CountryPricing::updateOrCreate(
                ['country_code' => $entry['country_code']],
                array_merge($entry, ['is_active' => true])
            );
        }

        $this->command->info('✅ Country pricing seeded: ' . count($entries) . ' entries');
    }
}
