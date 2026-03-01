@extends('layouts.landing')
@section('content')

<div class="pt-20 sm:pt-24 pb-10 sm:pb-16 min-h-screen">
    {{-- Header --}}
    <div class="text-center py-10 sm:py-16 relative px-4 sm:px-0">
        <div class="absolute inset-0 bg-gradient-radial from-brand-500/15 via-transparent to-transparent pointer-events-none"></div>
        <span class="relative inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">{{ __('landing.pricing_badge') }}</span>
        <h1 class="relative text-3xl sm:text-5xl md:text-6xl font-extrabold mb-4">
            {{ __('landing.pricing_page_title') }} <span class="gradient-text">{{ __('landing.pricing_page_title_hl') }}</span>
        </h1>
        <p class="relative text-base sm:text-xl text-gray-400 mb-3">{{ __('landing.pricing_page_sub') }}</p>
        <p class="relative text-brand-400 font-semibold text-sm sm:text-base md:text-lg">{{ __('landing.pricing_page_free_note') }}</p>

        {{-- Billing toggle --}}
        <div class="relative flex items-center justify-center gap-4 mt-8">
            <span id="labelMonthly" class="text-sm font-medium text-white">{{ __('landing.billing_monthly') }}</span>
            <button id="billingToggle" onclick="toggleBilling()"
                    class="relative w-12 h-6 rounded-full bg-brand-500 transition-colors focus:outline-none shadow-lg">
                <div id="toggleDot" class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white transition-transform shadow"></div>
            </button>
            <span id="labelYearly" class="text-sm font-medium text-gray-400">
                {{ __('landing.billing_yearly') }}
                <span class="ml-1 bg-green-500/20 text-green-400 text-xs font-bold px-2 py-0.5 rounded-full">{{ __('landing.billing_save') }}</span>
            </span>
        </div>
    </div>

    {{-- Plans --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($plans->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-{{ min($plans->count(), 3) }} gap-6 max-w-5xl mx-auto">
            @foreach($plans as $plan)
            @php
                $features = is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []);
                $yearlyPrice = number_format($plan->price * 12 * 0.8, 0);
            @endphp
            <div class="glass rounded-3xl p-8 card-hover {{ $plan->is_popular ? 'border-2 border-brand-500 relative' : 'border border-white/10' }}">
                @if($plan->is_popular)
                <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                    <span class="btn-primary text-white text-xs font-bold px-5 py-2 rounded-full shadow-lg">{{ __('landing.most_popular') }}</span>
                </div>
                @endif

                {{-- Plan Header --}}
                <div class="mb-6">
                    <h2 class="text-2xl font-extrabold text-white mb-1">{{ $plan->name }}</h2>
                    <p class="text-gray-400 text-sm">{{ $plan->description }}</p>
                </div>

                {{-- Price --}}
                <div class="mb-6">
                    <div id="priceMonthly{{ $plan->id }}" class="flex items-baseline gap-1">
                        <span class="text-4xl sm:text-6xl font-black text-white">${{ number_format($plan->price, 0) }}</span>
                        <span class="text-gray-400">/{{ __('landing.per_month') }}</span>
                    </div>
                    <div id="priceYearly{{ $plan->id }}" class="flex items-baseline gap-1 hidden">
                        <span class="text-4xl sm:text-6xl font-black text-white">${{ $yearlyPrice }}</span>
                        <span class="text-gray-400">/{{ __('landing.per_year') }}</span>
                    </div>
                    <p class="text-green-400 text-xs mt-1 hidden" id="yearlyNote{{ $plan->id }}">
                        That's ${{ number_format($plan->price * 0.8, 0) }}/mo — save ${{ number_format($plan->price * 12 * 0.2, 0) }}/year
                    </p>
                </div>

                {{-- CTA --}}
                <a href="{{ route('signup') }}?plan={{ $plan->id }}"
                   class="{{ $plan->is_popular ? 'btn-primary' : 'glass border border-brand-500/40 hover:border-brand-500' }} text-white font-bold text-sm px-6 py-3.5 rounded-xl block text-center mb-8 transition-all">
                    @if($plan->trial_days > 0)
                        {{ __('landing.start_trial', ['days' => $plan->trial_days]) }}
                    @else
                        {{ __('landing.get_started') }}
                    @endif
                </a>

                {{-- Divider --}}
                <div class="border-t border-white/10 pt-6 mb-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-4">{{ __('landing.whats_included') }}</p>
                </div>

                {{-- Features List --}}
                <ul class="space-y-3">
                    <li class="flex items-start gap-2 text-sm text-gray-300">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        <span>{{ $plan->max_users == -1 ? __('landing.unlimited_staff') : __('landing.up_to_staff', ['n' => $plan->max_users]) }}</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm text-gray-300">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        <span>{{ $plan->max_appointments == -1 ? __('landing.unlimited_appointments') : __('landing.appointments_per_month', ['n' => number_format($plan->max_appointments)]) }}</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm text-gray-300">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        <span>{{ ($plan->storage_limit == -1 || is_null($plan->storage_limit)) ? __('landing.unlimited_storage') : __('landing.gb_storage', ['n' => round($plan->storage_limit / 1024)]) }}</span>
                    </li>
                    @if(is_array($features))
                        @foreach($features as $feature)
                        <li class="flex items-start gap-2 text-sm text-gray-300">
                            <svg class="w-4 h-4 text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                            </svg>
                            <span>{{ $feature }}</span>
                        </li>
                        @endforeach
                    @endif
                </ul>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Feature Comparison Table --}}
        <div class="mt-16 sm:mt-24 max-w-5xl mx-auto">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-center mb-6 sm:mb-10">{{ __('landing.feature_comparison') }}</h2>
            <div class="overflow-x-auto rounded-2xl">
            <div class="glass rounded-2xl overflow-hidden min-w-[520px]">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left px-6 py-4 text-gray-400 font-semibold">{{ __('landing.feature') }}</th>
                            @foreach($plans->take(3) as $plan)
                            <th class="px-6 py-4 text-center {{ $plan->is_popular ? 'text-brand-400 font-bold' : 'text-gray-300 font-semibold' }}">
                                {{ $plan->name }}
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @php
                            $displayPlans = $plans->take(3);
                            // Limit rows
                            $limitRow = function($val, $unit = '') {
                                if (is_null($val) || $val == -1 || $val == 0) return '∞';
                                return number_format($val) . ($unit ? ' ' . $unit : '');
                            };
                        @endphp
                        {{-- Hardcoded limit rows from DB fields --}}
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">{{ __('landing.comp_staff') }}</td>
                            @foreach($displayPlans as $plan)
                            <td class="px-6 py-3.5 text-center font-semibold text-white">{{ $limitRow($plan->max_users) }}</td>
                            @endforeach
                        </tr>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">{{ __('landing.comp_appointments') }}</td>
                            @foreach($displayPlans as $plan)
                            <td class="px-6 py-3.5 text-center font-semibold text-white">{{ $limitRow($plan->max_appointments) }}</td>
                            @endforeach
                        </tr>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">{{ __('landing.comp_storage') }}</td>
                            @foreach($displayPlans as $plan)
                            <td class="px-6 py-3.5 text-center font-semibold text-white">{{ is_null($plan->storage_limit) || $plan->storage_limit == -1 ? '∞' : round($plan->storage_limit / 1024) . ' GB' }}</td>
                            @endforeach
                        </tr>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">{{ __('landing.comp_trial') }}</td>
                            @foreach($displayPlans as $plan)
                            <td class="px-6 py-3.5 text-center text-brand-400 font-semibold">
                                {{ $plan->trial_days > 0 ? $plan->trial_days . ' ' . __('landing.days') : '—' }}
                            </td>
                            @endforeach
                        </tr>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">{{ __('landing.comp_billing') }}</td>
                            @foreach($displayPlans as $plan)
                            <td class="px-6 py-3.5 text-center text-gray-300 capitalize">{{ $plan->billing_cycle }}</td>
                            @endforeach
                        </tr>
                        {{-- Feature rows: tier-based inheritance (tier 1=Basic+, 2=Professional+, 3=Enterprise only) --}}
                        @php
                            $displayPlans = $plans->take(3)->values();
                            $comparisonFeatures = [
                                [__('landing.feat_appointment_mgmt'),  1],
                                [__('landing.feat_queue_basic'),        1],
                                [__('landing.feat_email_notif'),        1],
                                [__('landing.feat_queue_advanced'),     2],
                                [__('landing.feat_sms_notif'),          2],
                                [__('landing.feat_custom_branding'),    2],
                                [__('landing.feat_priority_support'),   2],
                                [__('landing.feat_reports'),            2],
                                [__('landing.feat_api_access'),         3],
                                [__('landing.feat_integrations'),       3],
                                [__('landing.feat_dedicated_support'),  3],
                                [__('landing.feat_sla'),                3],
                                [__('landing.feat_white_label'),        3],
                            ];
                        @endphp
                        @foreach($comparisonFeatures as [$featureName, $minTier])
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">{{ $featureName }}</td>
                            @foreach($displayPlans as $i => $plan)
                            <td class="px-6 py-3.5 text-center">
                                @if(($i + 1) >= $minTier)
                                <svg class="w-5 h-5 text-green-400 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                </svg>
                                @else
                                <span class="text-gray-600">—</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        {{-- FAQ --}}
        <div class="mt-14 sm:mt-20 max-w-2xl mx-auto text-center px-4 sm:px-0">
            <h2 class="text-2xl sm:text-3xl font-extrabold mb-3">{{ __('landing.pricing_faq_title') }}</h2>
            <p class="text-gray-400 mb-6 text-sm sm:text-base">{{ __('landing.pricing_faq_sub') }}</p>
            <a href="mailto:support@velora.com"
               class="btn-primary text-white font-semibold px-8 py-3 rounded-xl inline-block">
                {{ __('landing.contact_support') }}
            </a>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let isYearly = false;
function toggleBilling() {
    isYearly = !isYearly;
    const dot = document.getElementById('toggleDot');
    dot.style.transform = isYearly ? 'translateX(24px)' : 'translateX(0)';
    document.getElementById('labelMonthly').className = `text-sm font-medium ${!isYearly ? 'text-white' : 'text-gray-400'}`;
    document.getElementById('labelYearly').className  = `text-sm font-medium ${isYearly ? 'text-white' : 'text-gray-400'}`;

    document.querySelectorAll('[id^="priceMonthly"]').forEach(el => el.classList.toggle('hidden', isYearly));
    document.querySelectorAll('[id^="priceYearly"]').forEach(el => el.classList.toggle('hidden', !isYearly));
    document.querySelectorAll('[id^="yearlyNote"]').forEach(el => el.classList.toggle('hidden', !isYearly));
}
</script>
@endpush
