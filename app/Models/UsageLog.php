<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class UsageLog extends Model
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
        'type',
        'details',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'details' => 'array',
    ];

    /**
     * Log a new usage entry.
     */
    public static function log(string $type, array $details = []): self
    {
        return self::create([
            'tenant_id' => tenant('id'),
            'type' => $type,
            'details' => $details,
            'user_id' => auth()->id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Scope a query to only include logs for a specific tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include logs of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include logs within a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
