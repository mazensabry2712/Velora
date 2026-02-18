<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpgradeRequest extends Model
{
    /**
     * The connection name for the model.
     * This model uses the central database (mysql) not tenant database
     */
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'current_plan_id',
        'requested_plan_id',
        'status',
        'requested_by_name',
        'requested_by_email',
        'message',
        'admin_notes',
        'processed_at',
        'processed_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * Get the current plan.
     */
    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'current_plan_id');
    }

    /**
     * Get the requested plan.
     */
    public function requestedPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'requested_plan_id');
    }

    /**
     * Scope a query to only include pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include processed requests.
     */
    public function scopeProcessed($query)
    {
        return $query->whereIn('status', ['approved', 'rejected']);
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
