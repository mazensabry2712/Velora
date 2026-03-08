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
<script>
window._vInit = {!! json_encode([
    'countryCode'      => $pricing['country_code'],
    'countryName'      => $pricing['country_name'],
    'rawPrice'         => (float)$pricing['price'],
    'currency'         => $pricing['currency'],
    'monthlyFormatted' => $pricing['formatted_price'],
    'paymentMethods'   => $pricing['payment_methods'] ?? [],
    'taxPct'           => (float)($taxPct ?? 0),
    'taxName'          => $taxName ?? 'VAT',
    'currentLang'      => $currentLocale ?? 'en',
]) !!};
</script>
<div x-data="homePricing(window._vInit)">

{{-- ══════════════════════════════════════════════════════════════════════
     GEO BAR — Hostinger-style sticky region banner
══════════════════════════════════════════════════════════════════════════ --}}
<div class="sticky top-16 z-40 py-2.5 px-4"
     style="background:rgba(108,99,255,0.09);border-bottom:1px solid rgba(108,99,255,0.18);backdrop-filter:blur(12px)">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-center gap-x-2.5 gap-y-1 text-xs sm:text-sm">
        <span class="flex items-center gap-1.5 text-gray-400">
            <span class="relative flex h-2 w-2 flex-shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-400"></span>
            </span>
            Prices for
        </span>
        <span class="font-semibold text-white inline-flex items-center gap-1.5">
            <span x-text="flagEmoji(countryCode)" class="text-base leading-none"></span>
            <span x-text="countryName"></span>
        </span>
        <span class="text-gray-600">·</span>
        <span class="text-gray-400 font-mono uppercase" x-text="currency"></span>
        <span class="text-gray-600">·</span>
        <span class="font-mono uppercase text-gray-400" x-text="currentLang"></span>
        <button @click="openSwitcher = true"
                class="ml-1 inline-flex items-center gap-1 font-semibold text-brand-400 hover:text-brand-300 transition-colors">
            Change region
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>
</div>

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
            <span class="text-center leading-tight">{{ __('landing.hero_badge', ['days' => $trialDays ?? 14]) }}</span>
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
                {{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }}
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
<section id="features" class="py-16 sm:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-10 sm:mb-16">
        <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">{{ __('landing.features_badge') }}</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4">
            {{ __('landing.features_title') }} <span class="gradient-text">{{ __('landing.features_title_hl') }}</span>
        </h2>
        <p class="text-base sm:text-xl text-gray-400 max-w-2xl mx-auto">
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
<section id="how-it-works" class="py-16 sm:py-24 bg-white/[0.02] border-y border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-10 sm:mb-16">
            <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">{{ __('landing.how_badge') }}</span>
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4">
                {{ __('landing.how_title') }} <span class="gradient-text">{{ __('landing.how_title_hl') }}</span>
            </h2>
            <p class="text-base sm:text-xl text-gray-400">{{ __('landing.how_sub') }}</p>
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

</section>

{{-- ══════════════════════════════════════════════════════════════════════
     TESTIMONIALS
══════════════════════════════════════════════════════════════════════════ --}}
<section id="testimonials" class="py-16 sm:py-24 bg-white/[0.02] border-y border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-10 sm:mb-16">
            <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">{{ __('landing.testimonials_badge') }}</span>
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4">
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
<section id="faq" class="py-16 sm:py-24 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-10 sm:mb-16">
        <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">{{ __('landing.faq_badge') }}</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4">
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
     PRICING SECTION — fully dynamic, reacts to country switcher
