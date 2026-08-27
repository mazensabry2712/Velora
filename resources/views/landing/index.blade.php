@extends('layouts.landing')

@push('styles')
<style>
    :root {
        --landing-navy-950: #000520;
        --landing-navy-900: #07142C;
        --landing-navy-800: #102349;
        --landing-navy-700: #183461;
        --landing-ink: #0A1530;
        --landing-muted: #65738B;
        --landing-line: #D9E1EC;
        --landing-bg: #F6F8FC;
        --landing-white: #FFFFFF;
    }

    .v-home {
        --v-ink: var(--landing-ink);
        --v-ink-soft: var(--landing-muted);
        --v-canvas: var(--landing-bg);
        --v-line: var(--landing-line);
        --v-teal-700: var(--landing-navy-900);
        --v-teal-600: var(--landing-navy-800);
        --v-teal-500: var(--landing-navy-700);
        --v-teal-400: #2B4F88;
        --v-teal-100: #E8EDF5;
        --v-teal-50: #F3F6FA;
        background: var(--landing-bg) !important;
        color: var(--landing-ink) !important;
        overflow: clip;
    }

    .v-home * { box-sizing: border-box; }
    .v-home .v-wrap { width: min(1160px, calc(100% - 40px)); margin: 0 auto; }
    .v-home .v-section { padding: 96px 0; }
    .v-home .v-section + .v-section { border-top: 1px solid var(--landing-line); }

    .v-home .v-btn,
    .v-home .v-chip,
    .v-home .v-card,
    .v-home .v-preview { transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease, background .2s ease; }

    .v-home .v-btn-primary {
        background: var(--landing-navy-900) !important;
        color: #fff !important;
        border: 1px solid var(--landing-navy-900) !important;
        box-shadow: 0 12px 28px rgba(0, 5, 32, .16) !important;
    }
    .v-home .v-btn-primary:hover { background: var(--landing-navy-800) !important; transform: translateY(-1px); }
    .v-home .v-btn-secondary {
        background: #fff !important;
        color: var(--landing-navy-900) !important;
        border: 1px solid var(--landing-line) !important;
    }
    .v-home .v-btn-secondary:hover { background: #F0F3F8 !important; border-color: #B9C5D6 !important; }

    .v-home .v-chip {
        background: #fff !important;
        border: 1px solid var(--landing-line) !important;
        color: var(--landing-navy-900) !important;
        letter-spacing: .05em;
    }

    .v-home .v-hero {
        position: relative;
        padding: 74px 0 84px;
        background: var(--landing-bg) !important;
    }
    .v-home .v-hero-glow { display: none !important; }
    .v-home .v-hero-grid {
        display: grid;
        grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr);
        gap: 56px;
        align-items: center;
    }
    .v-home .v-hero-copy { max-width: 600px; }
    [dir="rtl"] .v-home .v-hero-copy { text-align: right; }
    .v-home .v-title {
        color: var(--landing-navy-950) !important;
        font-size: clamp(48px, 6vw, 76px) !important;
        line-height: 1.02 !important;
        letter-spacing: -.055em !important;
    }
    .v-home .v-teal { color: var(--landing-navy-900) !important; }
    .v-home .v-muted { color: var(--landing-muted) !important; }
    .v-home .v-trust { color: #6F7C91 !important; }
    .v-home .v-trust i { color: var(--landing-navy-700) !important; }

    .v-home .v-proof { margin-top: 30px; }
    .v-home .v-proof-card {
        background: #fff !important;
        border: 1px solid var(--landing-line) !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 30px rgba(7, 20, 44, .05);
    }
    .v-home .v-number { color: var(--landing-navy-900) !important; }

    .v-home .v-preview {
        background: var(--landing-navy-950) !important;
        border: 1px solid var(--landing-navy-800) !important;
        border-radius: 24px !important;
        box-shadow: 0 28px 70px rgba(0, 5, 32, .17) !important;
    }
    .v-home .v-browser { background: var(--landing-navy-900) !important; border-color: var(--landing-navy-800) !important; }
    .v-home .v-app { background: var(--landing-navy-900) !important; }
    .v-home .v-side { background: #03081D !important; border-color: var(--landing-navy-800) !important; }
    .v-home .v-logo-lockup { background: #fff !important; border-color: rgba(255,255,255,.16) !important; }
    .v-home .v-side-line { background: #18294E !important; }
    .v-home .v-side-line.active { background: #fff !important; }
    .v-home .v-main { background: #F5F7FB !important; }
    .v-home .v-stat,
    .v-home .v-soft { background: #fff !important; border-color: var(--landing-line) !important; }
    .v-home .v-icon,
    .v-home .v-step-num { background: #EDF1F7 !important; color: var(--landing-navy-900) !important; border-color: #D7E0EC !important; }

    .v-home .v-section-head { max-width: 760px; }
    .v-home .v-section-head.center { margin-inline: auto; text-align: center; }
    .v-home .v-h2 { color: var(--landing-navy-950) !important; font-size: clamp(34px, 4vw, 52px) !important; line-height: 1.06 !important; letter-spacing: -.045em !important; }

    .v-home .v-feature-grid,
    .v-home .v-how-grid { margin-top: 42px !important; }
    .v-home .v-card {
        background: #fff !important;
        border: 1px solid var(--landing-line) !important;
        border-radius: 18px !important;
        box-shadow: none !important;
    }
    .v-home .v-card:hover { border-color: #B8C5D7 !important; transform: translateY(-2px); box-shadow: 0 16px 36px rgba(7,20,44,.07) !important; }
    .v-home .v-card h3 { color: var(--landing-navy-900) !important; }

    .v-home #pricing {
        background: #EEF2F7 !important;
        border-color: var(--landing-line) !important;
    }
    .v-home .v-pricing-card { background: #fff !important; border-color: var(--landing-line) !important; }
    .v-home .v-cta {
        background: var(--landing-navy-950) !important;
        border: 1px solid var(--landing-navy-800) !important;
    }

    .v-home .v-footer-ident { filter: none !important; }

    /* Responsive layout: stack early and remove desktop-only density. */
    @media (max-width: 980px) {
        .v-home .v-hero-grid { grid-template-columns: 1fr; gap: 38px; }
        .v-home .v-hero-copy { max-width: 760px; margin-inline: auto; text-align: center; }
        [dir="rtl"] .v-home .v-hero-copy { text-align: center; }
        .v-home .v-preview { max-width: 820px; width: 100%; margin-inline: auto; }
    }
    @media (max-width: 760px) {
        .v-home .v-wrap { width: calc(100% - 24px); }
        .v-home .v-section { padding: 70px 0; }
        .v-home .v-hero { padding: 46px 0 58px; }
        .v-home .v-title { font-size: clamp(38px, 11vw, 56px) !important; }
        .v-home .v-hero-copy > p { font-size: 15px !important; line-height: 1.75 !important; }
        .v-home .v-hero-copy .v-trust { justify-content: center; }
        .v-home .v-proof { grid-template-columns: 1fr; }
        .v-home .v-btn { width: 100%; min-height: 52px; }
        .v-home .v-preview { border-radius: 18px !important; }
        .v-home .v-app { grid-template-columns: 1fr !important; min-height: 0 !important; }
        .v-home .v-side { display: none !important; }
        .v-home .v-main { padding: 16px !important; }
    }
    @media (max-width: 520px) {
        .v-home .v-title { font-size: 35px !important; }
        .v-home .v-h2 { font-size: 30px !important; }
        .v-home .v-stat-grid { grid-template-columns: 1fr !important; }
        .v-home .v-pricing-card { padding: 20px !important; }
        .v-home .v-checks { grid-template-columns: 1fr !important; }
    }
    @media (max-width: 380px) {
        .v-home .v-wrap { width: calc(100% - 18px); }
        .v-home .v-title { font-size: 32px !important; }
    }
    @media (prefers-reduced-motion: reduce) {
        .v-home .v-btn, .v-home .v-card { transition: none !important; }
    }
</style>
@endpush

@section('content')
@php
    $appLabel = $appName ?? 'Velora';
@endphp

<div class="v-home">
    <section class="v-hero">
        <div class="v-wrap v-hero-grid">
            <div class="v-hero-copy">
                <span class="v-chip">
                    <span class="inline-block w-2 h-2 rounded-full bg-[#07142C]"></span>
                    {{ __('landing.hero_badge', ['days' => $trialDays ?? 14]) }}
                </span>

                <h1 class="v-title mt-7">
                    {{ __('landing.hero_headline_1') }}
                    <span class="block">{{ __('landing.hero_headline_2') }}</span>
                    <span class="block v-teal">{{ __('landing.hero_headline_hl') }}</span>
                </h1>

                <p class="v-muted mt-6 max-w-2xl text-lg sm:text-xl leading-8">
                    {{ __('landing.hero_sub') }}
                </p>

                <div class="mt-8 flex flex-col sm:flex-row gap-3 max-w-xl mx-auto lg:mx-0">
                    <a href="{{ route('signup') }}" class="v-btn v-btn-primary">
                        {{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }}
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="#features" class="v-btn v-btn-secondary">
                        {{ __('landing.hero_cta_how') }}
                    </a>
                </div>

                <div class="v-trust mt-6 flex flex-wrap gap-3 sm:gap-5 text-xs font-semibold justify-center lg:justify-start">
                    <span>✓ {{ __('landing.trust_no_card') }}</span>
                    <span>✓ {{ __('landing.trust_setup') }}</span>
                    <span>✓ {{ __('landing.trust_cancel') }}</span>
                    <span>✓ {{ __('landing.trust_languages') }}</span>
                </div>
            </div>

            <div class="v-preview">
                <div class="v-browser">
                    <span class="v-dot"></span><span class="v-dot"></span><span class="v-dot"></span>
                    <div class="flex-1 text-center text-[11px] text-slate-300 truncate">app.velora</div>
                </div>
                <div class="v-app">
                    <aside class="v-side">
                        <div class="v-logo-lockup mb-6">
                            <img src="{{ asset('logo.png') }}" alt="Velora" class="h-7 w-auto">
                        </div>
                        <div class="v-side-line active"></div>
                        <div class="v-side-line"></div>
                        <div class="v-side-line"></div>
                        <div class="v-side-line"></div>
                        <div class="v-side-line"></div>
                    </aside>
                    <div class="v-main">
                        <div class="flex items-end justify-between gap-4 mb-5">
                            <div class="min-w-0">
                                <div class="v-kicker">{{ __('landing.dashboard_preview_greeting') ?? 'Dashboard' }}</div>
                                <div class="text-xl sm:text-2xl font-bold text-[#07142C] mt-1 truncate">{{ __('landing.dashboard_preview_title') ?? 'Everything in one place' }}</div>
                            </div>
                            <div class="v-icon shrink-0">↗</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="v-stat">
                                <div class="v-kicker">{{ __('landing.dashboard_stat_appointments') ?? 'Appointments' }}</div>
                                <div class="v-number">24</div>
                            </div>
                            <div class="v-stat">
                                <div class="v-kicker">{{ __('landing.dashboard_stat_queue') ?? 'Queue' }}</div>
                                <div class="v-number">07</div>
                            </div>
                            <div class="v-stat">
                                <div class="v-kicker">{{ __('landing.dashboard_stat_revenue') ?? 'Revenue' }}</div>
                                <div class="v-number">1.8k</div>
                            </div>
                        </div>

                        <div class="v-soft mt-4 p-5">
                            <div class="text-sm font-bold text-[#07142C]">{{ __('landing.dashboard_preview_schedule') ?? 'Today' }}</div>
                            <div class="space-y-3 mt-4">
                                <div class="h-3 rounded-full bg-[#E2E7F0]"></div>
                                <div class="h-3 rounded-full bg-[#E2E7F0] w-10/12"></div>
                                <div class="h-3 rounded-full bg-[#E2E7F0] w-8/12"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="v-section">
        <div class="v-wrap">
            <div class="v-section-head">
                <span class="v-chip">{{ __('landing.features_badge') }}</span>
                <h2 class="v-h2 mt-5">{{ __('landing.features_title') }} <span class="v-teal">{{ __('landing.features_title_hl') }}</span></h2>
                <p class="v-muted mt-4 text-lg leading-8">{{ __('landing.features_subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 v-feature-grid">
                @foreach([
                    ['icon'=>'◷','title'=>__('landing.feature_booking_title'),'desc'=>__('landing.feature_booking_desc')],
                    ['icon'=>'#','title'=>__('landing.feature_queue_title'),'desc'=>__('landing.feature_queue_desc')],
                    ['icon'=>'◎','title'=>__('landing.feature_staff_title'),'desc'=>__('landing.feature_staff_desc')],
                    ['icon'=>'↗','title'=>__('landing.feature_analytics_title'),'desc'=>__('landing.feature_analytics_desc')],
                    ['icon'=>'↻','title'=>__('landing.feature_reminders_title'),'desc'=>__('landing.feature_reminders_desc')],
                    ['icon'=>'⌘','title'=>__('landing.feature_multilang_title'),'desc'=>__('landing.feature_multilang_desc')],
                ] as $feature)
                    <article class="v-card p-6">
                        <div class="v-icon">{{ $feature['icon'] }}</div>
                        <h3 class="text-lg font-bold mt-5">{{ $feature['title'] }}</h3>
                        <p class="v-muted mt-3 leading-7 text-sm">{{ $feature['desc'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="v-section" style="background:#fff">
        <div class="v-wrap">
            <div class="v-section-head center">
                <span class="v-chip">{{ __('landing.how_badge') }}</span>
                <h2 class="v-h2 mt-5">{{ __('landing.how_title') }}</h2>
                <p class="v-muted mt-4 text-lg leading-8">{{ __('landing.how_sub') }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 v-how-grid">
                @foreach([
                    [1, __('landing.how_step1_title'), __('landing.how_step1_desc')],
                    [2, __('landing.how_step2_title'), __('landing.how_step2_desc')],
                    [3, __('landing.how_step3_title'), __('landing.how_step3_desc')],
                    [4, __('landing.how_step4_title'), __('landing.how_step4_desc')],
                ] as $step)
                    <article class="v-card p-6">
                        <div class="v-step-num">{{ $step[0] }}</div>
                        <h3 class="text-lg font-bold text-[#07142C] mt-5">{{ $step[1] }}</h3>
                        <p class="v-muted mt-3 text-sm leading-7">{{ $step[2] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="v-section">
        <div class="v-wrap">
            <div class="v-section-head center">
                <span class="v-chip">{{ __('landing.pricing_badge') }}</span>
                <h2 class="v-h2 mt-5">{{ __('landing.pricing_title') }} <span class="v-teal">{{ __('landing.pricing_title_hl') }}</span></h2>
                <p class="v-muted mt-4 text-lg leading-8">{{ __('landing.pricing_one_plan') }}</p>
            </div>

            <div class="v-card v-pricing-card max-w-4xl mx-auto mt-10 p-6 sm:p-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <div>
                        <div class="text-sm font-bold text-[#07142C]">{{ $appLabel }}</div>
                        <div class="mt-3 flex items-end gap-3 flex-wrap">
                            <span class="text-5xl sm:text-6xl font-extrabold tracking-tight text-[#000520]" x-text="monthlyFormatted">{{ $pricing['formatted_price'] }}</span>
                            <span class="v-muted mb-1">{{ __('landing.pricing_per_mo') }}</span>
                        </div>
                        <p class="v-muted mt-3 text-sm">{{ __('landing.pricing_no_card_trial') }}</p>
                        <a href="{{ route('signup') }}" class="v-btn v-btn-primary mt-6">
                            {{ __('landing.pricing_start_trial', ['days' => $trialDays]) }}
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>

                    <div class="rounded-2xl border border-[#D9E1EC] bg-[#F7F9FC] p-5">
                        <div class="font-bold text-[#07142C]">{{ __('landing.whats_included') }}</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-5 text-sm v-muted">
                            @foreach([
                                __('landing.pricing_feat_scheduling'),
                                __('landing.pricing_feat_queue'),
                                __('landing.pricing_feat_staff'),
                                __('landing.pricing_feat_analytics'),
                                __('landing.pricing_feat_reminders'),
                                __('landing.pricing_feat_languages')
                            ] as $item)
                                <div class="flex gap-2 min-w-0"><span class="font-bold text-[#183461]">✓</span><span class="v-safe">{{ $item }}</span></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="v-section pt-0">
        <div class="v-wrap">
            <div class="v-cta p-8 sm:p-12 text-center rounded-3xl">
                <div class="relative z-10">
                    <div class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-3 mb-6">
                        <img src="{{ asset('logo.png') }}" alt="Velora" class="h-8 w-auto">
                    </div>
                    <h2 class="text-white text-3xl sm:text-5xl font-extrabold tracking-tight">{{ __('landing.how_cta') }}</h2>
                    <p class="max-w-2xl mx-auto mt-4 text-[#AEB9CC] text-base sm:text-lg leading-8">{{ __('landing.hero_sub') }}</p>
                    <a href="{{ route('signup') }}" class="v-btn mt-7 inline-flex bg-white text-[#000520] border border-white hover:bg-[#EEF2F7]">
                        {{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }}
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
