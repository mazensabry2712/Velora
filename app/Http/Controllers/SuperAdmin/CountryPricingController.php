<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CountryPricing;
use App\Models\CountrySetting;
use App\Models\CountryTax;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class CountryPricingController extends Controller
{
    private const AVAILABLE_METHODS = [
        'stripe', 'paypal', 'mada', 'fawry', 'razorpay', 'moyasar',
        'paymob', 'telr', 'tap', 'iyzico', 'pagseguro',
    ];

    private const AVAILABLE_LANGUAGES = [
        'en' => 'English',
        'ar' => 'العربية',
        'fr' => 'Français',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'zh' => '中文',
        'ja' => '日本語',
        'tr' => 'Türkçe',
        'hi' => 'हिंदी',
        'ko' => '한국어',
        'nl' => 'Nederlands',
        'id' => 'Bahasa Indonesia',
    ];

    public function index()
    {
        $entries = CountryPricing::orderByRaw("CASE WHEN country_code = 'GLOBAL' THEN 0 ELSE 1 END")
            ->orderBy('country_name')
            ->paginate(25);

        $codes = $entries->pluck('country_code');

        $taxes = CountryTax::whereIn('country_code', $codes)->get()->keyBy('country_code');
        $settings = CountrySetting::whereIn('country_code', $codes)->get()->keyBy('country_code');

        return view('super-admin.country-pricing.index', [
            'entries'          => $entries,
            'taxes'            => $taxes,
            'settings'         => $settings,
            'availableMethods' => self::AVAILABLE_METHODS,
        ]);
    }

    public function create()
    {
        return view('super-admin.country-pricing.form', [
            'entry'              => null,
            'tax'                => null,
            'setting'            => null,
            'availableMethods'   => self::AVAILABLE_METHODS,
            'availableLanguages' => self::AVAILABLE_LANGUAGES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'country_code'      => ['required', 'string', 'max:10', 'regex:/^[A-Z]{2,10}$/', Rule::unique('country_pricing', 'country_code')],
            'country_name'      => ['required', 'string', 'max:100'],
            'price'             => ['required', 'numeric', 'min:0'],
            'currency'          => ['required', 'string', 'size:3'],
            'payment_methods'   => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['string', Rule::in(self::AVAILABLE_METHODS)],
            'is_active'         => ['boolean'],
        ]);

        $data['country_code'] = strtoupper($data['country_code']);
        $data['currency']     = strtoupper($data['currency']);
        $data['is_active']    = $request->boolean('is_active', true);

        $pricing = CountryPricing::create($data);

        $this->syncCountrySetting($request, $pricing->country_code, $pricing->country_name, $data['currency'], $data['is_active']);
        $this->syncTax($request, $pricing->country_code);

        return redirect()->route('super-admin.country-pricing.index')
            ->with('success', "Pricing for {$data['country_code']} created successfully.");
    }

    public function edit(CountryPricing $countryPricing)
    {
        $tax     = CountryTax::where('country_code', $countryPricing->country_code)->first();
        $setting = CountrySetting::where('country_code', $countryPricing->country_code)->first();

        return view('super-admin.country-pricing.form', [
            'entry'              => $countryPricing,
            'tax'                => $tax,
            'setting'            => $setting,
            'availableMethods'   => self::AVAILABLE_METHODS,
            'availableLanguages' => self::AVAILABLE_LANGUAGES,
        ]);
    }

    public function update(Request $request, CountryPricing $countryPricing)
    {
        $data = $request->validate([
            'country_code'      => ['required', 'string', 'max:10', 'regex:/^[A-Z]{2,10}$/', Rule::unique('country_pricing', 'country_code')->ignore($countryPricing->id)],
            'country_name'      => ['required', 'string', 'max:100'],
            'price'             => ['required', 'numeric', 'min:0'],
            'currency'          => ['required', 'string', 'size:3'],
            'payment_methods'   => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['string', Rule::in(self::AVAILABLE_METHODS)],
            'is_active'         => ['boolean'],
        ]);

        $data['country_code'] = strtoupper($data['country_code']);
        $data['currency']     = strtoupper($data['currency']);
        $data['is_active']    = $request->boolean('is_active', true);

        $countryPricing->update($data);

        $this->syncCountrySetting($request, $countryPricing->country_code, $data['country_name'], $data['currency'], $data['is_active']);
        $this->syncTax($request, $countryPricing->country_code);

        return redirect()->route('super-admin.country-pricing.index')
            ->with('success', "Pricing for {$data['country_code']} updated.");
    }

    public function destroy(CountryPricing $countryPricing)
    {
        if ($countryPricing->country_code === 'GLOBAL') {
            return back()->with('error', 'The GLOBAL fallback record cannot be deleted.');
        }

        $countryPricing->delete();

        return redirect()->route('super-admin.country-pricing.index')
            ->with('success', 'Country pricing entry deleted.');
    }

    public function toggleActive(CountryPricing $countryPricing)
    {
        if ($countryPricing->country_code === 'GLOBAL' && $countryPricing->is_active) {
            return response()->json(['error' => 'GLOBAL record cannot be disabled.'], 422);
        }

        $countryPricing->update(['is_active' => ! $countryPricing->is_active]);

        Cache::forget("country_pricing:{$countryPricing->country_code}");

        return response()->json([
            'is_active' => $countryPricing->is_active,
            'message'   => $countryPricing->is_active ? 'Activated' : 'Deactivated',
        ]);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    private function syncCountrySetting(Request $request, string $code, string $name, string $currency, bool $active): void
    {
        if ($code === 'GLOBAL') {
            return;
        }

        $lang = $request->input('default_language');
        if (! $lang) {
            return;
        }

        CountrySetting::updateOrCreate(
            ['country_code' => $code],
            [
                'country_name'     => $name,
                'default_language' => $lang,
                'default_currency' => $currency,
                'is_active'        => $active,
            ]
        );

        Cache::forget("country_setting:{$code}");
        Cache::forget('country_settings:all_active');
    }

    private function syncTax(Request $request, string $code): void
    {
        $taxPercent = $request->input('tax_percentage');
        if ($taxPercent === null || $taxPercent === '') {
            return;
        }

        CountryTax::updateOrCreate(
            ['country_code' => $code],
            [
                'tax_name'       => $request->input('tax_name', 'VAT'),
                'tax_percentage' => (float) $taxPercent,
                'is_active'      => $request->boolean('tax_active', true),
            ]
        );

        Cache::forget("country_tax:{$code}");
    }
}
