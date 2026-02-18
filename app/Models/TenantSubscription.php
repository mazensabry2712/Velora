<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;

class TenantSubscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'amount_paid',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Get the tenant
     */
    public function tenant()
    {        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Get the subscription plan
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Check if subscription is active
     */
    public function isActive()
    {
        return $this->status === 'active' && (!$this->ends_at || $this->ends_at->isFuture());
    }

    /**
     * Check if subscription is on trial
     */
    public function onTrial()
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }
}
