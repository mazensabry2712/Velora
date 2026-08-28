<!doctype html>
@php
    $locale = app()->getLocale() ?: 'en';
    $isArabic = $locale === 'ar';
    $isRtl = in_array($locale, ['ar', 'he', 'fa'], true);
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $isArabic ? __('verification.email_verified') : __('Email verified') }} · Velora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
    <main class="va-center">
        <section class="va-card">
            <div class="va-brand" style="justify-content:center">
                <img src="{{ asset('logo-bais.png') }}" alt="Velora">
                <span><strong>Velora</strong><span>{{ $isArabic ? 'مساحة عملك جاهزة' : 'Your workspace experience' }}</span></span>
            </div>

            <div class="va-status" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <p style="text-align:center;color:var(--va-accent);font-size:11px;font-weight:900;letter-spacing:.18em;text-transform:uppercase">Velora</p>
            <h1>{{ $isArabic ? __('verification.email_verified') : __('Email verified') }}</h1>
            <p>
                {{ $isArabic ? __('verification.message') : __('Your email has been verified. Please return to your workspace setup page to continue.') }}
            </p>

            @if (! empty($businessName))
                <div class="va-business">
                    <small>{{ $isArabic ? __('verification.business') : __('Business') }}</small>
                    <strong>{{ $businessName }}</strong>
                    @if (! empty($adminEmail))
                        <small style="margin-top:8px">{{ $isArabic ? 'الحساب' : 'Admin account' }}</small>
                        <strong style="font-weight:700">{{ $adminEmail }}</strong>
                    @endif
                </div>
            @endif

            <div class="va-meta">
                {{ $isArabic ? 'تم تأكيد بريدك الإلكتروني بنجاح.' : 'Your email address has been successfully verified.' }}
                <br>
                {{ $isArabic ? 'يمكنك الآن متابعة إعداد مساحة العمل.' : 'You can now continue with your workspace setup.' }}
            </div>
        </section>
    </main>
</body>
</html>
