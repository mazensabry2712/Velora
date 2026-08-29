<!doctype html>
@php
    $locale = app()->getLocale() ?: config('app.locale', 'ar');
    $isRtl = in_array($locale, ['ar', 'he', 'fa'], true);
    $businessSettings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
    $businessLogo = $businessSettings?->logo ?? null;
    $businessName = $businessSettings?->business_name ?? null;

    if (is_array($businessName)) {
        $businessName = $businessName[$locale] ?? ($businessName['en'] ?? (reset($businessName) ?: null));
    }

    $displayName = is_scalar($businessName) && (string) $businessName !== ''
        ? (string) $businessName
        : tenant()->name;

    $tenantDomain = request()->getHost();
    $supportedLocales = config('localizer.supported_locales', ['ar', 'en']);
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0D1226">
    <meta name="color-scheme" content="light dark">
    <title>{{ __('messages.login') }} · {{ $displayName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-auth.css') }}">
    <style>
        :root{color-scheme:light dark}
        .vtl-page{min-height:100dvh;background:var(--va-bg);color:var(--va-text);position:relative;overflow:hidden}
        .vtl-page:before{content:"";position:absolute;width:720px;height:720px;top:-360px;inset-inline-start:-170px;border-radius:50%;background:radial-gradient(circle,rgba(109,70,255,.13),transparent 69%);pointer-events:none}
        .vtl-page:after{content:"";position:absolute;width:620px;height:620px;bottom:-380px;inset-inline-end:-220px;border-radius:50%;background:radial-gradient(circle,rgba(0,184,255,.10),transparent 70%);pointer-events:none}
        .vtl-shell{position:relative;z-index:1;width:min(1180px,calc(100% - 32px));margin-inline:auto;padding:18px 0 30px;min-height:100dvh;display:flex;flex-direction:column}
        .vtl-topbar{height:62px;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .vtl-brand{display:inline-flex;align-items:center;gap:11px;min-width:0;text-decoration:none;color:inherit}
        .vtl-brand img{width:40px;height:40px;object-fit:contain;border-radius:11px;flex:0 0 auto}
        .vtl-brand-copy{min-width:0}.vtl-brand-copy strong{display:block;font-size:14px;font-weight:800;letter-spacing:-.02em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px}.vtl-brand-copy span{display:block;margin-top:2px;font-size:9px;font-weight:700;color:var(--va-muted)}
        .vtl-actions{display:flex;align-items:center;gap:7px}
        .vtl-tool{height:38px;min-width:38px;padding:0 10px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--va-line);border-radius:11px;background:color-mix(in srgb,var(--va-surface) 94%,transparent);color:var(--va-text);font-size:10px;font-weight:800;cursor:pointer;transition:transform .18s,border-color .18s,color .18s}
        .vtl-tool:hover{transform:translateY(-1px);border-color:rgba(0,108,255,.35);color:var(--va-accent)}
        .vtl-language{position:relative}.vtl-menu{position:absolute;top:calc(100% + 8px);inset-inline-end:0;width:190px;padding:7px;border:1px solid var(--va-line);border-radius:15px;background:var(--va-surface);box-shadow:0 24px 60px rgba(13,18,38,.16);z-index:30}.vtl-menu[hidden]{display:none}.vtl-menu a{display:flex;align-items:center;justify-content:space-between;min-height:38px;padding:0 10px;border-radius:10px;color:var(--va-muted);font-size:10px;font-weight:800;text-decoration:none}.vtl-menu a:hover,.vtl-menu a.active{background:rgba(0,108,255,.07);color:var(--va-accent)}
        .vtl-main{flex:1;display:grid;place-items:center;padding:32px 0 38px}
        .vtl-grid{width:min(960px,100%);display:grid;grid-template-columns:minmax(0,.88fr) minmax(360px,.72fr);gap:54px;align-items:center}
        .vtl-intro{padding-inline:8px}.vtl-eyebrow{display:inline-flex;align-items:center;gap:8px;padding:8px 11px;border:1px solid rgba(109,70,255,.18);border-radius:999px;background:rgba(109,70,255,.05);color:var(--va-accent);font-size:10px;font-weight:800}.vtl-eyebrow i{width:6px;height:6px;border-radius:50%;background:var(--va-gradient);display:block}
        .vtl-title{margin:18px 0 0;font-size:clamp(42px,5.5vw,66px);line-height:1.02;letter-spacing:-.065em;font-weight:800}.vtl-title .accent{background:var(--va-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}.vtl-copy{max-width:500px;margin:17px 0 0;color:var(--va-muted);font-size:14px;line-height:1.85}
        .vtl-domain{display:inline-flex;align-items:center;gap:8px;margin-top:22px;padding:10px 12px;border:1px solid var(--va-line);border-radius:12px;background:color-mix(in srgb,var(--va-surface) 92%,var(--va-bg));font-size:10px;font-weight:800;color:var(--va-text);max-width:100%;overflow:hidden}.vtl-domain svg{color:var(--va-accent);flex:0 0 auto}.vtl-domain span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .vtl-note{display:flex;flex-wrap:wrap;gap:8px 15px;margin-top:18px;color:#7e899b;font-size:9px;font-weight:700}.vtl-note span{display:inline-flex;align-items:center;gap:6px}.vtl-note b{width:5px;height:5px;border-radius:50%;background:#19b37a;display:block}
        .vtl-card{border:1px solid var(--va-line);border-radius:25px;background:color-mix(in srgb,var(--va-surface) 97%,transparent);box-shadow:0 28px 80px rgba(13,18,38,.11);overflow:hidden}.vtl-accent{height:4px;background:var(--va-gradient)}.vtl-inner{padding:29px}.vtl-head{display:flex;align-items:flex-start;gap:13px}.vtl-logo{width:48px;height:48px;object-fit:contain;border-radius:13px;border:1px solid var(--va-line);padding:6px;background:var(--va-surface);flex:0 0 auto}.vtl-head h2{margin:0;font-size:24px;line-height:1.15;letter-spacing:-.04em;font-weight:800}.vtl-head p{margin:7px 0 0;color:var(--va-muted);font-size:11px;line-height:1.65}.vtl-form{display:grid;gap:16px;margin-top:25px}.vtl-field label{display:block;margin-bottom:8px;font-size:10px;font-weight:800}.vtl-input-wrap{position:relative}.vtl-input-icon{position:absolute;top:50%;transform:translateY(-50%);inset-inline-start:14px;color:#8b95a7;pointer-events:none}.vtl-input{width:100%;height:52px;padding:0 43px 0 43px;border:1px solid var(--va-line);border-radius:13px;background:color-mix(in srgb,var(--va-surface) 91%,var(--va-bg));color:var(--va-text);font-size:13px;outline:none;transition:border-color .18s,box-shadow .18s}.vtl-input:focus{border-color:rgba(0,108,255,.55);box-shadow:0 0 0 4px rgba(0,108,255,.08)}.vtl-input::placeholder{color:#98a2b3}.vtl-password-toggle{position:absolute;top:50%;transform:translateY(-50%);inset-inline-end:8px;width:34px;height:34px;border:0;background:transparent;color:#8b95a7;cursor:pointer;border-radius:9px}.vtl-password-toggle:hover{background:rgba(0,108,255,.06);color:var(--va-accent)}
        .vtl-row{display:flex;align-items:center;justify-content:space-between;gap:12px}.vtl-check{display:flex;align-items:center;gap:7px;color:var(--va-muted);font-size:10px;font-weight:600}.vtl-check input{accent-color:var(--va-accent)}.vtl-forgot{color:var(--va-accent);font-size:10px;font-weight:800;cursor:default}.vtl-forgot[aria-disabled="true"]{opacity:.72}
        .vtl-alert{padding:11px 12px;border-radius:12px;font-size:10px;line-height:1.65}.vtl-alert.error{border:1px solid rgba(239,68,68,.18);background:rgba(239,68,68,.06);color:#B42318}.vtl-alert.success{border:1px solid rgba(16,185,129,.18);background:rgba(16,185,129,.06);color:#067647}
        .vtl-submit{width:100%;min-height:54px;display:flex;align-items:center;justify-content:center;gap:9px;border:0;border-radius:14px;background:var(--va-gradient);color:#fff;font-size:12px;font-weight:900;cursor:pointer;box-shadow:0 14px 32px rgba(0,108,255,.18);transition:transform .18s,box-shadow .18s,opacity .18s}.vtl-submit:hover{transform:translateY(-1px);box-shadow:0 19px 40px rgba(0,108,255,.23)}.vtl-submit:disabled{opacity:.62;cursor:not-allowed;transform:none}.vtl-submit-spinner{font-size:15px;line-height:1}
        .vtl-meta{margin-top:16px;padding-top:15px;border-top:1px solid var(--va-line);text-align:center;color:var(--va-muted);font-size:9px;line-height:1.7}.vtl-meta strong{color:var(--va-text);font-weight:800}.vtl-footer{text-align:center;color:#8994a6;font-size:9px}.vtl-footer a{color:var(--va-accent);font-weight:800;text-decoration:none}
        html[dir="rtl"] .vtl-title{letter-spacing:0}html[data-theme="dark"] .vtl-card{box-shadow:0 30px 90px rgba(0,0,0,.3)}html[data-theme="dark"] .vtl-input,html[data-theme="dark"] .vtl-domain{background:#10172A}
        @media(max-width:900px){.vtl-grid{grid-template-columns:1fr;gap:26px;width:min(650px,100%)}.vtl-intro{text-align:center}.vtl-copy{margin-inline:auto}.vtl-domain{max-width:100%}.vtl-note{justify-content:center}.vtl-card{width:100%}}
        @media(max-width:600px){.vtl-shell{width:min(100% - 20px,1180px);padding-top:9px}.vtl-topbar{height:54px}.vtl-brand img{width:36px;height:36px}.vtl-brand-copy strong{max-width:190px;font-size:13px}.vtl-brand-copy span{display:none}.vtl-tool{height:36px;min-width:36px}.vtl-main{padding:22px 0 24px}.vtl-title{font-size:clamp(38px,11vw,52px)}.vtl-copy{font-size:13px}.vtl-inner{padding:22px 19px}.vtl-card{border-radius:21px}.vtl-head h2{font-size:22px}.vtl-footer{padding-bottom:4px}}
        @media(max-width:400px){.vtl-brand-copy{display:none}.vtl-row{align-items:flex-start;flex-direction:column}.vtl-submit{min-height:52px}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{transition:none!important}}
    </style>
    <script>(function(){const saved=localStorage.getItem('velora-theme');const preferred=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.dataset.theme=saved||(preferred?'dark':'light')})();</script>
</head>
<body class="va-page">
<div class="vtl-page">
    <div class="vtl-shell">
        <header class="vtl-topbar">
            <a href="{{ route('customer.booking') }}" class="vtl-brand" aria-label="{{ $displayName }}">
                <img src="{{ asset($businessLogo ? 'storage/'.$businessLogo : 'logo-bais.png') }}" alt="{{ $displayName }}">
                <span class="vtl-brand-copy">
                    <strong>{{ $displayName }}</strong>
                    <span>{{ __('messages.login_to_account') }}</span>
                </span>
            </a>

            <div class="vtl-actions">
                <button id="themeToggle" type="button" class="vtl-tool" aria-label="{{ __('Toggle theme') }}">◐</button>
                <div class="vtl-language">
                    <button id="languageToggle" type="button" class="vtl-tool" aria-haspopup="listbox" aria-expanded="false" aria-label="{{ __('messages.language') }}">{{ strtoupper($locale) }}</button>
                    <div id="languageMenu" class="vtl-menu" role="listbox" hidden>
                        @foreach ($supportedLocales as $supportedLocale)
                            <a role="option" class="{{ $supportedLocale === $locale ? 'active' : '' }}" aria-selected="{{ $supportedLocale === $locale ? 'true' : 'false' }}" href="{{ route('tenant.change.language', ['lang' => $supportedLocale]) }}">
                                <span>{{ strtoupper($supportedLocale) }}</span>
                                @if ($supportedLocale === $locale)<span>✓</span>@endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </header>

        <main class="vtl-main">
            <div class="vtl-grid">
                <section class="vtl-intro" aria-labelledby="login-title">
                    <span class="vtl-eyebrow"><i></i>{{ __('messages.dashboard') }}</span>
                    <h1 id="login-title" class="vtl-title">{{ __('messages.login_to_account') }} <span class="accent">Velora</span></h1>
                    <p class="vtl-copy">{{ __('messages.login_to_account') }}</p>

                    <div class="vtl-domain" title="{{ $tenantDomain }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 3.8 5.6 3.8 9s-1.2 6.4-3.8 9c-2.6-2.6-3.8-5.6-3.8-9S9.4 5.6 12 3z"/></svg>
                        <span>{{ $tenantDomain }}</span>
                    </div>

                    <div class="vtl-note">
                        <span><b></b>{{ __('messages.login') }}</span>
                        <span><b></b>{{ __('messages.remember_me') }}</span>
                        <span><b></b>RTL / LTR</span>
                    </div>
                </section>

                <section class="vtl-card" aria-labelledby="form-title">
                    <div class="vtl-accent"></div>
                    <div class="vtl-inner">
                        <div class="vtl-head">
                            <img class="vtl-logo" src="{{ asset($businessLogo ? 'storage/'.$businessLogo : 'logo-bais.png') }}" alt="{{ $displayName }}">
                            <div>
                                <h2 id="form-title">{{ __('messages.login') }}</h2>
                                <p>{{ __('messages.login_to_account') }}</p>
                            </div>
                        </div>

                        <form id="loginForm" class="vtl-form" novalidate>
                            @csrf
                            <div class="vtl-field">
                                <label for="email">{{ __('messages.email') }}</label>
                                <div class="vtl-input-wrap">
                                    <span class="vtl-input-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span>
                                    <input class="vtl-input" type="email" id="email" name="email" autocomplete="username" required autofocus placeholder="name@example.com">
                                </div>
                            </div>

                            <div class="vtl-field">
                                <label for="password">{{ __('messages.password') }}</label>
                                <div class="vtl-input-wrap">
                                    <span class="vtl-input-icon" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
                                    <input class="vtl-input" type="password" id="password" name="password" autocomplete="current-password" required placeholder="••••••••">
                                    <button id="passwordToggle" type="button" class="vtl-password-toggle" aria-label="{{ __('messages.password') }}">◉</button>
                                </div>
                            </div>

                            <div class="vtl-row">
                                <label class="vtl-check"><input type="checkbox" id="remember"><span>{{ __('messages.remember_me') }}</span></label>
                                <span class="vtl-forgot" aria-disabled="true" title="{{ __('Password reset is not enabled yet') }}">{{ __('Forgot your password?') }}</span>
                            </div>

                            <div id="errorMessage" class="vtl-alert error" hidden></div>
                            <div id="successMessage" class="vtl-alert success" hidden></div>

                            <button type="submit" id="submitBtn" class="vtl-submit">
                                <span id="btnText">{{ __('messages.login') }}</span>
                                <span id="loadingSpinner" class="vtl-submit-spinner" hidden aria-hidden="true">◌</span>
                            </button>
                        </form>

                        <div class="vtl-meta"><strong>{{ $tenantDomain }}</strong><br>{{ __('messages.login_to_account') }}</div>
                    </div>
                </section>
            </div>
        </main>

        <footer class="vtl-footer">
            <a href="{{ route('customer.booking') }}">{{ __('Back to workspace') }}</a> · Velora · {{ date('Y') }}
        </footer>
    </div>
</div>

<script>
    const texts = {
        loggingIn: @json(__('messages.loading')),
        login: @json(__('messages.login')),
        loginSuccess: @json(__('messages.login_success')),
        loginError: @json(__('messages.login_failed')),
        errorOccurred: @json(__('messages.error_occurred')),
    };

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
        if (!event.target.closest('.vtl-language')) {
            languageMenu.hidden = true;
            languageToggle.setAttribute('aria-expanded', 'false');
        }
    });

    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');
    passwordToggle.addEventListener('click', () => {
        const visible = passwordInput.type === 'text';
        passwordInput.type = visible ? 'password' : 'text';
        passwordToggle.textContent = visible ? '◉' : '◌';
    });

    document.getElementById('loginForm').addEventListener('submit', async (event) => {
        event.preventDefault();

        const errorDiv = document.getElementById('errorMessage');
        const successDiv = document.getElementById('successMessage');
        const submitButton = document.getElementById('submitBtn');
        const buttonText = document.getElementById('btnText');
        const spinner = document.getElementById('loadingSpinner');

        errorDiv.hidden = true;
        successDiv.hidden = true;
        submitButton.disabled = true;
        buttonText.textContent = texts.loggingIn;
        spinner.hidden = false;

        try {
            const response = await fetch('{{ url('/api/auth/login') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    email: document.getElementById('email').value.trim(),
                    password: passwordInput.value,
                    remember: document.getElementById('remember').checked,
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.success) {
                const validation = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                throw new Error(validation || data.message || data.error || texts.loginError);
            }

            if (data.access_token) localStorage.setItem('auth_token', data.access_token);
            if (data.user) localStorage.setItem('user', JSON.stringify(data.user));

            successDiv.textContent = '✓ ' + texts.loginSuccess;
            successDiv.hidden = false;
            window.setTimeout(() => window.location.replace(data.redirect_to || '/admin/dashboard'), 450);
        } catch (error) {
            errorDiv.textContent = '✕ ' + (error.message || texts.errorOccurred);
            errorDiv.hidden = false;
            submitButton.disabled = false;
            buttonText.textContent = texts.login;
            spinner.hidden = true;
        }
    });
</script>
</body>
</html>
