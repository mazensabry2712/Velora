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
        'grace_ends_at',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'amount_paid',
        'payment_method',
        'notes',
        'activated_at',
        'converted_at',
        'nudges_sent',
        'aha_reached',
        'aha_reached_at',
        'founder_alerted',
        'trial_extended',
        'trial_extended_at',
    ];

    protected $casts = [
        'trial_ends_at'      => 'datetime',
        'grace_ends_at'      => 'datetime',
        'starts_at'          => 'datetime',
        'ends_at'            => 'datetime',
        'cancelled_at'       => 'datetime',
        'activated_at'       => 'datetime',
        'converted_at'       => 'datetime',
        'aha_reached_at'     => 'datetime',
        'trial_extended_at'  => 'datetime',
        'amount_paid'        => 'decimal:2',
        'nudges_sent'        => 'array',
        'aha_reached'        => 'boolean',
        'founder_alerted'    => 'boolean',
        'trial_extended'     => 'boolean',
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
    public function isActive(): bool
    {
        return $this->status === 'active' && (!$this->ends_at || $this->ends_at->isFuture());
    }

    /**
     * Check if subscription is on trial
     */
    public function onTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Trial days elapsed since subscription was created.
     */
    public function trialDayElapsed(): int
    {
        return (int) $this->created_at->diffInDays(now());
    }

    /**
     * Trial days remaining (0 if expired).
     */
    public function trialDaysLeft(): int
    {
        if (! $this->trial_ends_at) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Whether a specific nudge day has been sent.
     */
    public function nudgeSent(int $day): bool
    {
        return (bool) ($this->nudges_sent[$day] ?? false);
    }

    /**
     * Mark a nudge day as sent.
     */
    public function markNudgeSent(int $day): void
    {
        $sent = $this->nudges_sent ?? [];
        $sent[$day] = true;
        $this->update(['nudges_sent' => $sent]);
    }
}