══════════════════════════════════════════════════════════════════════════ --}}
<section id="pricing" class="py-14 sm:py-20 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="text-center mb-12">
        <span class="inline-block glass text-brand-400 text-xs font-semibold uppercase tracking-widest px-4 py-1.5 rounded-full mb-4">Pricing</span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4">
            Simple, honest <span class="gradient-text">pricing.</span>
        </h2>
        <p class="text-base sm:text-xl text-gray-400 max-w-xl mx-auto">
            One plan &mdash; all features &mdash; price adapts to your region.
        </p>
    </div>

    {{-- Billing Toggle --}}
    <div class="flex justify-center mb-10">
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

    {{-- 2-col: sticky pricing card + features --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

        {{-- LEFT: Pricing Card (sticky) --}}
        <div class="lg:sticky lg:top-28 order-first">
            <div class="glass rounded-3xl overflow-hidden border border-brand-500/40"
                 style="box-shadow:0 0 60px rgba(108,99,255,0.12);">

                {{-- Card Header --}}
                <div class="px-5 sm:px-8 pt-6 sm:pt-8 pb-5 sm:pb-6 border-b border-white/5">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                 style="background:linear-gradient(135deg,#6C63FF,#8b76ff);box-shadow:0 4px 15px rgba(108,99,255,0.4)">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-white text-sm">{{ $appName }}</p>
                                <p class="text-gray-500 text-xs">
                                    Full Platform &mdash; <span x-text="countryName" class="text-gray-400"></span>
                                    <template x-if="currentLang && currentLang !== 'en'">
                                        <span class="ml-1 uppercase font-mono text-brand-400/70 text-[10px]" x-text="'· ' + currentLang"></span>
                                    </template>
                                </p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-3 py-1.5 rounded-full bg-brand-500/20 text-brand-300 border border-brand-500/30 whitespace-nowrap">
                            {{ $trialDays }}-day free trial
                        </span>
                    </div>

                    {{-- Price --}}
                    <div class="mb-3">
                        <div x-show="billing === 'monthly'" class="flex items-end gap-2">
                            <span class="text-4xl sm:text-6xl font-black text-white leading-none" x-text="monthlyFormatted"></span>
                            <span class="text-gray-400 text-lg mb-1">/mo</span>
                        </div>
                        <div x-show="billing === 'annual'" x-cloak class="flex items-end gap-2">
                            <span class="text-4xl sm:text-6xl font-black text-white leading-none" x-text="annualPerMonthFormatted"></span>
                            <div class="mb-1">
                                <p class="text-gray-400 text-sm">/mo</p>
                                <p class="text-green-400 text-xs font-medium" x-text="'Billed ' + annualTotalFormatted + '/yr'"></p>
                            </div>
                        </div>
                        <p class="text-green-400 text-xs font-medium mt-2">✓ No credit card for the free trial</p>
                    </div>

                    {{-- Tax badge --}}
                    <div x-show="taxPct > 0" x-cloak
                         class="mb-1 inline-flex items-center gap-1.5 text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20 px-3 py-1 rounded-full">
                        <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                        </svg>
                        <span x-text="taxName"></span> (<span x-text="taxPct + '%'"></span>) — total <span x-text="taxTotalFormatted" class="font-bold"></span>/mo
                    </div>
                </div>

                {{-- CTA --}}
                <div class="px-5 sm:px-8 py-5 sm:py-6 border-b border-white/5">
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
                <div class="px-5 sm:px-8 py-4 sm:py-5 border-b border-white/5">
                    <p class="text-xs text-gray-500 uppercase tracking-widest mb-3">Payment methods</p>
                    <div class="flex flex-wrap gap-2" x-html="renderPaymentMethods()"></div>
                </div>

                {{-- Tax notice --}}
                <div x-show="taxPct > 0" x-cloak class="mx-5 sm:mx-8 mb-1 mt-2 flex items-center gap-2 rounded-xl border border-yellow-500/20 bg-yellow-500/5 px-4 py-2.5 text-xs">
                    <svg class="w-4 h-4 flex-shrink-0 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-yellow-300">
                        <span x-text="taxName"></span> (<span x-text="taxPct + '%'"></span>) will be added at checkout.
                        Total: <span x-text="taxTotalFormatted" class="font-bold"></span>/mo
                    </span>
                </div>

                {{-- Footer meta --}}
                <div class="px-5 sm:px-8 py-4 sm:py-5 space-y-3.5">
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

        {{-- RIGHT: Features + Trial Timeline + Stats --}}
        <div class="space-y-6">
            <div class="glass rounded-3xl p-5 sm:p-8 border border-white/5">
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
            <div class="glass rounded-3xl p-5 sm:p-8 border border-white/5">
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
            <div class="glass rounded-3xl p-4 sm:p-6 border border-white/5">
                <div class="grid grid-cols-3 divide-x divide-white/5 text-center">
                    <div class="px-4">
                        <p class="text-2xl sm:text-3xl font-black text-white">2,400<span class="text-brand-400">+</span></p>
                        <p class="text-xs text-gray-500 mt-1">Businesses</p>
                    </div>
                    <div class="px-4">
                        <p class="text-2xl sm:text-3xl font-black text-white">1M<span class="text-brand-400">+</span></p>
                        <p class="text-xs text-gray-500 mt-1">Appointments</p>
                    </div>
                    <div class="px-4">
                        <p class="text-2xl sm:text-3xl font-black text-white">29<span class="text-brand-400">+</span></p>
                        <p class="text-xs text-gray-500 mt-1">Countries</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     CTA SECTION
══════════════════════════════════════════════════════════════════════════ --}}
<section class="py-16 sm:py-24 relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-radial from-brand-500/20 via-transparent to-transparent pointer-events-none"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <div class="glass rounded-3xl p-6 sm:p-12 border border-brand-500/30">
            <div class="text-6xl mb-6">🚀</div>
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4">
                {{ __('landing.cta_title') }} <span class="gradient-text">{{ __('landing.cta_title_hl') }}</span> {{ __('landing.cta_title_sfx') }}
            </h2>
            <p class="text-base sm:text-xl text-gray-400 mb-8 max-w-2xl mx-auto">
                {{ __('landing.cta_sub') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('signup') }}"
                   class="btn-primary text-white font-bold text-lg px-10 py-4 rounded-2xl inline-flex items-center justify-center gap-3">
                    {{ __('landing.cta_button') }}
                </a>
            </div>
            <p class="text-gray-600 text-sm mt-6">{{ __('landing.cta_note') }}</p>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     COUNTRY SWITCHER MODAL
