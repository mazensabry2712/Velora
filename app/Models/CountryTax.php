<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CountryTax extends Model
{
    protected $fillable = [
        'country_code',
        'tax_name',
        'tax_percentage',
        'is_active',
    ];

    protected $casts = [
        'tax_percentage' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    /**
     * Get an active tax record for a country code (cached 1 hour).
     * Returns null if no tax applies.
     */
    public static function forCountry(string $code): ?self
    {
        $code = strtoupper($code);
        return Cache::remember("country_tax:{$code}", 3600, function () use ($code) {
            return static::where('country_code', $code)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Get tax percentage for a country, defaulting to 0.
     */
    public static function percentageFor(string $code): float
    {
        return (float) (static::forCountry($code)?->tax_percentage ?? 0);
    }

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            Cache::forget("country_tax:{$model->country_code}");
        });
        static::deleted(function (self $model) {
            Cache::forget("country_tax:{$model->country_code}");
        });
    }
}
