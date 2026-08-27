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
    $landingLocale = app()->getLocale() ?: config('localizer.supported_locales.0', 'en');
@endphp

<style>
    .signup-page{position:relative;overflow:hidden;padding:48px 0 76px;background:var(--v-canvas)}
    .signup-page::before,.signup-page::after{content:"";position:absolute;border-radius:999px;pointer-events:none;filter:blur(4px)}
    .signup-page::before{width:520px;height:520px;top:-260px;right:-180px;background:radial-gradient(circle,rgba(109,70,255,.13),rgba(0,184,255,0) 70%)}
    .signup-page::after{width:420px;height:420px;bottom:-250px;left:-180px;background:radial-gradient(circle,rgba(0,184,255,.10),rgba(109,70,255,0) 70%)}
    .signup-container{position:relative;z-index:1;width:min(1080px,calc(100% - 40px));margin-inline:auto}
    .signup-hero{text-align:center;max-width:760px;margin:0 auto}
    .signup-eyebrow{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid rgba(109,70,255,.16);border-radius:999px;background:color-mix(in srgb,var(--v-surface) 92%,transparent);color:var(--v-primary-blue);font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
    .signup-eyebrow i{width:7px;height:7px;border-radius:50%;background:var(--v-gradient)}
    .signup-title{margin:16px 0 0;color:var(--v-ink);font-size:clamp(36px,5vw,58px);line-height:1.02;letter-spacing:-.055em;font-weight:800}
    .signup-title span{background:var(--v-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}
    .signup-subtitle{max-width:620px;margin:15px auto 0;color:var(--v-muted);font-size:14px;line-height:1.8}
    .signup-trust{display:flex;justify-content:center;flex-wrap:wrap;gap:8px 18px;margin-top:17px;color:var(--v-muted);font-size:10px;font-weight:700}
    .signup-layout{display:grid;grid-template-columns:minmax(280px,.78fr) minmax(0,1.22fr);gap:24px;align-items:start;margin-top:38px}
    .signup-aside{position:sticky;top:124px}
    .signup-panel,.signup-form{border:1px solid var(--v-line);border-radius:22px;background:var(--v-surface);box-shadow:0 20px 58px rgba(13,18,38,.07)}
    .signup-panel{padding:24px}
    .signup-brand{display:flex;align-items:center;gap:11px}
    .signup-brand img{width:42px;height:42px;display:block;object-fit:contain;border-radius:11px}
    .signup-brand strong{display:block;color:var(--v-ink);font-size:15px;font-weight:800}
    .signup-brand small{display:block;margin-top:2px;color:var(--v-muted);font-size:9px}
    .signup-panel h2{margin:23px 0 0;color:var(--v-ink);font-size:24px;line-height:1.12;letter-spacing:-.04em;font-weight:800}
    .signup-panel-copy{margin:9px 0 0;color:var(--v-muted);font-size:12px;line-height:1.7}
    .signup-benefits{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:19px}
    .signup-benefit{display:flex;align-items:flex-start;gap:9px;padding:11px;border:1px solid var(--v-line);border-radius:14px;background:color-mix(in srgb,var(--v-surface) 92%,var(--v-canvas))}
    .signup-benefit i{width:28px;height:28px;display:grid;place-items:center;flex:0 0 auto;border-radius:9px;background:var(--v-gradient);color:#fff;font-style:normal;font-size:12px;font-weight:900}
    .signup-benefit span{color:var(--v-ink-soft);font-size:10px;font-weight:700;line-height:1.45}
    .signup-steps{margin-top:20px;padding-top:17px;border-top:1px solid var(--v-line)}
    .signup-step{display:flex;gap:10px;padding:7px 0}
    .signup-step-no{width:27px;height:27px;display:grid;place-items:center;flex:0 0 auto;border-radius:50%;background:var(--v-gradient);color:#fff;font-size:9px;font-weight:900}
    .signup-step strong{display:block;color:var(--v-ink);font-size:10px;font-weight:800}
    .signup-step p{margin:3px 0 0;color:var(--v-muted);font-size:9px;line-height:1.5}
    .signup-form-wrap{min-width:0}
    .signup-back{display:inline-flex;align-items:center;gap:7px;margin:0 0 14px;color:var(--v-muted);font-size:10px;font-weight:800}
    .signup-back:hover{color:var(--v-primary-blue)}
    .signup-form{overflow:hidden}
    .signup-form-header{padding:23px 26px 19px;border-bottom:1px solid var(--v-line)}
    .signup-form-header h2{margin:0;color:var(--v-ink);font-size:23px;letter-spacing:-.035em;font-weight:800}
    .signup-form-header p{margin:6px 0 0;color:var(--v-muted);font-size:10px;line-height:1.6}
    .signup-form-body{padding:24px 26px 27px}
    .signup-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .signup-full{grid-column:1/-1}
    .signup-field label{display:block;margin:0 0 7px;color:var(--v-ink-soft);font-size:10px;font-weight:800}
    .signup-input,.signup-select{width:100%;height:46px;padding:0 13px;border:1px solid var(--v-line);border-radius:12px;background:color-mix(in srgb,var(--v-surface) 94%,var(--v-canvas));color:var(--v-ink);font:inherit;font-size:12px;outline:none;transition:border-color .18s,box-shadow .18s,background .18s}
    .signup-input::placeholder{color:#98A2B3}
    .signup-input:focus,.signup-select:focus{border-color:rgba(0,108,255,.52);box-shadow:0 0 0 4px rgba(0,108,255,.08);background:var(--v-surface)}
    .signup-help{margin:6px 0 0;color:#8A95A8;font-size:9px;line-height:1.5}
    .signup-types{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:7px}
    .signup-type{position:relative;cursor:pointer}
    .signup-type input{position:absolute;opacity:0;pointer-events:none}
    .signup-type-box{min-height:66px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;padding:7px 4px;border:1px solid var(--v-line);border-radius:12px;background:color-mix(in srgb,var(--v-surface) 92%,var(--v-canvas));color:var(--v-muted);transition:.18s}
    .signup-type:hover .signup-type-box{transform:translateY(-1px);border-color:#D2D8E3}
    .signup-type input:checked + .signup-type-box{border-color:rgba(0,108,255,.52);background:linear-gradient(180deg,rgba(109,70,255,.08),rgba(0,184,255,.04));color:var(--v-ink);box-shadow:0 8px 20px rgba(0,108,255,.07)}
    .signup-type-box b{font-size:17px;font-weight:500;line-height:1}
    .signup-type-box span{font-size:8px;font-weight:800;text-align:center;line-height:1.15}
    .signup-slug{display:flex;align-items:stretch}
    .signup-slug .signup-input{border-start-end-radius:0;border-end-end-radius:0}
    .signup-domain{display:flex;align-items:center;padding:0 11px;border:1px solid var(--v-line);border-inline-start:0;border-start-end-radius:12px;border-end-end-radius:12px;background:color-mix(in srgb,var(--v-surface) 90%,var(--v-canvas));color:#7B8798;font-size:9px;white-space:nowrap}
    .signup-password{position:relative}
    .signup-password .signup-input{padding-inline-end:44px}
    .signup-password button{position:absolute;inset-block:0;inset-inline-end:0;width:43px;border:0;background:none;color:#98A2B3;cursor:pointer}
    .signup-terms{display:flex;align-items:flex-start;gap:9px;margin-top:18px;padding:13px;border:1px solid var(--v-line);border-radius:13px;background:color-mix(in srgb,var(--v-surface) 90%,var(--v-canvas))}
    .signup-terms input{margin-top:2px;accent-color:#006CFF}
    .signup-terms span{color:var(--v-muted);font-size:9px;line-height:1.55}
    .signup-terms a{color:var(--v-primary-blue);font-weight:800;text-decoration:underline}
    .signup-coupon{margin-top:17px;padding:15px 0;border-top:1px solid var(--v-line);border-bottom:1px solid var(--v-line)}
    .signup-coupon button{border:0;background:none;padding:0;color:var(--v-muted);font-size:10px;font-weight:800;cursor:pointer}
    .signup-coupon-row{margin-top:9px}
    .signup-submit{width:100%;min-height:50px;border:0;border-radius:13px;background:var(--v-gradient);color:#fff;font-size:12px;font-weight:900;box-shadow:0 13px 28px rgba(0,108,255,.17);cursor:pointer;transition:.18s}
    .signup-submit:hover{transform:translateY(-1px);box-shadow:0 17px 32px rgba(0,108,255,.23)}
    .signup-existing{margin:13px 0 0;text-align:center;color:var(--v-muted);font-size:9px}
    .signup-existing a{color:var(--v-primary-blue);font-weight:800}
    .signup-secure{display:flex;align-items:center;justify-content:center;gap:7px;margin-top:14px;padding-top:14px;border-top:1px solid var(--v-line);color:#8A95A8;font-size:8px}
    .signup-errors{margin-top:14px;padding:13px 15px;border:1px solid rgba(239,68,68,.18);border-radius:13px;background:rgba(239,68,68,.05);color:#B42318;font-size:10px}
    html[data-theme="dark"] .signup-panel,html[data-theme="dark"] .signup-form{box-shadow:0 24px 64px rgba(0,0,0,.25)}
    html[data-theme="dark"] .signup-input,html[data-theme="dark"] .signup-select,html[data-theme="dark"] .signup-type-box,html[data-theme="dark"] .signup-domain,html[data-theme="dark"] .signup-terms{background:#10172A}
    html[data-theme="dark"] .signup-type input:checked + .signup-type-box{background:linear-gradient(180deg,rgba(109,70,255,.16),rgba(0,184,255,.08))}
    @media(max-width:960px){.signup-layout{grid-template-columns:1fr}.signup-aside{position:static}.signup-panel{max-width:760px;margin-inline:auto}.signup-form-wrap{max-width:760px;margin-inline:auto;width:100%}}
    @media(max-width:700px){.signup-page{padding:34px 0 56px}.signup-container{width:calc(100% - 24px)}.signup-layout{margin-top:28px}.signup-panel,.signup-form{border-radius:19px}.signup-panel{padding:20px}.signup-form-header{padding:20px 19px 17px}.signup-form-body{padding:20px 19px 22px}.signup-grid{grid-template-columns:1fr}.signup-full{grid-column:auto}.signup-benefits{grid-template-columns:1fr 1fr}.signup-types{grid-template-columns:repeat(3,minmax(0,1fr))}.signup-title{font-size:clamp(35px,11vw,50px)}.signup-subtitle{font-size:13px}}
    @media(max-width:440px){.signup-benefits{grid-template-columns:1fr}.signup-types{gap:6px}.signup-type-box{min-height:62px}.signup-domain{padding:0 8px;font-size:8px}.signup-trust{gap:7px 11px}}
</style>

<section class="signup-page">
    <div class="signup-container">
        <header class="signup-hero">
            <div class="signup-eyebrow"><i></i>{{ __('landing.nav_start_trial') }}</div>
            <h1 class="signup-title">{{ __('landing.signup_hero_line1') }} <span>{{ __('landing.signup_hero_line2', ['days' => $maxTrialDays]) }}</span></h1>
            <p class="signup-subtitle">{{ __('landing.signup_hero_sub') }}</p>
            <div class="signup-trust"><span>✓ {{ __('landing.trust_no_card') }}</span><span>✓ {{ __('landing.trust_setup') }}</span><span>✓ {{ __('landing.trust_cancel') }}</span></div>
        </header>

        <div class="signup-layout">
            <aside class="signup-aside">
                <div class="signup-panel">
                    <div class="signup-brand"><img src="{{ asset('logo-bais.png') }}" alt="Velora"><div><strong>{{ config('app.name', 'Velora') }}</strong><small>{{ __('landing.signup_what_next') }}</small></div></div>
                    <h2>{{ __('landing.signup_what_next') }}</h2>
                    <p class="signup-panel-copy">{{ __('landing.signup_form_sub', ['days' => $maxTrialDays]) }}</p>
                    <div class="signup-benefits">
                        @foreach($benefits as $benefit)
                            <div class="signup-benefit"><i>{{ $benefit['icon'] }}</i><span>{{ $benefit['title'] }}</span></div>
                        @endforeach
                    </div>
                    <div class="signup-steps">
                        @foreach([1,2,3] as $step)
                            <div class="signup-step">
                                <div class="signup-step-no">{{ $step }}</div>
                                <div>
                                    <strong>{{ __('landing.signup_step'.$step.'_title', ['days' => $maxTrialDays, 'grace_start' => $maxTrialDays + 1, 'grace_end' => $maxTrialDays + 3, 'readonly_start' => $maxTrialDays + 4]) }}</strong>
                                    <p>{{ __('landing.signup_step'.$step.'_desc', ['days' => $maxTrialDays]) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>

            <div class="signup-form-wrap">
                <a href="{{ route('landing') }}" class="signup-back">← {{ __('landing.back_to_home') }}</a>
                <div class="signup-form">
                    <div class="signup-form-header">
                        <h2>{{ __('landing.signup_form_title') }}</h2>
                        <p>{{ __('landing.signup_form_sub', ['days' => $maxTrialDays]) }}</p>
                    </div>
                    <form id="signupForm" method="POST" action="{{ route('signup') }}">
                        @csrf
                        <div class="signup-form-body">
                            <div class="signup-grid">
                                <div class="signup-field signup-full">
                                    <label for="business_name">{{ __('landing.signup_business_name') }} *</label>
                                    <input class="signup-input" id="business_name" name="business_name" type="text" value="{{ old('business_name') }}" placeholder="{{ __('landing.signup_business_name') }}" autocomplete="organization" required>
                                </div>

                                <div class="signup-field signup-full">
                                    <label>{{ __('landing.signup_business_type') }}</label>
                                    <div class="signup-types">
                                        @foreach($types as $type => $icon)
                                            <label class="signup-type">
                                                <input type="radio" name="business_type" value="{{ $type }}" {{ old('business_type', 'salon') === $type ? 'checked' : '' }}>
                                                <span class="signup-type-box"><b>{{ $icon }}</b><span>{{ __('landing.signup_type_'.$type) }}</span></span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="signup-field">
                                    <label for="slug">{{ __('landing.signup_booking_slug') }} *</label>
                                    <div class="signup-slug"><input class="signup-input" id="slug" name="slug" type="text" value="{{ old('slug') }}" autocomplete="url" required><span class="signup-domain">.velora.com</span></div>
                                    <p class="signup-help">{{ __('landing.signup_booking_slug_hint') }}</p>
                                </div>

                                <div class="signup-field">
                                    <label for="email">{{ __('landing.signup_email') }} *</label>
                                    <input class="signup-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                                </div>

                                <div class="signup-field">
                                    <label for="password">{{ __('landing.signup_password') }} *</label>
                                    <div class="signup-password">
                                        <input class="signup-input" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                                        <button type="button" onclick="togglePassword('password','passwordIcon')" aria-label="{{ __('landing.signup_show_password') ?? 'Show password' }}"><span id="passwordIcon">◎</span></button>
                                    </div>
                                    <p class="signup-help">{{ __('landing.signup_password_hint') }}</p>
                                </div>

                                <div class="signup-field">
                                    <label for="password_confirmation">{{ __('landing.signup_password_confirmation') }} *</label>
                                    <input class="signup-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                                </div>

                                <div class="signup-field">
                                    <label for="country">{{ __('landing.signup_country') }}</label>
                                    <select class="signup-select" id="country" name="country">
                                        @foreach(config('localizer.countries', []) as $code => $country)
                                            <option value="{{ $code }}" {{ old('country') === $code ? 'selected' : '' }}>{{ $country['flag'] ?? '' }} {{ $country['name'] ?? $code }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="signup-field">
                                    <label for="admin_locale">{{ __('landing.signup_admin_locale') }}</label>
                                    <select class="signup-select" id="admin_locale" name="admin_locale">
                                        @foreach(config('localizer.supported_locales', ['en','ar']) as $loc)
                                            <option value="{{ $loc }}" {{ old('admin_locale', $landingLocale) === $loc ? 'selected' : '' }}>{{ strtoupper($loc) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <label class="signup-terms">
                                <input type="checkbox" name="terms" value="1" required>
                                <span>{{ __('landing.signup_terms_prefix') }} <a href="#">{{ __('landing.signup_terms') }}</a> {{ __('landing.signup_and') }} <a href="#">{{ __('landing.signup_privacy') }}</a></span>
                            </label>

                            <div class="signup-coupon">
                                <button type="button" onclick="toggleCoupon()">◇ {{ __('landing.signup_coupon_question') }}</button>
                                <div id="couponRow" class="signup-coupon-row" hidden>
                                    <input class="signup-input" name="coupon" type="text" placeholder="{{ __('landing.signup_coupon_placeholder') }}">
                                </div>
                            </div>

                            <div style="margin-top:21px">
                                <button id="submitBtn" type="submit" class="signup-submit"><span>{{ __('landing.signup_submit') }}</span> <span>→</span></button>
                                <p class="signup-existing">{{ __('landing.signup_existing') }} <a href="{{ route('central.login') }}">{{ __('landing.signup_login') }}</a></p>
                                <div class="signup-secure">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="1.5" d="M12 15v2m-5-8V7a5 5 0 0110 0v2m-8 0h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6a2 2 0 012-2z"/></svg>
                                    <span>{{ __('landing.signup_isolated_data') }}</span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                @if ($errors->any())
                    <div class="signup-errors">
                        <ul style="margin:0;padding:0;list-style:none">
                            @foreach($errors->all() as $error)
                                <li style="margin-top:4px">• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
function togglePassword(inputId, iconId){
    const input=document.getElementById(inputId), icon=document.getElementById(iconId);
    if(!input||!icon)return;
    input.type=input.type==='password'?'text':'password';
    icon.textContent=input.type==='password'?'◎':'◉';
}
function toggleCoupon(){
    const row=document.getElementById('couponRow');
    if(!row)return;
    row.hidden=!row.hidden;
    if(!row.hidden) row.querySelector('input')?.focus();
}
</script>
@endpush