══════════════════════════════════════════════════════════════════════════ --}}
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

        <div class="px-6 py-5 border-b border-white/5 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-white" x-text="firstVisit ? 'Select your region' : 'Choose your country'"></h3>
                <p x-show="firstVisit" x-cloak class="text-xs text-gray-400 mt-0.5">See local pricing and payment options for your location</p>
            </div>
            <button @click="openSwitcher = false"
                    class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-white/10 text-gray-400 hover:text-white transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

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

        <div class="px-6 py-3 border-t border-white/5">
            <p class="text-xs text-gray-500 text-center">
                Regional pricing reflects local purchasing power. Same features everywhere.
            </p>
        </div>
    </div>
</div>

</div>{{-- end x-data="homePricing" --}}

@endsection

@push('scripts')
<script>
function homePricing({ countryCode, countryName, rawPrice, currency, monthlyFormatted, paymentMethods, taxPct, taxName, currentLang }) {

    const SYMBOLS = {
        USD:'$',  GBP:'£',  EUR:'€',  AED:'AED ', SAR:'SAR ',
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
        currentLang,
        billing:       'monthly',
        openSwitcher:  false,
        countrySearch: '',
        firstVisit:    !localStorage.getItem('velora_region_set'),
        _allData:      (() => { const d = {!! $allDataJson !!}; if (d['GLOBAL']) d['GLOBAL'].name = 'Other countries'; return d; })(),

        init() {
            if (!localStorage.getItem('velora_region_set')) {
                setTimeout(() => this.openSwitcher = true, 700);
            }
        },

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
        get taxAmount()               { return this.taxPct > 0 ? this.rawPrice * this.taxPct / 100 : 0; },
        get taxTotalFormatted()       { return fmt(this.rawPrice + this.taxAmount, this.currency); },

        switchCountry(code) {
            const d = this._allData[code];
            if (!d) return;
            const newLang = d.lang ?? 'en';
            // Update UI immediately for visual feedback before the reload
            this.countryCode      = code;
            this.countryName      = d.name;
            this.monthlyFormatted = d.monthly;
            this.rawPrice         = d.price;
            this.currency         = d.currency;
            this.paymentMethods   = d.methods;
            this.taxPct           = d.taxPct  ?? 0;
            this.taxName          = d.taxName ?? 'VAT';
            this.currentLang      = newLang;
            this.openSwitcher     = false;
            this.countrySearch    = '';
            this.firstVisit       = false;
            localStorage.setItem('velora_region_set', '1');
            localStorage.setItem('velora_lang', newLang);
            document.dispatchEvent(new CustomEvent('velora:lang-changed', { detail: { lang: newLang } }));
            // Navigate to the combined region route — sets locale + country in one server-side hop
            // This avoids any AJAX cookie timing race and always triggers a full reload
            window.location.href = '/region/' + newLang + '/' + code;
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

function toggleFaq(index) {
    const answer = document.getElementById('faqAnswer' + index);
    const icon   = document.getElementById('faqIcon' + index);
    const isOpen = !answer.classList.contains('hidden');

    document.querySelectorAll('[id^="faqAnswer"]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^="faqIcon"]').forEach(el => el.classList.remove('rotate-180'));

    if (!isOpen) {
        answer.classList.remove('hidden');
        icon.classList.add('rotate-180');
    }
}
</script>
@endpush
