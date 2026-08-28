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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-auth.css') }}">
    <style>
        .vl-page{min-height:100dvh;background:var(--va-bg);color:var(--va-text);overflow:hidden;position:relative}
        .vl-page::before{content:"";position:absolute;width:720px;height:720px;top:-330px;right:-260px;border-radius:50%;background:radial-gradient(circle,rgba(109,70,255,.16),rgba(0,184,255,0) 68%);pointer-events:none}
        .vl-page::after{content:"";position:absolute;width:540px;height:540px;left:-240px;bottom:-300px;border-radius:50%;background:radial-gradient(circle,rgba(0,184,255,.12),rgba(109,70,255,0) 68%);pointer-events:none}
        .vl-shell{position:relative;z-index:1;width:min(1180px,calc(100% - 40px));margin-inline:auto;padding:18px 0 56px}
        .vl-nav{height:68px;display:flex;align-items:center;justify-content:space-between;gap:18px;padding:10px 12px 10px 16px;border:1px solid var(--va-line);border-radius:20px;background:color-mix(in srgb,var(--va-surface) 93%,transparent);backdrop-filter:blur(18px);box-shadow:0 14px 40px rgba(13,18,38,.07)}
        .vl-brand{display:flex;align-items:center;gap:12px;min-width:0}
        .vl-brand img{width:42px;height:42px;object-fit:contain;border-radius:12px;flex:0 0 auto}
        .vl-brand-copy strong{display:block;font-size:16px;font-weight:800;letter-spacing:-.02em;color:var(--va-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:250px}
        .vl-brand-copy span{display:block;margin-top:2px;color:var(--va-muted);font-size:10px;font-weight:600}
        .vl-nav-actions{display:flex;align-items:center;gap:7px}
        .vl-tool{height:42px;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:0 12px;border:1px solid var(--va-line);border-radius:12px;background:var(--va-surface);color:var(--va-text);font-size:11px;font-weight:800;cursor:pointer;transition:.18s}
        .vl-tool:hover{transform:translateY(-1px);border-color:rgba(0,108,255,.35);color:var(--va-accent)}
        .vl-language{position:relative}
        .vl-language-menu{position:absolute;top:calc(100% + 8px);inset-inline-end:0;width:190px;padding:7px;border:1px solid var(--va-line);border-radius:16px;background:var(--va-surface);box-shadow:0 20px 48px rgba(13,18,38,.14);z-index:20}
        .vl-language-menu[hidden]{display:none}
        .vl-language-option{display:flex;align-items:center;justify-content:space-between;min-height:40px;padding:0 11px;border-radius:11px;color:var(--va-muted);font-size:11px;font-weight:800;text-transform:uppercase}
        .vl-language-option:hover,.vl-language-option.active{background:rgba(0,108,255,.07);color:var(--va-accent)}
        .vl-main{display:grid;grid-template-columns:minmax(0,1.03fr) minmax(440px,.97fr);gap:34px;align-items:center;margin-top:36px}
        .vl-hero{padding:18px 12px 18px 8px}
        .vl-kicker{display:inline-flex;align-items:center;gap:8px;padding:9px 13px;border:1px solid rgba(109,70,255,.16);border-radius:999px;background:rgba(109,70,255,.05);color:var(--va-accent);font-size:11px;font-weight:800}
        .vl-dot{width:7px;height:7px;border-radius:50%;background:var(--va-gradient)}
        .vl-title{margin:20px 0 0;font-size:clamp(42px,5.5vw,72px);line-height:1.02;letter-spacing:-.065em;font-weight:800;color:var(--va-text)}
        .vl-title span{background:var(--va-gradient);-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}
        .vl-copy{max-width:600px;margin:18px 0 0;color:var(--va-muted);font-size:16px;line-height:1.85}
        .vl-highlights{display:grid;grid-template-columns:1fr;gap:10px;margin-top:28px;max-width:600px}
        .vl-highlight{display:flex;align-items:flex-start;gap:12px;padding:14px 15px;border:1px solid var(--va-line);border-radius:17px;background:color-mix(in srgb,var(--va-surface) 93%,var(--va-bg));box-shadow:0 8px 24px rgba(13,18,38,.03)}
        .vl-icon{width:36px;height:36px;display:grid;place-items:center;flex:0 0 auto;border-radius:11px;background:var(--va-gradient);color:#fff;font-size:13px;font-weight:900}
        .vl-highlight strong{display:block;color:var(--va-text);font-size:12px;font-weight:800}
        .vl-highlight span{display:block;margin-top:4px;color:var(--va-muted);font-size:11px;line-height:1.55}
        .vl-trust{display:flex;flex-wrap:wrap;gap:8px 16px;margin-top:19px;color:#7a8597;font-size:10px;font-weight:700}
        .vl-form-card{position:relative;border:1px solid var(--va-line);border-radius:28px;background:color-mix(in srgb,var(--va-surface) 96%,transparent);box-shadow:0 30px 90px rgba(13,18,38,.11);overflow:hidden}
        .vl-form-accent{height:5px;background:var(--va-gradient)}
        .vl-form-inner{padding:34px}
        .vl-form-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
        .vl-form-head h2{margin:0;color:var(--va-text);font-size:29px;letter-spacing:-.04em;font-weight:800}
        .vl-form-head p{margin:7px 0 0;color:var(--va-muted);font-size:12px;line-height:1.65}
        .vl-form{display:grid;gap:17px;margin-top:26px}
        .vl-field label{display:block;margin-bottom:8px;color:var(--va-text);font-size:11px;font-weight:800}
        .vl-input-wrap{position:relative}
        .vl-input{width:100%;height:54px;padding:0 15px;border:1px solid var(--va-line);border-radius:14px;background:color-mix(in srgb,var(--va-surface) 92%,var(--va-bg));color:var(--va-text);font-size:14px;outline:none;transition:border-color .18s,box-shadow .18s,background .18s}
        .vl-input::placeholder{color:#98A2B3}
        .vl-input:focus{border-color:rgba(0,108,255,.55);box-shadow:0 0 0 4px rgba(0,108,255,.08);background:var(--va-surface)}
        .vl-row{display:flex;align-items:center;justify-content:space-between;gap:14px}
        .vl-check{display:flex;align-items:center;gap:8px;color:var(--va-muted);font-size:11px;font-weight:600}
        .vl-check input{accent-color:var(--va-accent)}
        .vl-link{color:var(--va-accent);font-size:11px;font-weight:800}
        .vl-alert{padding:12px 13px;border-radius:13px;font-size:11px;line-height:1.6}
        .vl-alert.error{border:1px solid rgba(239,68,68,.18);background:rgba(239,68,68,.06);color:#B42318}
        .vl-alert.success{border:1px solid rgba(16,185,129,.18);background:rgba(16,185,129,.06);color:#067647}
        .vl-submit{width:100%;min-height:56px;display:flex;align-items:center;justify-content:center;gap:9px;border:0;border-radius:15px;background:var(--va-gradient);color:#fff;font-size:13px;font-weight:900;cursor:pointer;box-shadow:0 15px 34px rgba(0,108,255,.18);transition:.18s}
        .vl-submit:hover{transform:translateY(-1px);box-shadow:0 20px 42px rgba(0,108,255,.24)}
        .vl-submit:disabled{opacity:.62;cursor:not-allowed;transform:none}
        .vl-meta{margin-top:18px;padding-top:17px;border-top:1px solid var(--va-line);text-align:center;color:var(--va-muted);font-size:10px;line-height:1.7}
        .vl-footer{margin-top:26px;text-align:center;color:#8A95A8;font-size:10px}
        .vl-footer a{color:var(--va-accent);font-weight:800}
        html[dir="rtl"] .vl-title{letter-spacing:0}
        html[data-theme="dark"] .vl-tool,html[data-theme="dark"] .vl-form-card,html[data-theme="dark"] .vl-nav{box-shadow:0 30px 90px rgba(0,0,0,.26)}
        html[data-theme="dark"] .vl-input,html[data-theme="dark"] .vl-highlight{background:#10172A}
        @media(max-width:980px){.vl-main{grid-template-columns:1fr}.vl-hero{padding-inline:4px}.vl-form-card{max-width:760px;width:100%;margin-inline:auto}}
        @media(max-width:680px){.vl-shell{width:calc(100% - 20px);padding-top:10px}.vl-nav{height:60px;border-radius:17px;padding:8px 9px 8px 12px}.vl-brand img{width:36px;height:36px}.vl-brand-copy strong{max-width:170px;font-size:14px}.vl-brand-copy span{display:none}.vl-tool{height:38px;padding-inline:10px}.vl-main{margin-top:24px;gap:20px}.vl-title{font-size:clamp(38px,11vw,54px)}.vl-copy{font-size:14px}.vl-form-inner{padding:23px 20px}.vl-form-card{border-radius:22px}.vl-form-head h2{font-size:25px}.vl-row{align-items:flex-start;flex-direction:column}.vl-highlights{gap:8px}.vl-highlight{padding:12px}.vl-footer{margin-top:18px}}
        @media(max-width:400px){.vl-shell{width:calc(100% - 14px)}.vl-nav-actions{gap:5px}.vl-language-toggle{font-size:10px}.vl-form-inner{padding:21px 17px}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{transition:none!important}}
    </style>
    <script>(function(){const saved=localStorage.getItem('velora-theme'),preferred=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.dataset.theme=saved||(preferred?'dark':'light')})();</script>
</head>
<body class="va-page">
<div class="vl-page">
    <div class="vl-shell">
        <header class="vl-nav">
            <a href="{{ route('customer.booking') }}" class="vl-brand" aria-label="{{ $displayName }}">
                @if ($businessLogo)
                    <img src="{{ asset('storage/' . $businessLogo) }}" alt="{{ $displayName }}">
                @else
                    <img src="{{ asset('logo-bais.png') }}" alt="Velora">
                @endif
                <span class="vl-brand-copy">
                    <strong>{{ $displayName }}</strong>
                    <span>{{ __('messages.login_to_account') }}</span>
                </span>
            </a>
            <div class="vl-nav-actions">
                <button id="themeToggle" type="button" class="vl-tool" aria-label="{{ __('Toggle theme') }}">◐</button>
                <div class="vl-language">
                    <button id="languageToggle" type="button" class="vl-tool" aria-haspopup="listbox" aria-expanded="false" aria-label="{{ __('messages.language') }}">{{ strtoupper($locale) }}</button>
                    <div id="languageMenu" class="vl-language-menu" role="listbox" hidden>
                        @foreach ($supportedLocales as $supportedLocale)
                            <a role="option" class="vl-language-option{{ $supportedLocale === $locale ? ' active' : '' }}" aria-selected="{{ $supportedLocale === $locale ? 'true' : 'false' }}" href="{{ route('tenant.change.language', ['lang' => $supportedLocale]) }}">
                                <span>{{ strtoupper($supportedLocale) }}</span>
                                @if ($supportedLocale === $locale)<span>✓</span>@endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </header>

        <main class="vl-main">
            <section class="vl-hero">
                <div class="vl-kicker"><span class="vl-dot"></span>{{ __('messages.dashboard') }}</div>
                <h1 class="vl-title">{{ __('Welcome back') }} <span>Velora</span></h1>
                <p class="vl-copy">{{ __('messages.login_to_account') }}</p>

                <div class="vl-highlights">
                    <div class="vl-highlight"><span class="vl-icon">✓</span><div><strong>{{ __('Workspace-aware access') }}</strong><span>{{ __('Your session is opened inside the correct tenant workspace.') }}</span></div></div>
                    <div class="vl-highlight"><span class="vl-icon">✓</span><div><strong>{{ __('Verification first') }}</strong><span>{{ __('Unverified accounts are blocked until email verification is complete.') }}</span></div></div>
                    <div class="vl-highlight"><span class="vl-icon">✓</span><div><strong>{{ __('Bilingual by design') }}</strong><span>{{ __('RTL and LTR are handled consistently across the experience.') }}</span></div></div>
                </div>
                <div class="vl-trust"><span>✓ {{ __('Secure access to your workspace') }}</span><span>✓ {{ __('messages.remember_me') }}</span><span>✓ RTL / LTR</span></div>
            </section>

            <section class="vl-form-card">
                <div class="vl-form-accent"></div>
                <div class="vl-form-inner">
                    <div class="vl-form-head">
                        <div><h2>{{ __('messages.login') }}</h2><p>{{ __('messages.login_to_account') }}</p></div>
                    </div>

                    <form id="loginForm" class="vl-form" novalidate>
                        @csrf
                        <div class="vl-field">
                            <label for="email">{{ __('messages.email') }}</label>
                            <input class="vl-input" type="email" id="email" name="email" autocomplete="username" required autofocus placeholder="name@example.com">
                        </div>
                        <div class="vl-field">
                            <label for="password">{{ __('messages.password') }}</label>
                            <input class="vl-input" type="password" id="password" name="password" autocomplete="current-password" required placeholder="••••••••">
                        </div>
                        <div class="vl-row">
                            <label class="vl-check"><input type="checkbox" id="remember"> <span>{{ __('messages.remember_me') }}</span></label>
                            <span class="vl-link" aria-disabled="true" title="{{ __('Password reset is not enabled yet') }}">{{ __('Forgot your password?') }}</span>
                        </div>

                        <div id="errorMessage" class="vl-alert error" hidden></div>
                        <div id="successMessage" class="vl-alert success" hidden></div>

                        <button type="submit" id="submitBtn" class="vl-submit">
                            <span id="btnText">{{ __('messages.login') }}</span>
                            <span id="loadingSpinner" hidden aria-hidden="true">◌</span>
                        </button>
                    </form>

                    <div class="vl-meta">{{ __('Need another workspace? Open its tenant domain and sign in there.') }}</div>
                </div>
            </section>
        </main>

        <footer class="vl-footer">
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
        if (!event.target.closest('.vl-language')) {
            languageMenu.hidden = true;
            languageToggle.setAttribute('aria-expanded', 'false');
        }
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
                    password: document.getElementById('password').value,
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
