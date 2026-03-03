<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringRule extends Model
{
    protected $table = 'recurring_rules';

    protected $fillable = [
        'frequency', 'interval', 'days_of_week',
        'ends_on', 'max_occurrences', 'generated_count',
    ];

    protected $casts = [
        'days_of_week'    => 'array',
        'ends_on'         => 'date',
        'interval'        => 'integer',
        'max_occurrences' => 'integer',
        'generated_count' => 'integer',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'recurring_id');
    }

    public function hasReachedLimit(): bool
    {
        if ($this->max_occurrences && $this->generated_count >= $this->max_occurrences) {
            return true;
        }

        if ($this->ends_on && $this->ends_on->isPast()) {
            return true;
        }

        return false;
    }
}
