{{-- geo-based single-plan pricing page --}}
@extends('layouts.landing')
@section('content')

@php
    $countriesWithPricing = $allPricing->where('country_code', '!=', 'GLOBAL')->sortBy('country_name');

    // Build per-country data including tax info for Alpine.js
    $allDataJson = json_encode(
        collect($allPricing)->mapWithKeys(function ($cp) {
            $taxRec = \App\Models\CountryTax::forCountry($cp->country_code);
            return [
                $cp->country_code => [
                    'name'     => $cp->country_name,
                    'monthly'  => $cp->formattedPrice(),
                    'price'    => (float) $cp->price,
                    'currency' => $cp->currency,
                    'methods'  => $cp->payment_methods ?? [],
                    'taxPct'   => (float) ($taxRec?->tax_percentage ?? 0),
                    'taxName'  => $taxRec?->tax_name ?? 'VAT',
                    'lang'     => \App\Models\CountrySetting::getByCode($cp->country_code)?->default_language ?? 'en',
                ],
            ];
        })
    );

    $globalDataJson = json_encode([
        'name'     => 'Other countries',
        'monthly'  => $globalPricing->formattedPrice(),
        'price'    => (float) $globalPricing->price,
        'currency' => $globalPricing->currency,
        'methods'  => $globalPricing->payment_methods ?? [],
        'taxPct'   => 0,
        'taxName'  => 'VAT',
        'lang'     => 'en',
    ]);
@endphp

