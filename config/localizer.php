<?php

declare(strict_types=1);

use App\Http\Middleware\LocaleSignalDetector;
use NielsNumbers\LaravelLocalizer\Detectors\BrowserDetector;
use NielsNumbers\LaravelLocalizer\Detectors\UserDetector;

return [
    'supported_locales' => [
        'ar', 'en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'tr', 'hi', 'ko', 'nl', 'id',
    ],

    'hide_default_locale' => true,
    // Explicit language URLs (/en, /fr, ...) must render directly.
    // The landing root (/) is the canonical Arabic URL and should not
    // redirect based on browser/session detection.
    'redirect_enabled' => false,

    'persist_locale' => [
        'session' => true,
        'cookie' => true,
    ],

    'detectors' => [
        LocaleSignalDetector::class,
        UserDetector::class,
        BrowserDetector::class,
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
