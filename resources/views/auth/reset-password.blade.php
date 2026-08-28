<!doctype html>
@php
    $locale = app()->getLocale() ?: 'en';
    $isRtl = in_array($locale, ['ar', 'he', 'fa'], true);
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('password_reset.reset_title') }} · Velora</title>
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-auth.css') }}">
    <style>
        .vrp-page{min-height:100dvh;display:grid;place-items:center;padding:24px;background:var(--va-bg);color:var(--va-text);position:relative;overflow:hidden}.vrp-page:after{content:'';position:absolute;width:580px;height:580px;left:-240px;bottom:-300px;border-radius:50%;background:radial-gradient(circle,rgba(0,184,255,.12),transparent 68%);pointer-events:none}.vrp-card{position:relative;z-index:1;width:min(100%,520px);border:1px solid var(--va-line);border-radius:28px;background:var(--va-surface);box-shadow:0 30px 90px rgba(13,18,38,.12);overflow:hidden}.vrp-accent{height:5px;background:var(--va-gradient)}.vrp-inner{padding:34px}.vrp-brand{display:flex;align-items:center;gap:12px}.vrp-brand img{width:42px;height:42px;object-fit:contain;border-radius:12px}.vrp-brand strong{display:block;font-size:16px;font-weight:800}.vrp-brand span{display:block;margin-top:2px;font-size:10px;color:var(--va-muted)}.vrp-title{margin-top:30px;font-size:34px;line-height:1.08;letter-spacing:-.04em;font-weight:800}.vrp-copy{margin-top:10px;color:var(--va-muted);font-size:13px;line-height:1.8}.vrp-form{display:grid;gap:15px;margin-top:24px}.vrp-label{display:block;margin-bottom:7px;font-size:11px;font-weight:800}.vrp-input{width:100%;height:54px;padding:0 15px;border:1px solid var(--va-line);border-radius:14px;background:var(--va-surface);color:var(--va-text);outline:none}.vrp-input:focus{border-color:rgba(0,108,255,.55);box-shadow:0 0 0 4px rgba(0,108,255,.08)}.vrp-submit{width:100%;height:56px;border:0;border-radius:15px;background:var(--va-gradient);color:#fff;font-weight:900;cursor:pointer;box-shadow:0 15px 34px rgba(0,108,255,.18)}.vrp-error{margin-top:15px;padding:12px 13px;border:1px solid rgba(239,68,68,.18);border-radius:13px;background:rgba(239,68,68,.06);color:#B42318;font-size:11px}.vrp-note{margin-top:16px;color:var(--va-muted);font-size:10px;line-height:1.7;text-align:center}.vrp-back{display:inline-flex;gap:8px;margin-top:20px;color:var(--va-accent);font-size:11px;font-weight:800}@media(max-width:540px){.vrp-page{padding:14px}.vrp-inner{padding:23px 20px}.vrp-title{font-size:29px}}
    </style>
</head>
<body>
<div class="vrp-page"><section class="vrp-card"><div class="vrp-accent"></div><div class="vrp-inner">
    <div class="vrp-brand"><img src="{{ asset('logo-bais.png') }}" alt="Velora"><div><strong>Velora</strong><span>{{ __('password_reset.secure_recovery') }}</span></div></div>
    <h1 class="vrp-title">{{ __('password_reset.reset_heading') }}</h1>
    <p class="vrp-copy">{{ __('password_reset.reset_description') }}</p>
    @if($errors->any())<div class="vrp-error">{{ $errors->first() }}</div>@endif
    <form class="vrp-form" method="POST" action="{{ route('password.update', ['token'=>$token]) }}">@csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <div><label class="vrp-label" for="password">{{ __('auth.password') }}</label><input class="vrp-input" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required></div>
        <div><label class="vrp-label" for="password_confirmation">{{ __('password_reset.confirm_password') }}</label><input class="vrp-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></div>
        <button class="vrp-submit" type="submit">{{ __('password_reset.update_password') }}</button>
    </form>
    <p class="vrp-note">{{ __('password_reset.token_note') }}</p>
    <a class="vrp-back" href="{{ route('login') }}">← {{ __('password_reset.back_to_login') }}</a>
</div></section></div>
<script>(function(){const s=localStorage.getItem('velora-theme'),d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.dataset.theme=s||(d?'dark':'light')})();</script>
</body></html>
