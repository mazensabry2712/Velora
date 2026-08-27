<?php

declare(strict_types=1);

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

    // The localized route is authoritative for the landing:
    // / is Arabic, /en is English, /fr is French, etc.
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
