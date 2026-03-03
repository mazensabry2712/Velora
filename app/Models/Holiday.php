<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Holiday extends Model
{
    use HasTranslations;

    protected $fillable = [
        'date', 'name', 'applies_to_all',
    ];

    protected $casts = [
        'date'           => 'date',
        'name'           => 'array',
        'applies_to_all' => 'boolean',
    ];

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'holiday_staff');
    }

    public function scopeOnDate($query, \Carbon\Carbon $date)
    {
        return $query->whereDate('date', $date->toDateString());
    }
}
