@extends('layouts.landing')

@push('styles')
<style>
    .velora-home {
        --c-ink:#0D1226;
        --c-muted:#657187;
        --c-border:#E5E7EB;
        --c-soft:#F5F7FA;
        --c-white:#FFFFFF;
        --c-purple:#6D46FF;
        --c-blue:#006CFF;
        --c-cyan:#00B8FF;
        --c-mint:#00D4A3;
        --c-gradient:linear-gradient(100deg,#6D46FF 0%,#006CFF 52%,#00B8FF 100%);
        background:var(--c-white);
        color:var(--c-ink);
    }
    .velora-home *,.velora-home *::before,.velora-home *::after{box-sizing:border-box}
    .vh-wrap{width:min(1160px,calc(100% - 40px));margin-inline:auto}
    .vh-section{padding:88px 0}

    .vh-hero{padding:70px 0 86px;background:#fff}
    .vh-hero-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);gap:58px;align-items:center}
    .vh-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(109,70,255,.16);border-radius:999px;background:#fff;color:var(--c-blue);font-size:11px;font-weight:800}
    .vh-badge-dot{width:7px;height:7px;border-radius:50%;background:var(--c-gradient)}
    .vh-hero h1{margin:22px 0 0;max-width:720px;font-size:clamp(44px,6vw,74px);line-height:1.03;letter-spacing:-.06em;font-weight:800}
    .vh-hero h1 span{background:var(--c-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}
    .vh-lead{max-width:630px;margin:20px 0 0;color:var(--c-muted);font-size:18px;line-height:1.8}
    .vh-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px}
    .vh-btn{min-height:50px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 18px;border-radius:13px;font-size:13px;font-weight:800;transition:.18s ease}
    .vh-btn-primary{color:#fff;background:var(--c-gradient);box-shadow:0 14px 28px rgba(0,108,255,.17)}
    .vh-btn-primary:hover{transform:translateY(-1px);box-shadow:0 18px 34px rgba(0,108,255,.23)}
    .vh-btn-secondary{color:var(--c-ink);border:1px solid var(--c-border);background:#fff}
    .vh-btn-secondary:hover{background:var(--c-soft);transform:translateY(-1px)}
    .vh-trust{display:flex;flex-wrap:wrap;gap:10px 18px;margin-top:18px;color:#778397;font-size:11px;font-weight:700}

    .vh-preview{border-radius:24px;padding:12px;background:var(--c-gradient);box-shadow:0 28px 70px rgba(13,18,38,.16)}
    .vh-preview-shell{overflow:hidden;border-radius:17px;background:#fff}
    .vh-browser{display:flex;align-items:center;gap:6px;padding:12px 14px;background:#0D1226}
    .vh-browser i{width:7px;height:7px;border-radius:50%;background:rgba(255,255,255,.45)}
    .vh-browser-url{flex:1;text-align:center;color:#b9c3d5;font-size:10px}
    .vh-dashboard{padding:18px;background:#F8FAFD}
    .vh-dashboard-head{display:flex;align-items:end;justify-content:space-between;gap:12px;margin-bottom:14px}
    .vh-dashboard-brand{display:flex;align-items:center;gap:9px}
    .vh-dashboard-brand img{width:34px;height:34px;object-fit:contain;border-radius:9px}
    .vh-dashboard-brand small{display:block;color:#7A879A;font-size:9px}
    .vh-dashboard-brand strong{display:block;margin-top:2px;color:var(--c-ink);font-size:13px}
    .vh-status{padding:6px 9px;border-radius:999px;background:#EFFFF9;color:#008C6C;font-size:9px;font-weight:800}
    .vh-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
    .vh-stat{padding:14px;border:1px solid #E7ECF3;border-radius:14px;background:#fff}
    .vh-stat small{display:block;color:#8A96A8;font-size:8px;font-weight:800;text-transform:uppercase}
    .vh-stat strong{display:block;margin-top:7px;color:var(--c-ink);font-size:22px;line-height:1}
    .vh-stat.accent strong{color:var(--c-blue)}
    .vh-list{margin-top:10px;padding:8px 14px;border:1px solid #E7ECF3;border-radius:14px;background:#fff}
    .vh-list-row{display:grid;grid-template-columns:34px 1fr auto;gap:10px;align-items:center;min-height:46px;border-bottom:1px solid #EFF2F6}
    .vh-list-row:last-child{border-bottom:0}
    .vh-avatar{width:28px;height:28px;border-radius:9px;background:linear-gradient(135deg,#ECE6FF,#E4F4FF)}
    .vh-lines b,.vh-lines span{display:block;border-radius:999px;background:#DDE4EF}
    .vh-lines b{width:92px;height:7px}.vh-lines span{width:58px;height:6px;margin-top:6px}
    .vh-pill{width:52px;height:20px;border-radius:999px;background:#EEF2F7}

    .vh-heading{max-width:720px}.vh-heading.center{text-align:center;margin-inline:auto}
    .vh-kicker{color:var(--c-blue);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .vh-heading h2{margin:13px 0 0;font-size:clamp(34px,4.5vw,52px);line-height:1.05;letter-spacing:-.045em;font-weight:800}
    .vh-heading h2 span{background:var(--c-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}
    .vh-heading p{margin:14px 0 0;color:var(--c-muted);font-size:16px;line-height:1.8}

    .vh-features{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:38px}
    .vh-card{padding:24px;border:1px solid var(--c-border);border-radius:18px;background:#fff}
    .vh-card-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:12px;background:var(--c-gradient);color:#fff;font-weight:900;box-shadow:0 8px 18px rgba(0,108,255,.13)}
    .vh-card h3{margin-top:15px;font-size:16px;font-weight:800}
    .vh-card p{margin-top:8px;color:var(--c-muted);font-size:13px;line-height:1.75}

    .vh-how{background:var(--c-soft)}
    .vh-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:38px}
    .vh-step{padding:26px;border:1px solid var(--c-border);border-radius:18px;background:#fff}
    .vh-step-no{width:38px;height:38px;display:grid;place-items:center;border-radius:50%;background:var(--c-gradient);color:#fff;font-size:12px;font-weight:900}
    .vh-step h3{margin-top:16px;font-size:16px;font-weight:800}.vh-step p{margin-top:8px;color:var(--c-muted);font-size:13px;line-height:1.75}

    .vh-pricing{display:grid;grid-template-columns:minmax(0,.82fr) minmax(0,1.18fr);gap:28px;padding:30px;border:1px solid var(--c-border);border-radius:22px;background:#fff;box-shadow:0 18px 48px rgba(13,18,38,.05);margin-top:38px}
    .vh-price-label{color:#718096;font-size:11px;font-weight:800}.vh-price-number{margin-top:7px;font-size:clamp(50px,6vw,70px);line-height:.95;letter-spacing:-.06em;font-weight:800}
    .vh-price-note{margin-top:10px;color:var(--c-muted);font-size:12px}.vh-price-list{display:grid;grid-template-columns:1fr 1fr;gap:12px 20px;align-content:center;padding-inline-start:24px;border-inline-start:1px solid var(--c-border)}
    .vh-check{display:flex;gap:8px;color:#4B5563;font-size:12px;line-height:1.5}.vh-check::before{content:'✓';color:var(--c-mint);font-weight:900}

    .vh-final{margin-top:0;padding:72px 22px;border-radius:26px;background:var(--c-ink);text-align:center;overflow:hidden;position:relative}
    .vh-final::before{content:'';position:absolute;width:420px;height:420px;right:-120px;top:-220px;border-radius:50%;background:radial-gradient(circle,rgba(0,184,255,.18),transparent 67%)}
    .vh-final > *{position:relative}.vh-final h2{color:#fff;font-size:clamp(34px,4.7vw,50px);line-height:1.05;letter-spacing:-.045em;font-weight:800}.vh-final h2 span{background:var(--c-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}.vh-final p{max-width:620px;margin:14px auto 0;color:#AEB8CA;font-size:15px;line-height:1.8}

    html[data-theme="dark"] .velora-home{--c-ink:#F8FAFC;--c-muted:#A7B0C0;--c-border:#252E45;--c-soft:#0A0F1F;--c-white:#0D1226}
    html[data-theme="dark"] .vh-hero{background:#080B18}html[data-theme="dark"] .vh-card,html[data-theme="dark"] .vh-step,html[data-theme="dark"] .vh-pricing,html[data-theme="dark"] .vh-btn-secondary{background:#0D1226;color:var(--c-ink)}
    html[data-theme="dark"] .vh-preview-shell{background:#0D1226}html[data-theme="dark"] .vh-dashboard{background:#0A0F1F}html[data-theme="dark"] .vh-stat,html[data-theme="dark"] .vh-list{background:#11172A;border-color:#252E45}html[data-theme="dark"] .vh-stat small{color:#8D99AF}html[data-theme="dark"] .vh-lines b,html[data-theme="dark"] .vh-lines span,html[data-theme="dark"] .vh-pill{background:#27324A}html[data-theme="dark"] .vh-price-list{border-color:#252E45}html[data-theme="dark"] .vh-check{color:#C3CCDA}

    @media (max-width:980px){.vh-hero-grid,.vh-pricing{grid-template-columns:1fr}.vh-price-list{border-inline-start:0;border-top:1px solid var(--c-border);padding:22px 0 0}}
    @media (max-width:760px){.vh-wrap{width:calc(100% - 24px)}.vh-section{padding:68px 0}.vh-hero{padding:44px 0 64px}.vh-hero-grid{gap:34px}.vh-hero h1{font-size:clamp(38px,12vw,56px)}.vh-lead{font-size:16px}.vh-actions{flex-direction:column}.vh-btn{width:100%}.vh-features,.vh-steps{grid-template-columns:1fr}.vh-stats{grid-template-columns:1fr}.vh-price-list{grid-template-columns:1fr}.vh-final{padding:56px 18px}}
    @media (max-width:400px){.vh-wrap{width:calc(100% - 18px)}.vh-card,.vh-step{padding:20px}}
</style>
@endpush

@section('content')
<div class="velora-home">
    <section class="vh-hero">
        <div class="vh-wrap vh-hero-grid">
            <div>
                <span class="vh-badge"><span class="vh-badge-dot"></span>{{ __('landing.hero_badge', ['days' => $trialDays ?? 14]) }}</span>
                <h1>{{ __('landing.hero_headline_1') }} <span>{{ __('landing.hero_headline_2') }} {{ __('landing.hero_headline_hl') }}</span></h1>
                <p class="vh-lead">{{ __('landing.hero_sub') }}</p>
                <div class="vh-actions">
                    @if($registrationEnabled ?? true)<a class="vh-btn vh-btn-primary" href="{{ route('signup') }}">{{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }} <span>→</span></a>@endif
                    <a class="vh-btn vh-btn-secondary" href="#how-it-works">{{ __('landing.hero_cta_how') }}</a>
                </div>
                <div class="vh-trust"><span>✓ {{ __('landing.trust_no_card') }}</span><span>✓ {{ __('landing.trust_setup') }}</span><span>✓ {{ __('landing.trust_cancel') }}</span><span>✓ {{ __('landing.trust_languages') }}</span></div>
            </div>

            <div class="vh-preview" aria-hidden="true">
                <div class="vh-preview-shell">
                    <div class="vh-browser"><i></i><i></i><i></i><div class="vh-browser-url">app.velora</div></div>
                    <div class="vh-dashboard">
                        <div class="vh-dashboard-head"><div class="vh-dashboard-brand"><img src="{{ asset('logo.png') }}" alt=""><div><small>Velora</small><strong>{{ __('landing.ticker_scheduling') }}</strong></div></div><div class="vh-status">99.9%</div></div>
                        <div class="vh-stats"><div class="vh-stat"><small>{{ __('landing.ticker_scheduling') }}</small><strong>24</strong></div><div class="vh-stat accent"><small>{{ __('landing.ticker_queue') }}</small><strong>07</strong></div><div class="vh-stat"><small>{{ __('landing.ticker_uptime') }}</small><strong>99.9%</strong></div></div>
                        <div class="vh-list"><div class="vh-list-row"><span class="vh-avatar"></span><div class="vh-lines"><b></b><span></span></div><span class="vh-pill"></span></div><div class="vh-list-row"><span class="vh-avatar"></span><div class="vh-lines"><b></b><span></span></div><span class="vh-pill"></span></div><div class="vh-list-row"><span class="vh-avatar"></span><div class="vh-lines"><b></b><span></span></div><span class="vh-pill"></span></div><div class="vh-list-row"><span class="vh-avatar"></span><div class="vh-lines"><b></b><span></span></div><span class="vh-pill"></span></div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="vh-section">
        <div class="vh-wrap">
            <div class="vh-heading center"><div class="vh-kicker">{{ __('landing.features_badge') }}</div><h2>{{ __('landing.features_title') }} <span>{{ __('landing.features_title_hl') }}</span></h2><p>{{ __('landing.features_sub') }}</p></div>
            <div class="vh-features">
                @foreach([['◷',__('landing.f1_title'),__('landing.f1_desc')],['#',__('landing.f2_title'),__('landing.f2_desc')],['◎',__('landing.f3_title'),__('landing.f3_desc')],['↗',__('landing.f4_title'),__('landing.f4_desc')],['↻',__('landing.f5_title'),__('landing.f5_desc')],['⌘',__('landing.f6_title'),__('landing.f6_desc')]] as $feature)
                    <article class="vh-card"><div class="vh-card-icon">{{ $feature[0] }}</div><h3>{{ $feature[1] }}</h3><p>{{ $feature[2] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="vh-section vh-how">
        <div class="vh-wrap">
            <div class="vh-heading center"><div class="vh-kicker">{{ __('landing.how_badge') }}</div><h2>{{ __('landing.how_title') }} <span>{{ __('landing.how_title_hl') }}</span></h2><p>{{ __('landing.how_sub') }}</p></div>
            <div class="vh-steps">
                @foreach([[1,__('landing.s1_title'),__('landing.s1_desc')],[2,__('landing.s2_title'),__('landing.s2_desc')],[3,__('landing.s3_title'),__('landing.s3_desc')]] as $step)
                    <article class="vh-step"><div class="vh-step-no">{{ $step[0] }}</div><h3>{{ $step[1] }}</h3><p>{{ $step[2] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="vh-section">
        <div class="vh-wrap">
            <div class="vh-heading center"><div class="vh-kicker">{{ __('landing.pricing_badge') }}</div><h2>{{ __('landing.pricing_title') }} <span>{{ __('landing.pricing_title_hl') }}</span></h2><p>{{ __('landing.pricing_one_plan') }} — {{ __('landing.pricing_after_trial') }}</p></div>
            <div class="vh-pricing">
                <div>
                    <div class="vh-price-label">{{ $appName ?? 'Velora' }}</div>
                    <div class="vh-price-number">{{ $pricing['formatted_price'] }}</div>
                    <div class="vh-price-note">✓ {{ __('landing.pricing_no_card_trial') }}</div>
                    @if($registrationEnabled ?? true)<div class="vh-actions"><a class="vh-btn vh-btn-primary" href="{{ route('signup') }}">{{ __('landing.pricing_start_trial', ['days' => $trialDays ?? 14]) }} <span>→</span></a><a class="vh-btn vh-btn-secondary" href="{{ url('/pricing') }}">{{ __('landing.pricing_badge') }}</a></div>@endif
                </div>
                <div class="vh-price-list">
                    @foreach([__('landing.pricing_feat_scheduling'),__('landing.pricing_feat_queue'),__('landing.pricing_feat_staff'),__('landing.pricing_feat_analytics'),__('landing.pricing_feat_reminders'),__('landing.pricing_feat_languages'),__('landing.pricing_feat_booking'),__('landing.pricing_feat_support')] as $feature)<div class="vh-check">{{ $feature }}</div>@endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="vh-section" style="padding-top:10px">
        <div class="vh-wrap">
            <div class="vh-final"><h2>{{ __('landing.cta_title') }} <span>Velora</span></h2><p>{{ __('landing.cta_sub') }}</p><div class="vh-actions" style="justify-content:center">@if($registrationEnabled ?? true)<a class="vh-btn vh-btn-primary" href="{{ route('signup') }}">{{ __('landing.cta_button') }} <span>→</span></a>@endif<a class="vh-btn vh-btn-secondary" href="#features">{{ __('landing.hero_cta_how') }}</a></div><div class="vh-trust" style="justify-content:center;color:#AEB8CA"><span>✓ {{ __('landing.cta_note') }}</span></div></div>
        </div>
    </section>
</div>
@endsection
