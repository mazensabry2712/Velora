<!doctype html>
@php
    $locale = app()->getLocale() ?: 'en';
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
    $supportedLocales = config('localizer.supported_locales', ['ar', 'en']);

    $businessLogo = $businessSettings?->logo;
    $hasBusinessLogo = is_string($businessLogo)
        && trim($businessLogo) !== ''
        && \Illuminate\Support\Facades\Storage::disk('public')->exists(ltrim($businessLogo, '/'));

    $logoUrl = $hasBusinessLogo
        ? asset('storage/' . ltrim($businessLogo, '/'))
        : asset('logo-bais.png');
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#F5F7FA">
    <meta name="color-scheme" content="light dark">
    <title>{{ __('password_reset.title') }} · {{ $displayName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-auth.css') }}">
    <style>
        *{box-sizing:border-box}
        .vpr-page{min-height:100dvh;background:var(--va-bg);color:var(--va-text);display:flex;flex-direction:column;position:relative;overflow:hidden}
        .vpr-page:before{content:"";position:absolute;width:620px;height:620px;top:-420px;inset-inline-start:-240px;border-radius:50%;background:radial-gradient(circle,rgba(109,70,255,.11),transparent 70%);pointer-events:none}
        .vpr-page:after{content:"";position:absolute;width:520px;height:520px;bottom:-360px;inset-inline-end:-240px;border-radius:50%;background:radial-gradient(circle,rgba(0,184,255,.08),transparent 70%);pointer-events:none}
        .vpr-shell{position:relative;z-index:1;width:min(100% - 32px,1120px);min-height:100dvh;margin-inline:auto;display:flex;flex-direction:column;padding:16px 0 18px}
        .vpr-topbar{height:54px;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .vpr-brand{display:inline-flex;align-items:center;gap:10px;text-decoration:none;color:inherit;min-width:0}
        .vpr-brand img{width:38px;height:38px;object-fit:contain;padding:5px;border:1px solid var(--va-line);border-radius:11px;background:var(--va-surface)}
        .vpr-brand-copy strong{display:block;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--va-text);font-size:14px;font-weight:800;letter-spacing:-.02em}
        .vpr-brand-copy span{display:block;margin-top:2px;color:var(--va-muted);font-size:9px;font-weight:700}
        .vpr-tools{display:flex;align-items:center;gap:7px}
        .vpr-tool{height:36px;min-width:36px;padding:0 10px;border:1px solid var(--va-line);border-radius:10px;background:var(--va-surface);color:var(--va-text);font-size:10px;font-weight:800;cursor:pointer;transition:.18s}
        .vpr-tool:hover{border-color:rgba(0,108,255,.35);color:var(--va-accent);transform:translateY(-1px)}
        .vpr-language{position:relative}.vpr-menu{position:absolute;top:calc(100% + 8px);inset-inline-end:0;width:190px;padding:7px;border:1px solid var(--va-line);border-radius:14px;background:var(--va-surface);box-shadow:0 24px 60px rgba(13,18,38,.14);z-index:30}.vpr-menu[hidden]{display:none}.vpr-menu a{display:flex;align-items:center;justify-content:space-between;min-height:38px;padding:0 10px;border-radius:9px;color:var(--va-muted);font-size:10px;font-weight:800;text-decoration:none}.vpr-menu a:hover,.vpr-menu a.active{background:rgba(0,108,255,.07);color:var(--va-accent)}
        .vpr-main{flex:1;display:grid;place-items:center;padding:18px 0 24px}
        .vpr-card{width:min(100%,440px);border:1px solid var(--va-line);border-radius:24px;background:var(--va-surface);box-shadow:0 28px 80px rgba(13,18,38,.09);overflow:hidden}
        .vpr-accent{height:4px;background:linear-gradient(90deg,#6D46FF 0%,#006CFF 52%,#00B8FF 100%)}
        .vpr-inner{padding:30px}
        .vpr-identity{text-align:center}
        .vpr-logo{width:62px;height:62px;object-fit:contain;padding:8px;border:1px solid var(--va-line);border-radius:17px;background:var(--va-surface);box-shadow:0 10px 28px rgba(13,18,38,.06)}
        .vpr-name{margin-top:11px;color:var(--va-text);font-size:17px;font-weight:800;letter-spacing:-.02em}
        .vpr-domain{display:inline-flex;align-items:center;gap:7px;margin-top:6px;max-width:100%;padding:6px 10px;border:1px solid var(--va-line);border-radius:999px;background:color-mix(in srgb,var(--va-surface) 94%,var(--va-bg));color:var(--va-muted);font-size:10px;font-weight:700}
        .vpr-domain svg{color:var(--va-accent);flex:0 0 auto}.vpr-domain span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .vpr-heading{margin-top:24px;text-align:center}.vpr-heading h1{margin:0;color:var(--va-text);font-size:28px;line-height:1.12;letter-spacing:-.04em;font-weight:800}.vpr-heading p{margin:9px auto 0;max-width:330px;color:var(--va-muted);font-size:11px;line-height:1.75}
        .vpr-form{display:grid;gap:15px;margin-top:23px}.vpr-label{display:block;margin-bottom:8px;color:var(--va-text);font-size:10px;font-weight:800}.vpr-input-wrap{position:relative}.vpr-input-icon{position:absolute;top:50%;transform:translateY(-50%);inset-inline-start:14px;color:#8b95a7;pointer-events:none}.vpr-input{width:100%;height:53px;padding:0 43px;border:1px solid var(--va-line);border-radius:13px;background:color-mix(in srgb,var(--va-surface) 93%,var(--va-bg));color:var(--va-text);font-size:13px;outline:none;transition:.18s}.vpr-input::placeholder{color:#98a2b3}.vpr-input:focus{border-color:rgba(0,108,255,.58);box-shadow:0 0 0 4px rgba(0,108,255,.08);background:var(--va-surface)}
        .vpr-status,.vpr-error{padding:11px 12px;border-radius:12px;font-size:10px;line-height:1.65}.vpr-status{border:1px solid rgba(16,185,129,.18);background:rgba(16,185,129,.06);color:#067647}.vpr-error{border:1px solid rgba(239,68,68,.18);background:rgba(239,68,68,.06);color:#B42318}
        .vpr-submit{width:100%;min-height:54px;border:0;border-radius:14px;background:linear-gradient(90deg,#6D46FF 0%,#006CFF 52%,#00B8FF 100%);color:#fff;font-size:12px;font-weight:900;cursor:pointer;box-shadow:0 14px 34px rgba(0,108,255,.18);transition:.18s}.vpr-submit:hover{transform:translateY(-1px);box-shadow:0 19px 42px rgba(0,108,255,.24)}
        .vpr-back{display:flex;align-items:center;justify-content:center;margin-top:16px;color:var(--va-accent);font-size:10px;font-weight:800;text-decoration:none}.vpr-back:hover{text-decoration:underline}
        .vpr-note{margin-top:17px;padding-top:14px;border-top:1px solid var(--va-line);text-align:center;color:#8792a5;font-size:9px;line-height:1.7}
        .vpr-footer{text-align:center;color:#8994a6;font-size:9px}.vpr-footer a{color:var(--va-accent);font-weight:800;text-decoration:none}
        html[dir="rtl"] .vpr-heading h1{letter-spacing:0}html[data-theme="dark"] .vpr-card{box-shadow:0 30px 90px rgba(0,0,0,.30)}html[data-theme="dark"] .vpr-input,html[data-theme="dark"] .vpr-domain{background:#10172A}
        @media(max-width:600px){.vpr-shell{width:calc(100% - 20px);padding-top:10px}.vpr-brand img{width:36px;height:36px}.vpr-brand-copy span{display:none}.vpr-main{padding:16px 0 20px}.vpr-inner{padding:23px 20px}.vpr-card{border-radius:21px}.vpr-logo{width:58px;height:58px}.vpr-heading h1{font-size:26px}}
        @media(max-width:400px){.vpr-inner{padding:21px 17px}.vpr-brand-copy{display:none}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{transition:none!important}}
    </style>
    <script>(function(){const saved=localStorage.getItem('velora-theme');const preferred=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.dataset.theme=saved||(preferred?'dark':'light')})();</script>
</head>
<body class="va-page">
<div class="vpr-page">
    <div class="vpr-shell">
        <header class="vpr-topbar">
            <a href="{{ route('login') }}" class="vpr-brand" aria-label="{{ $displayName }}">
                <img src="{{ $logoUrl }}" alt="{{ $displayName }}">
                <span class="vpr-brand-copy">
                    <strong>{{ $displayName }}</strong>
                    <span>{{ __('password_reset.secure_recovery') }}</span>
                </span>
            </a>
            <div class="vpr-tools">
                <button id="themeToggle" type="button" class="vpr-tool" aria-label="{{ __('Toggle theme') }}">◐</button>
                <div class="vpr-language">
                    <button id="languageToggle" type="button" class="vpr-tool" aria-haspopup="listbox" aria-expanded="false" aria-label="{{ __('messages.language') }}">{{ strtoupper($locale) }}</button>
                    <div id="languageMenu" class="vpr-menu" role="listbox" hidden>
                        @foreach ($supportedLocales as $supportedLocale)
                            <a role="option" class="{{ $supportedLocale === $locale ? 'active' : '' }}" aria-selected="{{ $supportedLocale === $locale ? 'true' : 'false' }}" href="{{ route('tenant.change.language', ['lang'=>$supportedLocale]) }}">
                                <span>{{ strtoupper($supportedLocale) }}</span>
                                @if ($supportedLocale === $locale)<span>✓</span>@endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </header>

        <main class="vpr-main">
            <section class="vpr-card" aria-labelledby="reset-title">
                <div class="vpr-accent"></div>
                <div class="vpr-inner">
                    <div class="vpr-identity">
                        <img class="vpr-logo" src="{{ $logoUrl }}" alt="{{ $displayName }}">
                        <div class="vpr-name">{{ $displayName }}</div>
                        <div class="vpr-domain" title="{{ $tenantDomain }}">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 3.8 5.6 3.8 9s-1.2 6.4-3.8 9-3.8-6.4-3.8-9S9.4 5.6 12 3z"/></svg>
                            <span>{{ $tenantDomain }}</span>
                        </div>
                    </div>

                    <div class="vpr-heading">
                        <h1 id="reset-title">{{ __('password_reset.heading') }}</h1>
                        <p>{{ __('password_reset.description') }}</p>
                    </div>

                    @if(session('status'))
                        <div class="vpr-status" role="status">{{ session('status') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="vpr-error" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <form class="vpr-form" method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <div>
                            <label class="vpr-label" for="email">{{ __('messages.email') }}</label>
                            <div class="vpr-input-wrap">
                                <span class="vpr-input-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span>
                                <input class="vpr-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus placeholder="name@example.com">
                            </div>
                        </div>
                        <button class="vpr-submit" type="submit">{{ __('password_reset.send_link') }}</button>
                    </form>

                    <a class="vpr-back" href="{{ route('login') }}">← {{ __('password_reset.back_to_login') }}</a>
                    <div class="vpr-note">{{ __('password_reset.secure_recovery') }}</div>
                </div>
            </section>
        </main>

        <footer class="vpr-footer">Velora · {{ date('Y') }}</footer>
    </div>
</div>

<script>
    const root = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', () => {
        const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = next;
        localStorage.setItem('velora-theme', next);
    });

    const languageToggle = document.getElementById('languageToggle');
    const languageMenu = document.getElementById('languageMenu');
    languageToggle.addEventListener('click', () => {
        const open = languageMenu.hidden;
        languageMenu.hidden = !open;
        languageToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.vpr-language')) {
            languageMenu.hidden = true;
            languageToggle.setAttribute('aria-expanded', 'false');
        }
    });
</script>
</body>
</html>
