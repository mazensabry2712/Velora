<!doctype html>
@php
    $locale = app()->getLocale() ?: config('app.locale', 'ar');
    $isRtl = in_array($locale, ['ar', 'he', 'fa'], true);
    $businessSettings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
    $businessName = $businessSettings?->business_name;
    if (is_array($businessName)) {
        $businessName = $businessName[$locale] ?? ($businessName['en'] ?? reset($businessName));
    }
    $displayName = is_scalar($businessName) && trim((string) $businessName) !== ''
        ? trim((string) $businessName)
        : tenant()->name;
    $tenantDomain = request()->getHost();
    $supportedLocales = array_values(array_unique(config('localizer.supported_locales', ['ar', 'en'])));
    $businessLogo = $businessSettings?->logo;
    $hasBusinessLogo = is_string($businessLogo)
        && trim($businessLogo) !== ''
        && \Illuminate\Support\Facades\Storage::disk('public')->exists(ltrim($businessLogo, '/'));
    $logoUrl = $hasBusinessLogo ? asset('storage/' . ltrim($businessLogo, '/')) : asset('logo-bais.png');
    $languageNames = [
        'ar' => 'العربية', 'de' => 'Deutsch', 'en' => 'English', 'es' => 'Español', 'fr' => 'Français',
        'hi' => 'हिन्दी', 'id' => 'Bahasa Indonesia', 'it' => 'Italiano', 'ja' => '日本語', 'ko' => '한국어',
        'nl' => 'Nederlands', 'pt' => 'Português', 'ru' => 'Русский', 'tr' => 'Türkçe', 'zh' => '中文',
    ];
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#F7F9FC">
    <meta name="color-scheme" content="light dark">
    <title>{{ __('password_reset.title') }} · {{ $displayName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-auth.css') }}">
    <style>
        *{box-sizing:border-box}html,body{margin:0;min-height:100%;font-family:Inter,'Plus Jakarta Sans',Tajawal,system-ui,sans-serif}
        .vpr-page{min-height:100dvh;background:var(--va-bg);color:var(--va-text)}
        .vpr-shell{width:min(calc(100% - 40px),1180px);min-height:100dvh;margin-inline:auto;display:flex;flex-direction:column}
        .vpr-topbar{height:72px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid color-mix(in srgb,var(--va-line) 75%,transparent)}
        .vpr-brand{display:flex;align-items:center;gap:11px;min-width:0;text-decoration:none;color:inherit}.vpr-brand-mark{width:38px;height:38px;display:grid;place-items:center;overflow:hidden;border:1px solid var(--va-line);border-radius:11px;background:var(--va-surface)}.vpr-brand-mark img{width:100%;height:100%;padding:6px;object-fit:contain}.vpr-brand-copy strong{display:block;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;font-weight:800}.vpr-brand-copy small{display:block;margin-top:2px;color:var(--va-muted);font-size:9px;font-weight:600}
        .vpr-tools{display:flex;align-items:center;gap:8px}.vpr-control{height:38px;display:inline-flex;align-items:center;gap:8px;padding:0 11px;border:1px solid var(--va-line);border-radius:11px;background:var(--va-surface);color:var(--va-text);font-size:10px;font-weight:800;cursor:pointer;transition:.18s}.vpr-control:hover{border-color:rgba(0,108,255,.35);box-shadow:0 7px 18px rgba(0,108,255,.08)}.vpr-control-icon{display:grid;place-items:center;color:var(--va-muted)}
        .vpr-language{position:relative}.vpr-menu{position:absolute;top:calc(100% + 9px);inset-inline-end:0;width:232px;padding:7px;border:1px solid var(--va-line);border-radius:15px;background:var(--va-surface);box-shadow:0 22px 60px rgba(13,18,38,.14);z-index:30}.vpr-menu[hidden]{display:none}.vpr-menu-head{padding:7px 9px 8px;color:var(--va-muted);font-size:9px;font-weight:800}.vpr-menu-list{max-height:300px;overflow:auto}.vpr-menu a{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:38px;padding:0 9px;border-radius:9px;color:var(--va-muted);font-size:10px;font-weight:700;text-decoration:none}.vpr-menu a:hover,.vpr-menu a.active{background:rgba(0,108,255,.07);color:var(--va-accent)}.vpr-menu .lang-main{display:flex;align-items:center;gap:8px}.vpr-menu .code{width:28px;color:var(--va-accent);font-size:9px;font-weight:900}
        .vpr-main{flex:1;display:grid;place-items:center;padding:34px 0}.vpr-card{width:min(100%,450px);border:1px solid var(--va-line);border-radius:23px;background:var(--va-surface);box-shadow:0 24px 70px rgba(13,18,38,.08);overflow:hidden}.vpr-accent{height:3px;background:var(--velora-gradient,linear-gradient(90deg,#6D46FF,#006CFF 52%,#00B8FF))}.vpr-inner{padding:32px}
        .vpr-identity{text-align:center}.vpr-logo{width:60px;height:60px;padding:7px;object-fit:contain;border:1px solid var(--va-line);border-radius:17px;background:var(--va-surface)}.vpr-name{margin-top:11px;font-size:17px;font-weight:800;letter-spacing:-.02em}.vpr-domain{display:inline-flex;align-items:center;gap:7px;margin-top:6px;padding:6px 10px;border:1px solid var(--va-line);border-radius:999px;background:color-mix(in srgb,var(--va-surface) 94%,var(--va-bg));color:var(--va-muted);font-size:10px;font-weight:700;max-width:100%}.vpr-domain span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.vpr-domain svg{color:var(--va-accent)}
        .vpr-heading{margin-top:25px;text-align:center}.vpr-heading h1{margin:0;font-size:29px;line-height:1.12;letter-spacing:-.04em;font-weight:800}.vpr-heading p{margin:9px auto 0;max-width:320px;color:var(--va-muted);font-size:11px;line-height:1.8}.vpr-form{display:grid;gap:16px;margin-top:24px}.vpr-label{display:block;margin-bottom:8px;font-size:10px;font-weight:800}.vpr-input-wrap{position:relative}.vpr-input-icon{position:absolute;top:50%;transform:translateY(-50%);inset-inline-start:14px;color:#8b95a7;pointer-events:none}.vpr-input{width:100%;height:54px;padding:0 43px;border:1px solid var(--va-line);border-radius:13px;background:var(--va-surface);color:var(--va-text);font-size:13px;outline:none;transition:.18s}.vpr-input:focus{border-color:rgba(0,108,255,.6);box-shadow:0 0 0 4px rgba(0,108,255,.08)}.vpr-input::placeholder{color:#98a2b3}
        .vpr-status,.vpr-error{padding:11px 12px;border-radius:12px;font-size:10px;line-height:1.65}.vpr-status{border:1px solid rgba(16,185,129,.18);background:rgba(16,185,129,.06);color:#067647}.vpr-error{border:1px solid rgba(239,68,68,.18);background:rgba(239,68,68,.06);color:#B42318}.vpr-submit{width:100%;min-height:54px;border:0;border-radius:14px;background:var(--velora-gradient,linear-gradient(90deg,#6D46FF,#006CFF 52%,#00B8FF));color:#fff;font-size:12px;font-weight:900;cursor:pointer;box-shadow:0 13px 30px rgba(0,108,255,.17);transition:.18s}.vpr-submit:hover{transform:translateY(-1px);box-shadow:0 18px 38px rgba(0,108,255,.23)}.vpr-back{display:flex;justify-content:center;margin-top:15px;color:var(--va-accent);font-size:10px;font-weight:800;text-decoration:none}.vpr-back:hover{text-decoration:underline}.vpr-note{margin-top:17px;padding-top:14px;border-top:1px solid var(--va-line);text-align:center;color:#8792a5;font-size:9px}.vpr-footer{text-align:center;padding-bottom:20px;color:#8994a6;font-size:9px}
        html[data-theme="dark"] .vpr-card{box-shadow:0 30px 90px rgba(0,0,0,.32)}
        @media(max-width:600px){.vpr-shell{width:calc(100% - 20px)}.vpr-topbar{height:64px}.vpr-brand-copy small{display:none}.vpr-brand-copy strong{max-width:170px}.vpr-control{width:38px;padding:0;justify-content:center}.vpr-control .text{display:none}.vpr-main{padding:20px 0 24px}.vpr-inner{padding:23px 20px}.vpr-card{border-radius:21px}.vpr-heading h1{font-size:26px}.vpr-logo{width:58px;height:58px}}
        @media(max-width:400px){.vpr-brand-copy{display:none}.vpr-inner{padding:20px 17px}}
    </style>
    <script>(function(){const saved=localStorage.getItem('velora-theme');const preferred=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.dataset.theme=saved||(preferred?'dark':'light')})();</script>
</head>
<body class="va-page">
<div class="vpr-page"><div class="vpr-shell">
    <header class="vpr-topbar">
        <a href="{{ route('login') }}" class="vpr-brand" aria-label="{{ $displayName }}">
            <span class="vpr-brand-mark"><img src="{{ $logoUrl }}" onerror="this.onerror=null;this.src='{{ asset('logo-bais.png') }}';" alt="{{ $displayName }}"></span>
            <span class="vpr-brand-copy"><strong>{{ $displayName }}</strong><small>{{ __('password_reset.secure_recovery') }}</small></span>
        </a>
        <div class="vpr-tools">
            <button id="themeToggle" type="button" class="vpr-control" aria-label="{{ __('Toggle theme') }}"><span class="vpr-control-icon" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3v2M12 19v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M3 12h2M19 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/><circle cx="12" cy="12" r="4"/></svg></span><span class="text">Theme</span></button>
            <div class="vpr-language">
                <button id="languageToggle" type="button" class="vpr-control" aria-haspopup="listbox" aria-expanded="false" aria-label="{{ __('messages.language') }}"><span class="vpr-control-icon" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 3.8 6.4 3.8 9s-1.2 6.4-3.8 9-3.8-6.4-3.8-9S9.4 5.6 12 3z"/></svg></span><span class="text">{{ strtoupper($locale) }}</span></button>
                <div id="languageMenu" class="vpr-menu" role="listbox" hidden>
                    <div class="vpr-menu-head">{{ __('messages.language') }}</div>
                    <div class="vpr-menu-list">
                        @foreach ($supportedLocales as $supportedLocale)
                            <a role="option" class="{{ $supportedLocale === $locale ? 'active' : '' }}" aria-selected="{{ $supportedLocale === $locale ? 'true' : 'false' }}" href="{{ route('tenant.change.language', ['lang'=>$supportedLocale]) }}"><span class="lang-main"><span class="code">{{ strtoupper($supportedLocale) }}</span><span>{{ $languageNames[$supportedLocale] ?? strtoupper($supportedLocale) }}</span></span>@if($supportedLocale === $locale)<span aria-hidden="true">✓</span>@endif</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </header>
    <main class="vpr-main"><section class="vpr-card" aria-labelledby="reset-title"><div class="vpr-accent"></div><div class="vpr-inner">
        <div class="vpr-identity">
            <img class="vpr-logo" src="{{ $logoUrl }}" onerror="this.onerror=null;this.src='{{ asset('logo-bais.png') }}';" alt="{{ $displayName }}">
            <div class="vpr-name">{{ $displayName }}</div>
            <div class="vpr-domain" title="{{ $tenantDomain }}"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 3.8 6.4 3.8 9s-1.2 6.4-3.8 9-3.8-6.4-3.8-9S9.4 5.6 12 3z"/></svg><span>{{ $tenantDomain }}</span></div>
        </div>
        <div class="vpr-heading"><h1 id="reset-title">{{ __('password_reset.heading') }}</h1><p>{{ __('password_reset.description') }}</p></div>
        @if(session('status'))<div class="vpr-status" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="vpr-error" role="alert">{{ $errors->first() }}</div>@endif
        <form class="vpr-form" method="POST" action="{{ route('password.email') }}">@csrf
            <div><label class="vpr-label" for="email">{{ __('messages.email') }}</label><div class="vpr-input-wrap"><span class="vpr-input-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span><input class="vpr-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus placeholder="name@example.com"></div></div>
            <button class="vpr-submit" type="submit">{{ __('password_reset.send_link') }}</button>
        </form>
        <a class="vpr-back" href="{{ route('login') }}">← {{ __('password_reset.back_to_login') }}</a>
        <div class="vpr-note">{{ __('password_reset.secure_recovery') }}</div>
    </div></section></main>
    <footer class="vpr-footer">Velora · {{ date('Y') }}</footer>
</div></div>
<script>
const root=document.documentElement,themeToggle=document.getElementById('themeToggle');themeToggle.addEventListener('click',()=>{const next=root.dataset.theme==='dark'?'light':'dark';root.dataset.theme=next;localStorage.setItem('velora-theme',next)});
const languageToggle=document.getElementById('languageToggle'),languageMenu=document.getElementById('languageMenu');languageToggle.addEventListener('click',()=>{const open=languageMenu.hidden;languageMenu.hidden=!open;languageToggle.setAttribute('aria-expanded',open?'true':'false')});document.addEventListener('click',event=>{if(!event.target.closest('.vpr-language')){languageMenu.hidden=true;languageToggle.setAttribute('aria-expanded','false')}});
</script>
</body>
</html>
