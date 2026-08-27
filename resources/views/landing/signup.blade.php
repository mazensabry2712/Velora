@extends('layouts.landing')
@section('content')
@php
    $types = [
        'salon' => '✂️', 'barber' => '💈', 'clinic' => '🏥', 'spa' => '🧖', 'gym' => '🏋️',
        'restaurant' => '🍽️', 'studio' => '🎨', 'school' => '🎓', 'other' => '✏️',
    ];
    $benefits = [
        ['icon' => '◷', 'title' => __('landing.signup_benefit_1')],
        ['icon' => '↗', 'title' => __('landing.signup_benefit_2')],
        ['icon' => '◎', 'title' => __('landing.signup_benefit_3')],
        ['icon' => '⌘', 'title' => __('landing.signup_benefit_4')],
    ];
    $landingLocale = app()->getLocale() ?: config('app.locale', 'en');
@endphp

<style>
    .velora-signup-page{position:relative;min-height:calc(100dvh - 112px);padding:58px 0 82px;overflow:hidden;background:var(--v-canvas)}
    .velora-signup-page::before{content:"";position:absolute;width:560px;height:560px;top:-250px;right:-140px;border-radius:50%;background:radial-gradient(circle,rgba(109,70,255,.14),rgba(0,184,255,0) 68%);pointer-events:none}
    .velora-signup-page::after{content:"";position:absolute;width:460px;height:460px;bottom:-240px;left:-180px;border-radius:50%;background:radial-gradient(circle,rgba(0,184,255,.11),rgba(109,70,255,0) 68%);pointer-events:none}
    .vs-wrap{position:relative;z-index:1;width:min(1160px,calc(100% - 40px));margin-inline:auto}
    .vs-intro{text-align:center;max-width:760px;margin:0 auto}.vs-kicker{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(109,70,255,.16);border-radius:999px;background:color-mix(in srgb,var(--v-surface) 90%,transparent);color:var(--v-primary-blue);font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase}.vs-kicker i{width:7px;height:7px;border-radius:50%;background:var(--v-gradient)}.vs-title{margin:18px 0 0;font-size:clamp(40px,5vw,62px);line-height:1.02;letter-spacing:-.055em;font-weight:800;color:var(--v-ink)}.vs-title span{background:var(--v-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}.vs-copy{max-width:640px;margin:16px auto 0;color:var(--v-muted);font-size:16px;line-height:1.8}.vs-trust{display:flex;justify-content:center;flex-wrap:wrap;gap:8px 18px;margin-top:20px;color:#667085;font-size:12px;font-weight:700}
    .vs-layout{display:grid;grid-template-columns:minmax(0,.78fr) minmax(520px,1.22fr);gap:28px;align-items:start;margin-top:42px}.vs-side{position:sticky;top:132px}.vs-side-card{padding:28px;border:1px solid var(--v-line);border-radius:24px;background:var(--v-surface);box-shadow:0 18px 52px rgba(13,18,38,.06)}.vs-brand{display:flex;align-items:center;gap:12px}.vs-brand img{width:44px;height:44px;object-fit:contain;border-radius:12px}.vs-brand strong{display:block;color:var(--v-ink);font-size:16px;font-weight:800}.vs-brand span{display:block;color:var(--v-muted);font-size:11px;margin-top:3px}.vs-side h2{margin-top:24px;color:var(--v-ink);font-size:28px;line-height:1.1;letter-spacing:-.04em;font-weight:800}.vs-side h2 span{background:var(--v-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}.vs-side-copy{margin-top:10px;color:var(--v-muted);font-size:14px;line-height:1.75}.vs-benefits{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:22px}.vs-benefit{display:flex;align-items:flex-start;gap:10px;padding:13px;border:1px solid var(--v-line);border-radius:15px;background:color-mix(in srgb,var(--v-surface) 92%,var(--v-canvas))}.vs-benefit i{width:30px;height:30px;display:grid;place-items:center;flex:0 0 auto;border-radius:10px;background:var(--v-gradient);color:#fff;font-style:normal;font-size:14px;font-weight:900}.vs-benefit span{color:var(--v-ink-soft);font-size:12px;font-weight:700;line-height:1.45}
    .vs-steps{margin-top:24px;padding-top:20px;border-top:1px solid var(--v-line)}.vs-step{display:flex;gap:12px;align-items:flex-start;padding:9px 0}.vs-step-no{width:28px;height:28px;display:grid;place-items:center;flex:0 0 auto;border-radius:50%;background:var(--v-gradient);color:#fff;font-size:10px;font-weight:900}.vs-step strong{display:block;color:var(--v-ink);font-size:13px;font-weight:800}.vs-step p{margin-top:3px;color:var(--v-muted);font-size:11px;line-height:1.55}
    .vs-form-wrap{min-width:0}.vs-form-card{border:1px solid var(--v-line);border-radius:26px;background:var(--v-surface);box-shadow:0 24px 70px rgba(13,18,38,.09);overflow:hidden}.vs-form-head{padding:28px 30px 22px;border-bottom:1px solid var(--v-line)}.vs-form-head h2{color:var(--v-ink);font-size:25px;letter-spacing:-.03em;font-weight:800}.vs-form-head p{margin-top:6px;color:var(--v-muted);font-size:13px;line-height:1.6}.vs-form-body{padding:26px 30px 30px}.vs-label{display:block;margin-bottom:8px;color:var(--v-ink-soft);font-size:14px;font-weight:800}.vs-input,.vs-select{width:100%;height:50px;padding:0 14px;border:1px solid var(--v-line);border-radius:13px;background:color-mix(in srgb,var(--v-surface) 94%,var(--v-canvas));color:var(--v-ink);font-size:15px;outline:none;transition:border-color .18s,box-shadow .18s,background .18s}.vs-input::placeholder{color:#98A2B3}.vs-input:focus,.vs-select:focus{border-color:rgba(0,108,255,.55);box-shadow:0 0 0 4px rgba(0,108,255,.08);background:var(--v-surface)}.vs-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.vs-full{grid-column:1/-1}.vs-types{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px}.vs-type{position:relative;cursor:pointer}.vs-type input{position:absolute;opacity:0;pointer-events:none}.vs-type-box{min-height:72px;padding:8px 5px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;border:1px solid var(--v-line);border-radius:13px;background:color-mix(in srgb,var(--v-surface) 92%,var(--v-canvas));color:var(--v-muted);transition:transform .16s,border-color .16s,background .16s,color .16s,box-shadow .16s}.vs-type:hover .vs-type-box{transform:translateY(-1px);border-color:#D2D8E3}.vs-type input:checked + .vs-type-box{border-color:rgba(0,108,255,.55);background:linear-gradient(180deg,rgba(109,70,255,.08),rgba(0,184,255,.05));color:var(--v-ink);box-shadow:0 8px 22px rgba(0,108,255,.08)}.vs-type-box b{font-size:20px;font-weight:500}.vs-type-box span{font-size:11px;font-weight:800;text-align:center;line-height:1.15}.vs-help{margin-top:7px;color:#8A95A8;font-size:12px;line-height:1.5}.vs-slug{display:flex;align-items:stretch}.vs-slug .vs-input{border-start-end-radius:0;border-end-end-radius:0}.vs-domain{display:flex;align-items:center;padding:0 12px;border:1px solid var(--v-line);border-inline-start:0;border-start-end-radius:13px;border-end-end-radius:13px;background:color-mix(in srgb,var(--v-surface) 90%,var(--v-canvas));color:#7B8798;font-size:11px;white-space:nowrap}.vs-check{display:flex;align-items:flex-start;gap:10px;padding:16px;border:1px solid var(--v-line);border-radius:14px;background:color-mix(in srgb,var(--v-surface) 88%,var(--v-canvas))}.vs-check input{margin-top:3px;accent-color:#006CFF}.vs-check span{color:var(--v-muted);font-size:12px;line-height:1.6}.vs-check a{color:var(--v-primary-blue);font-weight:800;text-decoration:underline}.vs-coupon{padding:16px 0;border-top:1px solid var(--v-line);border-bottom:1px solid var(--v-line)}.vs-coupon-btn{border:0;background:none;padding:0;color:var(--v-muted);font-size:12px;font-weight:800;cursor:pointer}.vs-coupon-row{margin-top:10px}.vs-submit{width:100%;min-height:54px;border:0;border-radius:14px;background:var(--v-gradient);color:#fff;font-size:14px;font-weight:900;box-shadow:0 14px 30px rgba(0,108,255,.18);cursor:pointer;transition:transform .18s,box-shadow .18s}.vs-submit:hover{transform:translateY(-1px);box-shadow:0 18px 36px rgba(0,108,255,.24)}.vs-existing{margin-top:16px;text-align:center;color:var(--v-muted);font-size:12px}.vs-existing a{color:var(--v-primary-blue);font-weight:800}.vs-secure{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:16px;padding-top:16px;border-top:1px solid var(--v-line);color:#8A95A8;font-size:11px}.vs-errors{margin:16px 0 0;padding:14px 16px;border:1px solid rgba(239,68,68,.18);border-radius:14px;background:rgba(239,68,68,.05);color:#B42318;font-size:12px}.vs-back{display:inline-flex;align-items:center;gap:8px;margin-bottom:18px;color:var(--v-muted);font-size:12px;font-weight:800}.vs-back:hover{color:var(--v-primary-blue)}
    html[data-theme="dark"] .vs-side-card,html[data-theme="dark"] .vs-form-card{box-shadow:0 28px 78px rgba(0,0,0,.24)}html[data-theme="dark"] .vs-input,html[data-theme="dark"] .vs-select,html[data-theme="dark"] .vs-type-box,html[data-theme="dark"] .vs-domain,html[data-theme="dark"] .vs-check{background:#10172A}html[data-theme="dark"] .vs-type input:checked + .vs-type-box{background:linear-gradient(180deg,rgba(109,70,255,.16),rgba(0,184,255,.08))}html[data-theme="dark"] .vs-trust{color:#A7B0C0}
    @media(max-width:1020px){.vs-layout{grid-template-columns:1fr}.vs-side{position:static}.vs-side-card{max-width:820px;margin-inline:auto}.vs-form-wrap{max-width:820px;margin-inline:auto;width:100%}}
    @media(max-width:760px){.velora-signup-page{padding:38px 0 60px;min-height:calc(100dvh - 84px)}.vs-wrap{width:calc(100% - 24px)}.vs-title{font-size:clamp(36px,11vw,52px)}.vs-copy{font-size:14px}.vs-layout{margin-top:30px}.vs-side-card{padding:22px}.vs-benefits{grid-template-columns:1fr 1fr}.vs-form-head{padding:22px 20px 18px}.vs-form-body{padding:20px}.vs-grid{grid-template-columns:1fr}.vs-full{grid-column:auto}.vs-types{grid-template-columns:repeat(3,minmax(0,1fr))}.vs-trust{gap:7px 12px}.vs-form-card{border-radius:22px}}
    @media(max-width:460px){.vs-benefits{grid-template-columns:1fr}.vs-types{grid-template-columns:repeat(3,1fr);gap:7px}.vs-type-box{min-height:66px}.vs-domain{padding:0 9px;font-size:10px}.vs-submit{min-height:52px}}
</style>

<section class="velora-signup-page">
    <div class="vs-wrap">
        <div class="vs-intro">
            <div class="vs-kicker"><i></i>{{ __('landing.nav_start_trial') }}</div>
            <h1 class="vs-title">{{ __('landing.signup_hero_line1') }} <span>{{ __('landing.signup_hero_line2', ['days' => $maxTrialDays]) }}</span></h1>
            <p class="vs-copy">{{ __('landing.signup_hero_sub') }}</p>
            <div class="vs-trust"><span>✓ {{ __('landing.trust_no_card') }}</span><span>✓ {{ __('landing.trust_setup') }}</span><span>✓ {{ __('landing.trust_cancel') }}</span></div>
        </div>

        <div class="vs-layout">
            <aside class="vs-side">
                <div class="vs-side-card">
                    <div class="vs-brand"><img src="{{ asset('logo-bais.png') }}" alt="Velora"><div><strong>{{ config('app.name', 'Velora') }}</strong><span>{{ __('landing.signup_what_next') }}</span></div></div>
                    <h2>{{ __('landing.signup_what_next') }}</h2>
                    <p class="vs-side-copy">{{ __('landing.signup_form_sub', ['days' => $maxTrialDays]) }}</p>
                    <div class="vs-benefits">@foreach ($benefits as $benefit)<div class="vs-benefit"><i>{{ $benefit['icon'] }}</i><span>{{ $benefit['title'] }}</span></div>@endforeach</div>
                    <div class="vs-steps">
                        @foreach ([1,2,3] as $step)
                            <div class="vs-step"><div class="vs-step-no">{{ $step }}</div><div><strong>{{ __('landing.signup_step'.$step.'_title', ['days' => $maxTrialDays, 'grace_start' => $maxTrialDays + 1, 'grace_end' => $maxTrialDays + 3, 'readonly_start' => $maxTrialDays + 4]) }}</strong><p>{{ __('landing.signup_step'.$step.'_desc', ['days' => $maxTrialDays]) }}</p></div></div>
                        @endforeach
                    </div>
                </div>
            </aside>

            <div class="vs-form-wrap">
                <a href="{{ route('landing') }}" class="vs-back">← {{ __('landing.back_to_home') }}</a>
                <div class="vs-form-card">
                    <div class="vs-form-head"><h2>{{ __('landing.signup_form_title') }}</h2><p>{{ __('landing.signup_form_sub', ['days' => $maxTrialDays]) }}</p></div>
                    <form id="signupForm" method="POST" action="{{ route('signup') }}">
                        @csrf
                        <div class="vs-form-body">
                            <div class="vs-grid">
                                <div class="vs-full"><label class="vs-label" for="business_name">{{ __('landing.signup_business_name') }} *</label><input class="vs-input" id="business_name" name="business_name" type="text" value="{{ old('business_name') }}" placeholder="{{ __('landing.signup_business_name') }}" required></div>
                                <div class="vs-full"><label class="vs-label">{{ __('landing.signup_business_type') }}</label><div class="vs-types">@foreach($types as $type => $icon)<label class="vs-type"><input type="radio" name="business_type" value="{{ $type }}" {{ old('business_type', 'salon') === $type ? 'checked' : '' }}><span class="vs-type-box"><b>{{ $icon }}</b><span>{{ __('landing.signup_type_'.$type) }}</span></span></label>@endforeach</div></div>
                                <div><label class="vs-label" for="slug">{{ __('landing.signup_booking_slug') }} *</label><div class="vs-slug"><input class="vs-input" id="slug" name="slug" type="text" value="{{ old('slug') }}" required><span class="vs-domain">.velora.com</span></div><p class="vs-help">{{ __('landing.signup_booking_slug_hint') }}</p></div>
                                <div><label class="vs-label" for="email">{{ __('landing.signup_email') }} *</label><input class="vs-input" id="email" name="email" type="email" value="{{ old('email') }}" required></div>
                                <div><label class="vs-label" for="password">{{ __('landing.signup_password') }} *</label><div style="position:relative"><input class="vs-input" id="password" name="password" type="password" required minlength="8" style="padding-inline-end:48px"><button type="button" onclick="togglePassword('password','passwordIcon')" aria-label="{{ __('landing.signup_show_password') ?? 'Show password' }}" style="position:absolute;top:0;inset-inline-end:0;width:46px;height:50px;border:0;background:none;color:#98A2B3;cursor:pointer"><span id="passwordIcon">◎</span></button></div><p class="vs-help">{{ __('landing.signup_password_hint') }}</p></div>
                                <div><label class="vs-label" for="password_confirmation">{{ __('landing.signup_password_confirmation') }} *</label><input class="vs-input" id="password_confirmation" name="password_confirmation" type="password" required minlength="8"></div>
                                <div><label class="vs-label" for="country">{{ __('landing.signup_country') }}</label><select class="vs-select" id="country" name="country">@foreach(config('localizer.countries', []) as $code => $country)<option value="{{ $code }}" {{ old('country') === $code ? 'selected' : '' }}>{{ $country['flag'] ?? '' }} {{ $country['name'] ?? $code }}</option>@endforeach</select></div>
                                <div><label class="vs-label" for="admin_locale">{{ __('landing.signup_admin_locale') }}</label><select class="vs-select" id="admin_locale" name="admin_locale">@foreach(config('localizer.supported_locales', ['en','ar']) as $loc)<option value="{{ $loc }}" {{ old('admin_locale', $landingLocale) === $loc ? 'selected' : '' }}>{{ strtoupper($loc) }}</option>@endforeach</select></div>
                            </div>
                            <div style="margin-top:20px"><label class="vs-check"><input type="checkbox" name="terms" value="1" required><span>{{ __('landing.signup_terms_prefix') }} <a href="#">{{ __('landing.signup_terms') }}</a> {{ __('landing.signup_and') }} <a href="#">{{ __('landing.signup_privacy') }}</a></span></label></div>
                            <div class="vs-coupon" style="margin-top:20px"><button type="button" class="vs-coupon-btn" onclick="toggleCoupon()">◇ {{ __('landing.signup_coupon_question') }}</button><div id="couponRow" class="vs-coupon-row" hidden><input class="vs-input" name="coupon" type="text" placeholder="{{ __('landing.signup_coupon_placeholder') }}"></div></div>
                            <div style="margin-top:22px"><button id="submitBtn" type="submit" class="vs-submit"><span>{{ __('landing.signup_submit') }}</span> <span>→</span></button><p class="vs-existing">{{ __('landing.signup_existing') }} <a href="{{ route('central.login') }}">{{ __('landing.signup_login') }}</a></p><div class="vs-secure"><svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="1.5" d="M12 15v2m-5-8V7a5 5 0 0110 0v2m-8 0h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6a2 2 0 012-2z"/></svg><span>{{ __('landing.signup_isolated_data') }}</span></div></div>
                        </div>
                    </form>
                </div>
                @if ($errors->any())<div class="vs-errors"><ul style="margin:0;padding:0;list-style:none">@foreach($errors->all() as $error)<li style="margin-top:4px">• {{ $error }}</li>@endforeach</ul></div>@endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function togglePassword(inputId, iconId){const i=document.getElementById(inputId),x=document.getElementById(iconId);if(!i||!x)return;i.type=i.type==='password'?'text':'password';x.textContent=i.type==='password'?'◎':'◉'}
function toggleCoupon(){const r=document.getElementById('couponRow');if(!r)return;r.hidden=!r.hidden}
</script>
@endpush
