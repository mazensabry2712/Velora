<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'max_users',
        'max_appointments',
        'storage_limit',
        'features',
        'is_active',
        'is_popular',
        'trial_days',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Get geo-localized prices for this plan.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class, 'plan_id');
    }

    /**
     * Get subscriptions for this plan
     */
    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class);
    }

    /**
     * Get active subscriptions count
     */
    public function activeSubscriptionsCount()
    {
        return $this->subscriptions()->where('status', 'active')->count();
    }
}
