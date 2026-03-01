@extends('layouts.landing')
@section('content')

<div class="pt-24 pb-16 min-h-screen">
    {{-- Header --}}
    <div class="text-center py-16 relative">
        <div class="absolute inset-0 bg-gradient-radial from-brand-500/15 via-transparent to-transparent pointer-events-none"></div>
        <span class="relative inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">Pricing</span>
        <h1 class="relative text-5xl sm:text-6xl font-extrabold mb-4">
            Simple, Honest <span class="gradient-text">Pricing</span>
        </h1>
        <p class="relative text-xl text-gray-400 mb-3">All plans include a free trial. No credit card required.</p>
        <p class="relative text-brand-400 font-semibold text-lg">🎉 Everything starts with a FREE trial — upgrade when you're ready.</p>

        {{-- Billing toggle --}}
        <div class="relative flex items-center justify-center gap-4 mt-8">
            <span id="labelMonthly" class="text-sm font-medium text-white">Monthly</span>
            <button id="billingToggle" onclick="toggleBilling()"
                    class="relative w-12 h-6 rounded-full bg-brand-500 transition-colors focus:outline-none shadow-lg">
                <div id="toggleDot" class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white transition-transform shadow"></div>
            </button>
            <span id="labelYearly" class="text-sm font-medium text-gray-400">
                Yearly
                <span class="ml-1 bg-green-500/20 text-green-400 text-xs font-bold px-2 py-0.5 rounded-full">Save 20%</span>
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
                    <span class="btn-primary text-white text-xs font-bold px-5 py-2 rounded-full shadow-lg">⭐ Most Popular</span>
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
                        <span class="text-6xl font-black text-white">${{ number_format($plan->price, 0) }}</span>
                        <span class="text-gray-400">/month</span>
                    </div>
                    <div id="priceYearly{{ $plan->id }}" class="flex items-baseline gap-1 hidden">
                        <span class="text-6xl font-black text-white">${{ $yearlyPrice }}</span>
                        <span class="text-gray-400">/year</span>
                    </div>
                    <p class="text-green-400 text-xs mt-1 hidden" id="yearlyNote{{ $plan->id }}">
                        That's ${{ number_format($plan->price * 0.8, 0) }}/mo — save ${{ number_format($plan->price * 12 * 0.2, 0) }}/year
                    </p>
                </div>

                {{-- CTA --}}
                <a href="{{ route('signup') }}?plan={{ $plan->id }}"
                   class="{{ $plan->is_popular ? 'btn-primary' : 'glass border border-brand-500/40 hover:border-brand-500' }} text-white font-bold text-sm px-6 py-3.5 rounded-xl block text-center mb-8 transition-all">
                    @if($plan->trial_days > 0)
                        Start {{ $plan->trial_days }}-Day Free Trial
                    @else
                        Get Started
                    @endif
                </a>

                {{-- Divider --}}
                <div class="border-t border-white/10 pt-6 mb-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-4">What's included</p>
                </div>

                {{-- Features List --}}
                <ul class="space-y-3">
                    <li class="flex items-start gap-2 text-sm text-gray-300">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        <span>{{ $plan->max_users == -1 ? 'Unlimited staff members' : 'Up to ' . $plan->max_users . ' staff members' }}</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm text-gray-300">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        <span>{{ $plan->max_appointments == -1 ? 'Unlimited appointments' : number_format($plan->max_appointments) . ' appointments/month' }}</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm text-gray-300">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        <span>{{ $plan->storage_limit == -1 ? 'Unlimited storage' : $plan->storage_limit . ' GB storage' }}</span>
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
        <div class="mt-24 max-w-5xl mx-auto">
            <h2 class="text-3xl font-extrabold text-center mb-10">Full Feature Comparison</h2>
            <div class="glass rounded-2xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="text-left px-6 py-4 text-gray-400 font-semibold">Feature</th>
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
                            <td class="px-6 py-3.5 text-gray-300">Staff Members</td>
                            @foreach($displayPlans as $plan)
                            <td class="px-6 py-3.5 text-center font-semibold text-white">{{ $limitRow($plan->max_users) }}</td>
                            @endforeach
                        </tr>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">Appointments / Month</td>
                            @foreach($displayPlans as $plan)
                            <td class="px-6 py-3.5 text-center font-semibold text-white">{{ $limitRow($plan->max_appointments) }}</td>
                            @endforeach
                        </tr>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">Storage</td>
                            @foreach($displayPlans as $plan)
                            <td class="px-6 py-3.5 text-center font-semibold text-white">{{ is_null($plan->storage_limit) || $plan->storage_limit == -1 ? '∞' : $plan->storage_limit . ' MB' }}</td>
                            @endforeach
                        </tr>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">Free Trial</td>
                            @foreach($displayPlans as $plan)
                            <td class="px-6 py-3.5 text-center text-brand-400 font-semibold">
                                {{ $plan->trial_days > 0 ? $plan->trial_days . ' days' : '—' }}
                            </td>
                            @endforeach
                        </tr>
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">Billing Cycle</td>
                            @foreach($displayPlans as $plan)
                            <td class="px-6 py-3.5 text-center text-gray-300 capitalize">{{ $plan->billing_cycle }}</td>
                            @endforeach
                        </tr>
                        {{-- Dynamic feature rows from DB --}}
                        @php
                            $allFeatures = $displayPlans
                                ->flatMap(fn($p) => is_array($p->features) ? $p->features : [])
                                ->unique()->values();
                        @endphp
                        @foreach($allFeatures as $feature)
                        <tr class="hover:bg-white/[0.02]">
                            <td class="px-6 py-3.5 text-gray-300">{{ $feature }}</td>
                            @foreach($displayPlans as $plan)
                            @php $planFeatures = is_array($plan->features) ? $plan->features : []; @endphp
                            <td class="px-6 py-3.5 text-center">
                                @if(in_array($feature, $planFeatures))
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
                        @if($allFeatures->isEmpty())
                        <tr>
                            <td colspan="{{ $displayPlans->count() + 1 }}" class="px-6 py-6 text-center text-gray-500 text-sm">
                                Add features to your plans from the admin panel to see a full comparison.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- FAQ --}}
        <div class="mt-20 max-w-2xl mx-auto text-center">
            <h2 class="text-3xl font-extrabold mb-3">Still have questions?</h2>
            <p class="text-gray-400 mb-6">We're happy to help. Reach out to our support team anytime.</p>
            <a href="mailto:support@velora.com"
               class="btn-primary text-white font-semibold px-8 py-3 rounded-xl inline-block">
                Contact Support
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
