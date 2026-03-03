<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'number',
        'tenant_id',
        'customer_id',
        'appointment_id',
        'amount',
        'tax_rate',
        'status',
        'notes',
        'due_date',
        'issued_at',
        'pdf_path',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'tax_rate'  => 'decimal:2',
        'due_date'  => 'date',
        'issued_at' => 'datetime',
    ];

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Subtotal = sum of all item totals (in decimal).
     * Falls back to $this->amount when items are not loaded.
     */
    public function getSubtotalAttribute(): float
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            return round($this->items->sum('total') / 100, 2);
        }

        return (float) $this->amount;
    }

    /**
     * Tax amount calculated from subtotal × tax_rate %.
     */
    public function getTaxAmountAttribute(): float
    {
        return round($this->subtotal * ($this->tax_rate / 100), 2);
    }

    /**
     * Total discount across all line items (in decimal).
     */
    public function getDiscountAttribute(): float
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            return round($this->items->sum('discount_amount') / 100, 2);
        }

        return 0.0;
    }

    /**
     * Grand total = subtotal + tax - discount.
     */
    public function getTotalAmountAttribute(): float
    {
        return round($this->subtotal + $this->tax_amount - $this->discount, 2);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
