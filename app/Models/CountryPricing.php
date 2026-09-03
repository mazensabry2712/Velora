<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CountryPricing extends Model
{
    protected $table = 'country_pricing';

    protected $fillable = [
        'country_code',
        'country_name',
        'price',
        'currency',
        'lang',
        'payment_methods',
        'is_active',
    ];

    protected $casts = [
        'price'           => 'decimal:2',
        'payment_methods' => 'array',
        'is_active'       => 'boolean',
    ];

    /**
     * Global pricing configuration is stored on the central database.
     */
    public function getConnectionName(): string
    {
        return (string) config('tenancy.database.central_connection', parent::getConnectionName() ?? 'mysql');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Static helpers ────────────────────────────────────────────────────────

    /**
     * Get pricing for a country code. Falls back to GLOBAL.
     * Cached for 30 minutes.
     */
    public static function forCountry(string $code): self
    {
        $code = strtoupper(trim($code));

        return Cache::remember("country_pricing:{$code}", 1800, function () use ($code) {
            // 1. Exact country match
            $pricing = static::where('country_code', $code)
                ->where('is_active', true)
                ->first();

            if ($pricing) {
                return $pricing;
            }

            // 2. Fallback to GLOBAL
            return static::where('country_code', 'GLOBAL')
                ->where('is_active', true)
                ->firstOrFail();
        });
    }

    /**
     * Get the GLOBAL fallback pricing record.
     */
    public static function global(): self
    {
        return Cache::remember('country_pricing:GLOBAL', 1800, function () {
            return static::where('country_code', 'GLOBAL')
                ->where('is_active', true)
                ->firstOrFail();
        });
    }

    /**
     * Flush cache entry when a record is saved or deleted.
     */
    protected static function booted(): void
    {
        $flush = function (self $model) {
            Cache::forget("country_pricing:{$model->country_code}");
            Cache::forget('country_pricing:GLOBAL');
        };

        static::saved($flush);
        static::deleted($flush);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns a formatted price string, e.g. "199 EGP" or "$39".
     */
    public function formattedPrice(): string
    {
        $symbols = [
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'SAR' => 'SAR ',
            'AED' => 'AED ', 'EGP' => 'EGP ', 'INR' => '₹', 'TRY' => '₺',
            'BRL' => 'R$ ', 'IDR' => 'IDR ', 'PKR' => '₨', 'NGN' => '₦',
            'MAD' => 'MAD ', 'JPY' => '¥', 'KRW' => '₩', 'MXN' => '$',
            'RUB' => '₽', 'CNY' => '¥',
        ];

        $symbol = $symbols[$this->currency] ?? ($this->currency . ' ');
        $amount = in_array($this->currency, ['JPY', 'KRW', 'IDR'])
            ? number_format((float) $this->price, 0)
            : number_format((float) $this->price, 0);

        return $symbol . $amount;
    }

    /**
     * Human-readable label for a payment method key.
     */
    public static function paymentMethodLabel(string $method): string
    {
        return match (strtolower($method)) {
            'stripe'    => 'Stripe',
            'paypal'    => 'PayPal',
            'mada'      => 'Mada',
            'fawry'     => 'Fawry',
            'razorpay'  => 'Razorpay',
            'moyasar'   => 'Moyasar',
            'paymob'    => 'Paymob',
            'telr'      => 'Telr',
            'tap'       => 'Tap Payments',
            'iyzico'    => 'Iyzico',
            'pagseguro' => 'PagSeguro',
            default     => ucfirst($method),
        };
    }
}
