@extends('super-admin.layout')

@section('title', $entry ? 'Edit: ' . $entry->country_name : 'Add Country Market')
@section('breadcrumb')
    <a href="{{ route('super-admin.country-pricing.index') }}" class="text-indigo-600 hover:underline">Country Pricing</a>
    <span class="mx-2 text-slate-400">/</span>
    <span class="text-slate-700 dark:text-slate-200 font-medium">{{ $entry ? $entry->country_name : 'Add Market' }}</span>
@endsection

@section('content')
<div
    x-data="{
        price: '{{ old('price', $entry?->price ?? '') }}',
        currency: '{{ old('currency', $entry?->currency ?? 'USD') }}',
        taxPct: '{{ old('tax_percentage', $tax?->tax_percentage ?? '') }}',
        taxActive: {{ old('tax_active', $tax?->is_active ?? false) ? 'true' : 'false' }},

        get preview() {
            if (!this.price || !this.currency) return '—';
            const n = parseFloat(this.price);
            if (isNaN(n)) return '—';
            const sym = { USD:'$',EUR:'€',GBP:'£',SAR:'﷼',AED:'د.إ',KWD:'KD',QAR:'QR',
                OMR:'OMR',BHD:'BD',JOD:'JD',EGP:'E£',TRY:'₺',INR:'₹',BRL:'R$',
                IDR:'Rp',MYR:'RM',PHP:'₱',PKR:'₨',NGN:'₦',KRW:'₩',JPY:'¥',
                CNY:'¥',CAD:'CA$',AUD:'A$',MXN:'MX$',ZAR:'R',SEK:'kr',NOK:'kr',
                DKK:'kr',CHF:'CHF',PLN:'zł' }[this.currency.toUpperCase()] || this.currency + ' ';
            return sym + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ' / mo';
        },

        get taxPreview() {
            const p = parseFloat(this.price), t = parseFloat(this.taxPct);
            if (!this.taxActive || isNaN(p) || isNaN(t) || t === 0) return null;
            const total = (p * (1 + t / 100)).toFixed(2);
            return '+' + t + '% → ' + total + ' ' + this.currency;
        },

        highlightMethod(el) {
            const lbl = el.closest('label');
            if (el.checked) {
                lbl.classList.add('border-indigo-400', 'bg-indigo-50', 'dark:bg-indigo-900/20');
                lbl.classList.remove('border-slate-200', 'dark:border-slate-600');
            } else {
                lbl.classList.remove('border-indigo-400', 'bg-indigo-50', 'dark:bg-indigo-900/20');
                lbl.classList.add('border-slate-200', 'dark:border-slate-600');
            }
        }
    }"
    class="max-w-3xl"
