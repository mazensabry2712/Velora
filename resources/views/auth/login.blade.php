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
    <meta name="theme-color" content="#F5F7FA">
    <meta name="color-scheme" content="light dark">
    <title>{{ __('messages.login') }} · {{ $displayName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-auth.css') }}">
    <style>
        :root { color-scheme: light dark; }
        .vtl-page { min-height:100dvh; background:var(--va-bg); color:var(--va-text); position:relative; overflow:hidden; }
        .vtl-page::before { content:""; position:absolute; width:620px; height:620px; top:-360px; inset-inline-start:-220px; border-radius:50%; background:radial-gradient(circle,rgba(109,70,255,.12),transparent 70%); pointer-events:none; }
        .vtl-page::after { content:""; position:absolute; width:520px; height:520px; bottom:-330px; inset-inline-end:-220px; border-radius:50%; background:radial-gradient(circle,rgba(0,184,255,.10),transparent 70%); pointer-events:none; }
        .vtl-shell { position:relative; z-index:1; width:min(100% - 32px,1120px); min-height:100dvh; margin-inline:auto; display:flex; flex-direction:column; padding:18px 0 22px; }
        .vtl-topbar { height:58px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .vtl-brand { display:inline-flex; align-items:center; gap:11px; min-width:0; color:inherit; text-decoration:none; }
        .vtl-brand img { width:40px; height:40px; object-fit:contain; border-radius:11px; flex:0 0 auto; background:var(--va-surface); border:1px solid var(--va-line); padding:5px; }
        .vtl-brand-copy { min-width:0; }
        .vtl-brand-copy strong { display:block; max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--va-text); font-size:14px; font-weight:800; letter-spacing:-.02em; }
        .vtl-brand-copy span { display:block; margin-top:2px; color:var(--va-muted); font-size:9px; font-weight:700; }
        .vtl-tools { display:flex; align-items:center; gap:7px; }
        .vtl-tool { height:38px; min-width:38px; padding:0 11px; border:1px solid var(--va-line); border-radius:11px; background:color-mix(in srgb,var(--va-surface) 95%,transparent); color:var(--va-text); font-size:10px; font-weight:800; cursor:pointer; transition:.18s; }
        .vtl-tool:hover { transform:translateY(-1px); border-color:rgba(0,108,255,.35); color:var(--va-accent); }
        .vtl-language { position:relative; }
        .vtl-menu { position:absolute; top:calc(100% + 8px); inset-inline-end:0; width:190px; padding:7px; border:1px solid var(--va-line); border-radius:15px; background:var(--va-surface); box-shadow:0 24px 60px rgba(13,18,38,.16); z-index:30; }
        .vtl-menu[hidden] { display:none; }
        .vtl-menu a { display:flex; align-items:center; justify-content:space-between; min-height:38px; padding:0 10px; border-radius:10px; color:var(--va-muted); font-size:10px; font-weight:800; text-decoration:none; }
        .vtl-menu a:hover,.vtl-menu a.active { background:rgba(0,108,255,.07); color:var(--va-accent); }
        .vtl-main { flex:1; display:grid; place-items:center; padding:26px 0 22px; }
        .vtl-card { width:min(100%,500px); border:1px solid var(--va-line); border-radius:28px; background:color-mix(in srgb,var(--va-surface) 97%,transparent); box-shadow:0 30px 90px rgba(13,18,38,.10); overflow:hidden; }
        .vtl-accent { height:4px; background:var(--va-gradient); }
        .vtl-inner { padding:34px; }
        .vtl-identity { display:flex; align-items:center; gap:14px; padding:14px; border:1px solid var(--va-line); border-radius:17px; background:color-mix(in srgb,var(--va-surface) 92%,var(--va-bg)); }
        .vtl-identity-logo { width:50px; height:50px; object-fit:contain; border-radius:14px; background:var(--va-surface); border:1px solid var(--va-line); padding:6px; flex:0 0 auto; }
        .vtl-identity-label { color:var(--va-muted); font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
        .vtl-identity-name { margin-top:4px; color:var(--va-text); font-size:16px; font-weight:800; letter-spacing:-.02em; }
        .vtl-domain { margin-top:4px; color:var(--va-muted); font-size:10px; font-weight:700; word-break:break-all; }
        .vtl-heading { margin-top:28px; }
        .vtl-heading h1 { margin:0; color:var(--va-text); font-size:34px; line-height:1.08; letter-spacing:-.045em; font-weight:800; }
        .vtl-heading p { margin:9px 0 0; color:var(--va-muted); font-size:12px; line-height:1.7; }
        .vtl-form { display:grid; gap:17px; margin-top:26px; }
        .vtl-field label { display:block; margin-bottom:8px; color:var(--va-text); font-size:10px; font-weight:800; }
        .vtl-input-wrap { position:relative; }
        .vtl-input-icon { position:absolute; top:50%; transform:translateY(-50%); inset-inline-start:14px; color:#8b95a7; pointer-events:none; }
        .vtl-input { width:100%; height:54px; padding:0 43px; border:1px solid var(--va-line); border-radius:14px; background:color-mix(in srgb,var(--va-surface) 91%,var(--va-bg)); color:var(--va-text); font-size:13px; outline:none; transition:border-color .18s,box-shadow .18s,background .18s; }
        .vtl-input::placeholder { color:#98a2b3; }
        .vtl-input:focus { border-color:rgba(0,108,255,.58); box-shadow:0 0 0 4px rgba(0,108,255,.08); background:var(--va-surface); }
        .vtl-password-toggle { position:absolute; top:50%; transform:translateY(-50%); inset-inline-end:7px; width:36px; height:36px; border:0; border-radius:9px; background:transparent; color:#8b95a7; cursor:pointer; }
        .vtl-password-toggle:hover { background:rgba(0,108,255,.06); color:var(--va-accent); }
        .vtl-row { display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .vtl-check { display:flex; align-items:center; gap:7px; color:var(--va-muted); font-size:10px; font-weight:600; }
        .vtl-check input { accent-color:var(--va-accent); }
        .vtl-link { color:var(--va-accent); font-size:10px; font-weight:800; text-decoration:none; }
        .vtl-link:hover { text-decoration:underline; }
        .vtl-alert { padding:11px 12px; border-radius:12px; font-size:10px; line-height:1.65; }
        .vtl-alert.error { border:1px solid rgba(239,68,68,.18); background:rgba(239,68,68,.06); color:#B42318; }
        .vtl-alert.success { border:1px solid rgba(16,185,129,.18); background:rgba(16,185,129,.06); color:#067647; }
        .vtl-submit { width:100%; min-height:55px; display:flex; align-items:center; justify-content:center; gap:9px; border:0; border-radius:14px; background:linear-gradient(90deg,#6D46FF 0%,#006CFF 52%,#00B8FF 100%); color:#fff; font-size:12px; font-weight:900; cursor:pointer; box-shadow:0 14px 34px rgba(0,108,255,.20); transition:transform .18s,box-shadow .18s,opacity .18s; }
        .vtl-submit:hover { transform:translateY(-1px); box-shadow:0 19px 42px rgba(0,108,255,.26); }
        .vtl-submit:disabled { opacity:.62; cursor:not-allowed; transform:none; }
        .vtl-footer { text-align:center; color:#8b95a7; font-size:9px; }
        .vtl-footer a { color:var(--va-accent); font-weight:800; text-decoration:none; }
        .vtl-secure { display:flex; justify-content:center; gap:14px; flex-wrap:wrap; margin-top:17px; color:#8792a5; font-size:9px; font-weight:700; }
        .vtl-secure span { display:inline-flex; align-items:center; gap:5px; }
        .vtl-secure i { width:5px; height:5px; border-radius:50%; background:#19b37a; display:block; }
        html[dir="rtl"] .vtl-heading h1 { letter-spacing:0; }
        html[data-theme="dark"] .vtl-card { box-shadow:0 30px 90px rgba(0,0,0,.30); }
        html[data-theme="dark"] .vtl-input,html[data-theme="dark"] .vtl-identity { background:#10172A; }
        @media(max-width:600px) {
            .vtl-shell { width:calc(100% - 20px); padding-top:10px; }
            .vtl-topbar { height:52px; }
            .vtl-brand img { width:36px; height:36px; }
            .vtl-brand-copy strong { max-width:180px; font-size:13px; }
            .vtl-brand-copy span { display:none; }
            .vtl-tool { height:36px; min-width:36px; }
            .vtl-main { padding:20px 0 18px; }
            .vtl-inner { padding:23px 20px; }
            .vtl-card { border-radius:22px; }
            .vtl-heading h1 { font-size:29px; }
            .vtl-identity { padding:12px; }
        }
        @media(max-width:400px) {
            .vtl-brand-copy { display:none; }
            .vtl-inner { padding:21px 17px; }
            .vtl-row { align-items:flex-start; flex-direction:column; }
        }
        @media(prefers-reduced-motion:reduce) { *,*::before,*::after { transition:none !important; } }
    </style>
    <script>(function(){const saved=localStorage.getItem('velora-theme');const preferred=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.dataset.theme=saved||(preferred?'dark':'light')})();</script>
</head>
<body class="va-page">
<div class="vtl-page">
    <div class="vtl-shell">
        <header class="vtl-topbar">
            <a href="{{ route('customer.booking') }}" class="vtl-brand" aria-label="{{ $displayName }}">
                <img src="{{ $logoUrl }}" alt="{{ $displayName }}">
                <span class="vtl-brand-copy">
                    <strong>{{ $displayName }}</strong>
                    <span>{{ __('messages.login_to_account') }}</span>
                </span>
            </a>

            <div class="vtl-tools">
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
            <section class="vtl-card" aria-labelledby="form-title">
                <div class="vtl-accent"></div>
                <div class="vtl-inner">
                    <div class="vtl-identity">
                        <img class="vtl-identity-logo" src="{{ $logoUrl }}" alt="{{ $displayName }}">
                        <div class="min-w-0">
                            <div class="vtl-identity-label">{{ __('messages.business_name') }}</div>
                            <div class="vtl-identity-name">{{ $displayName }}</div>
                            <div class="vtl-domain">{{ $tenantDomain }}</div>
                        </div>
                    </div>

                    <div class="vtl-heading">
                        <h1 id="form-title">{{ __('messages.login') }}</h1>
                        <p>{{ __('messages.login_to_account') }}</p>
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
                                <button id="passwordToggle" type="button" class="vtl-password-toggle" aria-label="{{ __('messages.password') }}" aria-pressed="false">◉</button>
                            </div>
                        </div>

                        <div class="vtl-row">
                            <label class="vtl-check"><input type="checkbox" id="remember"><span>{{ __('messages.remember_me') }}</span></label>
                            <a class="vtl-link" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                        </div>

                        <div id="errorMessage" class="vtl-alert error" hidden></div>
                        <div id="successMessage" class="vtl-alert success" hidden></div>

                        <button type="submit" id="submitBtn" class="vtl-submit">
                            <span id="btnText">{{ __('messages.login') }}</span>
                            <span id="loadingSpinner" hidden aria-hidden="true">◌</span>
                        </button>
                    </form>

                    <div class="vtl-secure" aria-label="{{ __('messages.login') }}">
                        <span><i></i>{{ __('messages.login') }}</span>
                        <span><i></i>{{ __('messages.remember_me') }}</span>
                        <span><i></i>RTL / LTR</span>
                    </div>
                </div>
            </section>
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
    const languageToggle = document.getElementById('languageToggle');
    const languageMenu = document.getElementById('languageMenu');
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');

    themeToggle.addEventListener('click', () => {
        const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = next;
        localStorage.setItem('velora-theme', next);
    });

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

    passwordToggle.addEventListener('click', () => {
        const visible = passwordInput.type === 'text';
        passwordInput.type = visible ? 'password' : 'text';
        passwordToggle.setAttribute('aria-pressed', visible ? 'false' : 'true');
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
