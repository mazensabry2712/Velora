<!doctype html>
@php
    $locale = app()->getLocale() ?: config('app.locale', 'ar');
    $isRtl = in_array($locale, ['ar', 'he', 'fa'], true);

    $businessSettings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
    $businessLogo = $businessSettings?->logo ?? null;
    $businessName = $businessSettings?->business_name ?? null;

    if (is_array($businessName)) {
        $businessName = $businessName[$locale]
            ?? ($businessName['en'] ?? (reset($businessName) ?: null));
    }

    $displayName = is_scalar($businessName) && trim((string) $businessName) !== ''
        ? trim((string) $businessName)
        : tenant()->name;

    $tenantDomain = request()->getHost();
    $supportedLocales = config('localizer.supported_locales', ['ar', 'en']);

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
    <meta name="theme-color" content="#F7F9FC">
    <meta name="color-scheme" content="light dark">
    <title>{{ __('messages.login') }} · {{ $displayName }}</title>

    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-auth.css') }}">
    <style>
        :root {
            --tl-bg: #f7f9fc;
            --tl-surface: #ffffff;
            --tl-text: #101828;
            --tl-muted: #667085;
            --tl-border: #e4e7ec;
            --tl-blue: #006cff;
            --tl-green: #12b76a;
        }

        html[data-theme="dark"] {
            --tl-bg: #080b18;
            --tl-surface: #0d1226;
            --tl-text: #f8fafc;
            --tl-muted: #a7b0c0;
            --tl-border: #252e45;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; min-width: 0; }

        body {
            min-height: 100dvh;
            background: var(--tl-bg);
            color: var(--tl-text);
            font-family: Inter, 'Tajawal', Arial, sans-serif !important;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        .tl-page {
            min-height: 100dvh;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 12% 5%, rgba(109,70,255,.09), transparent 25%),
                radial-gradient(circle at 90% 95%, rgba(0,184,255,.08), transparent 24%),
                var(--tl-bg);
        }

        .tl-shell {
            width: min(100% - 40px, 1180px);
            min-height: 100dvh;
            margin-inline: auto;
            display: flex;
            flex-direction: column;
            padding: 20px 0 18px;
            position: relative;
            z-index: 1;
        }

        .tl-header {
            min-height: 46px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .tl-header-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: inherit;
            text-decoration: none;
            min-width: 0;
        }

        .tl-header-brand img {
            width: 34px;
            height: 34px;
            padding: 4px;
            object-fit: contain;
            border-radius: 9px;
            border: 1px solid var(--tl-border);
            background: var(--tl-surface);
        }

        .tl-header-name {
            min-width: 0;
        }

        .tl-header-name strong {
            display: block;
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 13px;
            line-height: 1.2;
            font-weight: 800;
        }

        .tl-header-name span {
            display: block;
            margin-top: 3px;
            color: var(--tl-muted);
            font-size: 9px;
            line-height: 1.2;
            font-weight: 600;
        }

        .tl-header-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tl-control {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--tl-border);
            border-radius: 9px;
            background: var(--tl-surface);
            color: var(--tl-text);
            font-size: 10px;
            font-weight: 800;
            cursor: pointer;
            transition: .15s ease;
        }

        .tl-control:hover {
            color: var(--tl-blue);
            border-color: rgba(0,108,255,.35);
            transform: translateY(-1px);
        }

        .tl-language { position: relative; }

        .tl-language-menu {
            position: absolute;
            top: calc(100% + 8px);
            inset-inline-end: 0;
            width: 184px;
            padding: 6px;
            border: 1px solid var(--tl-border);
            border-radius: 12px;
            background: var(--tl-surface);
            box-shadow: 0 18px 42px rgba(16,24,40,.14);
            z-index: 20;
        }

        .tl-language-menu[hidden] { display: none; }

        .tl-language-menu a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 36px;
            padding: 0 9px;
            border-radius: 8px;
            color: var(--tl-muted);
            text-decoration: none;
            font-size: 10px;
            font-weight: 800;
        }

        .tl-language-menu a:hover,
        .tl-language-menu a.active {
            color: var(--tl-blue);
            background: rgba(0,108,255,.07);
        }

        .tl-main {
            flex: 1;
            display: grid;
            place-items: center;
            padding: 28px 0 36px;
        }

        .tl-card {
            width: min(100%, 420px);
            border: 1px solid var(--tl-border);
            border-radius: 22px;
            background: var(--tl-surface);
            box-shadow: 0 26px 70px rgba(16,24,40,.10);
            overflow: hidden;
        }

        .tl-accent {
            height: 3px;
            background: linear-gradient(90deg, #6D46FF 0%, #006CFF 52%, #00B8FF 100%);
        }

        .tl-card-content { padding: 30px; }

        .tl-tenant {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .tl-logo {
            width: 54px;
            height: 54px;
            flex: 0 0 auto;
            padding: 7px;
            object-fit: contain;
            border-radius: 14px;
            background: #fff;
            border: 1px solid var(--tl-border);
            box-shadow: 0 8px 20px rgba(16,24,40,.06);
        }

        .tl-tenant-copy { min-width: 0; }

        .tl-tenant-name {
            color: var(--tl-text);
            font-size: 17px;
            line-height: 1.25;
            font-weight: 800;
            letter-spacing: -.02em;
        }

        .tl-domain {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            max-width: 100%;
            margin-top: 5px;
            color: var(--tl-muted);
            font-size: 10px;
            line-height: 1.2;
            font-weight: 700;
        }

        .tl-domain span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tl-domain svg {
            flex: 0 0 auto;
            color: var(--tl-blue);
        }

        .tl-divider {
            height: 1px;
            margin: 24px 0;
            background: var(--tl-border);
        }

        .tl-heading h1 {
            margin: 0;
            color: var(--tl-text);
            font-size: 28px;
            line-height: 1.1;
            letter-spacing: -.045em;
            font-weight: 800;
        }

        .tl-heading p {
            margin: 8px 0 0;
            color: var(--tl-muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .tl-form {
            display: grid;
            gap: 16px;
            margin-top: 24px;
        }

        .tl-field label {
            display: block;
            margin-bottom: 7px;
            color: var(--tl-text);
            font-size: 10px;
            line-height: 1.2;
            font-weight: 800;
        }

        .tl-input-wrap { position: relative; }

        .tl-input-icon {
            position: absolute;
            top: 50%;
            inset-inline-start: 13px;
            transform: translateY(-50%);
            color: #98A2B3;
            pointer-events: none;
        }

        .tl-input {
            width: 100%;
            height: 50px;
            padding: 0 41px;
            border: 1px solid var(--tl-border);
            border-radius: 12px;
            background: var(--tl-bg);
            color: var(--tl-text);
            font: inherit;
            font-size: 13px;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }

        .tl-input::placeholder { color: #98A2B3; }

        .tl-input:focus {
            border-color: rgba(0,108,255,.55);
            background: var(--tl-surface);
            box-shadow: 0 0 0 4px rgba(0,108,255,.07);
        }

        .tl-password-toggle {
            position: absolute;
            top: 50%;
            inset-inline-end: 7px;
            width: 33px;
            height: 33px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #98A2B3;
            cursor: pointer;
        }

        .tl-password-toggle:hover {
            color: var(--tl-blue);
            background: rgba(0,108,255,.06);
        }

        .tl-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .tl-check {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--tl-muted);
            font-size: 10px;
            font-weight: 600;
        }

        .tl-check input { accent-color: var(--tl-blue); }

        .tl-forgot {
            color: var(--tl-blue);
            font-size: 10px;
            font-weight: 800;
            text-decoration: none;
        }

        .tl-forgot:hover { text-decoration: underline; }

        .tl-alert {
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 10px;
            line-height: 1.6;
        }

        .tl-alert.error {
            border: 1px solid rgba(239,68,68,.18);
            background: rgba(239,68,68,.06);
            color: #B42318;
        }

        .tl-alert.success {
            border: 1px solid rgba(18,183,106,.18);
            background: rgba(18,183,106,.06);
            color: #067647;
        }

        .tl-submit {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(90deg, #6D46FF 0%, #006CFF 52%, #00B8FF 100%);
            color: #fff;
            font: inherit;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 12px 26px rgba(0,108,255,.18);
            transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        }

        .tl-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 32px rgba(0,108,255,.24);
        }

        .tl-submit:disabled {
            opacity: .62;
            cursor: not-allowed;
            transform: none;
        }

        .tl-secure {
            margin-top: 17px;
            padding-top: 15px;
            border-top: 1px solid var(--tl-border);
            text-align: center;
            color: #8792A5;
            font-size: 9px;
            font-weight: 700;
        }

        .tl-secure span { display: inline-flex; align-items: center; gap: 5px; }
        .tl-secure i { width: 5px; height: 5px; border-radius: 50%; background: var(--tl-green); }

        .tl-footer {
            text-align: center;
            color: #8B95A7;
            font-size: 9px;
        }

        .tl-footer a {
            color: var(--tl-blue);
            font-weight: 800;
            text-decoration: none;
        }

        html[dir="rtl"] .tl-heading h1 { letter-spacing: 0; }
        html[data-theme="dark"] .tl-card { box-shadow: 0 28px 80px rgba(0,0,0,.30); }
        html[data-theme="dark"] .tl-input { background: #10172A; }
        html[data-theme="dark"] .tl-input:focus { background: var(--tl-surface); }

        @media (max-width: 600px) {
            .tl-shell { width: calc(100% - 20px); padding-top: 11px; }
            .tl-header-name span { display: none; }
            .tl-main { padding: 20px 0 26px; }
            .tl-card { border-radius: 20px; }
            .tl-card-content { padding: 23px 20px; }
            .tl-logo { width: 50px; height: 50px; }
            .tl-tenant-name { font-size: 16px; }
            .tl-heading h1 { font-size: 25px; }
        }

        @media (max-width: 390px) {
            .tl-shell { width: calc(100% - 14px); }
            .tl-header-name { display: none; }
            .tl-card-content { padding: 21px 17px; }
            .tl-row { align-items: flex-start; flex-direction: column; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; }
        }
    </style>
    <script>
        (function () {
            const saved = localStorage.getItem('velora-theme');
            const preferred = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.theme = saved || (preferred ? 'dark' : 'light');
        })();
    </script>
</head>
<body>
<div class="tl-page">
    <div class="tl-shell">
        <header class="tl-header">
            <a href="{{ route('customer.booking') }}" class="tl-header-brand" aria-label="{{ $displayName }}">
                <img src="{{ $logoUrl }}" alt="{{ $displayName }}" onerror="this.onerror=null;this.src='{{ asset('logo-bais.png') }}';">
                <span class="tl-header-name">
                    <strong>{{ $displayName }}</strong>
                    <span>{{ __('messages.login_to_account') }}</span>
                </span>
            </a>

            <div class="tl-header-actions">
                <button id="themeToggle" type="button" class="tl-control" aria-label="{{ __('Toggle theme') }}">◐</button>
                <div class="tl-language">
                    <button id="languageToggle" type="button" class="tl-control" aria-haspopup="listbox" aria-expanded="false" aria-label="{{ __('messages.language') }}">{{ strtoupper($locale) }}</button>
                    <div id="languageMenu" class="tl-language-menu" role="listbox" hidden>
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

        <main class="tl-main">
            <section class="tl-card" aria-labelledby="loginTitle">
                <div class="tl-accent"></div>
                <div class="tl-card-content">
                    <div class="tl-tenant">
                        <img
                            class="tl-logo"
                            src="{{ $logoUrl }}"
                            alt="{{ $displayName }}"
                            onerror="this.onerror=null;this.src='{{ asset('logo-bais.png') }}';"
                        >
                        <div class="tl-tenant-copy">
                            <div class="tl-tenant-name">{{ $displayName }}</div>
                            <div class="tl-domain" title="{{ $tenantDomain }}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"/>
                                    <path d="M3 12h18M12 3c2.6 2.6 3.8 6.4 3.8 9s-1.2 6.4-3.8 9S8.2 14.6 8.2 12 9.4 5.6 12 3z"/>
                                </svg>
                                <span>{{ $tenantDomain }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="tl-divider"></div>

                    <div class="tl-heading">
                        <h1 id="loginTitle">{{ __('messages.login') }}</h1>
                        <p>{{ __('messages.login_to_account') }}</p>
                    </div>

                    <form id="loginForm" class="tl-form" novalidate>
                        @csrf

                        <div class="tl-field">
                            <label for="email">{{ __('messages.email') }}</label>
                            <div class="tl-input-wrap">
                                <span class="tl-input-icon" aria-hidden="true">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                                        <path d="m4 7 8 6 8-6"/>
                                    </svg>
                                </span>
                                <input class="tl-input" type="email" id="email" name="email" autocomplete="username" required autofocus placeholder="name@example.com">
                            </div>
                        </div>

                        <div class="tl-field">
                            <label for="password">{{ __('messages.password') }}</label>
                            <div class="tl-input-wrap">
                                <span class="tl-input-icon" aria-hidden="true">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <rect x="4" y="10" width="16" height="10" rx="2"/>
                                        <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                                    </svg>
                                </span>
                                <input class="tl-input" type="password" id="password" name="password" autocomplete="current-password" required placeholder="••••••••">
                                <button id="passwordToggle" type="button" class="tl-password-toggle" aria-label="{{ __('messages.password') }}">◉</button>
                            </div>
                        </div>

                        <div class="tl-row">
                            <label class="tl-check">
                                <input type="checkbox" id="remember">
                                <span>{{ __('messages.remember_me') }}</span>
                            </label>
                            <a class="tl-forgot" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                        </div>

                        <div id="errorMessage" class="tl-alert error" hidden></div>
                        <div id="successMessage" class="tl-alert success" hidden></div>

                        <button type="submit" id="submitBtn" class="tl-submit">
                            <span id="btnText">{{ __('messages.login') }}</span>
                            <span id="loadingSpinner" hidden aria-hidden="true">◌</span>
                        </button>
                    </form>

                    <div class="tl-secure">
                        <span><i></i>{{ $tenantDomain }}</span>
                    </div>
                </div>
            </section>
        </main>

        <footer class="tl-footer">
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
        if (!event.target.closest('.tl-language')) {
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
