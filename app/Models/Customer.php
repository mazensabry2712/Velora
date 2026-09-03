<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'email', 'phone', 'phone_country',
        'dob', 'gender', 'avatar', 'language', 'timezone', 'notes',
        'tags', 'is_blocked', 'block_reason', 'gdpr_consent',
        'gdpr_consent_at', 'gdpr_consent_ip', 'total_spent',
        'total_visits', 'last_visit_at', 'ltv_tier',
        'acquisition_source', 'referral_code', 'metadata',
    ];

    protected $casts = [
        'dob'              => 'date',
        'tags'             => 'array',
        'metadata'         => 'array',
        'is_blocked'       => 'boolean',
        'gdpr_consent'     => 'boolean',
        'gdpr_consent_at'  => 'datetime',
        'last_visit_at'    => 'datetime',
        'total_spent'      => 'decimal:2',
        'total_visits'     => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'customer_id_new');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function waitingList(): HasMany
    {
        return $this->hasMany(WaitingList::class);
    }

    public function gdprConsents(): HasMany
    {
        return $this->hasMany(GdprConsent::class);
    }

    public function pushTokens(): HasMany
    {
        return $this->hasMany(PushToken::class, 'owner_id')
            ->where('owner_type', 'customer');
    }

    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeVip($query)
    {
        return $query->where('ltv_tier', 'vip');
    }

    /** Compatibility accessor; canonical storage is Customer::ltv_tier. */
    public function getIsVipAttribute(): bool
    {
        return $this->ltv_tier === 'vip'
            || (bool) ($this->relationLoaded('user') ? $this->user?->is_vip : false);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1)
        );
    }

    public function recalculateStats(): void
    {
        $stats = $this->appointments()
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as visits, SUM(price) as spent, MAX(starts_at) as last_visit')
            ->first();

        $this->update([
            'total_visits'  => $stats->visits ?? 0,
            'total_spent'   => $stats->spent ?? 0,
            'last_visit_at' => $stats->last_visit,
            'ltv_tier'      => $this->calculateLtvTier($stats->visits ?? 0, $stats->spent ?? 0),
        ]);
    }

    private function calculateLtvTier(int $visits, float $spent): string
    {
        if ($visits === 0) {
            return 'new';
        }

        if ($spent >= 2000 || $visits >= 20) {
            return 'vip';
        }

        if ($visits >= 5) {
            return 'regular';
        }

        if ($this->last_visit_at && $this->last_visit_at->lt(now()->subMonths(6))) {
            return 'at_risk';
        }

        return 'regular';
    }
}
