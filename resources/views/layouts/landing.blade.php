<!DOCTYPE html>
@php
    $landingLocale = app()->getLocale() ?: config('localizer.supported_locales.0', 'ar');
    $isRtl = $landingLocale === 'ar';
    $landingLanguages = config('locales.languages', []);
@endphp
<html lang="{{ $landingLocale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="description" content="{{ $metaDescription ?? __('landing.meta_description') }}" />
    <meta name="theme-color" content="#0D1226" />
    <meta name="color-scheme" content="light dark" />
    <title>{{ $pageTitle ?? __('landing.meta_title') }}</title>
    <meta property="og:title" content="{{ $pageTitle ?? __('landing.meta_title') }}" />
    <meta property="og:description" content="{{ $metaDescription ?? __('landing.meta_description') }}" />
    <meta property="og:image" content="{{ asset('logo-bais.png') }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />
    <link rel="icon" type="image/png" href="{{ asset('logo-bais.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>(function(){const s=localStorage.getItem('velora-theme'),d=window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.dataset.theme=s||(d?'dark':'light')})();</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root{--v-ink:#0D1226;--v-ink-soft:#4B5563;--v-canvas:#F5F7FA;--v-line:#E5E7EB;--v-surface:#FFFFFF;--v-primary-purple:#6D46FF;--v-primary-blue:#006CFF;--v-cyan:#00B8FF;--v-gradient:linear-gradient(90deg,#6D46FF 0%,#006CFF 52%,#00B8FF 100%);--v-sky-blue:#1677FF;--v-mint:#00D4A3;--v-muted:#6B7280}
        html[data-theme="dark"]{--v-ink:#F8FAFC;--v-ink-soft:#A7B0C0;--v-canvas:#080B18;--v-line:#252E45;--v-surface:#0D1226;--v-muted:#A7B0C0}
        *{box-sizing:border-box}html,body{margin:0;min-width:0;overflow-x:hidden;background:var(--v-canvas)}body{font-family:'Plus Jakarta Sans',Inter,system-ui,sans-serif;color:var(--v-ink);-webkit-font-smoothing:antialiased}a{text-decoration:none;color:inherit}button{font:inherit}[x-cloak]{display:none!important}.v-site{min-height:100dvh;background:var(--v-canvas)}.v-shell{width:min(1180px,calc(100% - 40px));margin-inline:auto}.v-header{position:fixed;inset:0 0 auto;z-index:60;padding-top:14px}.v-nav{display:flex;align-items:center;justify-content:space-between;gap:18px;min-height:68px;padding:10px 14px 10px 18px;background:color-mix(in srgb,var(--v-surface) 94%,transparent);backdrop-filter:blur(18px);border:1px solid var(--v-line);border-radius:20px;box-shadow:0 12px 34px rgba(13,18,38,.08)}.v-logo{display:flex;align-items:center;min-width:0}.v-logo img{display:block;width:44px;height:44px;object-fit:contain;border-radius:12px}.v-desktop-nav{display:flex;align-items:center;gap:6px}.v-nav-link{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 12px;border-radius:11px;color:var(--v-muted);font-size:13px;font-weight:700;transition:.2s}.v-nav-link:hover{color:var(--v-primary-blue);background:color-mix(in srgb,var(--v-sky-blue) 8%,var(--v-surface))}.v-nav-cta{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:0 16px;border-radius:13px;background:var(--v-gradient);color:#fff;font-size:13px;font-weight:800;box-shadow:0 10px 24px rgba(0,108,255,.16);transition:.2s}.v-nav-cta:hover{transform:translateY(-1px);box-shadow:0 14px 30px rgba(0,108,255,.24)}.v-desktop-tools,.v-mobile-tools{display:flex;align-items:center;gap:6px}.v-mobile-tools{display:none}.v-icon-btn{width:42px;height:42px;display:grid;place-items:center;border:1px solid var(--v-line);border-radius:12px;background:var(--v-surface);color:var(--v-ink);transition:.2s;cursor:pointer}.v-icon-btn:hover{border-color:var(--v-sky-blue);color:var(--v-sky-blue)}.v-icon-btn svg{pointer-events:none}.v-menu{padding:10px;margin-top:8px;background:var(--v-surface);border:1px solid var(--v-line);border-radius:18px;box-shadow:0 18px 40px rgba(13,18,38,.12)}.v-menu[hidden]{display:none!important}.v-menu.is-open{display:block!important}.v-menu a{display:flex;align-items:center;min-height:46px;padding:0 12px;border-radius:12px;color:var(--v-muted);font-size:14px;font-weight:700}.v-menu a:hover{background:color-mix(in srgb,var(--v-sky-blue) 8%,var(--v-surface));color:var(--v-sky-blue)}.v-main{padding-top:112px}.v-footer{border-top:1px solid var(--v-line);background:var(--v-surface)}.v-footer-inner{width:min(1180px,calc(100% - 40px));margin-inline:auto;padding:34px 0;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}.v-footer-ident{height:38px;width:auto;display:block;object-fit:contain}.v-footer-meta{color:var(--v-muted);font-size:11px}.btn-primary,.nav-cta{background:var(--v-gradient)!important;color:#fff!important;border-color:transparent!important;box-shadow:0 10px 24px rgba(0,108,255,.16)!important}.gradient-text{background-image:var(--v-gradient)!important;background-size:100% 100%!important;-webkit-background-clip:text!important;background-clip:text!important;color:transparent!important;-webkit-text-fill-color:transparent!important}.glass,.glass-light{background:var(--v-surface)!important;border-color:var(--v-line)!important}.text-brand-300,.text-brand-400,.text-brand-500,.text-brand-600,.text-brand-700{color:var(--v-primary-blue)!important}.bg-brand-300,.bg-brand-400,.bg-brand-500,.bg-brand-600,.bg-brand-700{background:var(--v-gradient)!important}.border-brand-300,.border-brand-400,.border-brand-500,.border-brand-600,.border-brand-700{border-color:var(--v-primary-blue)!important}
        .velora-inline-language-switcher{position:relative;z-index:90;display:flex;align-items:center}.velora-language-trigger{height:42px;min-width:54px;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:0 9px;border:1px solid var(--v-line);border-radius:12px;background:var(--v-surface);color:var(--v-ink);font:inherit;font-size:11px;font-weight:900;letter-spacing:.04em;cursor:pointer;transition:border-color .18s,box-shadow .18s,background .18s}.velora-language-trigger:hover,.velora-language-trigger[aria-expanded="true"]{border-color:#6D46FF;box-shadow:0 7px 18px rgba(109,70,255,.10)}.velora-language-arrow{opacity:.5;transition:transform .18s}.velora-language-trigger[aria-expanded="true"] .velora-language-arrow{transform:rotate(180deg)}.velora-language-menu{position:absolute;top:calc(100% + 8px);inset-inline-end:0;width:248px;padding:8px;border:1px solid var(--v-line);border-radius:14px;background:var(--v-surface);box-shadow:0 20px 50px rgba(13,18,38,.16);overflow:hidden}.velora-language-menu[hidden]{display:none!important}.velora-language-list{display:grid;grid-template-columns:repeat(3,1fr);gap:4px;max-height:300px;overflow:auto}.velora-language-item{height:38px;display:flex;align-items:center;justify-content:center;gap:5px;border-radius:9px;color:var(--v-ink);text-decoration:none;transition:background .14s,border-color .14s,color .14s}.velora-language-item:hover{background:rgba(22,119,255,.07);color:#1677FF}.velora-language-item.is-active{background:linear-gradient(135deg,rgba(109,70,255,.10),rgba(0,184,255,.07));color:#6D46FF}.velora-language-code{font-size:10px;font-weight:900;letter-spacing:.06em}.velora-language-check{width:15px;height:15px;display:grid;place-items:center;border-radius:5px;background:#00D4A3;color:#fff;font-size:9px;font-weight:900}.velora-language-list::-webkit-scrollbar{width:4px}.velora-language-list::-webkit-scrollbar-thumb{background:#D8DEEA;border-radius:99px}html[data-theme="dark"] .velora-language-menu{background:#0D1226;border-color:#252E45;box-shadow:0 26px 70px rgba(0,0,0,.46)}html[data-theme="dark"] .velora-language-item{color:#F8FAFC}html[data-theme="dark"] .velora-language-item:hover{background:rgba(22,119,255,.12)}html[data-theme="dark"] .velora-language-item.is-active{background:linear-gradient(135deg,rgba(138,92,255,.14),rgba(0,184,255,.08));color:#B79CFF}html[data-theme="dark"] .velora-language-trigger{background:#0D1226;border-color:#252E45;color:#F8FAFC}
        @media (max-width:980px){.v-desktop-nav{display:none}.v-desktop-tools{display:none}.v-mobile-tools{display:flex}.v-menu{display:none;position:relative;z-index:80}.velora-inline-language-switcher{z-index:91}.velora-language-menu{position:fixed;top:72px;inset-inline-end:12px;width:248px}}
        @media (max-width:680px){.v-shell,.v-footer-inner{width:calc(100% - 24px)}.v-header{padding-top:8px}.v-nav{min-height:60px;border-radius:16px;padding:8px 9px}.v-logo img{width:36px;height:36px}.v-main{padding-top:84px}.v-footer-inner{flex-direction:column;align-items:flex-start}.v-footer-ident{height:30px}.velora-language-trigger{height:38px;min-width:44px;padding:0 8px;border-radius:10px}.velora-language-arrow{display:none}.velora-language-menu{top:64px;inset-inline:8px;width:auto;padding:8px;border-radius:14px}.velora-language-list{grid-template-columns:repeat(3,1fr);gap:4px;max-height:calc(100dvh - 110px)}.velora-language-item{height:36px}.velora-language-code{font-size:9px}.velora-language-check{width:14px;height:14px;font-size:8px}}
        @media (max-width:380px){.v-shell,.v-footer-inner{width:calc(100% - 18px)}.v-icon-btn{width:39px;height:39px}}
    </style>
    @stack('styles')
</head>
<body>
<div class="v-site">
<header class="v-header"><div class="v-shell"><nav class="v-nav" aria-label="Primary">
<a class="v-logo" href="{{ route('landing') }}"><img src="{{ asset('logo-bais.png') }}" alt="Velora" /></a>
<div class="v-desktop-nav">
<a class="v-nav-link" href="{{ route('landing') }}#features">{{ __('landing.nav_features') }}</a>
<a class="v-nav-link" href="{{ route('landing') }}#how-it-works">{{ __('landing.nav_how_it_works') }}</a>
<a class="v-nav-link" href="{{ route('pricing') }}">{{ __('landing.nav_pricing') }}</a>
<a class="v-nav-link" href="{{ route('central.login') }}">{{ __('landing.nav_company_admin_sign_in') }}</a>
</div>
<div class="v-desktop-tools">
@if ($registrationEnabled ?? true)<a class="v-nav-cta" href="{{ route('signup') }}">{{ __('landing.nav_start_trial') }} <span aria-hidden="true">→</span></a>@endif
<button id="themeToggleDesktop" type="button" class="v-icon-btn" aria-label="{{ __('landing.dark_mode') ?? 'Dark mode' }}"></button>
<div class="velora-inline-language-switcher">
<button type="button" class="velora-language-trigger" aria-haspopup="true" aria-expanded="false" aria-controls="velora-language-menu-desktop" aria-label="{{ __('landing.switcher_lang_label') ?? 'Change language' }}"><span class="velora-language-current">{{ strtoupper($landingLocale) }}</span><svg class="velora-language-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
<div id="velora-language-menu-desktop" class="velora-language-menu" hidden><div class="velora-language-list">
@foreach ($landingLanguages as $locale => $language)
<a class="velora-language-item{{ $locale === $landingLocale ? ' is-active' : '' }}" href="{{ route('landing', ['locale' => $locale]) }}" lang="{{ $locale }}" dir="{{ $language['direction'] ?? 'ltr' }}" aria-current="{{ $locale === $landingLocale ? 'true' : 'false' }}"><span class="velora-language-code">{{ strtoupper($locale) }}</span>@if ($locale === $landingLocale)<span class="velora-language-check" aria-hidden="true">✓</span>@endif</a>
@endforeach
</div></div></div>
</div>
<div class="v-mobile-tools">
<button id="themeToggleMobile" type="button" class="v-icon-btn" aria-label="{{ __('landing.dark_mode') ?? 'Dark mode' }}"></button>
<div class="velora-inline-language-switcher">
<button type="button" class="velora-language-trigger" aria-haspopup="true" aria-expanded="false" aria-controls="velora-language-menu-mobile" aria-label="{{ __('landing.switcher_lang_label') ?? 'Change language' }}"><span class="velora-language-current">{{ strtoupper($landingLocale) }}</span><svg class="velora-language-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
<div id="velora-language-menu-mobile" class="velora-language-menu" hidden><div class="velora-language-list">
@foreach ($landingLanguages as $locale => $language)
<a class="velora-language-item{{ $locale === $landingLocale ? ' is-active' : '' }}" href="{{ route('landing', ['locale' => $locale]) }}" lang="{{ $locale }}" dir="{{ $language['direction'] ?? 'ltr' }}" aria-current="{{ $locale === $landingLocale ? 'true' : 'false' }}"><span class="velora-language-code">{{ strtoupper($locale) }}</span>@if ($locale === $landingLocale)<span class="velora-language-check" aria-hidden="true">✓</span>@endif</a>
@endforeach
</div></div></div>
<button id="menuToggle" type="button" class="v-icon-btn" aria-label="Open menu" aria-controls="mobileMenu" aria-expanded="false"><svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="1.9" d="M4 7h16M4 12h16M4 17h16"/></svg></button>
</div></nav>
<div id="mobileMenu" class="v-menu" hidden>
<a href="{{ route('landing') }}#features">{{ __('landing.nav_features') }}</a><a href="{{ route('landing') }}#how-it-works">{{ __('landing.nav_how_it_works') }}</a><a href="{{ route('pricing') }}">{{ __('landing.nav_pricing') }}</a><a href="{{ route('central.login') }}">{{ __('landing.nav_company_admin_sign_in') }}</a><a href="{{ route('super-admin.login') }}">{{ __('landing.nav_super_admin_sign_in') }}</a>
@if ($registrationEnabled ?? true)<a href="{{ route('signup') }}" style="background:var(--v-gradient);color:#fff;justify-content:center">{{ __('landing.nav_start_trial') }}</a>@endif
</div></div></header>
<main class="v-main">@yield('content')</main>
<footer class="v-footer"><div class="v-footer-inner"><img class="v-footer-ident" src="{{ asset('logo-bais.png') }}" alt="Velora" /><div class="v-footer-meta">{{ date('Y') }} {{ $appName ?? config('app.name', 'Velora') }}. {{ __('landing.footer_rights') }}</div></div></footer>
</div>
<script>
(function(){const r=document.documentElement,m=document.querySelector('meta[name="theme-color"]'),d=document.getElementById('themeToggleDesktop'),mo=document.getElementById('themeToggleMobile'),menu=document.getElementById('mobileMenu'),mt=document.getElementById('menuToggle');const i={sun:'<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="4" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l-1.42-1.42m11.3-11.3 1.42-1.42"/></svg>',moon:'<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="1.8" d="M20.2 15.6A8.5 8.5 0 0 1 8.4 3.8 8.5 8.5 0 1 0 20.2 15.6Z"/></svg>'};function sync(){const dark=r.dataset.theme==='dark';[d,mo].forEach(b=>{if(!b)return;b.innerHTML=dark?i.sun:i.moon;b.setAttribute('aria-label',dark?'Switch to light mode':'Switch to dark mode')});if(m)m.content=dark?'#080B18':'#0D1226'}function toggle(){const n=r.dataset.theme==='dark'?'light':'dark';r.dataset.theme=n;localStorage.setItem('velora-theme',n);sync()}function setMenu(open){if(!menu||!mt)return;menu.hidden=!open;menu.classList.toggle('is-open',open);mt.setAttribute('aria-expanded',String(open));mt.setAttribute('aria-label',open?'Close menu':'Open menu')}d?.addEventListener('click',toggle);mo?.addEventListener('click',toggle);sync();mt?.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();setMenu(!menu.classList.contains('is-open'))});menu?.addEventListener('click',function(e){if(e.target.closest('a'))setMenu(false)});document.addEventListener('click',function(e){if(!menu||!mt)return;if(menu.classList.contains('is-open')&&!menu.contains(e.target)&&!mt.contains(e.target))setMenu(false)});document.addEventListener('keydown',function(e){if(e.key==='Escape')setMenu(false)});window.addEventListener('resize',function(){if(window.innerWidth>980)setMenu(false)})})();
</script>
<script id="velora-language-selector-script">
(function(){
    document.querySelectorAll('.velora-inline-language-switcher').forEach(function(wrapper){
        const trigger=wrapper.querySelector('.velora-language-trigger');
        const menu=wrapper.querySelector('.velora-language-menu');
        if(!trigger||!menu||wrapper.dataset.ready==='1')return;
        wrapper.dataset.ready='1';
        function close(){menu.hidden=true;trigger.setAttribute('aria-expanded','false')}
        function open(){menu.hidden=false;trigger.setAttribute('aria-expanded','true')}
        trigger.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();menu.hidden?open():close()});
        menu.addEventListener('click',function(e){e.stopPropagation()});
        document.addEventListener('click',function(e){if(!wrapper.contains(e.target))close()});
        document.addEventListener('keydown',function(e){if(e.key==='Escape')close()});
    });
})();
</script>
@stack('scripts')
</body>
</html>
