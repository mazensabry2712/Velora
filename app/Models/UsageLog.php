<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class UsageLog extends Model
{
    /**
     * The central database connection used by the model.
     */
    protected $connection = 'mysql';

    /**
     * Keep central logging on the configured tenancy central connection.
     */
    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection', parent::getConnectionName());
    }

    protected $fillable = [
        'tenant_id',
        'type',
        'details',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'details' => 'array',
    ];

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

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
