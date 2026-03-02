<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CountrySetting extends Model
{
    protected $fillable = [
        'country_code',
        'country_name',
        'default_language',
        'default_currency',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get a country setting by code (cached 1 hour).
     */
    public static function getByCode(string $code): ?self
    {
        return Cache::remember("country_setting:{$code}", 3600, function () use ($code) {
            return static::where('country_code', strtoupper($code))
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Flush the cache for a specific country.
     */
    public static function flushCache(string $code): void
    {
        Cache::forget("country_setting:{$code}");
    }

    /**
     * Get all active countries (cached 1 hour).
     */
    public static function allActive(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('country_settings:all_active', 3600, function () {
            return static::where('is_active', true)->orderBy('country_name')->get();
        });
    }

    // Clear allActive cache on save/delete
    protected static function booted(): void
    {
        static::saved(function (self $model) {
            Cache::forget('country_settings:all_active');
            Cache::forget("country_setting:{$model->country_code}");
        });
        static::deleted(function (self $model) {
            Cache::forget('country_settings:all_active');
            Cache::forget("country_setting:{$model->country_code}");
        });
    }
}
