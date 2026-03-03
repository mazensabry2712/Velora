<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes, HasTranslations;

    protected $fillable = [
        'category_id', 'name', 'name_ar', 'name_i18n', 'description',
        'description_i18n', 'slug', 'duration', 'duration_minutes',
        'buffer_before_minutes', 'buffer_after_minutes', 'price',
        'deposit_amount', 'deposit_pct', 'max_capacity', 'is_group',
        'is_active', 'is_online_bookable', 'image', 'sort_order', 'metadata',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'is_group'               => 'boolean',
        'is_online_bookable'     => 'boolean',
        'price'                  => 'decimal:2',
        'deposit_amount'         => 'decimal:2',
        'name_i18n'              => 'array',
        'description_i18n'       => 'array',
        'metadata'               => 'array',
        'max_capacity'           => 'integer',
        'duration_minutes'       => 'integer',
        'buffer_before_minutes'  => 'integer',
        'buffer_after_minutes'   => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    /** Legacy: staff linked via users table */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'staff_services', 'service_id', 'user_id')
                    ->withPivot(['override_price', 'override_duration'])
                    ->withTimestamps();
    }

    /** New: staff linked via dedicated staff table */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_services', 'service_id', 'staff_id')
                    ->withPivot(['override_price', 'override_duration'])
                    ->withTimestamps();
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'service_resources')
                    ->withPivot('quantity');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnlineBookable($query)
    {
        return $query->where('is_online_bookable', true)->where('is_active', true);
    }

    // ── Accessors ────────────────────────────────────────────────────────

    /**
     * Localized name — prefers name_i18n JSON, falls back to name/name_ar legacy columns.
     */
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();

        if ($this->name_i18n) {
            return $this->trans('name_i18n') ?? $this->name;
        }

        return ($locale === 'ar' && $this->name_ar) ? $this->name_ar : $this->name;
    }

    /**
     * Total duration including buffers (used by SlotEngine).
     */
    public function getTotalDurationAttribute(): int
    {
        $base = $this->duration_minutes ?: (int) $this->duration;
        return $base + $this->buffer_before_minutes + $this->buffer_after_minutes;
    }
}
