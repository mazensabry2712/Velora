<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'tenant_id',
        'business_name',
        'business_name_ar',
        'phone',
        'email',
        'address',
        'logo',
        'whatsapp',
        'facebook',
        'instagram',
        'twitter',
        'tiktok',
        'snapchat',
        'working_hours',
        'notification_settings',
        'language',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'notification_settings' => 'array',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