>

    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            @if($entry)
                ✏️ {{ $entry->country_name }}
                @if($entry->country_code === 'GLOBAL')
                    <span class="text-sm font-medium bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 px-2.5 py-1 rounded-full">Global Fallback</span>
                @endif
            @else
                🌍 Add Country Market
            @endif
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
            Configure pricing, payment methods, tax, and locale for this market in one place.
        </p>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 text-sm">
        <p class="font-semibold mb-1">Please fix the following errors:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST"
          action="{{ $entry ? route('super-admin.country-pricing.update', $entry) : route('super-admin.country-pricing.store') }}">
        @csrf
        @if($entry) @method('PUT') @endif

        <div class="space-y-5">

            {{-- ── 1. Country Identity ─────────────────────────────────────── --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white dark:bg-slate-600 border border-slate-200 dark:border-slate-500 flex items-center justify-center">
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm">Country Identity</span>
                    </div>

                    {{-- Active Status Toggle --}}
                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input type="hidden" name="is_active" value="0">
                        <div class="relative">
                            <input type="checkbox" name="is_active" value="1" id="is_active"
                                   {{ old('is_active', $entry?->is_active ?? true) ? 'checked' : '' }}
                                   class="sr-only peer" />
                            <div class="w-11 h-6 bg-slate-300 dark:bg-slate-600 peer-checked:bg-emerald-500 rounded-full transition-colors ring-offset-2"></div>
                            <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Active Market</span>
                    </label>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
                            Country Code <span class="text-red-500">*</span>
                            <span class="ml-1 normal-case font-normal text-slate-400">(ISO 3166, e.g. SA — or GLOBAL)</span>
                        </label>
                        <input type="text" name="country_code"
                               value="{{ old('country_code', $entry?->country_code) }}"
                               placeholder="e.g. SA"
                               oninput="this.value=this.value.toUpperCase()"
                               {{ $entry && $entry->country_code === 'GLOBAL' ? 'readonly' : '' }}
                               class="w-full border border-slate-300 dark:border-slate-600 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono {{ $entry && $entry->country_code === 'GLOBAL' ? 'opacity-60 cursor-not-allowed' : '' }}"
                               required />
                        @error('country_code')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
                            Country Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="country_name"
                               value="{{ old('country_name', $entry?->country_name) }}"
                               placeholder="e.g. Saudi Arabia"
                               class="w-full border border-slate-300 dark:border-slate-600 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                               required />
                        @error('country_name')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── 2. Pricing ──────────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 border border-indigo-200 dark:border-indigo-700 flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm">Pricing</span>
                    </div>
                    {{-- Live Preview --}}
                    <div class="text-right">
                        <div class="text-xs text-slate-400 mb-0.5">Preview</div>
                        <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400 tabular-nums" x-text="preview"></div>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
                            Price <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="price" step="0.01" min="0"
                               x-model="price"
                               placeholder="e.g. 99"
                               class="w-full border border-slate-300 dark:border-slate-600 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                               required />
                        @error('price')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
                            Currency <span class="text-red-500">*</span>
                            <span class="ml-1 normal-case font-normal">(ISO 4217)</span>
                        </label>
                        <input type="text" name="currency" maxlength="3"
                               x-model="currency"
                               placeholder="SAR"
                               oninput="this.value=this.value.toUpperCase()"
                               class="w-full border border-slate-300 dark:border-slate-600 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm font-mono uppercase"
                               required />
                        @error('currency')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ── 3. Payment Methods ──────────────────────────────────────── --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 border border-emerald-200 dark:border-emerald-700 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm">Payment Methods</span>
                    <span class="text-xs text-slate-400 ml-1">— available gateways for this market</span>
                </div>
                <div class="p-6">
                    @php $selectedMethods = old('payment_methods', $entry?->payment_methods ?? []); @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        @foreach($availableMethods as $method)
                        @php $isChecked = in_array($method, $selectedMethods); @endphp
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-all select-none
                                      {{ $isChecked
                                           ? 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20'
                                           : 'border-slate-200 dark:border-slate-600 hover:border-indigo-300 dark:hover:border-indigo-500' }}">
                            <input type="checkbox" name="payment_methods[]" value="{{ $method }}"
                                   {{ $isChecked ? 'checked' : '' }}
                                   class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500"
                                   @change="highlightMethod($el)" />
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300 leading-tight">
                                {{ \App\Models\CountryPricing::paymentMethodLabel($method) }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                    @error('payment_methods')
                        <p class="mt-3 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- ── 4. Tax & Compliance ─────────────────────────────────────── --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/40 border border-amber-200 dark:border-amber-700 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm">Tax &amp; Compliance</span>
                    </div>
                    {{-- Tax total preview badge --}}
                    <div x-show="taxPreview !== null"
                         x-cloak
                         class="text-xs font-semibold bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700 px-3 py-1 rounded-full"
                         x-text="taxPreview"></div>
                </div>
                <div class="p-6 space-y-5">
                    {{-- Tax Active Toggle --}}
                    <label class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer select-none transition-all
                                  {{ old('tax_active', $tax?->is_active ?? false) ? 'border-amber-300 bg-amber-50 dark:bg-amber-900/10 dark:border-amber-700' : 'border-slate-200 dark:border-slate-600 hover:border-amber-200 dark:hover:border-amber-700' }}"
                           :class="taxActive
                               ? 'border-amber-300 bg-amber-50 dark:bg-amber-900/10 dark:border-amber-700'
                               : 'border-slate-200 dark:border-slate-600'">
                        <input type="hidden" name="tax_active" value="0">
                        <div class="relative flex-shrink-0">
                            <input type="checkbox" name="tax_active" value="1" id="tax_active"
                                   x-model="taxActive"
                                   {{ old('tax_active', $tax?->is_active ?? false) ? 'checked' : '' }}
                                   class="sr-only peer" />
                            <div class="w-10 h-5 bg-slate-300 dark:bg-slate-600 peer-checked:bg-amber-500 rounded-full transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-700 dark:text-slate-300">Apply tax for this market</div>
                            <div class="text-xs text-slate-400 mt-0.5">Shown at checkout. Tax amount added on top of the base price.</div>
                        </div>
                    </label>

                    {{-- Tax fields --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 transition-opacity duration-200"
                         :class="taxActive ? 'opacity-100' : 'opacity-40 pointer-events-none'">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
                                Tax Name
                                <span class="ml-1 normal-case font-normal text-slate-400">(VAT, GST, KDV…)</span>
                            </label>
                            <input type="text" name="tax_name"
                                   value="{{ old('tax_name', $tax?->tax_name ?? 'VAT') }}"
                                   placeholder="VAT"
                                   class="w-full border border-slate-300 dark:border-slate-600 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
                                Tax Rate
                            </label>
                            <div class="relative">
                                <input type="number" name="tax_percentage" step="0.01" min="0" max="100"
                                       x-model="taxPct"
                                       value="{{ old('tax_percentage', $tax?->tax_percentage ?? '') }}"
                                       placeholder="e.g. 15"
                                       class="w-full border border-slate-300 dark:border-slate-600 rounded-xl px-4 py-2.5 pr-10 text-slate-900 dark:text-white bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm" />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── 5. Locale & Language ────────────────────────────────────── --}}
            @if(!$entry || $entry->country_code !== 'GLOBAL')
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/40 border border-violet-200 dark:border-violet-700 flex items-center justify-center">
                        <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-slate-800 dark:text-slate-100 text-sm">Locale &amp; Language</span>
                    <span class="text-xs text-slate-400 ml-1">— default interface language for this market</span>
                </div>
                <div class="p-6">
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
                        Default Language
                    </label>
                    <select name="default_language"
                            class="w-full sm:w-1/2 border border-slate-300 dark:border-slate-600 rounded-xl px-4 py-2.5 text-slate-900 dark:text-white bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-500 text-sm">
                        <option value="">— Not configured —</option>
                        @foreach($availableLanguages as $code => $label)
                            <option value="{{ $code }}"
                                {{ old('default_language', $setting?->default_language) === $code ? 'selected' : '' }}>
                                {{ $label }} ({{ $code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

        </div>{{-- end space-y-5 --}}

        {{-- ── Sticky Submit Bar ───────────────────────────────────────────── --}}
        <div class="mt-8 flex items-center gap-3 sticky bottom-4 z-10 bg-white/90 dark:bg-slate-900/90 backdrop-blur border border-slate-200 dark:border-slate-700 rounded-2xl px-6 py-4 shadow-lg shadow-slate-200/60 dark:shadow-slate-900/60">
            <button type="submit"
                    class="bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold px-7 py-2.5 rounded-xl shadow-lg shadow-indigo-200 dark:shadow-indigo-900/50 transition-all hover:-translate-y-0.5 text-sm">
                {{ $entry ? '💾 Save Changes' : '🌍 Add Market' }}
            </button>
            <a href="{{ route('super-admin.country-pricing.index') }}"
               class="px-5 py-2.5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium text-sm transition-colors">
                Cancel
            </a>
            @if($entry)
                <span class="ml-auto text-xs text-slate-400">Last updated: {{ $entry->updated_at->diffForHumans() }}</span>
            @endif
        </div>

    </form>
</div>
@endsection
