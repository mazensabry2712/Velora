<?php

declare(strict_types=1);

use App\Http\Middleware\LocaleSignalDetector;

return [
    'supported_locales' => [
        'ar', 'en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'tr', 'hi', 'ko', 'nl', 'id',
    ],

    'hide_default_locale' => true,
    // Arabic is the canonical locale for the unprefixed landing URL (/).
    // Every other locale uses its explicit /{locale} URL.
    'omitted_locale' => 'ar',
    'redirect_enabled' => false,

    'persist_locale' => [
        'session' => true,
        'cookie' => true,
    ],

    // Keep Velora's existing pricing-country locale signals authoritative
    // for the unprefixed landing route. Laravel Localizer still resolves
    // explicit locale URLs (/en, /fr, ...) from their localized route data.
    'detectors' => [
        LocaleSignalDetector::class,
    ],

    'locale_directions' => [
        'ar' => 'rtl',
        'en' => 'ltr',
        'fr' => 'ltr',
        'es' => 'ltr',
        'de' => 'ltr',
        'it' => 'ltr',
        'pt' => 'ltr',
        'ru' => 'ltr',
        'zh' => 'ltr',
        'ja' => 'ltr',
        'tr' => 'ltr',
        'hi' => 'ltr',
        'ko' => 'ltr',
        'nl' => 'ltr',
        'id' => 'ltr',
    ],
];
