<!doctype html>
@php
    $locale = app()->getLocale() ?: 'en';
    $isRtl = in_array($locale, ['ar', 'he', 'fa'], true);
    $businessSettings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
    $businessName = $businessSettings?->business_name;
    if (is_array($businessName)) {
        $businessName = $businessName[$locale] ?? ($businessName['en'] ?? reset($businessName));
    }
    $displayName = is_scalar($businessName) && (string) $businessName !== '' ? (string) $businessName : tenant()->name;
    $supportedLocales = config('localizer.supported_locales', ['ar', 'en']);
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('password_reset.title') }} · {{ $displayName }}</title>
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-auth.css') }}">
    <style>
        .vpr-page{min-height:100dvh;display:grid;place-items:center;padding:24px;background:var(--va-bg);position:relative;overflow:hidden;color:var(--va-text)}
        .vpr-page:before{content:'';position:absolute;width:600px;height:600px;top:-260px;right:-220px;border-radius:50%;background:radial-gradient(circle,rgba(109,70,255,.16),transparent 68%);pointer-events:none}
        .vpr-card{position:relative;z-index:1;width:min(100%,520px);border:1px solid var(--va-line);border-radius:28px;background:var(--va-surface);box-shadow:0 30px 90px rgba(13,18,38,.12);overflow:hidden}
        .vpr-accent{height:5px;background:var(--va-gradient)}.vpr-inner{padding:34px}.vpr-brand{display:flex;align-items:center;gap:12px}.vpr-brand img{width:42px;height:42px;object-fit:contain;border-radius:12px}.vpr-brand strong{display:block;font-size:16px;font-weight:800}.vpr-brand span{display:block;margin-top:2px;font-size:10px;color:var(--va-muted)}
        .vpr-icon{width:54px;height:54px;display:grid;place-items:center;margin-top:30px;border-radius:16px;background:rgba(0,108,255,.08);color:var(--va-accent)}.vpr-icon svg{width:25px;height:25px}.vpr-title{margin-top:20px;font-size:34px;line-height:1.08;letter-spacing:-.04em;font-weight:800}.vpr-copy{margin-top:10px;color:var(--va-muted);font-size:13px;line-height:1.8}.vpr-form{display:grid;gap:15px;margin-top:24px}.vpr-label{font-size:11px;font-weight:800;margin-bottom:7px;display:block}.vpr-input{width:100%;height:54px;border:1px solid var(--va-line);border-radius:14px;background:var(--va-surface);color:var(--va-text);padding:0 15px;outline:none}.vpr-input:focus{border-color:rgba(0,108,255,.55);box-shadow:0 0 0 4px rgba(0,108,255,.08)}.vpr-submit{width:100%;height:56px;border:0;border-radius:15px;background:var(--va-gradient);color:#fff;font-weight:900;cursor:pointer;box-shadow:0 15px 34px rgba(0,108,255,.18)}.vpr-status{margin-top:15px;padding:12px 13px;border:1px solid rgba(16,185,129,.18);border-radius:13px;background:rgba(16,185,129,.06);color:#067647;font-size:11px;line-height:1.6}.vpr-error{margin-top:15px;padding:12px 13px;border:1px solid rgba(239,68,68,.18);border-radius:13px;background:rgba(239,68,68,.06);color:#B42318;font-size:11px}.vpr-back{display:inline-flex;align-items:center;gap:8px;margin-top:20px;color:var(--va-accent);font-size:11px;font-weight:800}.vpr-langs{display:flex;flex-wrap:wrap;gap:6px;margin-top:22px;padding-top:17px;border-top:1px solid var(--va-line)}.vpr-langs a{font-size:10px;font-weight:800;padding:7px 9px;border:1px solid var(--va-line);border-radius:9px;color:var(--va-muted)}.vpr-langs a.active{color:var(--va-accent);border-color:rgba(0,108,255,.35);background:rgba(0,108,255,.06)}
        html[data-theme="dark"] .vpr-card{box-shadow:0 30px 90px rgba(0,0,0,.28)}
        @media(max-width:540px){.vpr-page{padding:14px}.vpr-inner{padding:23px 20px}.vpr-title{font-size:29px}}
    </style>
</head>
<body>
<div class="vpr-page"><section class="vpr-card"><div class="vpr-accent"></div><div class="vpr-inner">
    <div class="vpr-brand"><img src="{{ asset($businessSettings?->logo ? 'storage/'.$businessSettings->logo : 'logo-bais.png') }}" alt="{{ $displayName }}"><div><strong>{{ $displayName }}</strong><span>{{ __('password_reset.secure_recovery') }}</span></div></div>
    <div class="vpr-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></div>
    <h1 class="vpr-title">{{ __('password_reset.heading') }}</h1>
    <p class="vpr-copy">{{ __('password_reset.description') }}</p>
    @if(session('status'))<div class="vpr-status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="vpr-error">{{ $errors->first() }}</div>@endif
    <form class="vpr-form" method="POST" action="{{ route('password.email') }}">@csrf
        <div><label class="vpr-label" for="email">{{ __('messages.email') }}</label><input class="vpr-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus></div>
        <button class="vpr-submit" type="submit">{{ __('password_reset.send_link') }}</button>
    </form>
    <a class="vpr-back" href="{{ route('login') }}">← {{ __('password_reset.back_to_login') }}</a>
    <div class="vpr-langs">@foreach($supportedLocales as $supportedLocale)<a class="{{ $supportedLocale === $locale ? 'active' : '' }}" href="{{ route('tenant.change.language', ['lang'=>$supportedLocale]) }}">{{ strtoupper($supportedLocale) }}</a>@endforeach</div>
</div></section></div>
<script>(function(){const s=localStorage.getItem('velora-theme'),d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.dataset.theme=s||(d?'dark':'light')})();</script>
</body></html>
