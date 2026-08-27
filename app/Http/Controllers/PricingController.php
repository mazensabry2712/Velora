<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function __construct(private PricingService $pricing) {}

    /**
     * AJAX: switch the active pricing country for this session.
     * Called by the Alpine.js country-switcher on the pricing page.
     *
     * POST /pricing/set-country
     * Body: { "country_code": "EG", "lang": "ar" }
     */
    public function setCountry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_code' => ['required', 'string', 'regex:/^[A-Z]{2,10}$/i', 'max:10'],
            'lang'         => ['nullable', 'string', 'max:5'],
        ]);

        $code    = strtoupper($validated['country_code']);
        $pricing = $this->pricing->setCountryOverride($code);

        // Keep Velora's explicit locale selection synchronized with the
        // Laravel Localizer's own persistence keys. Without this, the POST
        // can set `central_locale=ar` while Localizer still sees a stale
        // `locale=en` value on the next GET and renders English on `/`.
        $supported = ['en', 'ar', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'tr', 'hi', 'ko', 'nl', 'id'];
        $lang = $validated['lang'] ?? 'en';
        if (in_array($lang, $supported, true)) {
            session()->put('central_locale', $lang);
            session()->put('locale', $lang);

            cookie()->queue(cookie()->forever('velora_locale_override', $lang));
            cookie()->queue(cookie()->forever('locale', $lang));
        }

        return response()->json([
            'ok'              => true,
            'country_code'    => $pricing->country_code,
            'country_name'    => $pricing->country_name,
            'price'           => (float) $pricing->price,
            'currency'        => $pricing->currency,
            'formatted_price' => $pricing->formattedPrice(),
            'payment_methods' => $pricing->payment_methods ?? [],
        ]);
    }
}
