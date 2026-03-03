<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * BusinessRule — tenant-level rules that govern booking & queue behavior.
 *
 * Examples:
 *  - max_advance_booking_days: How far ahead a customer can book
 *  - min_advance_booking_hours: Minimum notice before an appointment
 *  - max_cancellation_hours: How late a customer can cancel
 *  - auto_confirm_bookings: Whether bookings are auto-confirmed
 *  - allow_same_day_booking: Whether same-day bookings are allowed
 *  - max_bookings_per_customer_per_day: Limit per customer per day
 */
class BusinessRule extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Predefined rule keys ─────────────────────────────────────────────

    public const MAX_ADVANCE_BOOKING_DAYS            = 'max_advance_booking_days';
    public const MIN_ADVANCE_BOOKING_HOURS           = 'min_advance_booking_hours';
    public const MAX_CANCELLATION_HOURS              = 'max_cancellation_hours';
    public const AUTO_CONFIRM_BOOKINGS               = 'auto_confirm_bookings';
    public const ALLOW_SAME_DAY_BOOKING              = 'allow_same_day_booking';
    public const MAX_BOOKINGS_PER_CUSTOMER_PER_DAY   = 'max_bookings_per_customer_per_day';
    public const REQUIRE_GDPR_CONSENT                = 'require_gdpr_consent';
    public const QUEUE_MAX_SIZE                      = 'queue_max_size';

    // ── Value helpers ────────────────────────────────────────────────────

    /**
     * Get a rule value by key. Returns $default if not found or inactive.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $rule = static::where('key', $key)->where('is_active', true)->first();

        if (! $rule) {
            return $default;
        }

        return match ($rule->type) {
            'boolean' => filter_var($rule->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $rule->value,
            'float'   => (float) $rule->value,
            default   => $rule->value,
        };
    }

    /**
     * Set (upsert) a rule value by key.
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', ?string $description = null): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value'       => (string) $value,
                'type'        => $type,
                'description' => $description,
                'is_active'   => true,
            ]
        );
    }
}
