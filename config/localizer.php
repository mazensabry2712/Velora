<?php

declare(strict_types=1);

use NielsNumbers\LaravelLocalizer\Detectors\BrowserDetector;
use NielsNumbers\LaravelLocalizer\Detectors\UserDetector;

return [
    'supported_locales' => [
        'ar', 'en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'tr', 'hi', 'ko', 'nl', 'id',
    ],

    'hide_default_locale' => true,
    // Keep the central landing canonical at / (Arabic) and let explicit
    // localized URLs such as /en or /fr determine the language.
    'redirect_enabled' => false,

    'persist_locale' => [
        'session' => true,
        'cookie' => true,
    ],

    // The landing should not auto-switch from browser language. The URL is
    // authoritative: / is Arabic, /en is English, /fr is French, etc.
    'detectors' => [],

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
