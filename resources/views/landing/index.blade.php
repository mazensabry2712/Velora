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

    .v-home { background: var(--landing-bg) !important; color: var(--landing-ink) !important; }
    .v-home * { box-sizing: border-box; }
    .v-wrap { width: min(1160px, calc(100% - 40px)); margin-inline: auto; }
    .v-section { padding: 88px 0; border-top: 1px solid var(--landing-line); }

    .v-hero { padding: 72px 0 88px; }
    .v-hero-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:56px; align-items:center; }
    .v-hero-copy { max-width: 610px; }
    .v-chip { display:inline-flex; align-items:center; gap:8px; padding:9px 13px; border:1px solid var(--landing-line); border-radius:999px; background:#fff; color:var(--landing-navy-900); font-size:12px; font-weight:800; }
    .v-title { margin:22px 0 0; color:var(--landing-navy-950); font-size:clamp(44px,6vw,72px); line-height:1.03; letter-spacing:-.055em; font-weight:800; }
    .v-title-accent { color:var(--landing-navy-900); }
    .v-muted { color:var(--landing-muted); }
    .v-btn { min-height:50px; display:inline-flex; align-items:center; justify-content:center; gap:10px; padding:0 18px; border-radius:13px; font-size:14px; font-weight:800; border:1px solid transparent; transition:transform .2s,background .2s,border-color .2s; }
    .v-btn:hover { transform:translateY(-1px); }
    .v-btn-primary { background:var(--landing-navy-900); color:#fff; border-color:var(--landing-navy-900); box-shadow:0 12px 28px rgba(0,5,32,.14); }
    .v-btn-primary:hover { background:var(--landing-navy-800); }
    .v-btn-secondary { background:#fff; color:var(--landing-navy-900); border-color:var(--landing-line); }
    .v-btn-secondary:hover { background:#EEF2F7; }
    .v-trust { color:#6F7C91; }

    .v-preview { overflow:hidden; background:var(--landing-navy-950); border:1px solid var(--landing-navy-800); border-radius:24px; box-shadow:0 28px 70px rgba(0,5,32,.16); }
    .v-browser { display:flex; align-items:center; gap:7px; padding:13px 15px; background:var(--landing-navy-900); border-bottom:1px solid var(--landing-navy-800); }
    .v-dot { width:8px; height:8px; border-radius:50%; background:#fff; opacity:.55; }
    .v-browser-label { flex:1; text-align:center; color:#C9D2E2; font-size:11px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .v-app { display:grid; grid-template-columns:150px minmax(0,1fr); min-height:410px; }
    .v-side { padding:18px; background:#03081D; border-right:1px solid var(--landing-navy-800); }
    .v-logo-lockup { display:flex; align-items:center; justify-content:center; padding:10px; border-radius:12px; background:#fff; }
    .v-side-line { height:11px; margin-top:14px; border-radius:999px; background:#18294E; }
    .v-side-line.active { background:#fff; width:74%; }
    .v-main { padding:24px; background:#F5F7FB; }
    .v-kicker { color:#78869B; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .v-stat, .v-soft { border:1px solid var(--landing-line); background:#fff; border-radius:16px; }
    .v-stat { padding:16px; }
    .v-number { margin-top:8px; color:var(--landing-navy-900); font-size:27px; line-height:1; font-weight:800; }
    .v-soft { padding:18px; }
    .v-stat-icon, .v-step-num, .v-feature-icon { display:grid; place-items:center; width:40px; height:40px; border-radius:12px; background:#EDF1F7; color:var(--landing-navy-900); font-weight:800; }

    .v-section-head { max-width:760px; }
    .v-section-head.center { margin-inline:auto; text-align:center; }
    .v-h2 { margin-top:18px; color:var(--landing-navy-950); font-size:clamp(34px,4vw,50px); line-height:1.08; letter-spacing:-.045em; font-weight:800; }
    .v-card { border:1px solid var(--landing-line); border-radius:18px; background:#fff; padding:24px; transition:.2s; }
    .v-card:hover { transform:translateY(-2px); border-color:#B9C6D8; box-shadow:0 16px 34px rgba(7,20,44,.06); }
    .v-card h3 { margin-top:16px; color:var(--landing-navy-900); font-size:18px; font-weight:800; }
    .v-card p { margin-top:10px; color:var(--landing-muted); font-size:14px; line-height:1.75; }
    .v-step-num { border-radius:50%; }
    .v-pricing-wrap { max-width:920px; margin:40px auto 0; }
    .v-pricing-card { display:grid; grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr); gap:28px; border:1px solid var(--landing-line); border-radius:22px; background:#fff; padding:28px; box-shadow:0 16px 40px rgba(7,20,44,.06); }
    .v-price { margin-top:10px; color:var(--landing-navy-950); font-size:58px; line-height:1; font-weight:800; letter-spacing:-.05em; }
    .v-feature-list { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .v-check { color:var(--landing-navy-700); font-weight:800; }
    .v-cta { padding:52px 28px; border-radius:28px; background:var(--landing-navy-950); text-align:center; }
    .v-cta h2 { color:#fff; font-size:clamp(32px,4vw,48px); line-height:1.08; font-weight:800; letter-spacing:-.04em; }
    .v-cta p { color:#AEB9CC; }

    @media (max-width:980px){
        .v-hero-grid,.v-pricing-card{ grid-template-columns:1fr; }
        .v-hero-copy{ max-width:760px; margin-inline:auto; text-align:center; }
        .v-hero-grid{ gap:38px; }
        .v-preview{ max-width:820px; margin-inline:auto; width:100%; }
    }
    @media (max-width:760px){
        .v-wrap{ width:calc(100% - 24px); }
        .v-section{ padding:70px 0; }
        .v-hero{ padding:44px 0 58px; }
        .v-title{ font-size:clamp(36px,11vw,54px); }
        .v-btn{ width:100%; }
        .v-app{ grid-template-columns:1fr; min-height:0; }
        .v-side{ display:none; }
        .v-main{ padding:16px; }
        .v-preview{ border-radius:18px; }
        .v-feature-list{ grid-template-columns:1fr; }
    }
    @media (max-width:520px){
        .v-wrap{ width:calc(100% - 18px); }
        .v-pricing-card{ padding:20px; }
        .v-price{ font-size:48px; }
    }
</style>
@endpush

@section('content')
@php($appLabel = $appName ?? 'Velora')

<div class="v-home">
    <section class="v-hero">
        <div class="v-wrap v-hero-grid">
            <div class="v-hero-copy">
                <span class="v-chip"><span class="inline-block w-2 h-2 rounded-full" style="background:#07142C"></span>{{ __('landing.hero_badge', ['days' => $trialDays ?? 14]) }}</span>
                <h1 class="v-title">
                    {{ __('landing.hero_headline_1') }}
                    <span class="block">{{ __('landing.hero_headline_2') }}</span>
                    <span class="block v-title-accent">{{ __('landing.hero_headline_hl') }}</span>
                </h1>
                <p class="v-muted mt-6 text-lg sm:text-xl leading-8">{{ __('landing.hero_sub') }}</p>
                <div class="mt-8 flex flex-col sm:flex-row gap-3 max-w-xl mx-auto lg:mx-0">
                    <a href="{{ route('signup') }}" class="v-btn v-btn-primary">{{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }} <span aria-hidden="true">→</span></a>
                    <a href="#features" class="v-btn v-btn-secondary">{{ __('landing.hero_cta_how') }}</a>
                </div>
                <div class="v-trust mt-6 flex flex-wrap justify-center lg:justify-start gap-3 sm:gap-5 text-xs font-semibold">
                    <span>✓ {{ __('landing.trust_no_card') }}</span>
                    <span>✓ {{ __('landing.trust_setup') }}</span>
                    <span>✓ {{ __('landing.trust_cancel') }}</span>
                    <span>✓ {{ __('landing.trust_languages') }}</span>
                </div>
            </div>

            <div class="v-preview" aria-label="Velora dashboard preview">
                <div class="v-browser">
                    <span class="v-dot"></span><span class="v-dot"></span><span class="v-dot"></span>
                    <div class="v-browser-label">app.velora</div>
                </div>
                <div class="v-app">
                    <aside class="v-side">
                        <div class="v-logo-lockup"><img src="{{ asset('logo.png') }}" alt="Velora" style="height:28px;width:auto"></div>
                        <div class="v-side-line active"></div><div class="v-side-line"></div><div class="v-side-line"></div><div class="v-side-line"></div><div class="v-side-line"></div>
                    </aside>
                    <div class="v-main">
                        <div class="flex items-end justify-between gap-4 mb-5">
                            <div class="min-w-0">
                                <div class="v-kicker">Velora</div>
                                <div class="text-xl sm:text-2xl font-bold text-[#07142C] mt-1">{{ __('landing.features_title') }}</div>
                            </div>
                            <div class="v-stat-icon shrink-0">↗</div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="v-stat"><div class="v-kicker">{{ __('landing.ticker_scheduling') }}</div><div class="v-number">24</div></div>
                            <div class="v-stat"><div class="v-kicker">{{ __('landing.ticker_queue') }}</div><div class="v-number">07</div></div>
                            <div class="v-stat"><div class="v-kicker">{{ __('landing.ticker_uptime') }}</div><div class="v-number">99.9%</div></div>
                        </div>
                        <div class="v-soft mt-4">
                            <div class="text-sm font-bold text-[#07142C]">{{ __('landing.pricing_one_plan') }}</div>
                            <div class="space-y-3 mt-4"><div class="h-3 rounded-full bg-[#E2E7F0]"></div><div class="h-3 rounded-full bg-[#E2E7F0] w-10/12"></div><div class="h-3 rounded-full bg-[#E2E7F0] w-8/12"></div></div>
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
                <h2 class="v-h2">{{ __('landing.features_title') }} <span class="v-title-accent">{{ __('landing.features_title_hl') }}</span></h2>
                <p class="v-muted mt-4 text-lg leading-8">{{ __('landing.features_sub') }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-10">
                @foreach([
                    ['icon'=>'◷','title'=>__('landing.f1_title'),'desc'=>__('landing.f1_desc')],
                    ['icon'=>'#','title'=>__('landing.f2_title'),'desc'=>__('landing.f2_desc')],
                    ['icon'=>'◎','title'=>__('landing.f3_title'),'desc'=>__('landing.f3_desc')],
                    ['icon'=>'↗','title'=>__('landing.f4_title'),'desc'=>__('landing.f4_desc')],
                    ['icon'=>'↻','title'=>__('landing.f5_title'),'desc'=>__('landing.f5_desc')],
                    ['icon'=>'⌘','title'=>__('landing.f6_title'),'desc'=>__('landing.f6_desc')],
                ] as $feature)
                    <article class="v-card"><div class="v-feature-icon">{{ $feature['icon'] }}</div><h3>{{ $feature['title'] }}</h3><p>{{ $feature['desc'] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="v-section" style="background:#fff">
        <div class="v-wrap">
            <div class="v-section-head center">
                <span class="v-chip">{{ __('landing.how_badge') }}</span>
                <h2 class="v-h2">{{ __('landing.how_title') }} <span class="v-title-accent">{{ __('landing.how_title_hl') }}</span></h2>
                <p class="v-muted mt-4 text-lg leading-8">{{ __('landing.how_sub') }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-10">
                @foreach([
                    [1, __('landing.s1_title'), __('landing.s1_desc')],
                    [2, __('landing.s2_title'), __('landing.s2_desc')],
                    [3, __('landing.s3_title'), __('landing.s3_desc')],
                ] as $step)
                    <article class="v-card"><div class="v-step-num">{{ $step[0] }}</div><h3>{{ $step[1] }}</h3><p>{{ $step[2] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="v-section">
        <div class="v-wrap">
            <div class="v-section-head center">
                <span class="v-chip">{{ __('landing.pricing_badge') }}</span>
                <h2 class="v-h2">{{ __('landing.pricing_title') }} <span class="v-title-accent">{{ __('landing.pricing_title_hl') }}</span></h2>
                <p class="v-muted mt-4 text-lg leading-8">{{ __('landing.pricing_one_plan') }}</p>
            </div>
            <div class="v-pricing-wrap">
                <div class="v-pricing-card">
                    <div>
                        <div class="font-bold text-[#07142C]">{{ $appLabel }}</div>
                        <div class="v-price">{{ $pricing['formatted_price'] ?? '—' }}</div>
                        <div class="v-muted mt-2">{{ __('landing.pricing_per_mo') }}</div>
                        <p class="v-muted mt-4 text-sm">{{ __('landing.pricing_no_card_trial') }}</p>
                        <a href="{{ route('signup') }}" class="v-btn v-btn-primary mt-6">{{ __('landing.pricing_start_trial', ['days' => $trialDays ?? 14]) }} <span aria-hidden="true">→</span></a>
                    </div>
                    <div class="rounded-2xl border border-[#D9E1EC] bg-[#F7F9FC] p-5">
                        <div class="font-bold text-[#07142C]">{{ __('landing.whats_included') }}</div>
                        <div class="v-feature-list mt-5 text-sm v-muted">
                            @foreach([
                                __('landing.pricing_feat_scheduling'), __('landing.pricing_feat_queue'), __('landing.pricing_feat_staff'),
                                __('landing.pricing_feat_analytics'), __('landing.pricing_feat_reminders'), __('landing.pricing_feat_languages')
                            ] as $item)
                                <div class="flex gap-2 min-w-0"><span class="v-check">✓</span><span>{{ $item }}</span></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="v-section" style="border-top:0;padding-top:0">
        <div class="v-wrap">
            <div class="v-cta">
                <h2>{{ __('landing.cta_title') }}</h2>
                <p class="mt-4 text-base sm:text-lg leading-8">{{ __('landing.cta_sub') }}</p>
                <a href="{{ route('signup') }}" class="v-btn mt-7 bg-white text-[#000520] border border-white hover:bg-[#EEF2F7]">{{ __('landing.cta_button') }} <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>
</div>
@endsection
