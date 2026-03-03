<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReminderRule extends Model
{
    use HasTranslations;

    protected $table = 'reminder_rules';

    protected $fillable = [
        'name', 'trigger_type', 'trigger_minutes', 'channel',
        'template_key', 'template_vars', 'send_to_customer',
        'send_to_staff', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'name'             => 'array',
        'template_vars'    => 'array',
        'is_active'        => 'boolean',
        'send_to_customer' => 'boolean',
        'send_to_staff'    => 'boolean',
        'trigger_minutes'  => 'integer',
        'sort_order'       => 'integer',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(ReminderLog::class, 'rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
