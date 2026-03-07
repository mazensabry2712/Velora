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
        'available_languages',
        'onboarding_completed',
        'onboarding_step',
        'booking_enabled',
        'queue_enabled',
    ];

    protected $casts = [
        'working_hours'        => 'array',
        'notification_settings'=> 'array',
        'available_languages'  => 'array',
        'onboarding_completed' => 'boolean',
        'onboarding_step'      => 'integer',
        'booking_enabled'      => 'boolean',
        'queue_enabled'        => 'boolean',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
