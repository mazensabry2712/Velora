<!doctype html>
@php
    $locale = app()->getLocale() ?: 'en';
    $isArabic = $locale === 'ar';
    $isRtl = in_array($locale, ['ar', 'he', 'fa'], true);
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('super-admin.login_page_title') }} · Velora</title>
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
            <a href="{{ route('landing') }}" class="va-brand" aria-label="Velora">
                <img src="{{ asset('logo-bais.png') }}" alt="Velora">
                <span><strong>Velora</strong><span>{{ $isArabic ? 'لوحة التحكم الرئيسية' : 'Platform control center' }}</span></span>
            </a>
            <div class="va-tools">
                <button id="themeToggle" type="button" class="va-tool">◐</button>
                <a class="va-tool" href="{{ route('super-admin.lang', 'en') }}">EN</a>
                <a class="va-tool" href="{{ route('super-admin.lang', 'ar') }}">AR</a>
            </div>
        </header>

        <main class="va-main">
            <section class="va-panel copy">
                <div>
                    <div class="va-kicker"><span class="va-dot"></span>{{ __('super-admin.login_section_title') }}</div>
                    <h1 class="va-title">{{ $isArabic ? 'تحكم مركزي في ' : 'One secure place for ' }}<span>Velora</span></h1>
                    <p class="va-copy">{{ __('super-admin.login_description') }}</p>
                    <div class="va-feature-list">
                        <div class="va-feature"><span class="va-icon">✓</span><div><strong>{{ $isArabic ? 'إدارة الشركات' : 'Tenant management' }}</strong><span>{{ $isArabic ? 'تابع الشركات، الاشتراكات وحالة المساحات.' : 'Manage tenants, subscriptions and workspace status.' }}</span></div></div>
                        <div class="va-feature"><span class="va-icon">✓</span><div><strong>{{ $isArabic ? 'تحليلات المنصة' : 'Platform analytics' }}</strong><span>{{ $isArabic ? 'الوصول إلى التقارير ومؤشرات الأداء من مكان واحد.' : 'Access platform reports and operating metrics from one place.' }}</span></div></div>
                        <div class="va-feature"><span class="va-icon">✓</span><div><strong>{{ $isArabic ? 'وصول محمي' : 'Protected access' }}</strong><span>{{ $isArabic ? 'هذه المساحة مخصصة للمشرفين المصرح لهم فقط.' : 'This area is restricted to authorized super administrators.' }}</span></div></div>
                    </div>
                </div>
                <p class="va-footnote">Velora · {{ date('Y') }} · {{ __('super-admin.login_secure') }}</p>
            </section>

            <section class="va-panel form">
                <div class="va-form-head">
                    <div>
                        <h2>{{ __('super-admin.login_page_title') }}</h2>
                        <p>{{ __('super-admin.login_subtitle') }}</p>
                    </div>
                </div>

                @if(session('success'))
                    <div class="va-alert success" style="margin-top:20px">✓ {{ session('success') }}</div>
                @endif
                @if(session('error') || $errors->any())
                    <div class="va-alert error" style="margin-top:20px">
                        {{ session('error') ?: $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('super-admin.login.post') }}" class="va-form">
                    @csrf
                    <div class="va-field">
                        <label for="email">{{ __('super-admin.login_email') }}</label>
                        <input class="va-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="admin@velora.app">
                    </div>
                    <div class="va-field">
                        <label for="password">{{ __('super-admin.login_password') }}</label>
                        <input class="va-input" type="password" id="password" name="password" autocomplete="current-password" required placeholder="••••••••">
                    </div>
                    <div class="va-row">
                        <label class="va-check"><input type="checkbox" id="remember" name="remember"> <span>{{ __('super-admin.login_remember') }}</span></label>
                        <span class="va-link" style="opacity:.55;cursor:not-allowed">{{ __('super-admin.login_forgot') }}</span>
                    </div>
                    <button type="submit" class="va-button">{{ __('super-admin.login_submit') }} <span aria-hidden="true">→</span></button>
                </form>
                <div class="va-meta">{{ __('super-admin.login_description_short') }}</div>
            </section>
        </main>
    </div>
</div>
<script>
    const root = document.documentElement;
    document.getElementById('themeToggle').addEventListener('click', () => {
        const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = next;
        localStorage.setItem('velora-theme', next);
    });
</script>
</body>
</html>
