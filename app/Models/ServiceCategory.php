<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name', 'slug', 'icon', 'color', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'name'      => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'category_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
