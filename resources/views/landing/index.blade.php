@extends('layouts.landing')

@push('styles')
<style>
    .velora-home {
        --v-navy-950: #000520;
        --v-navy-900: #07142c;
        --v-navy-800: #102349;
        --v-navy-700: #183461;
        --v-ink: #0b1630;
        --v-muted: #66748b;
        --v-line: #d9e1ec;
        --v-soft: #f6f8fb;
        --v-white: #ffffff;
        color: var(--v-ink);
        background: var(--v-soft);
    }

    .velora-home * { box-sizing: border-box; }
    .v-container { width: min(1120px, calc(100% - 32px)); margin: 0 auto; }
    .v-section { padding: 84px 0; }
    .v-section + .v-section { border-top: 1px solid var(--v-line); }

    .v-hero { padding: 64px 0 72px; background: #fff; }
    .v-hero-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(420px, .9fr); gap: 56px; align-items: center; }
    .v-eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 7px 11px; border: 1px solid var(--v-line); border-radius: 999px; background: #fff; color: var(--v-navy-900); font-size: 11px; font-weight: 800; }
    .v-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--v-navy-900); }
    .v-hero h1 { margin: 20px 0 0; max-width: 700px; color: var(--v-navy-950); font-size: clamp(42px, 6vw, 72px); line-height: 1.03; letter-spacing: -.055em; font-weight: 800; }
    .v-hero h1 span { color: var(--v-navy-800); }
    .v-lead { margin-top: 20px; max-width: 620px; color: var(--v-muted); font-size: 18px; line-height: 1.8; }
    .v-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
    .v-btn { min-height: 48px; display: inline-flex; align-items: center; justify-content: center; gap: 9px; padding: 0 18px; border-radius: 12px; font-size: 13px; font-weight: 800; transition: .2s ease; }
    .v-btn-primary { color: #fff; background: var(--v-navy-900); border: 1px solid var(--v-navy-900); box-shadow: 0 12px 24px rgba(7,20,44,.12); }
    .v-btn-primary:hover { background: var(--v-navy-800); transform: translateY(-1px); }
    .v-btn-secondary { color: var(--v-navy-900); background: #fff; border: 1px solid var(--v-line); }
    .v-btn-secondary:hover { background: #f0f3f7; transform: translateY(-1px); }
    .v-trust { display: flex; flex-wrap: wrap; gap: 14px 20px; margin-top: 20px; color: #738096; font-size: 11px; font-weight: 700; }

    .v-product { border: 1px solid var(--v-navy-800); border-radius: 22px; overflow: hidden; background: var(--v-navy-950); box-shadow: 0 24px 60px rgba(0,5,32,.15); }
    .v-product-bar { display: flex; align-items: center; gap: 6px; padding: 12px 14px; border-bottom: 1px solid rgba(255,255,255,.08); background: var(--v-navy-900); }
    .v-product-bar i { width: 7px; height: 7px; border-radius: 50%; background: #fff; opacity: .55; }
    .v-product-url { flex: 1; text-align: center; color: #c6d0e0; font-size: 10px; }
    .v-product-body { padding: 18px; background: #f5f7fb; }
    .v-product-head { display: flex; align-items: end; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
    .v-product-kicker { color: #76849a; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; }
    .v-product-title { margin-top: 4px; color: var(--v-navy-900); font-size: 18px; font-weight: 800; }
    .v-product-badge { padding: 6px 9px; border: 1px solid #d7dfeb; border-radius: 9px; background: #fff; color: var(--v-navy-900); font-size: 9px; font-weight: 800; }
    .v-product-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .v-product-stat { padding: 14px; border: 1px solid var(--v-line); border-radius: 14px; background: #fff; }
    .v-product-stat small { color: #7c899d; font-size: 9px; font-weight: 800; text-transform: uppercase; }
    .v-product-stat strong { display: block; margin-top: 7px; color: var(--v-navy-900); font-size: 22px; line-height: 1; }
    .v-product-table { margin-top: 10px; padding: 14px; border: 1px solid var(--v-line); border-radius: 14px; background: #fff; }
    .v-row { display: grid; grid-template-columns: 1.2fr .7fr .6fr; gap: 8px; align-items: center; padding: 8px 0; border-bottom: 1px solid #edf0f4; }
    .v-row:last-child { border-bottom: 0; }
    .v-row span { display: block; height: 8px; border-radius: 999px; background: #e3e8ef; }
    .v-row span:first-child { background: #d6deea; }
    .v-row span:last-child { width: 70%; justify-self: end; }

    .v-head { max-width: 720px; }
    .v-head.center { margin: 0 auto; text-align: center; }
    .v-head h2 { margin-top: 16px; color: var(--v-navy-950); font-size: clamp(34px, 4vw, 52px); line-height: 1.06; letter-spacing: -.045em; font-weight: 800; }
    .v-head p { margin-top: 14px; color: var(--v-muted); font-size: 16px; line-height: 1.8; }

    .v-features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 36px; }
    .v-card { padding: 22px; border: 1px solid var(--v-line); border-radius: 17px; background: #fff; }
    .v-icon { width: 38px; height: 38px; display: grid; place-items: center; border: 1px solid #d6deea; border-radius: 11px; background: #eef2f7; color: var(--v-navy-900); font-weight: 900; }
    .v-card h3 { margin-top: 15px; color: var(--v-navy-900); font-size: 16px; font-weight: 800; }
    .v-card p { margin-top: 8px; color: var(--v-muted); font-size: 13px; line-height: 1.7; }

    .v-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 36px; }
    .v-step { position: relative; padding: 24px; border: 1px solid var(--v-line); border-radius: 17px; background: #fff; }
    .v-step::after { content: ''; position: absolute; top: 43px; right: -9px; width: 18px; height: 1px; background: #cad3df; }
    .v-step:last-child::after { display: none; }
    .v-step-num { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 50%; background: var(--v-navy-900); color: #fff; font-size: 12px; font-weight: 900; }
    .v-step h3 { margin-top: 16px; color: var(--v-navy-900); font-size: 16px; font-weight: 800; }
    .v-step p { margin-top: 8px; color: var(--v-muted); font-size: 13px; line-height: 1.7; }

    .v-price { display: grid; grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr); gap: 24px; align-items: stretch; margin-top: 36px; padding: 26px; border: 1px solid var(--v-line); border-radius: 22px; background: #fff; box-shadow: 0 18px 44px rgba(7,20,44,.05); }
    .v-price-main { display: flex; flex-direction: column; justify-content: center; }
    .v-price-brand { display: flex; align-items: center; gap: 10px; }
    .v-price-logo { width: 42px; height: 42px; display: grid; place-items: center; border: 1px solid var(--v-line); border-radius: 12px; background: #fff; }
    .v-price-logo img { max-width: 29px; max-height: 29px; }
    .v-price-name { color: var(--v-navy-900); font-size: 14px; font-weight: 800; }
    .v-price-sub { color: var(--v-muted); font-size: 10px; }
    .v-price-number { margin-top: 22px; color: var(--v-navy-950); font-size: clamp(48px, 7vw, 68px); line-height: .95; font-weight: 850; letter-spacing: -.06em; }
    .v-price-note { margin-top: 9px; color: var(--v-navy-800); font-size: 12px; font-weight: 700; }
    .v-price-features { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 18px; align-content: center; padding: 20px; border-left: 1px solid var(--v-line); }
    .v-check { display: flex; gap: 8px; color: #42516a; font-size: 12px; line-height: 1.5; }
    .v-check::before { content: '✓'; color: var(--v-navy-700); font-weight: 900; }
    .v-price-link { margin-top: 20px; display: inline-flex; color: var(--v-navy-900); font-size: 12px; font-weight: 800; text-decoration: underline; text-underline-offset: 3px; }

    .v-final { padding: 66px 20px; border-radius: 24px; background: var(--v-navy-950); text-align: center; }
    .v-final h2 { color: #fff; font-size: clamp(32px, 4vw, 48px); line-height: 1.06; letter-spacing: -.04em; font-weight: 800; }
    .v-final p { max-width: 620px; margin: 14px auto 0; color: #aeb9cc; font-size: 15px; line-height: 1.8; }
    .v-final .v-btn-secondary { border-color: rgba(255,255,255,.18); background: transparent; color: #fff; }
    .v-final .v-btn-secondary:hover { background: rgba(255,255,255,.08); }

    @media (max-width: 960px) {
        .v-hero-grid, .v-price { grid-template-columns: 1fr; }
        .v-price-features { border-left: 0; border-top: 1px solid var(--v-line); padding: 20px 0 0; }
    }
    @media (max-width: 720px) {
        .v-container { width: calc(100% - 20px); }
        .v-section { padding: 64px 0; }
        .v-hero { padding: 42px 0 56px; }
        .v-hero-grid { gap: 34px; }
        .v-hero h1 { font-size: clamp(36px, 12vw, 54px); }
        .v-lead { font-size: 16px; line-height: 1.7; }
        .v-actions { flex-direction: column; }
        .v-btn { width: 100%; }
        .v-product-grid { grid-template-columns: 1fr; }
        .v-features, .v-steps { grid-template-columns: 1fr; }
        .v-step::after { display: none; }
        .v-price { padding: 20px; }
        .v-price-features { grid-template-columns: 1fr; }
        .v-trust { gap: 10px 14px; }
    }
    @media (max-width: 400px) {
        .v-container { width: calc(100% - 16px); }
        .v-card, .v-step { padding: 19px; }
        .v-final { padding: 52px 16px; }
    }
</style>
@endpush

@section('content')
<div class="velora-home">
    <section class="v-hero">
        <div class="v-container v-hero-grid">
            <div>
                <span class="v-eyebrow"><span class="v-dot"></span>{{ __('landing.hero_badge', ['days' => $trialDays ?? 14]) }}</span>
                <h1>
                    {{ __('landing.hero_headline_1') }}
                    <span>{{ __('landing.hero_headline_2') }} {{ __('landing.hero_headline_hl') }}</span>
                </h1>
                <p class="v-lead">{{ __('landing.hero_sub') }}</p>
                <div class="v-actions">
                    <a href="{{ route('signup') }}" class="v-btn v-btn-primary">{{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }} <span aria-hidden="true">→</span></a>
                    <a href="#how-it-works" class="v-btn v-btn-secondary">{{ __('landing.hero_cta_how') }}</a>
                </div>
                <div class="v-trust">
                    <span>✓ {{ __('landing.trust_no_card') }}</span>
                    <span>✓ {{ __('landing.trust_setup') }}</span>
                    <span>✓ {{ __('landing.trust_cancel') }}</span>
                    <span>✓ {{ __('landing.trust_languages') }}</span>
                </div>
            </div>

            <div class="v-product" aria-label="Velora product preview">
                <div class="v-product-bar">
                    <i></i><i></i><i></i>
                    <div class="v-product-url">app.velora</div>
                </div>
                <div class="v-product-body">
                    <div class="v-product-head">
                        <div>
                            <div class="v-product-kicker">Velora</div>
                            <div class="v-product-title">{{ __('landing.ticker_scheduling') }}</div>
                        </div>
                        <div class="v-product-badge">99.9%</div>
                    </div>
                    <div class="v-product-grid">
                        <div class="v-product-stat"><small>{{ __('landing.ticker_scheduling') }}</small><strong>24</strong></div>
                        <div class="v-product-stat"><small>{{ __('landing.ticker_queue') }}</small><strong>07</strong></div>
                        <div class="v-product-stat"><small>{{ __('landing.ticker_uptime') }}</small><strong>99.9%</strong></div>
                    </div>
                    <div class="v-product-table">
                        <div class="v-row"><span></span><span></span><span></span></div>
                        <div class="v-row"><span></span><span></span><span></span></div>
                        <div class="v-row"><span></span><span></span><span></span></div>
                        <div class="v-row"><span></span><span></span><span></span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="v-section">
        <div class="v-container">
            <div class="v-head center">
                <span class="v-eyebrow">{{ __('landing.features_badge') }}</span>
                <h2>{{ __('landing.features_title') }} <span>{{ __('landing.features_title_hl') }}</span></h2>
                <p>{{ __('landing.features_sub') }}</p>
            </div>
            <div class="v-features">
                @foreach([
                    ['◷', __('landing.f1_title'), __('landing.f1_desc')],
                    ['#', __('landing.f2_title'), __('landing.f2_desc')],
                    ['◎', __('landing.f3_title'), __('landing.f3_desc')],
                    ['↗', __('landing.f4_title'), __('landing.f4_desc')],
                    ['↻', __('landing.f5_title'), __('landing.f5_desc')],
                    ['⌘', __('landing.f6_title'), __('landing.f6_desc')],
                ] as $feature)
                    <article class="v-card">
                        <div class="v-icon">{{ $feature[0] }}</div>
                        <h3>{{ $feature[1] }}</h3>
                        <p>{{ $feature[2] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="v-section" style="background:#fff">
        <div class="v-container">
            <div class="v-head center">
                <span class="v-eyebrow">{{ __('landing.how_badge') }}</span>
                <h2>{{ __('landing.how_title') }} <span>{{ __('landing.how_title_hl') }}</span></h2>
                <p>{{ __('landing.how_sub') }}</p>
            </div>
            <div class="v-steps">
                @foreach([
                    [1, __('landing.s1_title'), __('landing.s1_desc')],
                    [2, __('landing.s2_title'), __('landing.s2_desc')],
                    [3, __('landing.s3_title'), __('landing.s3_desc')],
                ] as $step)
                    <article class="v-step">
                        <div class="v-step-num">{{ $step[0] }}</div>
                        <h3>{{ $step[1] }}</h3>
                        <p>{{ $step[2] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="v-section">
        <div class="v-container">
            <div class="v-head center">
                <span class="v-eyebrow">{{ __('landing.pricing_badge') }}</span>
                <h2>{{ __('landing.pricing_title') }} <span>{{ __('landing.pricing_title_hl') }}</span></h2>
                <p>{{ __('landing.pricing_one_plan') }} — {{ __('landing.pricing_after_trial') }}</p>
            </div>

            <div class="v-price">
                <div class="v-price-main">
                    <div class="v-price-brand">
                        <div class="v-price-logo"><img src="{{ asset('logo.png') }}" alt="Velora"></div>
                        <div>
                            <div class="v-price-name">{{ $appName ?? 'Velora' }}</div>
                            <div class="v-price-sub">{{ __('landing.pricing_platform') }}</div>
                        </div>
                    </div>
                    <div class="v-price-number">{{ $pricing['formatted_price'] }}</div>
                    <div class="v-price-note">✓ {{ __('landing.pricing_no_card_trial') }}</div>
                    @if($registrationEnabled ?? true)
                        <a href="{{ route('signup') }}" class="v-btn v-btn-primary" style="margin-top:20px">{{ __('landing.pricing_start_trial', ['days' => $trialDays ?? 14]) }} <span aria-hidden="true">→</span></a>
                    @endif
                    <a href="{{ url('/pricing') }}" class="v-price-link">{{ __('landing.pricing_badge') }}</a>
                </div>
                <div class="v-price-features">
                    @foreach([
                        __('landing.pricing_feat_scheduling'),
                        __('landing.pricing_feat_queue'),
                        __('landing.pricing_feat_staff'),
                        __('landing.pricing_feat_analytics'),
                        __('landing.pricing_feat_reminders'),
                        __('landing.pricing_feat_languages'),
                        __('landing.pricing_feat_booking'),
                        __('landing.pricing_feat_support'),
                    ] as $feature)
                        <div class="v-check">{{ $feature }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="v-section" style="background:#fff">
        <div class="v-container">
            <div class="v-final">
                <h2>{{ __('landing.cta_title') }}</h2>
                <p>{{ __('landing.cta_sub') }}</p>
                <div class="v-actions" style="justify-content:center">
                    @if($registrationEnabled ?? true)
                        <a href="{{ route('signup') }}" class="v-btn v-btn-primary" style="background:#fff;color:var(--v-navy-950);border-color:#fff">{{ __('landing.cta_button') }} <span aria-hidden="true">→</span></a>
                    @endif
                    <a href="#features" class="v-btn v-btn-secondary">{{ __('landing.hero_cta_how') }}</a>
                </div>
                <div class="v-trust" style="justify-content:center;color:#9eabc0"><span>✓ {{ __('landing.cta_note') }}</span></div>
            </div>
        </div>
    </section>
</div>
@endsection
