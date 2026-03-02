<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class PlanPrice extends Model
{
    protected $fillable = [
        'plan_id',
        'country_code',
        'currency',
        'amount',
        'stripe_price_id',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    // ─── Static helpers ───────────────────────────────────────────────────────

    /**
     * Get the best price for a plan + country, falling back to the default price.
     * Cached for 30 minutes.
     */
    public static function forPlanAndCountry(int $planId, string $countryCode): ?self
    {
        $countryCode = strtoupper($countryCode);
        $cacheKey    = "plan_price:{$planId}:{$countryCode}";

        return Cache::remember($cacheKey, 1800, function () use ($planId, $countryCode) {
            // Country-specific price first
            $price = static::where('plan_id', $planId)
                ->where('country_code', $countryCode)
                ->where('is_active', true)
                ->first();

            if ($price) {
                return $price;
            }

            // Fall back to default
            return static::where('plan_id', $planId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Get default price for a plan (no country).
     */
    public static function defaultForPlan(int $planId): ?self
    {
        return static::where('plan_id', $planId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Flush all cached prices for a plan.
     */
    public static function flushPlanCache(int $planId): void
    {
        // Flush default
        Cache::forget("plan_price:{$planId}:default");
        // Individual country caches are flushed lazily (they expire in 30min)
        // For immediate flush, use Cache::flush() or a tagged cache driver
    }

    protected static function booted(): void
    {
        static::saved(function (self $model) {
            Cache::forget("plan_price:{$model->plan_id}:{$model->country_code}");
        });
        static::deleted(function (self $model) {
            Cache::forget("plan_price:{$model->plan_id}:{$model->country_code}");
        });
    }
}
