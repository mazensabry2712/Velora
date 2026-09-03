<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    public static function get($key, $default = null)
    {
        try {
            $setting = static::where('key', $key)->first();
        } catch (Throwable $e) {
            // Central settings are optional for isolated feature tests and fresh installations.
            return $default;
        }

        if (! $setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    public static function set($key, $value, $type = 'string', $group = 'general')
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'group' => $group,
            ]
        );
    }

    protected static function booted(): void
    {
        $flushGatewayCache = static function (): void {
            Cache::forget('gateway_router:enabled');
        };

        static::saved($flushGatewayCache);
        static::deleted($flushGatewayCache);
    }

    protected static function castValue($value, $type)
    {
        return match($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
