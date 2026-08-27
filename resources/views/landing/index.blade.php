@extends('layouts.landing')

@push('styles')
<style>
    .velora-home {
        --vh-ink: #0D1226;
        --vh-muted: #4B5563;
        --vh-bg: #F5F7FA;
        --vh-surface: #FFFFFF;
        --vh-border: #E5E7EB;
        --vh-purple: #6D46FF;
        --vh-blue: #006CFF;
        --vh-cyan: #00B8FF;
        --vh-sky: #1677FF;
        --vh-mint: #00D4A3;
        --vh-gradient: linear-gradient(135deg,#6D46FF 0%,#006CFF 52%,#00B8FF 100%);
        background: var(--vh-bg);
        color: var(--vh-ink);
        overflow: clip;
    }
    .velora-home * { box-sizing: border-box; }
    .vh-container { width:min(1120px,calc(100% - 32px)); margin-inline:auto; }
    .vh-section { padding:88px 0; }
    .vh-section:nth-of-type(even) { background:var(--vh-surface); }
    .vh-section-head { max-width:680px; }
    .vh-section-head.center { margin-inline:auto; text-align:center; }
    .vh-kicker { display:inline-flex; align-items:center; gap:8px; color:var(--vh-blue); font-size:12px; font-weight:800; }
    .vh-kicker::before { content:""; width:20px; height:2px; border-radius:99px; background:var(--vh-gradient); }
    .vh-section-head h2 { margin:14px 0 0; font-size:clamp(34px,5vw,54px); line-height:1.02; letter-spacing:-.045em; font-weight:800; }
    .vh-section-head p { margin:16px 0 0; color:var(--vh-muted); font-size:16px; line-height:1.8; }

    .vh-hero { position:relative; padding:72px 0 84px; background:var(--vh-surface); }
    .vh-hero::before { content:""; position:absolute; inset:auto -120px 0 auto; width:360px; height:360px; border-radius:50%; background:radial-gradient(circle,rgba(0,184,255,.14),rgba(0,184,255,0) 68%); pointer-events:none; }
    .vh-hero-grid { position:relative; display:grid; grid-template-columns:minmax(0,1fr) minmax(0,.92fr); gap:56px; align-items:center; }
    .vh-badge { display:inline-flex; align-items:center; gap:8px; min-height:34px; padding:0 12px; border:1px solid rgba(22,119,255,.16); border-radius:999px; background:linear-gradient(180deg,#fff,#f9faff); color:var(--vh-blue); font-size:12px; font-weight:800; }
    .vh-badge i { width:7px; height:7px; border-radius:50%; background:var(--vh-gradient); }
    .vh-hero h1 { margin:20px 0 0; max-width:680px; font-size:clamp(44px,6.2vw,74px); line-height:1.01; letter-spacing:-.06em; font-weight:800; }
    .vh-hero h1 span { background:var(--vh-gradient); -webkit-background-clip:text; background-clip:text; color:transparent; }
    .vh-lead { max-width:620px; margin:20px 0 0; color:var(--vh-muted); font-size:18px; line-height:1.8; }
    .vh-actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:28px; }
    .vh-btn { min-height:50px; display:inline-flex; align-items:center; justify-content:center; gap:9px; padding:0 18px; border-radius:13px; font-size:13px; font-weight:800; transition:transform .2s,box-shadow .2s,background .2s; }
    .vh-btn-primary { color:#fff; background:var(--vh-gradient); border:1px solid transparent; box-shadow:0 14px 32px rgba(0,108,255,.18); }
    .vh-btn-primary:hover { transform:translateY(-1px); box-shadow:0 18px 40px rgba(0,108,255,.24); }
    .vh-btn-secondary { color:var(--vh-ink); background:#fff; border:1px solid var(--vh-border); }
    .vh-btn-secondary:hover { transform:translateY(-1px); border-color:#cbd5e1; background:#f9fbff; }
    .vh-trust { display:flex; flex-wrap:wrap; gap:10px 18px; margin-top:20px; color:#6B7280; font-size:11px; font-weight:700; }

    .vh-preview { position:relative; border:1px solid #dfe5ef; border-radius:24px; background:#fff; box-shadow:0 24px 70px rgba(13,18,38,.10); overflow:hidden; }
    .vh-preview-top { display:flex; align-items:center; gap:7px; padding:12px 14px; background:#0D1226; }
    .vh-preview-top i { width:7px; height:7px; border-radius:50%; background:#fff; opacity:.45; }
    .vh-preview-url { flex:1; text-align:center; color:#b9c3d5; font-size:10px; }
    .vh-preview-main { padding:16px; background:#fbfcff; }
    .vh-preview-header { display:flex; align-items:end; justify-content:space-between; gap:12px; margin-bottom:14px; }
    .vh-preview-brand { display:flex; align-items:center; gap:9px; }
    .vh-preview-brand img { width:32px; height:32px; object-fit:contain; border-radius:9px; }
    .vh-preview-brand small { display:block; color:#7B8799; font-size:9px; }
    .vh-preview-brand strong { display:block; margin-top:2px; color:var(--vh-ink); font-size:13px; }
    .vh-preview-status { padding:6px 9px; border-radius:999px; background:#E9FFF8; color:#038567; font-size:9px; font-weight:800; }
    .vh-preview-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:9px; }
    .vh-stat { padding:13px; border:1px solid var(--vh-border); border-radius:14px; background:#fff; }
    .vh-stat span { display:block; color:#8A95A7; font-size:9px; font-weight:800; }
    .vh-stat strong { display:block; margin-top:7px; color:var(--vh-ink); font-size:22px; line-height:1; }
    .vh-stat.accent strong { color:var(--vh-blue); }
    .vh-table { margin-top:10px; padding:12px 14px; border:1px solid var(--vh-border); border-radius:14px; background:#fff; }
    .vh-table-row { display:grid; grid-template-columns:1.3fr .7fr .5fr; gap:10px; align-items:center; min-height:28px; border-bottom:1px solid #f0f2f6; }
    .vh-table-row:last-child { border-bottom:0; }
    .vh-table-row b,.vh-table-row em,.vh-table-row i { display:block; height:7px; border-radius:99px; background:#e4e9f0; }
    .vh-table-row b { width:72%; }
    .vh-table-row em { width:85%; }
    .vh-table-row i { width:60%; justify-self:end; }

    .vh-features { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-top:38px; }
    .vh-card { padding:22px; border:1px solid var(--vh-border); border-radius:18px; background:var(--vh-surface); box-shadow:0 10px 28px rgba(13,18,38,.035); }
    .vh-icon { width:42px; height:42px; display:grid; place-items:center; border-radius:12px; color:var(--vh-blue); background:linear-gradient(135deg,rgba(109,70,255,.11),rgba(0,184,255,.12)); font-weight:900; }
    .vh-card h3 { margin-top:16px; font-size:17px; font-weight:800; }
    .vh-card p { margin-top:8px; color:var(--vh-muted); font-size:13px; line-height:1.7; }

    .vh-steps { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-top:38px; }
    .vh-step { padding:24px; border:1px solid var(--vh-border); border-radius:18px; background:#fff; }
    .vh-step-no { width:36px; height:36px; display:grid; place-items:center; border-radius:50%; color:#fff; background:var(--vh-gradient); font-size:12px; font-weight:900; }
    .vh-step h3 { margin-top:16px; font-size:17px; font-weight:800; }
    .vh-step p { margin-top:8px; color:var(--vh-muted); font-size:13px; line-height:1.7; }

    .vh-price-wrap { margin-top:38px; }
    .vh-price { display:grid; grid-template-columns:.8fr 1.2fr; gap:22px; padding:28px; border:1px solid var(--vh-border); border-radius:22px; background:#fff; box-shadow:0 18px 48px rgba(13,18,38,.055); }
    .vh-price-main { display:flex; flex-direction:column; justify-content:center; }
    .vh-price-logo { width:46px; height:46px; display:grid; place-items:center; border:1px solid var(--vh-border); border-radius:13px; background:#fff; }
    .vh-price-logo img { max-width:30px; max-height:30px; }
    .vh-price-label { margin-top:18px; color:#718096; font-size:11px; font-weight:800; }
    .vh-price-number { margin-top:6px; font-size:clamp(48px,6vw,68px); line-height:.95; letter-spacing:-.06em; font-weight:800; }
    .vh-price-note { margin-top:10px; color:var(--vh-muted); font-size:12px; }
    .vh-price-list { display:grid; grid-template-columns:1fr 1fr; gap:10px 18px; align-content:center; padding:18px 0 18px 22px; border-left:1px solid var(--vh-border); }
    .vh-check { display:flex; gap:8px; color:#445269; font-size:12px; line-height:1.5; }
    .vh-check::before { content:"✓"; color:var(--vh-mint); font-weight:900; }

    .vh-final { margin-top:88px; padding:72px 20px; border-radius:26px; background:var(--vh-ink); text-align:center; position:relative; overflow:hidden; }
    .vh-final::before { content:""; position:absolute; width:420px; height:420px; inset:-140px 50% auto auto; transform:translateX(50%); border-radius:50%; background:radial-gradient(circle,rgba(0,184,255,.18),rgba(0,184,255,0) 66%); }
    .vh-final > * { position:relative; }
    .vh-final h2 { color:#fff; font-size:clamp(34px,5vw,52px); line-height:1.03; letter-spacing:-.045em; font-weight:800; }
    .vh-final p { max-width:620px; margin:14px auto 0; color:#aeb8cc; font-size:15px; line-height:1.8; }
    .vh-final .vh-btn-secondary { background:rgba(255,255,255,.06); border-color:rgba(255,255,255,.14); color:#fff; }

    html[data-theme="dark"] .velora-home { --vh-bg:#080B18; --vh-surface:#0D1226; --vh-ink:#F8FAFC; --vh-muted:#A7B0C0; --vh-border:#252E45; }
    html[data-theme="dark"] .vh-hero { background:#080B18; }
    html[data-theme="dark"] .vh-badge,.html[data-theme="dark"] .vh-btn-secondary,.html[data-theme="dark"] .vh-preview-main,.html[data-theme="dark"] .vh-stat,.html[data-theme="dark"] .vh-table,.html[data-theme="dark"] .vh-card,.html[data-theme="dark"] .vh-step,.html[data-theme="dark"] .vh-price,.html[data-theme="dark"] .vh-price-logo { background:#0D1226; }
    html[data-theme="dark"] .vh-preview-main { background:#0A0F1F; }
    html[data-theme="dark"] .vh-stat span,.html[data-theme="dark"] .vh-preview-brand small { color:#8D99AF; }
    html[data-theme="dark"] .vh-check { color:#C3CCDA; }

    @media (max-width:960px) {
        .vh-hero-grid,.vh-price { grid-template-columns:1fr; }
        .vh-price-list { border-left:0; border-top:1px solid var(--vh-border); padding:20px 0 0; }
    }
    @media (max-width:720px) {
        .vh-container { width:calc(100% - 20px); }
        .vh-section { padding:64px 0; }
        .vh-hero { padding:46px 0 58px; }
        .vh-hero-grid { gap:34px; }
        .vh-hero h1 { font-size:clamp(38px,12vw,56px); }
        .vh-lead { font-size:16px; }
        .vh-actions { flex-direction:column; }
        .vh-btn { width:100%; }
        .vh-features,.vh-steps { grid-template-columns:1fr; }
        .vh-preview-stats { grid-template-columns:1fr; }
        .vh-price { padding:20px; }
        .vh-price-list { grid-template-columns:1fr; }
        .vh-final { margin-top:64px; padding:56px 16px; }
    }
    @media (max-width:400px) {
        .vh-container { width:calc(100% - 16px); }
        .vh-card,.vh-step { padding:19px; }
    }
    @media (prefers-reduced-motion:reduce) { *,*::before,*::after { transition:none!important; } }
</style>
@endpush

@section('content')
<div class="velora-home">
    <section class="vh-hero">
        <div class="vh-container vh-hero-grid">
            <div>
                <span class="vh-badge"><i></i>{{ __('landing.hero_badge', ['days' => $trialDays ?? 14]) }}</span>
                <h1>{{ __('landing.hero_headline_1') }} <span>{{ __('landing.hero_headline_2') }} {{ __('landing.hero_headline_hl') }}</span></h1>
                <p class="vh-lead">{{ __('landing.hero_sub') }}</p>
                <div class="vh-actions">
                    @if ($registrationEnabled ?? true)
                        <a class="vh-btn vh-btn-primary" href="{{ route('signup') }}">{{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }} <span>→</span></a>
                    @endif
                    <a class="vh-btn vh-btn-secondary" href="#how-it-works">{{ __('landing.hero_cta_how') }}</a>
                </div>
                <div class="vh-trust">
                    <span>✓ {{ __('landing.trust_no_card') }}</span>
                    <span>✓ {{ __('landing.trust_setup') }}</span>
                    <span>✓ {{ __('landing.trust_cancel') }}</span>
                    <span>✓ {{ __('landing.trust_languages') }}</span>
                </div>
            </div>

            <div class="vh-preview">
                <div class="vh-preview-top"><i></i><i></i><i></i><div class="vh-preview-url">app.velora</div></div>
                <div class="vh-preview-main">
                    <div class="vh-preview-header">
                        <div class="vh-preview-brand">
                            <img src="{{ asset('logo.png') }}" alt="Velora" />
                            <div><small>Velora</small><strong>{{ __('landing.ticker_scheduling') }}</strong></div>
                        </div>
                        <span class="vh-preview-status">{{ __('landing.ticker_uptime') }}</span>
                    </div>
                    <div class="vh-preview-stats">
                        <div class="vh-stat accent"><span>{{ __('landing.ticker_scheduling') }}</span><strong>24</strong></div>
                        <div class="vh-stat"><span>{{ __('landing.ticker_queue') }}</span><strong>07</strong></div>
                        <div class="vh-stat"><span>{{ __('landing.ticker_custom') }}</span><strong>100%</strong></div>
                    </div>
                    <div class="vh-table">
                        <div class="vh-table-row"><b></b><em></em><i></i></div>
                        <div class="vh-table-row"><b></b><em></em><i></i></div>
                        <div class="vh-table-row"><b></b><em></em><i></i></div>
                        <div class="vh-table-row"><b></b><em></em><i></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="vh-section">
        <div class="vh-container">
            <div class="vh-section-head center">
                <div class="vh-kicker">{{ __('landing.features_badge') }}</div>
                <h2>{{ __('landing.features_title') }} <span>{{ __('landing.features_title_hl') }}</span></h2>
                <p>{{ __('landing.features_sub') }}</p>
            </div>
            <div class="vh-features">
                @foreach ([1,2,3,4,5,6] as $i)
                    <article class="vh-card">
                        <div class="vh-icon">{{ sprintf('%02d', $i) }}</div>
                        <h3>{{ __('landing.f'.$i.'_title') }}</h3>
                        <p>{{ __('landing.f'.$i.'_desc') }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="vh-section">
        <div class="vh-container">
            <div class="vh-section-head center">
                <div class="vh-kicker">{{ __('landing.how_badge') }}</div>
                <h2>{{ __('landing.how_title') }} <span>{{ __('landing.how_title_hl') }}</span></h2>
                <p>{{ __('landing.how_sub') }}</p>
            </div>
            <div class="vh-steps">
                @foreach ([1,2,3] as $i)
                    <article class="vh-step">
                        <div class="vh-step-no">{{ $i }}</div>
                        <h3>{{ __('landing.s'.$i.'_title') }}</h3>
                        <p>{{ __('landing.s'.$i.'_desc') }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="vh-section">
        <div class="vh-container">
            <div class="vh-section-head center">
                <div class="vh-kicker">{{ __('landing.pricing_badge') }}</div>
                <h2>{{ __('landing.pricing_title') }} <span>{{ __('landing.pricing_title_hl') }}</span></h2>
                <p>{{ __('landing.pricing_one_plan') }}</p>
            </div>
            <div class="vh-price-wrap">
                <div class="vh-price">
                    <div class="vh-price-main">
                        <div class="vh-price-logo"><img src="{{ asset('logo.png') }}" alt="Velora" /></div>
                        <div class="vh-price-label">{{ __('landing.pricing_platform') }}</div>
                        <div class="vh-price-number">$0</div>
                        <div class="vh-price-note">{{ __('landing.pricing_no_card_trial') }}</div>
                        @if ($registrationEnabled ?? true)
                            <div class="vh-actions"><a class="vh-btn vh-btn-primary" href="{{ route('signup') }}">{{ __('landing.pricing_start_trial', ['days' => $trialDays ?? 14]) }} →</a></div>
                        @endif
                    </div>
                    <div class="vh-price-list">
                        @foreach (['scheduling','queue','staff','analytics','reminders','languages'] as $feature)
                            <div class="vh-check">{{ __('landing.pricing_feat_'.$feature) }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="vh-final">
                <h2>{{ __('landing.cta_title') }}</h2>
                <p>{{ __('landing.cta_sub') }}</p>
                @if ($registrationEnabled ?? true)
                    <div class="vh-actions" style="justify-content:center">
                        <a class="vh-btn vh-btn-primary" href="{{ route('signup') }}">{{ __('landing.cta_button') }} →</a>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
