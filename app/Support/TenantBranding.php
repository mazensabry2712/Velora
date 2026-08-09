<?php

namespace App\Support;

use App\Models\Setting;

class TenantBranding
{
    /**
     * Resolve the current tenant's display name and logo for the admin layout.
     *
     * @return array{name: string, logo: ?string}
     */
    public static function resolve(): array
    {
        $locale = app()->getLocale();

        $settings = Setting::where('tenant_id', tenant()->id)->first();

        $name = $settings?->business_name ?? tenant()->name ?? config('app.name');

        return [
            'name' => self::normalizeName($name, $locale),
            'logo' => $settings?->logo,
        ];
    }

    /**
     * business_name can be a plain string, a translatable array ({'en' => ..., 'ar' => ...}),
     * or a cast object — normalize all three shapes down to a single display string.
     */
    private static function normalizeName(mixed $name, string $locale): string
    {
        if (is_array($name)) {
            $name = $name[$locale]
                ?? $name['en']
                ?? (is_array(reset($name)) ? config('app.name') : reset($name))
                ?? config('app.name');
        }

        if (is_object($name)) {
            $name = $name->{$locale} ?? $name->en ?? config('app.name');
        }

        return is_scalar($name) ? (string) $name : config('app.name');
    }
}
