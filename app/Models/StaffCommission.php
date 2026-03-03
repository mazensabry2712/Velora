<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffCommission extends Model
{
    protected $table = 'staff_commissions';

    protected $fillable = [
        'staff_id', 'appointment_id', 'transaction_id',
        'gross_amount', 'commission_type', 'commission_rate',
        'commission_amount', 'currency', 'is_paid', 'paid_at',
        'payout_batch_id', 'metadata',
    ];

    protected $casts = [
        'gross_amount'      => 'integer',
        'commission_amount' => 'integer',
        'commission_rate'   => 'decimal:4',
        'is_paid'           => 'boolean',
        'paid_at'           => 'datetime',
        'metadata'          => 'array',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function getCommissionDecimalAttribute(): float
    {
        return $this->commission_amount / 100;
    }
}
