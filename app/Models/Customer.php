<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'phone_country',
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

    // ── Relationships ────────────────────────────────────────────────────

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'customer_id_new');
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

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeVip($query)
    {
        return $query->where('ltv_tier', 'vip');
    }

    // ── Accessors ────────────────────────────────────────────────────────

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

    // ── Methods ──────────────────────────────────────────────────────────

    /**
     * Recalculate and update customer lifecycle stats.
     * Called after each appointment completion.
     */
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

        // Check last visit recency
        if ($this->last_visit_at && $this->last_visit_at->lt(now()->subMonths(6))) {
            return 'at_risk';
        }

        return 'regular';
    }
}
