<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\App;

/**
 * HasTranslations — reads JSON translation columns like name, title, bio, description.
 *
 * Usage in model:
 *   use HasTranslations;
 *   protected array $translatable = ['name', 'description'];
 *
 * Usage in code:
 *   $service->trans('name')          → current locale value
 *   $service->trans('name', 'ar')    → Arabic value
 *   $service->transOrDefault('name') → current locale with EN fallback
 */
trait HasTranslations
{
    /**
     * Get a translated value for the given field and locale.
     * Falls back to English, then to first available translation.
     */
    public function trans(string $field, ?string $locale = null): ?string
    {
        $locale ??= App::getLocale();
        $data = $this->getAttribute($field);

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (! is_array($data)) {
            return $data;
        }

        return $data[$locale]
            ?? $data[config('app.fallback_locale', 'en')]
            ?? array_values(array_filter($data))[0]
            ?? null;
    }

    /**
     * Set a translation for a specific locale on a JSON field.
     */
    public function setTrans(string $field, string $value, ?string $locale = null): static
    {
        $locale ??= App::getLocale();
        $data = $this->getAttribute($field);

        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }

        $data = is_array($data) ? $data : [];
        $data[$locale] = $value;

        $this->setAttribute($field, $data);

        return $this;
    }

    /**
     * Get all translations for a field as an associative array.
     */
    public function allTrans(string $field): array
    {
        $data = $this->getAttribute($field);

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Cast a translatable field → array automatically.
     * Call this inside the model's casts() or $casts.
     */
    protected function getTranslatableCasts(): array
    {
        return array_fill_keys($this->translatable ?? [], 'array');
    }
}
