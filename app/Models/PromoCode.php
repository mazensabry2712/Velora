<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'used_count'     => 'integer',
        'max_uses'       => 'integer',
        'is_active'      => 'boolean',
        'expires_at'     => 'datetime',
    ];

    /**
     * Check whether this promo code can still be used.
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Increment the usage counter by 1.
     */
    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    /**
     * Scope: only active codes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
