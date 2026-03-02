@extends('layouts.landing')

@push('styles')
<style>
    .hero-bg {
        background:
            radial-gradient(ellipse 90% 60% at 50% -5%, rgba(108,99,255,0.28) 0%, transparent 65%),
            radial-gradient(ellipse 50% 40% at 80% 50%, rgba(56,189,248,0.1) 0%, transparent 60%),
            #0f0e1a;
    }
    .feature-icon {
        background: linear-gradient(135deg, rgba(108,99,255,0.2) 0%, rgba(139,118,255,0.1) 100%);
        border: 1px solid rgba(108,99,255,0.3);
    }
    .pricing-popular {
        background: linear-gradient(135deg, rgba(108,99,255,0.15) 0%, rgba(56,189,248,0.08) 100%);
        border: 2px solid #6C63FF !important;
    }
    .stat-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(108,99,255,0.05) 100%);
        border: 1px solid rgba(255,255,255,0.08);
    }
    .step-connector::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 100%;
        width: 100%;
        height: 1px;
        background: linear-gradient(90deg, rgba(108,99,255,0.5), transparent);
        transform: translateY(-50%);
    }
    .testimonial-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, rgba(108,99,255,0.04) 100%);
    }
    .animate-delay-1 { animation-delay: 0.1s; }
    .animate-delay-2 { animation-delay: 0.2s; }
    .animate-delay-3 { animation-delay: 0.3s; }
    .animate-delay-4 { animation-delay: 0.4s; }
    .animate-delay-5 { animation-delay: 0.5s; }

    .ticker-wrap {
        overflow: hidden;
        white-space: nowrap;
    }
    .ticker {
        display: inline-block;
        animation: ticker 20s linear infinite;
    }
    @keyframes ticker {
        0%   { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════════════════════════ --}}
