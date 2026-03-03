<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprConsent extends Model
{
    public $timestamps = false;

    protected $table = 'gdpr_consents';

    protected $fillable = [
        'customer_id', 'type', 'granted', 'ip_address', 'user_agent',
        'source', 'legal_basis', 'granted_at', 'revoked_at',
    ];

    protected $casts = [
        'granted'    => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeGranted($query)
    {
        return $query->where('granted', true)->whereNull('revoked_at');
    }
}
