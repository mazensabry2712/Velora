<!DOCTYPE html>
@php
    $landingLocale = session('central_locale', 'en');
    app()->setLocale($landingLocale);
    $isRtl = $landingLocale === 'ar';
@endphp
<html lang="{{ $landingLocale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="description" content="{{ $metaDescription ?? 'Velora — Smart booking and queue management for modern businesses.' }}" />
    <meta name="theme-color" content="#000520" />
    <meta name="color-scheme" content="light" />
    <title>{{ $pageTitle ?? 'Velora — Smart Booking & Queue Management' }}</title>
    <meta property="og:title" content="{{ $pageTitle ?? 'Velora' }}" />
    <meta property="og:description" content="{{ $metaDescription ?? 'Smart booking platform for modern businesses.' }}" />
    <meta property="og:image" content="{{ asset('ident.png') }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --v-ink: #000520;
            --v-ink-soft: #42516a;
            --v-canvas: #f6f8fc;
            --v-line: #d9e1ec;
            --v-navy-950: #000520;
            --v-navy-900: #07142c;
            --v-navy-800: #102349;
            --v-navy-700: #183461;
            --v-navy-600: #24477d;
            --v-navy-100: #e8edf5;
            --v-navy-50: #f4f7fb;
            --v-teal-700: #07142c;
            --v-teal-600: #102349;
            --v-teal-500: #183461;
            --v-teal-400: #24477d;
            --v-teal-100: #e8edf5;
            --v-teal-50: #f4f7fb;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-width: 0; overflow-x: hidden; }
        body {
            font-family: 'Plus Jakarta Sans', Inter, system-ui, sans-serif;
            background: var(--v-canvas);
            color: var(--v-ink);
            -webkit-font-smoothing: antialiased;
        }
        a { text-decoration: none; color: inherit; }
        button { font: inherit; }
        [x-cloak] { display: none !important; }
        .v-site { min-height: 100dvh; background: var(--v-canvas); }
        .v-shell { width: min(1180px, calc(100% - 40px)); margin-inline: auto; }
        .v-header { position: fixed; inset: 0 0 auto; z-index: 60; padding-top: 14px; }
        .v-nav {
            display: flex; align-items: center; justify-content: space-between; gap: 18px;
            min-height: 68px; padding: 10px 14px 10px 18px;
            background: rgba(255,255,255,.97); backdrop-filter: blur(18px);
            border: 1px solid var(--v-line); border-radius: 20px;
            box-shadow: 0 12px 34px rgba(0,5,32,.08);
        }
        .v-logo { display: flex; align-items: center; min-width: 0; }
        .v-logo img { display: block; width: 44px; height: 44px; object-fit: contain; border-radius: 12px; }
        .v-desktop-nav { display: flex; align-items: center; gap: 6px; }
        .v-nav-link {
            display: inline-flex; align-items: center; justify-content: center; min-height: 42px;
            padding: 0 12px; border-radius: 11px; color: #52627b; font-size: 13px; font-weight: 700; transition: .2s;
        }
        .v-nav-link:hover { color: var(--v-navy-900); background: var(--v-navy-50); }
        .v-nav-cta {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 44px;
            padding: 0 16px; border-radius: 13px; background: var(--v-navy-900); color: #fff;
            font-size: 13px; font-weight: 800; box-shadow: 0 10px 24px rgba(0,5,32,.15); transition: .2s;
        }
        .v-nav-cta:hover { background: var(--v-navy-800); transform: translateY(-1px); }
        .v-nav-tools { display: none; align-items: center; gap: 6px; }
        .v-icon-btn { width: 42px; height: 42px; display: grid; place-items: center; border: 1px solid var(--v-line); border-radius: 12px; background: #fff; color: var(--v-ink); }
        .v-menu { display: none; padding: 10px; margin-top: 8px; background: #fff; border: 1px solid var(--v-line); border-radius: 18px; box-shadow: 0 18px 40px rgba(0,5,32,.12); }
        .v-menu a { display: flex; align-items: center; min-height: 46px; padding: 0 12px; border-radius: 12px; color: #52627b; font-size: 14px; font-weight: 700; }
        .v-menu a:hover { background: var(--v-navy-50); color: var(--v-navy-900); }
        .v-main { padding-top: 112px; }
        .v-footer { border-top: 1px solid var(--v-line); background: #fff; }
        .v-footer-inner { width: min(1180px, calc(100% - 40px)); margin-inline: auto; padding: 34px 0; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        .v-footer-ident { height: 28px; width: auto; display: block; }
        .v-footer-meta { color: #74819a; font-size: 11px; }

        /* Shared controls for landing sub-pages. */
        .btn-primary, .nav-cta { background: var(--v-navy-900) !important; color: #fff !important; border-color: var(--v-navy-900) !important; box-shadow: 0 10px 24px rgba(0,5,32,.14) !important; }
        .btn-primary:hover, .nav-cta:hover { background: var(--v-navy-800) !important; }
        .gradient-text { color: var(--v-navy-900) !important; background-image: none !important; -webkit-text-fill-color: currentColor !important; }
        .glass, .glass-light { background: #fff !important; border-color: var(--v-line) !important; }
        .text-brand-300, .text-brand-400, .text-brand-500, .text-brand-600, .text-brand-700 { color: var(--v-navy-900) !important; }
        .bg-brand-300, .bg-brand-400, .bg-brand-500, .bg-brand-600, .bg-brand-700 { background-color: var(--v-navy-900) !important; }
        .border-brand-300, .border-brand-400, .border-brand-500, .border-brand-600, .border-brand-700 { border-color: var(--v-line) !important; }
        .focus\\:ring-brand-500:focus { --tw-ring-color: var(--v-navy-700) !important; }

        @media (max-width: 980px) {
            .v-desktop-nav { display: none; }
            .v-nav-tools { display: flex; }
            .v-menu { display: block; }
            .v-menu[hidden] { display: none; }
        }
        @media (max-width: 680px) {
            .v-shell, .v-footer-inner { width: calc(100% - 24px); }
            .v-header { padding-top: 8px; }
            .v-nav { min-height: 60px; border-radius: 16px; padding: 8px 9px; }
            .v-logo img { width: 36px; height: 36px; }
            .v-main { padding-top: 84px; }
            .v-footer-inner { flex-direction: column; align-items: flex-start; }
            .v-footer-ident { height: 22px; }
        }
        @media (max-width: 380px) {
            .v-shell, .v-footer-inner { width: calc(100% - 18px); }
            .v-icon-btn { width: 39px; height: 39px; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; } }
    </style>
    @stack('styles')
</head>
<body>
<div class="v-site">
    <header class="v-header"><div class="v-shell">
        <nav class="v-nav" aria-label="Primary">
            <a class="v-logo" href="{{ route('landing') }}"><img src="{{ asset('logo.png') }}" alt="Velora" /></a>
            <div class="v-desktop-nav">
                <a class="v-nav-link" href="{{ route('landing') }}#features">{{ __('landing.nav_features') }}</a>
                <a class="v-nav-link" href="{{ route('landing') }}#how-it-works">{{ __('landing.nav_how_it_works') }}</a>
                <a class="v-nav-link" href="{{ route('landing') }}#pricing">{{ __('landing.nav_pricing') }}</a>
                <a class="v-nav-link" href="{{ route('central.login') }}">{{ __('landing.nav_company_admin_sign_in') }}</a>
                @if ($registrationEnabled ?? true)<a class="v-nav-cta" href="{{ route('signup') }}">{{ __('landing.nav_start_trial') }} <span aria-hidden="true">→</span></a>@endif
            </div>
            <div class="v-nav-tools">
                <button type="button" onclick="window.dispatchEvent(new Event('velora:open-lang-switcher'))" class="v-icon-btn v-focus" aria-label="{{ __('landing.switcher_lang_label') ?? 'Change language' }}"><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h12M9 3v2m1.1 9.2A17.8 17.8 0 0 1 6.4 9m6.1 9 4.5-9 4.5 9m-.8-2h-7.4"/></svg></button>
                <button id="menuToggle" type="button" class="v-icon-btn v-focus" aria-label="Open menu" aria-controls="mobileMenu" aria-expanded="false"><svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 7h16M4 12h16M4 17h16"/></svg></button>
            </div>
        </nav>
        <div id="mobileMenu" class="v-menu" hidden>
            <a href="{{ route('landing') }}#features">{{ __('landing.nav_features') }}</a>
            <a href="{{ route('landing') }}#how-it-works">{{ __('landing.nav_how_it_works') }}</a>
            <a href="{{ route('landing') }}#pricing">{{ __('landing.nav_pricing') }}</a>
            <a href="{{ route('central.login') }}">{{ __('landing.nav_company_admin_sign_in') }}</a>
            <a href="{{ route('super-admin.login') }}">{{ __('landing.nav_super_admin_sign_in') }}</a>
            @if ($registrationEnabled ?? true)<a href="{{ route('signup') }}" style="background:#07142c;color:#fff;justify-content:center">{{ __('landing.nav_start_trial') }}</a>@endif
        </div>
    </div></header>
    <main class="v-main">@yield('content')</main>
    <footer class="v-footer"><div class="v-footer-inner"><img class="v-footer-ident" src="{{ asset('ident.png') }}" alt="Velora" /><div class="v-footer-meta">© {{ date('Y') }} Velora. All rights reserved.</div></div></footer>
</div>
<script>(function(){const b=document.getElementById('menuToggle'),m=document.getElementById('mobileMenu');if(!b||!m)return;const close=()=>{m.hidden=true;b.setAttribute('aria-expanded','false')};b.addEventListener('click',()=>{const open=m.hidden;m.hidden=!open;b.setAttribute('aria-expanded',String(open))});m.querySelectorAll('a').forEach(a=>a.addEventListener('click',close));document.addEventListener('click',e=>{if(!m.hidden&&!m.contains(e.target)&&!b.contains(e.target))close()});})();</script>
@stack('scripts')
</body></html>
