@extends('layouts.landing')

@push('styles')
<style>
    .v-home{background:#061113;color:#eff8f7}
    .v-wrap{width:min(1160px,calc(100% - 32px));margin:0 auto}
    .v-section{padding:92px 0}
    .v-card{background:#0a1b1d;border:1px solid #173638;border-radius:24px}
    .v-soft{background:#0b2022;border:1px solid #1c4042;border-radius:18px}
    .v-muted{color:#8ea5a6}
    .v-teal{color:#35c7be}
    .v-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:50px;padding:0 20px;border-radius:14px;font-weight:700;transition:.2s}
    .v-btn-primary{background:#19a79f;color:#041011;box-shadow:0 14px 34px rgba(25,167,159,.18)}
    .v-btn-primary:hover{background:#25b8b0;transform:translateY(-1px)}
    .v-btn-secondary{background:#0b1c1e;border:1px solid #214547;color:#eaf5f5}
    .v-btn-secondary:hover{background:#0f2527;border-color:#2c5d60}
    .v-chip{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#0b2022;border:1px solid #1b3d40;color:#9ee7e1;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .v-title{font-size:clamp(42px,7vw,82px);line-height:1.02;letter-spacing:-.045em;font-weight:800}
    .v-h2{font-size:clamp(32px,4vw,52px);line-height:1.05;letter-spacing:-.035em;font-weight:800}
    .v-grid{display:grid;gap:20px}
    .v-preview{overflow:hidden;border:1px solid #214547;border-radius:28px;background:#081719;box-shadow:0 34px 100px rgba(0,0,0,.34)}
    .v-browser{display:flex;align-items:center;gap:7px;padding:13px 16px;border-bottom:1px solid #173638;background:#0a2022}
    .v-dot{width:9px;height:9px;border-radius:50%;background:#315759}
    .v-app{display:grid;grid-template-columns:190px 1fr;min-height:370px}
    .v-side{padding:22px;border-right:1px solid #173638;background:#09191b}
    .v-main{padding:24px}
    .v-side-line{height:10px;border-radius:999px;background:#143032;margin-bottom:12px}
    .v-side-line.active{background:#1ba69e}
    .v-stat{padding:18px;border-radius:16px;background:#0c2022;border:1px solid #173c3e}
    .v-kicker{font-size:12px;color:#7f999a}
    .v-number{font-size:28px;font-weight:800;margin-top:6px}
    .v-feature{padding:28px}
    .v-icon{width:44px;height:44px;display:grid;place-items:center;border-radius:14px;background:#0e292b;border:1px solid #245154;color:#5eddd4;font-weight:800}
    .v-step{padding:26px}
    .v-step-num{width:40px;height:40px;display:grid;place-items:center;border-radius:12px;background:#123336;border:1px solid #27585a;color:#63ddd5;font-weight:800}
    @media(max-width:860px){.v-app{grid-template-columns:1fr}.v-side{display:none}}
    @media(max-width:640px){.v-wrap{width:min(100% - 20px,1160px)}.v-section{padding:66px 0}.v-btn{width:100%}}
</style>
@endpush

@section('content')
@php
    $appLabel = $appName ?? 'Velora';
@endphp
<div class="v-home">
    <section class="relative overflow-hidden pt-32 pb-24 sm:pt-40 sm:pb-32">
        <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(circle at 50% 0%,rgba(28,179,170,.15),transparent 48%)"></div>
        <div class="v-wrap relative text-center">
            <span class="v-chip"><span class="h-2 w-2 rounded-full" style="background:#35c7be"></span>{{ __('landing.hero_badge', ['days' => $trialDays ?? 14]) }}</span>
            <h1 class="v-title mt-7 max-w-5xl mx-auto">
                <span class="block">{{ __('landing.hero_headline_1') }}</span>
                <span class="block">{{ __('landing.hero_headline_2') }}</span>
                <span class="block v-teal">{{ __('landing.hero_headline_hl') }}</span>
            </h1>
            <p class="v-muted max-w-2xl mx-auto mt-7 text-lg sm:text-xl leading-8">{{ __('landing.hero_sub') }}</p>
            <div class="mt-9 flex flex-col sm:flex-row justify-center gap-3 max-w-lg mx-auto">
                <a href="{{ route('signup') }}" class="v-btn v-btn-primary">{{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }} <span>→</span></a>
                <a href="#how-it-works" class="v-btn v-btn-secondary">{{ __('landing.hero_cta_how') }}</a>
            </div>
            <div class="mt-7 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm v-muted">
                <span>✓ {{ __('landing.trust_no_card') }}</span><span>✓ {{ __('landing.trust_setup') }}</span><span>✓ {{ __('landing.trust_cancel') }}</span><span>✓ {{ __('landing.trust_languages') }}</span>
            </div>
        </div>
        <div class="v-wrap relative mt-16">
            <div class="v-preview">
                <div class="v-browser"><span class="v-dot"></span><span class="v-dot"></span><span class="v-dot"></span><div class="flex-1 text-center text-xs text-slate-500">app.velora</div></div>
                <div class="v-app">
                    <aside class="v-side"><div class="font-bold text-sm text-white mb-6">{{ $appLabel }}</div><div class="v-side-line active"></div><div class="v-side-line"></div><div class="v-side-line"></div><div class="v-side-line"></div><div class="v-side-line"></div></aside>
                    <div class="v-main">
                        <div class="flex items-end justify-between gap-4 mb-5"><div><div class="v-kicker">{{ __('landing.dashboard_preview_greeting') ?? 'Dashboard' }}</div><div class="text-2xl font-bold mt-1">{{ __('landing.dashboard_preview_title') ?? 'Everything in one place' }}</div></div><div class="v-icon">↗</div></div>
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
            <div class="max-w-3xl"><span class="v-chip">{{ __('landing.features_badge') }}</span><h2 class="v-h2 mt-5">{{ __('landing.features_title') }} <span class="v-teal">{{ __('landing.features_title_hl') }}</span></h2><p class="v-muted mt-5 text-lg leading-8">{{ __('landing.features_subtitle') }}</p></div>
            <div class="v-grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 mt-12">
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
            <div class="text-center max-w-2xl mx-auto"><span class="v-chip">{{ __('landing.how_badge') }}</span><h2 class="v-h2 mt-5">{{ __('landing.how_title') }}</h2><p class="v-muted mt-4 text-lg">{{ __('landing.how_sub') }}</p></div>
            <div class="v-grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 mt-12">
                @foreach([[1,__('landing.how_step1_title'),__('landing.how_step1_desc')],[2,__('landing.how_step2_title'),__('landing.how_step2_desc')],[3,__('landing.how_step3_title'),__('landing.how_step3_desc')],[4,__('landing.how_step4_title'),__('landing.how_step4_desc')]] as $step)
                    <article class="v-card v-step"><div class="v-step-num">{{ $step[0] }}</div><h3 class="text-lg font-bold mt-5">{{ $step[1] }}</h3><p class="v-muted mt-3 text-sm leading-7">{{ $step[2] }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="v-section border-y" style="border-color:#122d2f">
        <div class="v-wrap">
            <div class="text-center max-w-3xl mx-auto"><span class="v-chip">{{ __('landing.pricing_badge') }}</span><h2 class="v-h2 mt-5">{{ __('landing.pricing_title') }} <span class="v-teal">{{ __('landing.pricing_title_hl') }}</span></h2><p class="v-muted mt-4 text-lg">{{ __('landing.pricing_one_plan') }}</p></div>
            <div class="v-card max-w-4xl mx-auto mt-12 p-7 sm:p-10">
                <div class="grid grid-cols-1 lg:grid-cols-[1fr_1fr] gap-10 items-center">
                    <div><div class="v-teal text-sm font-bold">{{ $appLabel }}</div><div class="mt-3 flex items-end gap-3"><span class="text-5xl font-extrabold" x-text="monthlyFormatted">{{ $pricing['formatted_price'] }}</span><span class="v-muted mb-1">{{ __('landing.pricing_per_mo') }}</span></div><p class="v-muted mt-3">{{ __('landing.pricing_no_card_trial') }}</p><a href="{{ route('signup') }}" class="v-btn v-btn-primary mt-7">{{ __('landing.pricing_start_trial', ['days' => $trialDays]) }} <span>→</span></a></div>
                    <div class="v-soft p-6"><div class="font-bold">{{ __('landing.whats_included') }}</div><div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-5 text-sm v-muted">@foreach([__('landing.pricing_feat_scheduling'),__('landing.pricing_feat_queue'),__('landing.pricing_feat_staff'),__('landing.pricing_feat_analytics'),__('landing.pricing_feat_reminders'),__('landing.pricing_feat_languages')] as $item)<div class="flex gap-2"><span class="v-teal">✓</span><span>{{ $item }}</span></div>@endforeach</div></div>
                </div>
            </div>
        </div>
    </section>

    <section class="v-section pb-24">
        <div class="v-wrap"><div class="v-card p-9 sm:p-12 text-center" style="background:linear-gradient(135deg,#0b2224,#0d2b2d)"><h2 class="v-h2">{{ __('landing.how_cta') }}</h2><p class="v-muted max-w-2xl mx-auto mt-4">{{ __('landing.hero_sub') }}</p><a href="{{ route('signup') }}" class="v-btn v-btn-primary mt-7">{{ __('landing.hero_cta_start', ['days' => $trialDays ?? 14]) }} <span>→</span></a></div></div>
    </section>
</div>
@endsection
