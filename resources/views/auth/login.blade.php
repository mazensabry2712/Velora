<!doctype html>
@php
    $locale = app()->getLocale() ?: config('app.locale', 'en');
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Login') }} · {{ $displayName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-auth.css') }}">
    <script>
        (function () {
            const saved = localStorage.getItem('velora-theme');
            const preferred = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.theme = saved || (preferred ? 'dark' : 'light');
        })();
    </script>
</head>
<body class="va-page">
<div class="va-shell">
    <div class="va-bg-orb one"></div>
    <div class="va-bg-orb two"></div>

    <div class="va-container">
        <header class="va-topbar">
            <a href="{{ route('customer.booking') }}" class="va-brand" aria-label="{{ $displayName }}">
                @if ($businessLogo)
                    <img src="{{ asset('storage/' . $businessLogo) }}" alt="{{ $displayName }}">
                @else
                    <img src="{{ asset('logo-bais.png') }}" alt="Velora">
                @endif
                <span>
                    <strong>{{ $displayName }}</strong>
                    <span>{{ __('Manage your business with clarity') }}</span>
                </span>
            </a>

            <div class="va-tools">
                <button id="themeToggle" type="button" class="va-tool" aria-label="{{ __('Toggle theme') }}">◐</button>
                <div class="va-language-menu">
                    <button id="languageToggle" type="button" class="va-tool va-language-toggle" aria-haspopup="listbox" aria-expanded="false" aria-label="{{ __('Language') }}">
                        {{ strtoupper($locale) }}
                    </button>
                    <div id="languageMenu" class="va-language-dropdown" role="listbox" hidden>
                        @foreach ($supportedLocales as $supportedLocale)
                            <a role="option" class="va-language-option{{ $supportedLocale === $locale ? ' active' : '' }}" aria-selected="{{ $supportedLocale === $locale ? 'true' : 'false' }}" href="{{ route('tenant.change.language', ['lang' => $supportedLocale]) }}">
                                {{ strtoupper($supportedLocale) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </header>

        <main class="va-main">
            <section class="va-panel copy">
                <div>
                    <div class="va-kicker"><span class="va-dot"></span>{{ __('Workspace') }}</div>
                    <h1 class="va-title">
                        {{ __('Welcome back') }} <span>Velora</span>
                    </h1>
                    <p class="va-copy">
                        {{ __('Sign in to keep your appointments, customers, team and bookings moving from one place.') }}
                    </p>

                    <div class="va-feature-list">
                        <div class="va-feature"><span class="va-icon">✓</span><div><strong>{{ __('Workspace-aware access') }}</strong><span>{{ __('Your session is opened inside the correct tenant workspace.') }}</span></div></div>
                        <div class="va-feature"><span class="va-icon">✓</span><div><strong>{{ __('Verification first') }}</strong><span>{{ __('Unverified accounts are blocked until email verification is complete.') }}</span></div></div>
                        <div class="va-feature"><span class="va-icon">✓</span><div><strong>{{ __('Bilingual by design') }}</strong><span>{{ __('RTL and LTR are handled consistently across the experience.') }}</span></div></div>
                    </div>
                </div>
                <p class="va-footnote">Velora · {{ date('Y') }} · {{ __('Secure access to your workspace') }}</p>
            </section>

            <section class="va-panel form">
                <div class="va-form-head">
                    <div>
                        <h2>{{ __('Login') }}</h2>
                        <p>{{ __('Use your account credentials to continue.') }}</p>
                    </div>
                </div>

                <form id="loginForm" class="va-form" novalidate>
                    @csrf
                    <div class="va-field">
                        <label for="email">{{ __('Email') }}</label>
                        <input class="va-input" type="email" id="email" name="email" autocomplete="username" required autofocus placeholder="name@example.com">
                    </div>

                    <div class="va-field">
                        <label for="password">{{ __('Password') }}</label>
                        <input class="va-input" type="password" id="password" name="password" autocomplete="current-password" required placeholder="••••••••">
                    </div>

                    <div class="va-row">
                        <label class="va-check"><input type="checkbox" id="remember"> <span>{{ __('Remember me') }}</span></label>
                        <span class="va-link" aria-disabled="true" title="{{ __('Password reset is not enabled yet') }}">{{ __('Forgot your password?') }}</span>
                    </div>

                    <div id="errorMessage" class="va-alert error" hidden></div>
                    <div id="successMessage" class="va-alert success" hidden></div>

                    <button type="submit" id="submitBtn" class="va-button">
                        <span id="btnText">{{ __('Login') }}</span>
                        <span id="loadingSpinner" hidden aria-hidden="true">◌</span>
                    </button>
                </form>

                <div class="va-meta">
                    {{ __('Need another workspace? Open its tenant domain and sign in there.') }}
                </div>
            </section>
        </main>
    </div>
</div>

<script>
    const texts = {
        loggingIn: @json(__('Logging in...')),
        login: @json(__('Login')),
        loginSuccess: @json(__('Login successful!')),
        loginError: @json(__('Invalid credentials')),
        errorOccurred: @json(__('An error occurred! Please try again')),
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
        if (!event.target.closest('.va-language-menu')) {
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
