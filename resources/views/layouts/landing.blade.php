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
    <meta name="theme-color" content="#0D1226" />
    <meta name="color-scheme" content="light dark" />
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
    <script>
        (function(){
            const saved = localStorage.getItem('velora-theme');
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.theme = saved || (systemDark ? 'dark' : 'light');
        })();
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --v-ink: #0D1226;
            --v-ink-soft: #4B5563;
            --v-canvas: #F5F7FA;
            --v-line: #E5E7EB;
            --v-surface: #FFFFFF;
            --v-primary-purple: #6D46FF;
            --v-primary-blue: #006CFF;
            --v-cyan: #00B8FF;
            --v-gradient: linear-gradient(90deg,#6D46FF 0%,#006CFF 52%,#00B8FF 100%);
            --v-sky-blue: #1677FF;
            --v-mint: #00D4A3;
            --v-muted: #6B7280;
        }
        html[data-theme="dark"] {
            --v-ink: #F8FAFC;
            --v-ink-soft: #A7B0C0;
            --v-canvas: #080B18;
            --v-line: #252E45;
            --v-surface: #0D1226;
            --v-muted: #A7B0C0;
        }
        * { box-sizing: border-box; }
        html, body { margin:0; min-width:0; overflow-x:hidden; background:var(--v-canvas); }
        body { font-family:'Plus Jakarta Sans',Inter,system-ui,sans-serif; color:var(--v-ink); -webkit-font-smoothing:antialiased; transition:background-color .2s,color .2s; }
        a { text-decoration:none; color:inherit; }
        button { font:inherit; }
        [x-cloak] { display:none !important; }
        .v-site { min-height:100dvh; background:var(--v-canvas); }
        .v-shell { width:min(1180px,calc(100% - 40px)); margin-inline:auto; }
        .v-header { position:fixed; inset:0 0 auto; z-index:60; padding-top:14px; }
        .v-nav { display:flex;align-items:center;justify-content:space-between;gap:18px;min-height:68px;padding:10px 14px 10px 18px;background:color-mix(in srgb,var(--v-surface) 94%,transparent);backdrop-filter:blur(18px);border:1px solid var(--v-line);border-radius:20px;box-shadow:0 12px 34px rgba(13,18,38,.08); }
        .v-logo { display:flex;align-items:center;min-width:0; }
        .v-logo img { display:block;width:44px;height:44px;object-fit:contain;border-radius:12px; }
        .v-desktop-nav { display:flex;align-items:center;gap:6px; }
        .v-nav-link { display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 12px;border-radius:11px;color:var(--v-muted);font-size:13px;font-weight:700;transition:.2s; }
        .v-nav-link:hover { color:var(--v-primary-blue);background:color-mix(in srgb,var(--v-sky-blue) 8%,var(--v-surface)); }
        .v-nav-cta { display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:0 16px;border-radius:13px;background:var(--v-gradient);color:#fff;font-size:13px;font-weight:800;box-shadow:0 10px 24px rgba(0,108,255,.16);transition:.2s; }
        .v-nav-cta:hover { transform:translateY(-1px);box-shadow:0 14px 30px rgba(0,108,255,.24); }
        .v-nav-tools { display:flex;align-items:center;gap:6px; }
        .v-desktop-tools { display:flex;align-items:center;gap:6px; }
        .v-icon-btn { width:42px;height:42px;display:grid;place-items:center;border:1px solid var(--v-line);border-radius:12px;background:var(--v-surface);color:var(--v-ink);transition:.2s;cursor:pointer; }
        .v-icon-btn:hover { border-color:var(--v-sky-blue);color:var(--v-sky-blue); }
        .v-icon-btn svg { pointer-events:none; }
        .v-menu { display:none;padding:10px;margin-top:8px;background:var(--v-surface);border:1px solid var(--v-line);border-radius:18px;box-shadow:0 18px 40px rgba(13,18,38,.12); }
        .v-menu a { display:flex;align-items:center;min-height:46px;padding:0 12px;border-radius:12px;color:var(--v-muted);font-size:14px;font-weight:700; }
        .v-menu a:hover { background:color-mix(in srgb,var(--v-sky-blue) 8%,var(--v-surface));color:var(--v-sky-blue); }
        .v-main { padding-top:112px; }
        .v-footer { border-top:1px solid var(--v-line);background:var(--v-surface); }
        .v-footer-inner { width:min(1180px,calc(100% - 40px));margin-inline:auto;padding:34px 0;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap; }
        .v-footer-ident { height:28px;width:auto;display:block; }
        .v-footer-meta { color:var(--v-muted);font-size:11px; }
        .btn-primary,.nav-cta { background:var(--v-gradient)!important;color:#fff!important;border-color:transparent!important;box-shadow:0 10px 24px rgba(0,108,255,.16)!important; }
        .btn-primary:hover,.nav-cta:hover { box-shadow:0 14px 30px rgba(0,108,255,.24)!important; }
        .gradient-text { background-image:var(--v-gradient)!important;background-size:100% 100%!important;-webkit-background-clip:text!important;background-clip:text!important;color:transparent!important;-webkit-text-fill-color:transparent!important; }
        .glass,.glass-light { background:var(--v-surface)!important;border-color:var(--v-line)!important; }
        .text-brand-300,.text-brand-400,.text-brand-500,.text-brand-600,.text-brand-700 { color:var(--v-primary-blue)!important; }
        .bg-brand-300,.bg-brand-400,.bg-brand-500,.bg-brand-600,.bg-brand-700 { background:var(--v-gradient)!important; }
        .border-brand-300,.border-brand-400,.border-brand-500,.border-brand-600,.border-brand-700 { border-color:var(--v-primary-blue)!important; }
        .focus\\:ring-brand-500:focus { --tw-ring-color:var(--v-primary-blue)!important; }
        html[data-theme="dark"] .v-nav, html[data-theme="dark"] .v-menu { box-shadow:0 18px 40px rgba(0,0,0,.35); }
        html[data-theme="dark"] .v-nav-link:hover, html[data-theme="dark"] .v-menu a:hover { background:rgba(22,119,255,.12); }
        html[data-theme="dark"] .v-footer { background:#0D1226; }
        @media (max-width:980px){ .v-desktop-nav{display:none;} .v-desktop-tools{display:none;} .v-menu{display:block;} }
        @media (max-width:680px){ .v-shell,.v-footer-inner{width:calc(100% - 24px);} .v-header{padding-top:8px;} .v-nav{min-height:60px;border-radius:16px;padding:8px 9px;} .v-logo img{width:36px;height:36px;} .v-main{padding-top:84px;} .v-footer-inner{flex-direction:column;align-items:flex-start;} .v-footer-ident{height:22px;} }
        @media (max-width:380px){ .v-shell,.v-footer-inner{width:calc(100% - 18px);} .v-icon-btn{width:39px;height:39px;} }
        @media (prefers-reduced-motion:reduce){ *,*::before,*::after{scroll-behavior:auto!important;transition:none!important;} }
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
                <div class="v-desktop-tools"><button id="themeToggleDesktop" type="button" class="v-icon-btn" aria-label="Switch to dark mode" title="Dark mode"></button></div>
            </div>
            <div class="v-nav-tools">
                <button id="themeToggleMobile" type="button" class="v-icon-btn" aria-label="Switch to dark mode" title="Dark mode"></button>
                <button type="button" onclick="window.dispatchEvent(new Event('velora:open-lang-switcher'))" class="v-icon-btn" aria-label="{{ __('landing.switcher_lang_label') ?? 'Change language' }}"><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h12M9 3v2m1.1 9.2A17.8 17.8 0 0 1 6.4 9m6.1 9 4.5-9 4.5 9m-.8-2h-7.4"/></svg></button>
                <button id="menuToggle" type="button" class="v-icon-btn" aria-label="Open menu" aria-controls="mobileMenu" aria-expanded="false"><svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 7h16M4 12h16M4 17h16"/></svg></button>
            </div>
        </nav>
        <div id="mobileMenu" class="v-menu" hidden>
            <a href="{{ route('landing') }}#features">{{ __('landing.nav_features') }}</a>
            <a href="{{ route('landing') }}#how-it-works">{{ __('landing.nav_how_it_works') }}</a>
            <a href="{{ route('landing') }}#pricing">{{ __('landing.nav_pricing') }}</a>
            <a href="{{ route('central.login') }}">{{ __('landing.nav_company_admin_sign_in') }}</a>
            <a href="{{ route('super-admin.login') }}">{{ __('landing.nav_super_admin_sign_in') }}</a>
            @if ($registrationEnabled ?? true)<a href="{{ route('signup') }}" style="background:var(--v-gradient);color:#fff;justify-content:center">{{ __('landing.nav_start_trial') }}</a>@endif
        </div>
    </div></header>
    <main class="v-main">@yield('content')</main>
    <footer class="v-footer"><div class="v-footer-inner"><img class="v-footer-ident" src="{{ asset('ident.png') }}" alt="Velora" /><div class="v-footer-meta">© {{ date('Y') }} Velora. All rights reserved.</div></div></footer>
</div>
<script>
(function(){
    const root=document.documentElement,meta=document.querySelector('meta[name="theme-color"]'),desktop=document.getElementById('themeToggleDesktop'),mobile=document.getElementById('themeToggleMobile'),menu=document.getElementById('mobileMenu'),menuToggle=document.getElementById('menuToggle');
    const icons={sun:'<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="4" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>',moon:'<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.2 15.6A8.5 8.5 0 0 1 8.4 3.8 8.5 8.5 0 1 0 20.2 15.6Z"/></svg>'};
    function apply(theme){root.dataset.theme=theme;localStorage.setItem('velora-theme',theme);const dark=theme==='dark';[desktop,mobile].forEach(b=>{if(!b)return;b.innerHTML=dark?icons.sun:icons.moon;b.setAttribute('aria-label',dark?'Switch to light mode':'Switch to dark mode');b.setAttribute('title',dark?'Light mode':'Dark mode');});if(meta)meta.setAttribute('content',dark?'#080B18':'#0D1226');}
    apply(root.dataset.theme||'light');
    [desktop,mobile].forEach(b=>b&&b.addEventListener('click',()=>apply(root.dataset.theme==='dark'?'light':'dark')));
    if(menuToggle&&menu){const close=()=>{menu.hidden=true;menuToggle.setAttribute('aria-expanded','false')};menuToggle.addEventListener('click',()=>{const open=menu.hidden;menu.hidden=!open;menuToggle.setAttribute('aria-expanded',String(open))});menu.querySelectorAll('a').forEach(a=>a.addEventListener('click',close));document.addEventListener('click',e=>{if(!menu.hidden&&!menu.contains(e.target)&&!menuToggle.contains(e.target))close()});}
})();
</script>
@stack('scripts')
</body></html>
