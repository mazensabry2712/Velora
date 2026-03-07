<?php

namespace App\Http\Controllers;

use App\Models\CountrySetting;
use App\Models\CountryTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CountrySettingController extends Controller
{
    public function index()
    {
        $allCountries = CountrySetting::orderBy('country_name')->get();

        // Load taxes separately (keyed by country_code)
        $taxes = CountryTax::where('is_active', true)
            ->get()
            ->keyBy('country_code');

        $countries = $allCountries->map(fn($c) => [
                'id'               => $c->id,
                'country_code'     => $c->country_code,
                'country_name'     => $c->country_name,
                'default_language' => $c->default_language,
                'default_currency' => $c->default_currency,
                'is_active'        => (bool) $c->is_active,
                'tax_name'         => $taxes->get($c->country_code)?->tax_name,
                'tax_percentage'   => $taxes->get($c->country_code)?->tax_percentage,
                'edit_url'         => route('super-admin.countries.edit', $c),
                'delete_url'       => route('super-admin.countries.destroy', $c),
            ]);

        return view('super-admin.countries.index', [
            'countriesJson' => $countries->toJson(),
            'total'         => $countries->count(),
        ]);
    }

    public function create()
    {
        return view('super-admin.countries.edit', ['country' => new CountrySetting(), 'tax' => new CountryTax()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'country_code'     => 'required|size:2|alpha|uppercase',
            'country_name'     => 'required|string|max:100',
            'default_language' => 'required|string|in:en,ar,fr,es,de,it,pt,ru,zh,ja,tr,hi,ko,nl,id',
            'default_currency' => 'required|string|size:3|alpha|uppercase',
            'is_active'        => 'boolean',
        ]);

        $country = CountrySetting::create($data);

        // Optional: create / update tax row
        $this->syncTax($request, $country->country_code);

        Cache::forget('country_settings:all_active');

        return redirect()->route('super-admin.countries.index')
            ->with('success', "Country created.");
    }

    public function edit(CountrySetting $country)
    {
        $tax = CountryTax::where('country_code', $country->country_code)->first() ?? new CountryTax();
        return view('super-admin.countries.edit', compact('country', 'tax'));
    }

    public function update(Request $request, CountrySetting $country)
    {
        $data = $request->validate([
            'country_name'     => 'required|string|max:100',
            'default_language' => 'required|string|in:en,ar,fr,es,de,it,pt,ru,zh,ja,tr,hi,ko,nl,id',
            'default_currency' => 'required|string|size:3|alpha|uppercase',
            'is_active'        => 'boolean',
        ]);

        $country->update($data);

        $this->syncTax($request, $country->country_code);

        return redirect()->route('super-admin.countries.index')
            ->with('success', "Country updated.");
    }

    public function destroy(CountrySetting $country)
    {
        $country->delete();
        CountryTax::where('country_code', $country->country_code)->delete();

        return redirect()->route('super-admin.countries.index')
            ->with('success', "Country deleted.");
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function syncTax(Request $request, string $countryCode): void
    {
        $taxPercent = $request->input('tax_percentage');
        if (is_null($taxPercent) || $taxPercent === '') {
            return;
        }

        CountryTax::updateOrCreate(
            ['country_code' => $countryCode],
            [
                'tax_name'       => $request->input('tax_name', 'VAT'),
                'tax_percentage' => (float) $taxPercent,
                'is_active'      => $request->boolean('tax_active', true),
            ]
        );

        Cache::forget("country_tax:{$countryCode}");
    }
}