<script>
window._vPInit = {!! json_encode([
    'countryCode'      => $pricing['country_code'],
    'countryName'      => $pricing['country_name'],
    'rawPrice'         => (float)$pricing['price'],
    'currency'         => $pricing['currency'],
    'monthlyFormatted' => $pricing['formatted_price'],
    'paymentMethods'   => $pricing['payment_methods'] ?? [],
    'taxPct'           => (float)($taxPct ?? 0),
    'taxName'          => $taxName ?? 'VAT',
]) !!};
</script>
<div class="min-h-screen"
     x-data="pricingPage(window._vPInit)">

    {{-- ── Slim Country Bar ───────────────────────────────────────────────── --}}
    <div class="pt-24 pb-0 flex justify-center px-4">
        <button @click="openSwitcher = true"
                class="group inline-flex items-center gap-2.5 glass rounded-full px-5 py-2.5 text-sm text-gray-300 border border-transparent hover:border-brand-500/40 transition-all">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
            </span>
            Pricing for
            <span x-text="countryName" class="font-semibold text-white"></span>
            <svg class="w-3.5 h-3.5 text-gray-500 group-hover:text-brand-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>

    {{-- ── Hero ────────────────────────────────────────────────────────────── --}}
    <div class="text-center pt-8 pb-10 px-4 relative">
        <div class="absolute inset-0 bg-gradient-radial from-brand-500/10 via-transparent to-transparent pointer-events-none"></div>
        <h1 class="relative text-4xl sm:text-5xl md:text-6xl font-extrabold mb-4 leading-tight">
            Simple, honest<br><span class="gradient-text">pricing.</span>
        </h1>
        <p class="relative text-gray-400 text-base sm:text-xl max-w-xl mx-auto">
            One plan &mdash; all features &mdash; price adapts to your region.
        </p>
    </div>

    {{-- ── Billing Toggle ──────────────────────────────────────────────────── --}}
    <div class="flex justify-center mb-10 px-4">
        <div class="glass rounded-2xl p-1 flex gap-1">
            <button @click="billing = 'monthly'"
                    :class="billing === 'monthly' ? 'bg-brand-600 text-white shadow-lg' : 'text-gray-400 hover:text-white'"
                    class="px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200">
                Monthly
            </button>
            <button @click="billing = 'annual'"
                    :class="billing === 'annual' ? 'bg-brand-600 text-white shadow-lg' : 'text-gray-400 hover:text-white'"
                    class="px-6 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center gap-2">
                Annual
                <span class="text-xs font-bold px-2 py-0.5 rounded-full transition-all"
                      :class="billing === 'annual' ? 'bg-green-500/30 text-green-300' : 'bg-white/8 text-gray-500'">
                    Save 2 months
                </span>
            </button>
        </div>
    </div>

    {{-- ── Main 2-col Layout ───────────────────────────────────────────────── --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 pb-20 grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

        {{-- ─── Left: Pricing Card (sticky) ───────────────────────────────── --}}
        <div class="lg:sticky lg:top-24 order-first">
            <div class="glass rounded-3xl overflow-hidden border border-brand-500/40"
                 style="box-shadow:0 0 60px rgba(108,99,255,0.12);">

                {{-- Card Header --}}
                <div class="px-8 pt-8 pb-6 border-b border-white/5">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                 style="background:linear-gradient(135deg,#6C63FF,#8b76ff);box-shadow:0 4px 15px rgba(108,99,255,0.4)">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-white text-sm">{{ $appName ?? 'Velora' }}</p>
                                <p class="text-gray-500 text-xs">Full Platform</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-3 py-1.5 rounded-full bg-brand-500/20 text-brand-300 border border-brand-500/30">
                            {{ $trialDays }}-day free trial
                        </span>
                    </div>

                    {{-- Price --}}
                    <div>
                        <div x-show="billing === 'monthly'" class="flex items-end gap-2">
                            <span class="text-6xl font-black text-white leading-none" x-text="monthlyFormatted"></span>
                            <span class="text-gray-400 text-lg mb-1">/mo</span>
                        </div>
                        <div x-show="billing === 'annual'" x-cloak class="flex items-end gap-2">
                            <span class="text-6xl font-black text-white leading-none" x-text="annualPerMonthFormatted"></span>
                            <div class="mb-1">
                                <p class="text-gray-400 text-sm">/mo</p>
                                <p class="text-green-400 text-xs font-medium" x-text="'Billed ' + annualTotalFormatted + '/yr'"></p>
                            </div>
                        </div>
                        <p class="text-green-400 text-xs font-medium mt-3">
                            ✓ No credit card for the free trial
                        </p>
                    </div>
                </div>

                {{-- CTA --}}
                <div class="px-8 py-6 border-b border-white/5">
                    @if($registrationEnabled ?? true)
                    <a href="{{ route('signup') }}"
                       class="btn-primary block w-full text-white font-bold text-base px-6 py-4 rounded-2xl text-center transition-transform hover:scale-[1.01]">
                        Start {{ $trialDays }}-Day Free Trial
                    </a>
                    <p class="text-center text-xs text-gray-500 mt-3">No credit card &nbsp;·&nbsp; Cancel anytime &nbsp;·&nbsp; No setup fees</p>
                    @else
                    <div class="text-center py-3 px-5 rounded-xl bg-white/5 border border-white/10 text-gray-400 text-sm">
                        Registration is currently closed. Check back soon.
                    </div>
                    @endif
                </div>

                {{-- Payment Methods --}}
                <div class="px-8 py-5 border-b border-white/5">
                    <p class="text-xs text-gray-500 uppercase tracking-widest mb-3">Payment methods</p>
                    <div class="flex flex-wrap gap-2" x-html="renderPaymentMethods()"></div>
                </div>

                {{-- Tax notice (shown only if taxPct > 0) --}}
                <div x-show="taxPct > 0" x-cloak class="mx-8 mb-1 mt-2 flex items-center gap-2 rounded-xl border border-yellow-500/20 bg-yellow-500/5 px-4 py-2.5 text-xs">
                    <svg class="w-4 h-4 flex-shrink-0 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-yellow-300">
                        <span x-text="taxName"></span> (<span x-text="taxPct + '%'"></span>) will be added at checkout.
                        Total: <span x-text="taxTotalFormatted" class="font-bold"></span>/mo
                    </span>
                </div>

                {{-- Footer meta --}}
                <div class="px-8 py-5 space-y-3.5">
                    <div class="flex items-center gap-3 text-sm">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span class="text-gray-300">30-day money-back guarantee</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm">
                        <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-gray-400">Region: <span x-text="countryName" class="text-white font-medium"></span></span>
                        <button @click="openSwitcher = true"
                                class="ml-auto text-brand-400 hover:text-brand-300 text-xs underline underline-offset-2 transition-colors">
                            Change
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Right: Features + Timeline + Stats ─────────────────────────── --}}
        <div class="space-y-6">

            {{-- Features --}}
            <div class="glass rounded-3xl p-8 border border-white/5">
                <h3 class="text-white font-bold text-lg mb-6">Everything included</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3.5">
                    @foreach([
                        'Smart appointment scheduling',
                        'Real-time digital queue',
                        'Unlimited staff accounts',
                        'Customer ratings & feedback',
                        'Analytics & business reports',
                        'Automated SMS & email reminders',
                        '15 interface languages',
                        'Onboarding wizard',
                        'Isolated database per tenant',
                        'API access',
                        'Custom booking page',
                        'Priority support',
                    ] as $feature)
                    <div class="flex items-center gap-3 text-sm text-gray-300">
                        <svg class="w-4 h-4 text-brand-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $feature }}
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Trial Timeline --}}
            <div class="glass rounded-3xl p-8 border border-white/5">
                <h3 class="text-white font-bold text-lg mb-6">What happens after the trial?</h3>
                @php $graceEnd = $trialDays + 3; $roDay = $trialDays + 4; @endphp
                <div class="space-y-5">
                    <div class="flex gap-4 items-start">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-black flex-shrink-0 mt-0.5"
                             style="background:rgba(108,99,255,0.2);border:1px solid rgba(108,99,255,0.4);color:#a78bfa">1</div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-white font-semibold text-sm">Full Access</p>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-brand-500/15 text-brand-300">Days 1–{{ $trialDays }}</span>
                            </div>
                            <p class="text-gray-400 text-xs mt-1">All features unlocked. Email only. No card needed.</p>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-black flex-shrink-0 mt-0.5"
                             style="background:rgba(251,191,36,0.15);border:1px solid rgba(251,191,36,0.35);color:#fbbf24">2</div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-white font-semibold text-sm">Grace Period</p>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-500/10 text-yellow-400">Days {{ $trialDays + 1 }}–{{ $graceEnd }}</span>
                            </div>
                            <p class="text-gray-400 text-xs mt-1">3 extra days to choose a plan before restrictions kick in.</p>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-black flex-shrink-0 mt-0.5"
                             style="background:rgba(156,163,175,0.08);border:1px solid rgba(156,163,175,0.2);color:#9ca3af">3</div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="text-white font-semibold text-sm">Read-Only Mode</p>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-white/5 text-gray-500">Day {{ $roDay }}+</span>
                            </div>
                            <p class="text-gray-400 text-xs mt-1">Nothing is deleted. Upgrade any time to restore full access.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="glass rounded-3xl p-6 border border-white/5">
                <div class="grid grid-cols-3 divide-x divide-white/5 text-center">
                    <div class="px-4">
                        <p class="text-3xl font-black text-white">2,400<span class="text-brand-400">+</span></p>
                        <p class="text-xs text-gray-500 mt-1">Businesses</p>
                    </div>
                    <div class="px-4">
                        <p class="text-3xl font-black text-white">1M<span class="text-brand-400">+</span></p>
                        <p class="text-xs text-gray-500 mt-1">Appointments</p>
                    </div>
                    <div class="px-4">
                        <p class="text-3xl font-black text-white">29<span class="text-brand-400">+</span></p>
                        <p class="text-xs text-gray-500 mt-1">Countries</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── FAQ ─────────────────────────────────────────────────────────────── --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 pb-20">
        <div class="text-center mb-10">
            <h2 class="text-3xl sm:text-4xl font-extrabold">
                {{ __('landing.faq_title') }} <span class="gradient-text">{{ __('landing.faq_title_hl') }}</span>
            </h2>
        </div>
        @php
        $faqs = [
            [__('landing.faq_1_q'), __('landing.faq_1_a')],
            [__('landing.faq_2_q'), __('landing.faq_2_a')],
            [__('landing.faq_3_q'), __('landing.faq_3_a')],
            ['Why does the price differ by country?', 'We use regional pricing to make Velora accessible to businesses everywhere. Prices reflect local purchasing power — you always get the exact same features regardless of where you are.'],
            [__('landing.faq_5_q'), __('landing.faq_5_a')],
            [__('landing.faq_7_q'), __('landing.faq_7_a')],
        ];
        @endphp
        <div class="space-y-3" x-data="{ open: null }">
            @foreach($faqs as $i => $faq)
            <div class="glass rounded-2xl overflow-hidden border border-white/5">
                <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="w-full flex items-center justify-between gap-4 px-6 py-5 text-left">
                    <span class="font-semibold text-white text-sm sm:text-base">{{ $faq[0] }}</span>
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform duration-200"
                         :class="open === {{ $i }} ? 'rotate-45' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}" x-collapse class="px-6 pb-5 text-gray-400 text-sm leading-relaxed">
                    {{ $faq[1] }}
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Bottom CTA ──────────────────────────────────────────────────────── --}}
    @if($registrationEnabled ?? true)
    <div class="text-center pb-24 px-4">
        <a href="{{ route('signup') }}"
           class="btn-primary inline-flex items-center gap-3 text-white font-bold text-lg px-10 py-5 rounded-2xl transition-transform hover:scale-[1.02]">
            Start Your Free Trial
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </a>
        <p class="text-gray-500 text-sm mt-3">
            {{ $trialDays }} days free &nbsp;·&nbsp; No setup fees &nbsp;·&nbsp;
            <span x-text="displayPrice"></span>/mo after trial
        </p>
    </div>
    @endif

    {{-- ── Country Switcher Modal ───────────────────────────────────────────── --}}
    <div x-show="openSwitcher"
         x-cloak
         @keydown.escape.window="openSwitcher = false"
         @click.self="openSwitcher = false"
         class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4"
         style="background:rgba(0,0,0,0.65);backdrop-filter:blur(6px)"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="w-full max-w-2xl glass rounded-3xl overflow-hidden border border-white/10 shadow-2xl"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

            {{-- Modal Header --}}
            <div class="px-6 py-5 border-b border-white/5 flex items-center justify-between">
                <h3 class="font-bold text-white">Choose your country</h3>
                <button @click="openSwitcher = false"
                        class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-white/10 text-gray-400 hover:text-white transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Search --}}
            <div class="px-6 pt-4 pb-2">
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text"
                           x-model="countrySearch"
                           placeholder="Search country…"
                           class="w-full bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2.5 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-brand-500 transition-colors">
                </div>
            </div>

            {{-- Country Grid — Hostinger-style --}}
            <div class="max-h-80 overflow-y-auto px-4 pb-4 pt-2">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-1">
                    <template x-for="country in filteredCountries()" :key="country.code">
                        <button @click="switchCountry(country.code)"
                                :class="countryCode === country.code
                                    ? 'bg-brand-600/25 border-brand-500/40 text-white'
                                    : 'border-transparent hover:bg-white/5 text-gray-300 hover:text-white'"
                                class="flex items-center gap-2.5 px-3 py-3 rounded-xl border text-left transition-all w-full group">
                            <span class="text-2xl leading-none flex-shrink-0" x-text="flagEmoji(country.code)"></span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium truncate" x-text="country.name"></div>
                                <div class="text-xs text-gray-500 group-hover:text-gray-400 truncate" x-text="langName(country.lang)"></div>
                            </div>
                        </button>
                    </template>
                    <button @click="switchCountry('GLOBAL')"
                            x-show="!countrySearch || 'other countries'.includes(countrySearch.toLowerCase())"
                            :class="countryCode === 'GLOBAL'
                                ? 'bg-brand-600/25 border-brand-500/40 text-white'
                                : 'border-transparent hover:bg-white/5 text-gray-400 hover:text-white'"
                            class="flex items-center gap-2.5 px-3 py-3 rounded-xl border text-left transition-all w-full">
                        <span class="text-2xl leading-none flex-shrink-0">🌍</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium">Other countries</div>
                            <div class="text-xs text-gray-500">English</div>
                        </div>
                    </button>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3 border-t border-white/5">
                <p class="text-xs text-gray-500 text-center">
                    Regional pricing reflects local purchasing power. Same features everywhere.
                </p>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function pricingPage({ countryCode, countryName, rawPrice, currency, monthlyFormatted, paymentMethods, taxPct, taxName }) {

    // Extended currency symbol map covering all countries in CountryPricingSeeder
    const SYMBOLS = {
        USD:'$',  GBP:'£',  EUR:'€',  AED:'AED ',  SAR:'SAR ',
        KWD:'KD ', QAR:'QR ', BHD:'BD ', OMR:'OMR ', JOD:'JOD ',
        EGP:'EGP ', MAD:'MAD ', TND:'TND ', DZD:'DZD ',
        INR:'₹',  PKR:'₨',  BDT:'৳',  LKR:'LKR ',
        IDR:'IDR ', MYR:'RM ', PHP:'₱',  THB:'฿',  SGD:'S$', HKD:'HK$',
        JPY:'¥',  KRW:'₩',  CNY:'¥',  TWD:'NT$',
        TRY:'₺',  RUB:'₽',  UAH:'₴',  PLN:'zł ', CZK:'Kč ', HUF:'Ft ',
        SEK:'kr ', NOK:'kr ', DKK:'kr ', CHF:'CHF ', RON:'lei ', BGN:'лв ',
        BRL:'R$ ', MXN:'$',  ARS:'$',  COP:'$',  CLP:'$',  PEN:'S/ ',
        NGN:'₦',  GHS:'GH₵', KES:'KSh ', ZAR:'R ',  TZS:'TSh ', UGX:'USh ',
        CAD:'CA$', AUD:'A$', NZD:'NZ$',
    };

    function fmt(amount, curr) {
        const sym = SYMBOLS[curr];
        const n   = Math.round(amount);
        return sym ? (sym + n) : (n + ' ' + curr);
    }

    return {
        countryCode,
        countryName,
        rawPrice,
        currency,
        monthlyFormatted,
        paymentMethods,
        taxPct,
        taxName,
        billing:       'monthly',
        openSwitcher:  false,
        countrySearch: '',
        _allData:      (() => { const d = {!! $allDataJson !!}; if (d['GLOBAL']) d['GLOBAL'].name = 'Other countries'; return d; })(),

        filteredCountries() {
            return Object.entries(this._allData)
                .filter(([code, data]) => {
                    if (code === 'GLOBAL') return false;
                    if (!this.countrySearch) return true;
                    return data.name.toLowerCase().includes(this.countrySearch.toLowerCase());
                })
                .map(([code, data]) => ({ code, name: data.name, lang: data.lang ?? 'en' }));
        },
        flagEmoji(code) {
            if (!code || code === 'GLOBAL') return '🌍';
            try {
                return [...code.toUpperCase()].map(c => String.fromCodePoint(c.charCodeAt(0) + 127397)).join('');
            } catch(e) { return '🏳'; }
        },
        langName(lang) {
            const L = {
                en:'English', ar:'العربية', fr:'Français', de:'Deutsch',
                es:'Español', pt:'Português', tr:'Türkçe', ru:'Русский',
                zh:'中文', ja:'日本語', ko:'한국어', id:'Indonesia',
                hi:'हिन्दी', ms:'Melayu', th:'ภาษาไทย',
            };
            return L[lang] || 'English';
        },

        get annualPerMonthFormatted() { return fmt(this.rawPrice * 10 / 12, this.currency); },
        get annualTotalFormatted()    { return fmt(this.rawPrice * 10,      this.currency); },
        get displayPrice()            { return this.billing === 'annual' ? this.annualPerMonthFormatted : this.monthlyFormatted; },

        // Price + tax for monthly billing
        get taxAmount()              { return this.taxPct > 0 ? Math.round(this.rawPrice * this.taxPct / 100) : 0; },
        get taxTotalFormatted()      { return fmt(this.rawPrice + this.taxAmount, this.currency); },

        switchCountry(code) {
            const d = this._allData[code];
            if (!d) return;
            this.countryCode      = code;
            this.countryName      = d.name;
            this.monthlyFormatted = d.monthly;
            this.rawPrice         = d.price;
            this.currency         = d.currency;
            this.paymentMethods   = d.methods;
            this.taxPct           = d.taxPct  ?? 0;
            this.taxName          = d.taxName ?? 'VAT';
            this.openSwitcher     = false;
            this.countrySearch    = '';
            fetch('/pricing/set-country', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ country_code: code }),
            }).catch(() => { /* silent – non-critical */ });
        },

        renderPaymentMethods() {
            const L = {
                stripe:'Stripe', paypal:'PayPal', mada:'Mada', fawry:'Fawry',
                razorpay:'Razorpay', moyasar:'Moyasar', paymob:'Paymob',
                telr:'Telr', tap:'Tap Payments', iyzico:'Iyzico', pagseguro:'PagSeguro',
            };
            return (this.paymentMethods || []).map(m =>
                `<span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold"
                       style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);color:#cbd5e1">${L[m] || m}</span>`
            ).join('');
        },
    };
}
</script>
@endpush

@endsection
