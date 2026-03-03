<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Resource extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name', 'type', 'quantity', 'color', 'is_active', 'metadata',
    ];

    protected $casts = [
        'name'      => 'array',
        'metadata'  => 'array',
        'is_active' => 'boolean',
        'quantity'  => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_resources')
                    ->withPivot('quantity');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
