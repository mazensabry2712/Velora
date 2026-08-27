@extends('layouts.landing')

@push('styles')
<style>
    .velora-home{
        --v-text:#0D1226;
        --v-muted:#667085;
        --v-border:#E5E7EB;
        --v-surface:#FFFFFF;
        --v-soft:#F5F7FA;
        --v-primary:#6D46FF;
        --v-blue:#006CFF;
        --v-cyan:#00B8FF;
        --v-mint:#00D4A3;
        --v-gradient:linear-gradient(120deg,#6D46FF 0%,#006CFF 55%,#00B8FF 100%);
        background:var(--v-surface);
        color:var(--v-text);
        overflow:hidden;
    }
    .velora-home *,.velora-home *::before,.velora-home *::after{box-sizing:border-box}
    .vh-wrap{width:min(1160px,calc(100% - 40px));margin-inline:auto}
    .vh-section{padding:92px 0}
    .vh-hero{padding:74px 0 88px;background:var(--v-surface)}
    .vh-hero-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(440px,.92fr);gap:64px;align-items:center}
    .vh-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(109,70,255,.16);border-radius:999px;background:#FBFAFF;color:var(--v-blue);font-size:11px;font-weight:800}
    .vh-badge i{width:7px;height:7px;border-radius:50%;background:var(--v-gradient)}
    .vh-hero h1{margin:21px 0 0;max-width:720px;font-size:clamp(44px,6.5vw,78px);line-height:1.02;letter-spacing:-.065em;font-weight:800}
    .vh-hero h1 span,.vh-section-title span{background:var(--v-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}
    .vh-lead{max-width:620px;margin:20px 0 0;color:var(--v-muted);font-size:18px;line-height:1.8}
    .vh-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px}
    .vh-btn{min-height:50px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 19px;border-radius:13px;font-size:13px;font-weight:800;transition:transform .18s,box-shadow .18s,background .18s}
    .vh-btn-primary{color:#fff;background:var(--v-gradient);box-shadow:0 14px 30px rgba(0,108,255,.18)}
    .vh-btn-primary:hover{transform:translateY(-1px);box-shadow:0 18px 38px rgba(0,108,255,.24)}
    .vh-btn-secondary{color:var(--v-text);border:1px solid var(--v-border);background:var(--v-surface)}
    .vh-btn-secondary:hover{transform:translateY(-1px);background:var(--v-soft)}
    .vh-trust{display:flex;flex-wrap:wrap;gap:10px 18px;margin-top:20px;color:#788397;font-size:11px;font-weight:700}
    .vh-visual{position:relative}.vh-visual::before{content:"";position:absolute;inset:-70px -90px auto auto;width:360px;height:360px;border-radius:50%;background:radial-gradient(circle,rgba(109,70,255,.14),rgba(0,184,255,0) 68%);pointer-events:none}
    .vh-preview{position:relative;padding:12px;border-radius:26px;background:var(--v-gradient);box-shadow:0 28px 80px rgba(13,18,38,.16)}
    .vh-preview-shell{overflow:hidden;border-radius:18px;background:#fff}
    .vh-browser{display:flex;align-items:center;gap:6px;padding:12px 14px;background:#0D1226}.vh-browser i{width:7px;height:7px;border-radius:50%;background:#fff;opacity:.4}.vh-browser-url{flex:1;text-align:center;color:#B9C3D5;font-size:10px}
    .vh-dashboard{padding:18px;background:#F8FAFD}.vh-dashboard-top{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:16px}.vh-brand-row{display:flex;align-items:center;gap:9px}.vh-brand-row img{width:34px;height:34px;object-fit:contain;border-radius:9px}.vh-brand-row small{display:block;color:#7C899D;font-size:9px}.vh-brand-row strong{display:block;color:#0D1226;font-size:13px;margin-top:2px}.vh-live{padding:6px 9px;border-radius:999px;background:#EFFFF8;color:#008C6C;font-size:9px;font-weight:800}
    .vh-stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}.vh-stat{padding:13px;border:1px solid #E7ECF3;border-radius:14px;background:#fff}.vh-stat span{display:block;color:#8A96A8;font-size:8px;font-weight:800;text-transform:uppercase}.vh-stat strong{display:block;margin-top:7px;color:#0D1226;font-size:22px;line-height:1}.vh-stat.accent strong{color:#006CFF}
    .vh-list{margin-top:10px;padding:7px 13px;border:1px solid #E7ECF3;border-radius:14px;background:#fff}.vh-list-row{display:grid;grid-template-columns:30px 1fr auto;gap:10px;align-items:center;min-height:48px;border-bottom:1px solid #EFF2F6}.vh-list-row:last-child{border-bottom:0}.vh-avatar{width:27px;height:27px;border-radius:9px;background:linear-gradient(135deg,#ECE6FF,#E4F4FF)}.vh-lines b,.vh-lines span{display:block;border-radius:99px;background:#DCE4EF}.vh-lines b{width:96px;height:7px}.vh-lines span{width:60px;height:6px;margin-top:6px}.vh-pill{width:52px;height:20px;border-radius:99px;background:#EEF2F7}
    .vh-section-head{max-width:700px}.vh-section-head.center{text-align:center;margin-inline:auto}.vh-kicker{display:inline-block;color:var(--v-blue);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.vh-section-title{margin:13px 0 0;font-size:clamp(34px,4.7vw,54px);line-height:1.04;letter-spacing:-.05em;font-weight:800}.vh-section-copy{margin-top:14px;color:var(--v-muted);font-size:16px;line-height:1.8}
    .vh-capabilities{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:40px}.vh-capability{padding:26px;border:1px solid var(--v-border);border-radius:20px;background:#fff;transition:transform .18s,box-shadow .18s,border-color .18s}.vh-capability:hover{transform:translateY(-3px);box-shadow:0 16px 34px rgba(13,18,38,.07);border-color:#D8DDE7}.vh-icon{width:44px;height:44px;display:grid;place-items:center;border-radius:13px;background:var(--v-gradient);color:#fff;font-weight:900;box-shadow:0 9px 20px rgba(0,108,255,.13)}.vh-capability h3{margin-top:16px;font-size:17px;font-weight:800}.vh-capability p{margin-top:8px;color:var(--v-muted);font-size:13px;line-height:1.75}
    .vh-how{background:var(--v-soft)}.vh-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:40px}.vh-step{position:relative;padding:27px;border:1px solid var(--v-border);border-radius:20px;background:#fff}.vh-step-no{width:38px;height:38px;display:grid;place-items:center;border-radius:50%;background:var(--v-gradient);color:#fff;font-size:12px;font-weight:900}.vh-step h3{margin-top:16px;font-size:17px;font-weight:800}.vh-step p{margin-top:8px;color:var(--v-muted);font-size:13px;line-height:1.75}
    .vh-price-section{padding-top:92px}.vh-pricing{display:grid;grid-template-columns:.82fr 1.18fr;gap:30px;margin-top:40px;padding:30px;border:1px solid var(--v-border);border-radius:24px;background:#fff;box-shadow:0 18px 52px rgba(13,18,38,.05)}.vh-price-main{display:flex;flex-direction:column;justify-content:center}.vh-price-label{color:#718096;font-size:11px;font-weight:800}.vh-price-number{margin-top:7px;font-size:clamp(52px,6vw,72px);line-height:.94;letter-spacing:-.06em;font-weight:800}.vh-price-note{margin-top:10px;color:var(--v-muted);font-size:12px}.vh-price-list{display:grid;grid-template-columns:1fr 1fr;align-content:center;gap:12px 20px;padding-inline-start:26px;border-inline-start:1px solid var(--v-border)}.vh-check{display:flex;gap:8px;color:#4B5563;font-size:12px;line-height:1.5}.vh-check::before{content:"✓";color:var(--v-mint);font-weight:900}
    .vh-final{margin-top:92px;padding:74px 22px;border-radius:28px;background:#0D1226;text-align:center;position:relative;overflow:hidden}.vh-final::before{content:"";position:absolute;width:460px;height:460px;right:-120px;top:-260px;border-radius:50%;background:radial-gradient(circle,rgba(0,184,255,.22),transparent 68%)}.vh-final::after{content:"";position:absolute;width:300px;height:300px;left:-150px;bottom:-180px;border-radius:50%;background:radial-gradient(circle,rgba(109,70,255,.20),transparent 68%)}.vh-final>*{position:relative;z-index:1}.vh-final h2{color:#fff;font-size:clamp(34px,4.8vw,52px);line-height:1.04;letter-spacing:-.05em;font-weight:800}.vh-final p{max-width:620px;margin:14px auto 0;color:#AEB8CA;font-size:15px;line-height:1.8}.vh-final .vh-btn-secondary{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.15);color:#fff}.vh-final .vh-btn-secondary:hover{background:rgba(255,255,255,.1)}
    html[data-theme="dark"] .velora-home{--v-text:#F8FAFC;--v-muted:#A7B0C0;--v-border:#252E45;--v-surface:#0D1226;--v-soft:#080B18}html[data-theme="dark"] .vh-hero{background:#080B18}html[data-theme="dark"] .vh-badge,html[data-theme="dark"] .vh-btn-secondary,html[data-theme="dark"] .vh-capability,html[data-theme="dark"] .vh-step,html[data-theme="dark"] .vh-pricing{background:#0D1226}html[data-theme="dark"] .vh-dashboard{background:#080B18}html[data-theme="dark"] .vh-stat,html[data-theme="dark"] .vh-list{background:#11172A;border-color:#252E45}html[data-theme="dark"] .vh-brand-row strong,html[data-theme="dark"] .vh-stat strong{color:#F8FAFC}html[data-theme="dark"] .vh-lines b,html[data-theme="dark"] .vh-lines span,html[data-theme="dark"] .vh-pill{background:#27324A}html[data-theme="dark"] .vh-check{color:#C3CCDA}
    @media(max-width:980px){.vh-hero-grid,.vh-pricing{grid-template-columns:1fr}.vh-price-list{border-inline-start:0;border-top:1px solid var(--v-border);padding:22px 0 0}}
    @media(max-width:760px){.vh-wrap{width:calc(100% - 24px)}.vh-section{padding:68px 0}.vh-hero{padding:44px 0 64px}.vh-hero-grid{gap:38px}.vh-hero h1{font-size:clamp(38px,12vw,56px)}.vh-lead{font-size:16px}.vh-actions{flex-direction:column}.vh-btn{width:100%}.vh-trust{gap:9px 13px}.vh-stat-grid{grid-template-columns:1fr}.vh-capabilities,.vh-steps{grid-template-columns:1fr}.vh-price-list{grid-template-columns:1fr}.vh-final{margin-top:64px;padding:56px 18px}}
    @media(max-width:400px){.vh-wrap{width:calc(100% - 18px)}.vh-capability,.vh-step{padding:20px}}
    @media(prefers-reduced-motion:reduce){*,*::before,*::after{transition:none!important}}
</style>
@endpush

@section('content')
<div class="velora-home">
    <section class="vh-hero">
        <div class="vh-wrap vh-hero-grid">
            <div>
                <span class="vh-badge"><i></i>{{ __('landing.hero_badge', ['days' => $trialDays ?? 14]) }}</span>
                <h1>{{ __('landing.hero_headline_1') }} <span>{{ __('landing.hero_headline_2') }} {{ __('landing.hero_headline_hl') }}</span></h1>
                <p class="vh-lead">{{ __('landing.hero_sub') }}</p>
                <div class="vh-actions">
                    @if($registrationEnabled ?? true)<a href="{{ route('signup') }}" class="vh-btn vh-btn-primary">{{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }} <span>→</span></a>@endif
                    <a href="#how-it-works" class="vh-btn vh-btn-secondary">{{ __('landing.hero_cta_how') }}</a>
                </div>
                <div class="vh-trust"><span>✓ {{ __('landing.trust_no_card') }}</span><span>✓ {{ __('landing.trust_setup') }}</span><span>✓ {{ __('landing.trust_cancel') }}</span><span>✓ {{ __('landing.trust_languages') }}</span></div>
            </div>
            <div class="vh-visual" aria-hidden="true">
                <div class="vh-preview"><div class="vh-preview-shell">
                    <div class="vh-browser"><i></i><i></i><i></i><div class="vh-browser-url">app.velora</div></div>
                    <div class="vh-dashboard">
                        <div class="vh-dashboard-top"><div class="vh-brand-row"><img src="{{ asset('logo.png') }}" alt=""><div><small>Velora</small><strong>{{ __('landing.ticker_scheduling') }}</strong></div></div><div class="vh-live">99.9%</div></div>
                        <div class="vh-stat-grid"><div class="vh-stat"><span>{{ __('landing.ticker_scheduling') }}</span><strong>24</strong></div><div class="vh-stat accent"><span>{{ __('landing.ticker_queue') }}</span><strong>07</strong></div><div class="vh-stat"><span>{{ __('landing.ticker_uptime') }}</span><strong>99.9%</strong></div></div>
                        <div class="vh-list"><div class="vh-list-row"><span class="vh-avatar"></span><div class="vh-lines"><b></b><span></span></div><span class="vh-pill"></span></div><div class="vh-list-row"><span class="vh-avatar"></span><div class="vh-lines"><b></b><span></span></div><span class="vh-pill"></span></div><div class="vh-list-row"><span class="vh-avatar"></span><div class="vh-lines"><b></b><span></span></div><span class="vh-pill"></span></div><div class="vh-list-row"><span class="vh-avatar"></span><div class="vh-lines"><b></b><span></span></div><span class="vh-pill"></span></div></div>
                    </div>
                </div></div>
            </div>
        </div>
    </section>

    <section id="features" class="vh-section">
        <div class="vh-wrap">
            <div class="vh-section-head center"><div class="vh-kicker">{{ __('landing.features_badge') }}</div><h2 class="vh-section-title">{{ __('landing.features_title') }} <span>{{ __('landing.features_title_hl') }}</span></h2><p class="vh-section-copy">{{ __('landing.features_sub') }}</p></div>
            <div class="vh-capabilities">
                @foreach([['◷',__('landing.f1_title'),__('landing.f1_desc')],['#',__('landing.f2_title'),__('landing.f2_desc')],['◎',__('landing.f3_title'),__('landing.f3_desc')],['↗',__('landing.f4_title'),__('landing.f4_desc')],['↻',__('landing.f5_title'),__('landing.f5_desc')],['⌘',__('landing.f6_title'),__('landing.f6_desc')]] as $feature)
                    <article class="vh-capability"><div class="vh-icon">{{ $feature[0] }}</div><h3>{{ $feature[1] }}</h3><p>{{ $feature[2] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="vh-section vh-how">
        <div class="vh-wrap">
            <div class="vh-section-head center"><div class="vh-kicker">{{ __('landing.how_badge') }}</div><h2 class="vh-section-title">{{ __('landing.how_title') }} <span>{{ __('landing.how_title_hl') }}</span></h2><p class="vh-section-copy">{{ __('landing.how_sub') }}</p></div>
            <div class="vh-steps">
                @foreach([[1,__('landing.s1_title'),__('landing.s1_desc')],[2,__('landing.s2_title'),__('landing.s2_desc')],[3,__('landing.s3_title'),__('landing.s3_desc')]] as $step)
                    <article class="vh-step"><div class="vh-step-no">{{ $step[0] }}</div><h3>{{ $step[1] }}</h3><p>{{ $step[2] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="vh-section vh-price-section">
        <div class="vh-wrap">
            <div class="vh-section-head center"><div class="vh-kicker">{{ __('landing.pricing_badge') }}</div><h2 class="vh-section-title">{{ __('landing.pricing_title') }} <span>{{ __('landing.pricing_title_hl') }}</span></h2><p class="vh-section-copy">{{ __('landing.pricing_one_plan') }} — {{ __('landing.pricing_after_trial') }}</p></div>
            <div class="vh-pricing">
                <div class="vh-price-main"><div class="vh-price-label">{{ $appName ?? 'Velora' }}</div><div class="vh-price-number">{{ $pricing['formatted_price'] }}</div><div class="vh-price-note">✓ {{ __('landing.pricing_no_card_trial') }}</div>@if($registrationEnabled ?? true)<a href="{{ route('signup') }}" class="vh-btn vh-btn-primary" style="margin-top:22px">{{ __('landing.pricing_start_trial', ['days' => $trialDays ?? 14]) }} <span>→</span></a>@endif</div>
                <div class="vh-price-list">@foreach([__('landing.pricing_feat_scheduling'),__('landing.pricing_feat_queue'),__('landing.pricing_feat_staff'),__('landing.pricing_feat_analytics'),__('landing.pricing_feat_reminders'),__('landing.pricing_feat_languages'),__('landing.pricing_feat_booking'),__('landing.pricing_feat_support')] as $feature)<div class="vh-check">{{ $feature }}</div>@endforeach</div>
            </div>
            <div class="vh-final"><h2>{{ __('landing.cta_title') }}</h2><p>{{ __('landing.cta_sub') }}</p><div class="vh-actions" style="justify-content:center">@if($registrationEnabled ?? true)<a href="{{ route('signup') }}" class="vh-btn vh-btn-primary">{{ __('landing.cta_button') }} <span>→</span></a>@endif<a href="#features" class="vh-btn vh-btn-secondary">{{ __('landing.hero_cta_how') }}</a></div></div>
        </div>
    </section>
</div>
@endsection