<section class="hero-bg min-h-screen flex flex-col items-center justify-center pt-20 pb-12 sm:pb-16 relative overflow-hidden">

    {{-- Background blobs --}}
    <div class="absolute top-1/4 left-10 w-72 h-72 bg-brand-500/10 rounded-full blur-3xl animate-pulse-slow pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-10 w-96 h-96 bg-sky-500/8 rounded-full blur-3xl animate-pulse-slow pointer-events-none" style="animation-delay:2s"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">

        {{-- Badge --}}
        <div class="inline-flex items-center gap-2 glass rounded-full px-3 sm:px-4 py-1.5 text-xs sm:text-sm text-gray-300 mb-6 sm:mb-8 animate-fade-up">
            <span class="w-2 h-2 flex-shrink-0 rounded-full bg-green-400 animate-pulse"></span>
            <span class="text-center leading-tight">{{ __('landing.hero_badge', ['days' => $maxTrialDays ?? 14]) }}</span>
        </div>

        {{-- Headline --}}
        <h1 class="text-4xl sm:text-5xl md:text-7xl lg:text-8xl font-extrabold tracking-tight mb-5 sm:mb-6 animate-fade-up animate-delay-1 leading-[1.08] sm:leading-[1.05]">
            {{ __('landing.hero_headline_1') }}<br />
            {{ __('landing.hero_headline_2') }} <span class="gradient-text">{{ __('landing.hero_headline_hl') }}</span>
        </h1>

        {{-- Subheadline --}}
        <p class="text-base sm:text-xl md:text-2xl text-gray-400 mb-8 sm:mb-10 max-w-3xl mx-auto leading-relaxed animate-fade-up animate-delay-2 px-2 sm:px-0">
            {{ __('landing.hero_sub') }}
        </p>

        {{-- CTA Buttons --}}
        <div class="flex flex-col xs:flex-row sm:flex-row gap-3 sm:gap-4 justify-center mb-10 sm:mb-16 animate-fade-up animate-delay-3 px-4 sm:px-0">
            <a href="{{ route('signup') }}"
               class="btn-primary text-white font-bold text-base sm:text-lg px-6 sm:px-8 py-3.5 sm:py-4 rounded-2xl inline-flex items-center justify-center gap-2 sm:gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                {{ __('landing.hero_cta_start', ['days' => $maxTrialDays ?? 14]) }}
            </a>
            <a href="#how-it-works"
               class="glass text-gray-300 hover:text-white font-semibold text-base sm:text-lg px-6 sm:px-8 py-3.5 sm:py-4 rounded-2xl inline-flex items-center justify-center gap-2 transition-all hover:border-brand-500/50">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ __('landing.hero_cta_how') }}
            </a>
        </div>

        {{-- Trust signals --}}
        <div class="flex flex-wrap justify-center gap-3 sm:gap-6 text-xs sm:text-sm text-gray-500 animate-fade-up animate-delay-4 mb-10 sm:mb-16 px-4 sm:px-0">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                {{ __('landing.trust_no_card') }}
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                {{ __('landing.trust_setup') }}
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                {{ __('landing.trust_cancel') }}
            </div>
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                {{ __('landing.trust_languages') }}
            </div>
        </div>

        {{-- Dashboard Preview --}}
        <div class="animate-fade-up animate-delay-5 relative max-w-5xl mx-auto px-2 sm:px-0">
            <div class="absolute inset-0 bg-brand-500/20 blur-3xl rounded-3xl pointer-events-none"></div>
            <div class="relative glass rounded-2xl overflow-hidden border border-white/10 animate-float shadow-2xl">
                {{-- Fake Browser Chrome --}}
                <div class="bg-white/5 px-3 sm:px-4 py-2.5 sm:py-3 flex items-center gap-2 border-b border-white/5">
                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-red-400/70"></div>
                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-yellow-400/70"></div>
                    <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-green-400/70"></div>
                    <div class="flex-1 mx-2 sm:mx-4 bg-white/5 rounded-md px-2 sm:px-3 py-1 text-xs text-gray-500 text-center font-mono truncate">
                        mysalon.velora.com/admin/dashboard
                    </div>
                </div>
                {{-- Dashboard Preview Grid --}}
                <div class="p-4 sm:p-6 bg-gray-900/60">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 mb-3 sm:mb-4">
                        @foreach([['📅','Appointments','24', '+12%'],['👥','Queue','8','Live'],['⭐','Rating','4.9','★★★★★'],['💰','Revenue','$1,240','+18%']] as [$icon, $label, $val, $sub])
                        <div class="glass rounded-xl p-2.5 sm:p-3 text-left">
                            <div class="text-base sm:text-lg mb-1">{{ $icon }}</div>
                            <div class="text-gray-400 text-xs mb-1 truncate">{{ $label }}</div>
                            <div class="text-white font-bold text-sm sm:text-base">{{ $val }}</div>
                            <div class="text-brand-400 text-xs">{{ $sub }}</div>
                        </div>
                        @endforeach
                    </div>
                    <div class="hidden sm:grid grid-cols-3 gap-3">
                        <div class="col-span-2 glass rounded-xl p-3">
                            <div class="text-xs text-gray-400 mb-2">Weekly Appointments</div>
                            <div class="flex items-end gap-1 h-16">
                                @foreach([30,50,40,70,60,85,45] as $h)
                                <div class="flex-1 rounded-sm bg-brand-500/60" style="height:{{ $h }}%"></div>
                                @endforeach
                            </div>
                        </div>
                        <div class="glass rounded-xl p-3">
                            <div class="text-xs text-gray-400 mb-2">Queue Status</div>
                            @foreach([['A-01','💇 Sarah K.','Serving'],['A-02','💅 Mike R.','Waiting'],['A-03','✂️ John D.','Waiting']] as [$n,$nm,$st])
                            <div class="flex items-center justify-between py-1 border-b border-white/5 last:border-0">
                                <span class="text-xs font-mono text-brand-400">{{ $n }}</span>
                                <span class="text-xs text-gray-300 truncate mx-1">{{ $nm }}</span>
                                <span class="text-xs {{ $st === 'Serving' ? 'text-green-400' : 'text-gray-500' }}">{{ $st }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     STATS TICKER
══════════════════════════════════════════════════════════════════════════ --}}
<div class="border-y border-white/5 py-5 bg-white/[0.02]">
    <div class="ticker-wrap">
        <div class="ticker">
            @php
                $items = [
                    __('landing.ticker_businesses'), __('landing.ticker_globally'),
                    __('landing.ticker_languages'),  __('landing.ticker_trial'),
                    __('landing.ticker_queue'),       __('landing.ticker_scheduling'),
                    __('landing.ticker_custom'),      __('landing.ticker_setup'),
                    __('landing.ticker_uptime'),      __('landing.ticker_security'),
                    __('landing.ticker_businesses'), __('landing.ticker_globally'),
                    __('landing.ticker_languages'),  __('landing.ticker_trial'),
                    __('landing.ticker_queue'),       __('landing.ticker_scheduling'),
                ];
            @endphp
            @foreach($items as $item)
                <span class="inline-flex items-center gap-3 mx-8 text-gray-400 text-sm font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
                    {{ $item }}
                </span>
            @endforeach
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     LOGOS / USED BY
══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <p class="text-gray-500 text-sm font-medium mb-8 uppercase tracking-widest">{{ __('landing.trusted_countries') }}</p>
    <div class="flex flex-wrap justify-center items-center gap-8 opacity-40 grayscale">
        @foreach(['Salon Pro','MediBook','BarberHub','SpaSync','ClinicFlow','NailArt Studio'] as $logo)
        <div class="glass px-6 py-3 rounded-xl text-white font-bold text-sm tracking-tight">{{ $logo }}</div>
        @endforeach
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     FEATURES
══════════════════════════════════════════════════════════════════════════ --}}
<section id="features" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-16">
        <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">{{ __('landing.features_badge') }}</span>
        <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
            {{ __('landing.features_title') }} <span class="gradient-text">{{ __('landing.features_title_hl') }}</span>
        </h2>
        <p class="text-xl text-gray-400 max-w-2xl mx-auto">
            {{ __('landing.features_sub') }}
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @php
        $features = [
            ['📅', __('landing.f1_title'), __('landing.f1_desc'), __('landing.f1_tag')],
            ['🎯', __('landing.f2_title'), __('landing.f2_desc'), __('landing.f2_tag')],
            ['👥', __('landing.f3_title'), __('landing.f3_desc'), __('landing.f3_tag')],
            ['📊', __('landing.f4_title'), __('landing.f4_desc'), __('landing.f4_tag')],
            ['🌍', __('landing.f5_title'), __('landing.f5_desc'), __('landing.f5_tag')],
            ['🔔', __('landing.f6_title'), __('landing.f6_desc'), __('landing.f6_tag')],
            ['⭐', __('landing.f7_title'), __('landing.f7_desc'), __('landing.f7_tag')],
            ['🛡️', __('landing.f8_title'), __('landing.f8_desc'), __('landing.f8_tag')],
            ['⚡', __('landing.f9_title'), __('landing.f9_desc'), __('landing.f9_tag')],
        ];
        @endphp

        @foreach($features as [$icon, $title, $desc, $tag])
        <div class="glass rounded-2xl p-6 card-hover group">
            <div class="feature-icon w-12 h-12 rounded-xl flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition-transform">
                {{ $icon }}
            </div>
            <h3 class="text-lg font-bold text-white mb-2">{{ $title }}</h3>
            <p class="text-gray-400 text-sm leading-relaxed mb-4">{{ $desc }}</p>
            <span class="inline-block text-xs font-semibold text-brand-400 bg-brand-500/10 px-3 py-1 rounded-full">
                {{ $tag }}
            </span>
        </div>
        @endforeach
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     HOW IT WORKS
══════════════════════════════════════════════════════════════════════════ --}}
<section id="how-it-works" class="py-24 bg-white/[0.02] border-y border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">{{ __('landing.how_badge') }}</span>
            <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
                {{ __('landing.how_title') }} <span class="gradient-text">{{ __('landing.how_title_hl') }}</span>
            </h2>
            <p class="text-xl text-gray-400">{{ __('landing.how_sub') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
            @php
            $steps = [
                ['01', '📝', __('landing.s1_title'), __('landing.s1_desc')],
                ['02', '⚙️', __('landing.s2_title'), __('landing.s2_desc')],
                ['03', '🚀', __('landing.s3_title'), __('landing.s3_desc')],
            ];
            @endphp

            @foreach($steps as $i => [$num, $icon, $title, $desc])
            <div class="relative group">
                {{-- Connector arrow (RTL-aware) --}}
                @if(!$loop->last)
                <div class="hidden md:block absolute top-12 ltr:left-full rtl:right-full w-full z-10 pointer-events-none">
                    <div class="h-px ltr:bg-gradient-to-r rtl:bg-gradient-to-l from-brand-500/50 to-transparent w-full"></div>
                    <div class="absolute ltr:right-0 rtl:left-0 top-1/2 -translate-y-1/2 border-t-4 ltr:border-r-4 rtl:border-l-4 border-brand-500/50 w-3 h-3 ltr:rotate-45 rtl:-rotate-45"></div>
                </div>
                @endif

                <div class="glass rounded-2xl p-8 card-hover h-full">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 rounded-2xl btn-primary flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                            {{ $icon }}
                        </div>
                        <span class="text-6xl font-black text-brand-500/20 leading-none">{{ $num }}</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">{{ $title }}</h3>
                    <p class="text-gray-400 leading-relaxed">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-12">
            <a href="{{ route('signup') }}"
               class="btn-primary text-white font-bold text-lg px-10 py-4 rounded-2xl inline-flex items-center gap-3">
                {{ __('landing.how_cta') }}
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     PRICING PREVIEW
══════════════════════════════════════════════════════════════════════════ --}}
<section id="pricing" class="py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-16">
        <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">{{ __('landing.pricing_badge') }}</span>
        <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
            {{ __('landing.pricing_title') }} <span class="gradient-text">{{ __('landing.pricing_title_hl') }}</span> {{ __('landing.pricing_title_sfx') }}
        </h2>
        <p class="text-xl text-gray-400 mb-2">{{ __('landing.pricing_sub') }}</p>
        <p class="text-brand-400 font-semibold">{{ __('landing.pricing_trial_note', ['days' => $maxTrialDays]) }}</p>
    </div>

    @if($plans->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-{{ min($plans->count(), 3) }} gap-6 max-w-5xl mx-auto">
        @foreach($plans->take(3) as $plan)
        @php
            $features = is_string($plan->features) ? json_decode($plan->features, true) : ($plan->features ?? []);
        @endphp
        <div class="glass rounded-2xl p-8 card-hover {{ $plan->is_popular ? 'pricing-popular relative' : '' }}">
            @if($plan->is_popular)
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                <span class="btn-primary text-white text-xs font-bold px-4 py-1.5 rounded-full">{{ __('landing.pricing_most_pop') }}</span>
            </div>
            @endif

            <h3 class="text-xl font-bold text-white mb-1">{{ $plan->name }}</h3>
            <p class="text-gray-400 text-sm mb-6">{{ $plan->description }}</p>

            <div class="mb-6">
                <div class="flex items-baseline gap-1">
                    <span class="text-5xl font-black text-white">${{ number_format($plan->price, 0) }}</span>
                    <span class="text-gray-400 text-sm">/{{ $plan->billing_cycle === 'yearly' ? __('landing.pricing_per_year') : __('landing.pricing_per_month') }}</span>
                </div>
                @if($plan->billing_cycle === 'yearly')
                <p class="text-green-400 text-xs mt-1">{{ __('landing.pricing_save_yearly') }}</p>
                @endif
            </div>

            <a href="{{ route('signup') }}?plan={{ $plan->id }}"
               class="{{ $plan->is_popular ? 'btn-primary' : 'glass border border-brand-500/40 hover:border-brand-500' }} text-white font-semibold text-sm px-6 py-3 rounded-xl block text-center mb-6 transition-all">
                @if($plan->trial_days > 0)
                    {{ __('landing.pricing_start_trial', ['days' => $plan->trial_days]) }}
                @else
                    {{ __('landing.pricing_get_started') }}
                @endif
            </a>

            <ul class="space-y-2.5">
                <li class="flex items-center gap-2 text-sm text-gray-300">
                    <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                    </svg>
                    {{ $plan->max_users == -1 ? __('landing.pricing_unlimited') : $plan->max_users }} {{ __('landing.pricing_staff') }}
                </li>
                <li class="flex items-center gap-2 text-sm text-gray-300">
                    <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                    </svg>
                    {{ $plan->max_appointments == -1 ? __('landing.pricing_unlimited') : number_format($plan->max_appointments) }} {{ __('landing.pricing_appt_mo') }}
                </li>
                @if(is_array($features))
                    @foreach(array_slice($features, 0, 5) as $feature)
                    <li class="flex items-center gap-2 text-sm text-gray-300">
                        <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                @endif
            </ul>
        </div>
        @endforeach
    </div>
    @else
    {{-- Placeholder pricing if no plans in DB --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        @foreach([
            ['Starter','9','5 staff','500 appt/mo', false],
            ['Professional','29','20 staff','Unlimited appt', true],
            ['Enterprise','79','Unlimited staff','Unlimited everything', false],
        ] as [$name, $price, $users, $appts, $popular])
        <div class="glass rounded-2xl p-8 card-hover {{ $popular ? 'pricing-popular relative' : '' }}">
            @if($popular)
            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                <span class="btn-primary text-white text-xs font-bold px-4 py-1.5 rounded-full">{{ __('landing.pricing_most_pop') }}</span>
            </div>
            @endif
            <h3 class="text-xl font-bold mb-1">{{ $name }}</h3>
            <div class="flex items-baseline gap-1 my-4">
                <span class="text-5xl font-black">${{ $price }}</span>
                <span class="text-gray-400 text-sm">{{ __('landing.pricing_per_month') }}</span>
            </div>
            <a href="{{ route('signup') }}"
               class="{{ $popular ? 'btn-primary' : 'glass border border-brand-500/40 hover:border-brand-500' }} text-white font-semibold text-sm px-6 py-3 rounded-xl block text-center mb-6 transition-all">
                {{ __('landing.pricing_get_started') }}
            </a>
            <ul class="space-y-2.5 text-sm text-gray-300">
                <li class="flex gap-2 items-center">✅ {{ $users }}</li>
                <li class="flex gap-2 items-center">✅ {{ $appts }}</li>
                <li class="flex gap-2 items-center">✅ Queue Management</li>
                <li class="flex gap-2 items-center">✅ Analytics Dashboard</li>
                <li class="flex gap-2 items-center">✅ Email Reminders</li>
            </ul>
        </div>
        @endforeach
    </div>
    @endif

    <div class="text-center mt-10">
        <a href="{{ route('pricing') }}" class="text-brand-400 hover:text-brand-300 text-sm font-semibold inline-flex items-center gap-1 transition-colors">
            {{ __('landing.pricing_view_full') }}
        </a>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     TESTIMONIALS
══════════════════════════════════════════════════════════════════════════ --}}
<section id="testimonials" class="py-24 bg-white/[0.02] border-y border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">{{ __('landing.testimonials_badge') }}</span>
            <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
                {{ __('landing.testimonials_title') }} <span class="gradient-text">{{ __('landing.testimonials_hl') }}</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php
            $testimonials = [
                ['⭐⭐⭐⭐⭐', '"Velora completely transformed how we manage our salon. Our no-show rate dropped by 60% and customers love the queue tracking."', 'Sarah Al-Rashidi', 'Salon Owner, Dubai 🇦🇪'],
                ['⭐⭐⭐⭐⭐', '"Setup took literally 4 minutes. We had appointments flowing in the same day. The multi-language support is perfect for our diverse clientele."', 'Marco Fernandez', 'Barbershop Owner, Madrid 🇪🇸'],
                ['⭐⭐⭐⭐⭐', '"The analytics alone are worth it. We can now see our busiest hours, best-performing staff, and customer satisfaction trends."', 'Wei Zhang', 'Clinic Manager, Shanghai 🇨🇳'],
                ['⭐⭐⭐⭐⭐', '"Finally a booking system that works for Arabic speakers! The RTL support is flawless. Our customers appreciate it deeply."', 'Ahmed Hassan', 'Spa Director, Cairo 🇪🇬'],
                ['⭐⭐⭐⭐⭐', '"Migrated from a $500/month enterprise tool to Velora. Same features for a fraction of the price. Absolutely no regrets."', 'Priya Sharma', 'MedSpa Owner, London 🇬🇧'],
                ['⭐⭐⭐⭐⭐', '"The QR code queue system is genius. Customers scan on arrival and wait comfortably without crowding our reception."', 'Lucas Weber', 'Clinic Owner, Berlin 🇩🇪'],
            ];
            @endphp

            @foreach($testimonials as [$stars, $quote, $name, $role])
            <div class="testimonial-card glass rounded-2xl p-6 card-hover">
                <div class="text-sm mb-3">{{ $stars }}</div>
                <p class="text-gray-300 text-sm leading-relaxed mb-4 italic">{{ $quote }}</p>
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full btn-primary flex items-center justify-center text-sm font-bold text-white">
                        {{ strtoupper(substr($name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-white">{{ $name }}</div>
                        <div class="text-xs text-gray-500">{{ $role }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     FAQ
══════════════════════════════════════════════════════════════════════════ --}}
<section id="faq" class="py-24 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-16">
        <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">{{ __('landing.faq_badge') }}</span>
        <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
            {{ __('landing.faq_title') }} <span class="gradient-text">{{ __('landing.faq_title_hl') }}</span>
        </h2>
    </div>

    <div class="space-y-4" id="faqList">
        @php
        $faqs = [
            [__('landing.faq_1_q'), __('landing.faq_1_a')],
            [__('landing.faq_2_q'), __('landing.faq_2_a')],
            [__('landing.faq_3_q'), __('landing.faq_3_a')],
            [__('landing.faq_4_q'), __('landing.faq_4_a')],
            [__('landing.faq_5_q'), __('landing.faq_5_a')],
            [__('landing.faq_6_q'), __('landing.faq_6_a')],
            [__('landing.faq_7_q'), __('landing.faq_7_a')],
            [__('landing.faq_8_q'), __('landing.faq_8_a')],
        ];
        @endphp

        @foreach($faqs as $i => [$q, $a])
        <div class="glass rounded-xl overflow-hidden">
            <button
                onclick="toggleFaq({{ $i }})"
                class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-white/5 transition-colors"
            >
                <span class="font-semibold text-white text-sm pr-4">{{ $q }}</span>
                <svg id="faqIcon{{ $i }}" class="w-5 h-5 text-brand-400 flex-shrink-0 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="faqAnswer{{ $i }}" class="hidden px-6 pb-4">
                <p class="text-gray-400 text-sm leading-relaxed">{{ $a }}</p>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     CTA SECTION
══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-24 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-radial from-brand-500/20 via-transparent to-transparent pointer-events-none"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="glass rounded-3xl p-12 border border-brand-500/30">
            <div class="text-6xl mb-6">🚀</div>
            <h2 class="text-4xl sm:text-5xl font-extrabold mb-4">
                {{ __('landing.cta_title') }} <span class="gradient-text">{{ __('landing.cta_title_hl') }}</span> {{ __('landing.cta_title_sfx') }}
            </h2>
            <p class="text-xl text-gray-400 mb-8 max-w-2xl mx-auto">
                {{ __('landing.cta_sub') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('signup') }}"
                   class="btn-primary text-white font-bold text-lg px-10 py-4 rounded-2xl inline-flex items-center justify-center gap-3">
                    {{ __('landing.cta_button') }}
                </a>
                <a href="{{ route('pricing') }}"
                   class="glass text-gray-300 hover:text-white font-semibold text-lg px-10 py-4 rounded-2xl inline-flex items-center justify-center gap-2 transition-all">
                    {{ __('landing.cta_pricing') }}
                </a>
            </div>
            <p class="text-gray-600 text-sm mt-6">{{ __('landing.cta_note') }}</p>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
function toggleFaq(index) {
    const answer = document.getElementById('faqAnswer' + index);
    const icon   = document.getElementById('faqIcon' + index);
    const isOpen = !answer.classList.contains('hidden');

    // Close all
    document.querySelectorAll('[id^="faqAnswer"]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^="faqIcon"]').forEach(el => el.classList.remove('rotate-180'));

    if (!isOpen) {
        answer.classList.remove('hidden');
        icon.classList.add('rotate-180');
    }
}
</script>
@endpush
