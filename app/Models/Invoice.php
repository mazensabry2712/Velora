<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'amount',
        'status',
        'pdf_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // Accessors
    public function getTotalAmountAttribute()
    {
        return $this->amount;
    }

    public function getTaxAmountAttribute()
    {
        return 0; // Default to 0, can be customized later
    }

    public function getDiscountAttribute()
    {
        return 0; // Default to 0, can be customized later
    }

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
