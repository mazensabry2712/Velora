<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    protected $table = 'payment_transactions';

    protected $fillable = [
        'ulid', 'appointment_id', 'customer_id', 'gateway',
        'gateway_transaction_id', 'gateway_intent_id', 'type', 'status',
        'amount', 'gateway_fee', 'net_amount', 'currency',
        'refunded_amount', 'refund_of', 'refund_reason',
        'gateway_response', 'metadata', 'processed_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'metadata'         => 'array',
        'processed_at'     => 'datetime',
        'amount'           => 'integer',
        'gateway_fee'      => 'integer',
        'net_amount'       => 'integer',
        'refunded_amount'  => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function refundOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'refund_of');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(self::class, 'refund_of');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(StaffCommission::class, 'transaction_id');
    }

    // ── Accessors ────────────────────────────────────────────────────────

    /** Amount in decimal (e.g. 1500 cents → 15.00) */
    public function getAmountDecimalAttribute(): float
    {
        return $this->amount / 100;
    }

    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isRefundable(): bool
    {
        return $this->status === 'succeeded' && $this->type === 'charge';
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }

    public function scopeForGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }
}
