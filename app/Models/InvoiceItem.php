<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'total',
        'currency',
        'item_type',
    ];

    protected $casts = [
        'description'     => 'array',
        'quantity'        => 'decimal:2',
        'unit_price'      => 'integer',   // stored in cents
        'discount_amount' => 'integer',   // stored in cents
        'total'           => 'integer',   // stored in cents
    ];

    // ── Accessors ─────────────────────────────────────────────────────────────

    /** Unit price in decimal (e.g. 10.50) */
    public function getUnitPriceDecimalAttribute(): float
    {
        return round($this->unit_price / 100, 2);
    }

    /** Discount in decimal */
    public function getDiscountDecimalAttribute(): float
    {
        return round($this->discount_amount / 100, 2);
    }

    /** Total in decimal */
    public function getTotalDecimalAttribute(): float
    {
        return round($this->total / 100, 2);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
