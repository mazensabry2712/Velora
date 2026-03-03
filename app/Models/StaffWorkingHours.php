<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffWorkingHours extends Model
{
    protected $table = 'staff_working_hours';

    protected $fillable = [
        'staff_id', 'day_of_week', 'start_time', 'end_time', 'is_working',
    ];

    protected $casts = [
        'is_working'  => 'boolean',
        'day_of_week' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
