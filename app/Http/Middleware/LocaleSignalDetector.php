<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use NielsNumbers\LaravelLocalizer\Contracts\DetectorInterface;

class LocaleSignalDetector implements DetectorInterface
{
    /**
     * Resolve an explicit Velora locale signal before browser detection.
     *
     * @return string|array<int, string>|null
     */
    public function detect(Request $request): string|array|null
    {
        $supported = config('localizer.supported_locales', []);

        $sessionLocale = $request->session()->get('central_locale');
        if (is_string($sessionLocale) && in_array($sessionLocale, $supported, true)) {
            return $sessionLocale;
        }

        $cookieLocale = $request->cookie('velora_locale_override');
        if (is_string($cookieLocale) && in_array($cookieLocale, $supported, true)) {
            return $cookieLocale;
        }

        return null;
    }
}
