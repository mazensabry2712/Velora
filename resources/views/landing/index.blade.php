@extends('layouts.landing')

@push('styles')
<style>
    .v-home{background:#061113;color:#eff8f7;overflow:hidden}
    .v-wrap{width:min(1160px,calc(100% - 32px));margin:0 auto}
    .v-section{padding:104px 0}
    .v-card{background:#0a1b1d;border:1px solid #173638;border-radius:24px}
    .v-soft{background:#0b2022;border:1px solid #1c4042;border-radius:18px}
    .v-muted{color:#8ea5a6}
    .v-teal{color:#35c7be}
    .v-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:50px;padding:0 20px;border-radius:14px;font-weight:800;transition:transform .2s ease,background .2s ease,border-color .2s ease,box-shadow .2s ease}
    .v-btn-primary{background:#19a79f;color:#041011;box-shadow:0 14px 34px rgba(25,167,159,.18)}
    .v-btn-primary:hover{background:#25b8b0;transform:translateY(-1px);box-shadow:0 16px 38px rgba(25,167,159,.22)}
    .v-btn-secondary{background:#0b1c1e;border:1px solid #214547;color:#eaf5f5}
    .v-btn-secondary:hover{background:#102628;border-color:#2c5d60;transform:translateY(-1px)}
    .v-chip{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#0b2022;border:1px solid #1b3d40;color:#9ee7e1;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .v-title{font-size:clamp(44px,7vw,82px);line-height:1.01;letter-spacing:-.05em;font-weight:800;text-wrap:balance}
    .v-h2{font-size:clamp(32px,4vw,52px);line-height:1.06;letter-spacing:-.04em;font-weight:800;text-wrap:balance}
    .v-grid{display:grid;gap:20px}
    .v-hero{position:relative;padding:88px 0 76px;background:linear-gradient(180deg,#061113 0%,#071719 100%)}
    .v-hero-glow{position:absolute;inset:-20% -10% auto;height:520px;pointer-events:none;background:radial-gradient(circle at 50% 0%,rgba(53,199,190,.18),transparent 62%)}
    .v-hero-grid{position:relative;display:grid;grid-template-columns:minmax(0,.95fr) minmax(0,1.05fr);gap:44px;align-items:center}
    .v-hero-copy{text-align:left}
    [dir="rtl"] .v-hero-copy{text-align:right}
    .v-trust{display:flex;flex-wrap:wrap;gap:10px 20px;color:#789192;font-size:13px}
    .v-trust span{white-space:nowrap}
    .v-preview{overflow:hidden;border:1px solid #214547;border-radius:28px;background:#081719;box-shadow:0 34px 100px rgba(0,0,0,.34)}
    .v-browser{display:flex;align-items:center;gap:7px;padding:13px 16px;border-bottom:1px solid #173638;background:#0a2022}
    .v-dot{width:9px;height:9px;border-radius:50%;background:#315759}
    .v-app{display:grid;grid-template-columns:184px minmax(0,1fr);min-height:360px}
    .v-side{padding:22px;border-inline-end:1px solid #173638;background:#09191b}
    .v-main{padding:24px;min-width:0}
    .v-side-line{height:10px;border-radius:999px;background:#143032;margin-bottom:12px}
    .v-side-line.active{background:#1ba69e}
    .v-stat{padding:18px;border-radius:16px;background:#0c2022;border:1px solid #173c3e;min-width:0}
    .v-kicker{font-size:12px;color:#7f999a}
    .v-number{font-size:28px;font-weight:800;margin-top:6px}
    .v-feature{padding:28px}
    .v-feature:hover{border-color:#245154;transform:translateY(-3px)}
    .v-icon{width:44px;height:44px;display:grid;place-items:center;border-radius:14px;background:#0e292b;border:1px solid #245154;color:#5eddd4;font-weight:800}
    .v-step{padding:26px}
    .v-step-num{width:40px;height:40px;display:grid;place-items:center;border-radius:12px;background:#123336;border:1px solid #27585a;color:#63ddd5;font-weight:800}
    .v-proof{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:34px}
    .v-proof-card{padding:18px 20px;background:#081719;border:1px solid #173638;border-radius:16px}
    .v-section-head{max-width:760px}
    .v-section-head.center{margin:0 auto;text-align:center}
    .v-feature-grid{margin-top:52px}
    .v-how-grid{margin-top:52px}
    .v-pricing-wrap{max-width:980px;margin:50px auto 0}
    .v-pricing-card{padding:34px}
    .v-cta{background:linear-gradient(135deg,#0b2224 0%,#0d2b2d 100%);position:relative;overflow:hidden}
    .v-cta::after{content:"";position:absolute;inset:auto -10% -55% auto;width:420px;height:420px;border-radius:50%;background:rgba(53,199,190,.10);filter:blur(10px);pointer-events:none}
    .v-safe{max-width:100%;overflow-wrap:anywhere}
    .v-logo-lockup{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border:1px solid #173638;border-radius:14px;background:#081719}
    .v-logo-lockup img{height:24px;width:auto;display:block}
    @media(max-width:980px){
        .v-hero-grid{grid-template-columns:1fr;gap:42px}
        .v-hero-copy,.v-section-head{max-width:760px;text-align:center;margin-inline:auto}
        [dir="rtl"] .v-hero-copy{text-align:center}
        .v-proof{max-width:760px;margin-inline:auto}
        .v-preview{max-width:900px;margin-inline:auto;width:100%}
    }
    @media(max-width:860px){.v-app{grid-template-columns:1fr}.v-side{display:none}}
    @media(max-width:720px){
        .v-section{padding:76px 0}
        .v-hero{padding:56px 0 58px}
        .v-title{font-size:clamp(38px,12vw,58px);letter-spacing:-.045em}
        .v-h2{font-size:clamp(30px,8vw,42px)}
        .v-wrap{width:calc(100% - 20px)}
        .v-btn{width:100%;min-height:52px}
        .v-trust{justify-content:center;gap:8px 16px;font-size:12px}
        .v-proof{grid-template-columns:1fr;gap:10px}
        .v-proof-card{text-align:center}
        .v-preview{border-radius:20px}
        .v-browser{padding:11px 12px}
        .v-main{padding:16px}
        .v-stat{padding:14px}
        .v-number{font-size:24px}
        .v-feature,.v-step{padding:22px}
        .v-pricing-card{padding:22px}
    }
    @media(max-width:420px){
        .v-hero{padding-top:46px}
        .v-title{font-size:36px}
        .v-btn{padding-inline:14px;font-size:14px}
        .v-kicker{font-size:11px}
    }
    @media(prefers-reduced-motion:reduce){.v-btn,.v-feature{transition:none}}
</style>
@endpush

@section('content')
@php
    $appLabel = $appName ?? 'Velora';
@endphp
<div class="v-home">
    <section class="v-hero">
        <div class="v-hero-glow"></div>
        <div class="v-wrap v-hero-grid">
            <div class="v-hero-copy v-safe">
                <span class="v-chip"><span class="h-2 w-2 rounded-full" style="background:#35c7be"></span>{{ __('landing.hero_badge', ['days' => $trialDays ?? 14]) }}</span>
                <h1 class="v-title mt-7">{{ __('landing.hero_headline_1') }} <span class="block">{{ __('landing.hero_headline_2') }}</span> <span class="block v-teal">{{ __('landing.hero_headline_hl') }}</span></h1>
                <p class="v-muted max-w-2xl mt-7 text-lg sm:text-xl leading-8">{{ __('landing.hero_sub') }}</p>
                <div class="mt-9 flex flex-col sm:flex-row gap-3 max-w-xl mx-auto lg:mx-0">
                    <a href="{{ route('signup') }}" class="v-btn v-btn-primary">{{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }} <span aria-hidden="true">→</span></a>
                    <a href="#how-it-works" class="v-btn v-btn-secondary">{{ __('landing.hero_cta_how') }}</a>
                </div>
                <div class="v-trust mt-7">
                    <span>✓ {{ __('landing.trust_no_card') }}</span><span>✓ {{ __('landing.trust_setup') }}</span><span>✓ {{ __('landing.trust_cancel') }}</span><span>✓ {{ __('landing.trust_languages') }}</span>
                </div>
                <div class="v-proof">
                    <div class="v-proof-card"><div class="v-kicker">{{ __('landing.dashboard_stat_appointments') ?? 'Appointments' }}</div><div class="v-number">24</div></div>
                    <div class="v-proof-card"><div class="v-kicker">{{ __('landing.dashboard_stat_queue') ?? 'Queue' }}</div><div class="v-number v-teal">07</div></div>
                    <div class="v-proof-card"><div class="v-kicker">{{ __('landing.dashboard_stat_revenue') ?? 'Revenue' }}</div><div class="v-number">1.8k</div></div>
                </div>
            </div>

            <div class="v-preview">
                <div class="v-browser"><span class="v-dot"></span><span class="v-dot"></span><span class="v-dot"></span><div class="flex-1 text-center text-xs text-slate-500 truncate">app.velora</div></div>
                <div class="v-app">
                    <aside class="v-side"><div class="v-logo-lockup mb-6"><img src="{{ asset('logo.png') }}" alt="Velora"></div><div class="v-side-line active"></div><div class="v-side-line"></div><div class="v-side-line"></div><div class="v-side-line"></div><div class="v-side-line"></div></aside>
                    <div class="v-main">
                        <div class="flex items-end justify-between gap-4 mb-5"><div class="min-w-0"><div class="v-kicker">{{ __('landing.dashboard_preview_greeting') ?? 'Dashboard' }}</div><div class="text-xl sm:text-2xl font-bold mt-1 v-safe">{{ __('landing.dashboard_preview_title') ?? 'Everything in one place' }}</div></div><div class="v-icon flex-shrink-0">↗</div></div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="v-stat"><div class="v-kicker">{{ __('landing.dashboard_stat_appointments') ?? 'Appointments' }}</div><div class="v-number">24</div></div>
                            <div class="v-stat"><div class="v-kicker">{{ __('landing.dashboard_stat_queue') ?? 'Queue' }}</div><div class="v-number v-teal">07</div></div>
                            <div class="v-stat"><div class="v-kicker">{{ __('landing.dashboard_stat_revenue') ?? 'Revenue' }}</div><div class="v-number">1.8k</div></div>
                        </div>
                        <div class="v-soft mt-4 p-5"><div class="text-sm font-semibold">{{ __('landing.dashboard_preview_schedule') ?? 'Today' }}</div><div class="space-y-3 mt-4"><div class="h-3 rounded-full bg-[#143032]"></div><div class="h-3 rounded-full bg-[#143032] w-10/12"></div><div class="h-3 rounded-full bg-[#143032] w-8/12"></div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="v-section border-t" style="border-color:#122d2f">
        <div class="v-wrap">
            <div class="v-section-head"><span class="v-chip">{{ __('landing.features_badge') }}</span><h2 class="v-h2 mt-5">{{ __('landing.features_title') }} <span class="v-teal">{{ __('landing.features_title_hl') }}</span></h2><p class="v-muted mt-5 text-lg leading-8">{{ __('landing.features_subtitle') }}</p></div>
            <div class="v-grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 v-feature-grid">
                @foreach([
                    ['icon'=>'◷','title'=>__('landing.feature_booking_title'),'desc'=>__('landing.feature_booking_desc')],
                    ['icon'=>'#','title'=>__('landing.feature_queue_title'),'desc'=>__('landing.feature_queue_desc')],
                    ['icon'=>'◎','title'=>__('landing.feature_staff_title'),'desc'=>__('landing.feature_staff_desc')],
                    ['icon'=>'↗','title'=>__('landing.feature_analytics_title'),'desc'=>__('landing.feature_analytics_desc')],
                    ['icon'=>'↻','title'=>__('landing.feature_reminders_title'),'desc'=>__('landing.feature_reminders_desc')],
                    ['icon'=>'⌘','title'=>__('landing.feature_multilang_title'),'desc'=>__('landing.feature_multilang_desc')],
                ] as $feature)
                <article class="v-card v-feature"><div class="v-icon">{{ $feature['icon'] }}</div><h3 class="text-xl font-bold mt-5">{{ $feature['title'] }}</h3><p class="v-muted mt-3 leading-7">{{ $feature['desc'] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="v-section">
        <div class="v-wrap">
            <div class="v-section-head center"><span class="v-chip">{{ __('landing.how_badge') }}</span><h2 class="v-h2 mt-5">{{ __('landing.how_title') }}</h2><p class="v-muted mt-4 text-lg leading-8">{{ __('landing.how_sub') }}</p></div>
            <div class="v-grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 v-how-grid">
                @foreach([[1,__('landing.how_step1_title'),__('landing.how_step1_desc')],[2,__('landing.how_step2_title'),__('landing.how_step2_desc')],[3,__('landing.how_step3_title'),__('landing.how_step3_desc')],[4,__('landing.how_step4_title'),__('landing.how_step4_desc')]] as $step)
                    <article class="v-card v-step"><div class="v-step-num">{{ $step[0] }}</div><h3 class="text-lg font-bold mt-5">{{ $step[1] }}</h3><p class="v-muted mt-3 text-sm leading-7">{{ $step[2] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="v-section border-y" style="border-color:#122d2f;background:#071719">
        <div class="v-wrap">
            <div class="v-section-head center"><span class="v-chip">{{ __('landing.pricing_badge') }}</span><h2 class="v-h2 mt-5">{{ __('landing.pricing_title') }} <span class="v-teal">{{ __('landing.pricing_title_hl') }}</span></h2><p class="v-muted mt-4 text-lg leading-8">{{ __('landing.pricing_one_plan') }}</p></div>
            <div class="v-card v-pricing-card v-pricing-wrap">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-9 items-center">
                    <div class="v-safe"><div class="v-teal text-sm font-bold">{{ $appLabel }}</div><div class="mt-3 flex items-end gap-3 flex-wrap"><span class="text-5xl sm:text-6xl font-extrabold" x-text="monthlyFormatted">{{ $pricing['formatted_price'] }}</span><span class="v-muted mb-1">{{ __('landing.pricing_per_mo') }}</span></div><p class="v-muted mt-3">{{ __('landing.pricing_no_card_trial') }}</p><a href="{{ route('signup') }}" class="v-btn v-btn-primary mt-7">{{ __('landing.pricing_start_trial', ['days' => $trialDays]) }} <span aria-hidden="true">→</span></a></div>
                    <div class="v-soft p-6"><div class="font-bold">{{ __('landing.whats_included') }}</div><div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-5 text-sm v-muted">@foreach([__('landing.pricing_feat_scheduling'),__('landing.pricing_feat_queue'),__('landing.pricing_feat_staff'),__('landing.pricing_feat_analytics'),__('landing.pricing_feat_reminders'),__('landing.pricing_feat_languages')] as $item)<div class="flex gap-2 min-w-0"><span class="v-teal">✓</span><span class="v-safe">{{ $item }}</span></div>@endforeach</div></div>
                </div>
            </div>
        </div>
    </section>

    <section class="v-section pb-24">
        <div class="v-wrap"><div class="v-card v-cta p-8 sm:p-12 text-center"><div class="relative z-10"><div class="v-logo-lockup mb-6"><img src="{{ asset('ident.png') }}" alt="Velora"></div><h2 class="v-h2">{{ __('landing.how_cta') }}</h2><p class="v-muted max-w-2xl mx-auto mt-4 text-lg leading-8">{{ __('landing.hero_sub') }}</p><a href="{{ route('signup') }}" class="v-btn v-btn-primary mt-7">{{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }} <span aria-hidden="true">→</span></a></div></div></div>
    </section>
</div>
@endsection
