<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, SoftDeletes;

    /**
     * Append computed attributes to JSON output
     */
    protected $appends = ['name', 'domain', 'email', 'active'];

    /**
     * Custom attributes stored in 'data' JSON column
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
        ];
    }

    /**
     * Get tenant name - stored in stancl data column
     */
    public function getNameAttribute()
    {
        // stancl/tenancy stores non-custom columns in the 'data' JSON column
        // and makes them accessible as regular attributes
        return data_get($this->getAttributes(), 'name',
            data_get(json_decode($this->getRawOriginal('data') ?? '{}', true), 'name', 'Tenant')
        );
    }

    /**
     * Get tenant email - stored in stancl data column
     */
    public function getEmailAttribute()
    {
        return data_get($this->getAttributes(), 'email',
            data_get(json_decode($this->getRawOriginal('data') ?? '{}', true), 'email', null)
        );
    }

    /**
     * Get tenant active status
     */
    public function getActiveAttribute()
    {
        return (bool) data_get($this->getAttributes(), 'active',
            data_get(json_decode($this->getRawOriginal('data') ?? '{}', true), 'active', true)
        );
    }

    /**
     * Get the primary domain
     */
    public function getDomainAttribute()
    {
        return $this->domains()->first()?->domain ?? 'unknown';
    }

    // Relationships
    public function settings()
    {
        return $this->hasOne(Setting::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class, 'tenant_id', 'id');
    }

    public function activeSubscription()
    {
        return $this->hasOne(TenantSubscription::class, 'tenant_id', 'id')
            ->where('status', 'active')
            ->latest();
    }

    /**
     * Get current active or trial subscription
     */
    public function currentSubscription()
    {
        return $this->hasOne(TenantSubscription::class, 'tenant_id', 'id')
            ->whereIn('status', ['active', 'trial'])
            ->latest();
    }
}
