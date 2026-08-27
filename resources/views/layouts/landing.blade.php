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
    <meta name="theme-color" content="#061113" />
    <meta name="color-scheme" content="dark" />
    <title>{{ $pageTitle ?? 'Velora — Smart Booking & Queue Management' }}</title>
    <meta property="og:title" content="{{ $pageTitle ?? 'Velora' }}" />
    <meta property="og:description" content="{{ $metaDescription ?? 'Smart booking platform for modern businesses.' }}" />
    <meta property="og:image" content="{{ asset('images/og-image.png') }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />
    <link rel="icon" href="{{ asset('favicon.svg') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50:'#e9fffd',100:'#cffaf7',200:'#9ee7e1',300:'#63ddd5',400:'#35c7be',
                            500:'#19a79f',600:'#128b85',700:'#0d706c',800:'#0b5956',900:'#0a4745',950:'#061113'
                        },
                        surface: '#061113'
                    }
                }
            }
        };
    </script>
    <style>
        :root{
            --v-bg:#061113;--v-surface:#0a1b1d;--v-surface-2:#0d2224;--v-border:#173638;
            --v-text:#eff8f7;--v-muted:#8ea5a6;--v-primary:#19a79f;--v-accent:#35c7be;
        }
        *{box-sizing:border-box}
        html{background:var(--v-bg);scroll-behavior:smooth}
        body{margin:0;background:var(--v-bg);color:var(--v-text);font-family:'Plus Jakarta Sans','Inter',sans-serif;min-width:320px;overflow-x:hidden}
        a,button{ -webkit-tap-highlight-color:transparent }
        button,input,select,textarea{font:inherit}
        [x-cloak]{display:none!important}
        .v-site{min-height:100dvh;background:var(--v-bg);overflow-x:hidden}
        .v-nav{position:fixed;inset:0 0 auto;z-index:60;border-bottom:1px solid rgba(23,54,56,.84);background:rgba(6,17,19,.88);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px)}
        .v-nav-inner{width:min(1160px,calc(100% - 32px));min-height:72px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:20px}
        .v-logo{display:flex;align-items:center;gap:10px;min-width:0;color:var(--v-text);text-decoration:none}
        .v-logo img{display:block;width:auto;height:34px;max-width:180px;object-fit:contain}
        .v-nav-links{display:flex;align-items:center;gap:8px}
        .v-nav-link{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 12px;border-radius:12px;color:#b4c7c8;text-decoration:none;font-size:13px;font-weight:600;transition:.2s}
        .v-nav-link:hover{color:#fff;background:#0d2224}
        .v-nav-cta{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 16px;border-radius:13px;background:var(--v-primary);color:#041011;text-decoration:none;font-size:13px;font-weight:800;box-shadow:0 10px 24px rgba(25,167,159,.18);transition:.2s}
        .v-nav-cta:hover{background:#25b8b0;transform:translateY(-1px)}
        .v-nav-tools{display:flex;align-items:center;gap:8px}
        .v-icon-btn{width:42px;height:42px;display:grid;place-items:center;border:1px solid var(--v-border);border-radius:12px;background:#0a1b1d;color:#b9cccd}
        .v-menu{display:none;position:absolute;left:16px;right:16px;top:calc(100% + 8px);padding:10px;border:1px solid var(--v-border);border-radius:18px;background:#081719;box-shadow:0 24px 60px rgba(0,0,0,.35)}
        .v-menu a{display:flex;align-items:center;min-height:46px;padding:0 12px;border-radius:12px;color:#b9cccd;text-decoration:none;font-size:14px;font-weight:600}
        .v-menu a:hover{background:#0d2224;color:#fff}
        .v-main{padding-top:72px}
        .v-focus:focus-visible{outline:2px solid var(--v-accent);outline-offset:3px}
        .v-footer{border-top:1px solid var(--v-border);background:#061113}
        .v-footer-inner{width:min(1160px,calc(100% - 32px));margin:0 auto;padding:34px 0;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
        .v-footer-ident{height:26px;width:auto;opacity:.94;display:block}
        .v-footer-meta{color:#718788;font-size:12px}
        @media(max-width:900px){.v-nav-links{display:none}.v-menu{display:block}.v-nav-inner{min-height:66px}.v-logo img{height:31px}}
        @media(max-width:640px){.v-nav-inner{width:calc(100% - 20px);min-height:62px;gap:10px}.v-logo img{height:28px;max-width:145px}.v-nav-tools{gap:6px}.v-icon-btn{width:40px;height:40px}.v-nav-cta{min-height:40px;padding:0 12px;font-size:12px}.v-main{padding-top:62px}.v-footer-inner{width:calc(100% - 20px);padding:26px 0;flex-direction:column;align-items:flex-start}.v-footer-ident{height:22px}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
    </style>
    @stack('styles')
</head>
<body>
<div class="v-site">
    <nav class="v-nav" aria-label="Primary">
        <div class="v-nav-inner">
            <a href="{{ route('landing') }}" class="v-logo v-focus" aria-label="Velora home">
                <img src="{{ asset('logo.png') }}" alt="Velora" />
            </a>
            <div class="v-nav-links">
                <a href="{{ route('landing') }}#features" class="v-nav-link">{{ __('landing.nav_features') }}</a>
                <a href="{{ route('landing') }}#how-it-works" class="v-nav-link">{{ __('landing.nav_how_it_works') }}</a>
                <a href="{{ route('landing') }}#pricing" class="v-nav-link">{{ __('landing.nav_pricing') }}</a>
                <button type="button" onclick="window.dispatchEvent(new Event('velora:open-lang-switcher'))" class="v-icon-btn v-focus" aria-label="{{ __('landing.switcher_lang_label') ?? 'Change language' }}">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h12M9 3v2m1.1 9.2A17.8 17.8 0 0 1 6.4 9m6.1 9 4.5-9 4.5 9m-.8-2h-7.4"/></svg>
                </button>
                <a href="{{ route('central.login') }}" class="v-nav-link">{{ __('landing.nav_company_admin_sign_in') }}</a>
                @if ($registrationEnabled ?? true)
                    <a href="{{ route('signup') }}" class="v-nav-cta">{{ __('landing.nav_start_trial') }}</a>
                @endif
            </div>
            <div class="v-nav-tools md:hidden">
                <button type="button" onclick="window.dispatchEvent(new Event('velora:open-lang-switcher'))" class="v-icon-btn v-focus" aria-label="{{ __('landing.switcher_lang_label') ?? 'Change language' }}">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5h12M9 3v2m1.1 9.2A17.8 17.8 0 0 1 6.4 9m6.1 9 4.5-9 4.5 9m-.8-2h-7.4"/></svg>
                </button>
                <button id="menuToggle" type="button" class="v-icon-btn v-focus" aria-label="Open menu" aria-controls="mobileMenu" aria-expanded="false">
                    <svg width="19" height="19" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>
        <div id="mobileMenu" class="v-menu" hidden>
            <a href="{{ route('landing') }}#features">{{ __('landing.nav_features') }}</a>
            <a href="{{ route('landing') }}#how-it-works">{{ __('landing.nav_how_it_works') }}</a>
            <a href="{{ route('landing') }}#pricing">{{ __('landing.nav_pricing') }}</a>
            <a href="{{ route('central.login') }}">{{ __('landing.nav_company_admin_sign_in') }}</a>
            <a href="{{ route('super-admin.login') }}">{{ __('landing.nav_super_admin_sign_in') }}</a>
            @if ($registrationEnabled ?? true)
                <a href="{{ route('signup') }}" style="background:var(--v-primary);color:#041011;justify-content:center">{{ __('landing.nav_start_trial') }}</a>
            @endif
        </div>
    </nav>

    <main class="v-main">
        @yield('content')
    </main>

    <footer class="v-footer">
        <div class="v-footer-inner">
            <img class="v-footer-ident" src="{{ asset('ident.png') }}" alt="Velora" />
            <div class="v-footer-meta">© {{ date('Y') }} Velora. All rights reserved.</div>
        </div>
    </footer>
</div>

<script>
(function(){
    const toggle=document.getElementById('menuToggle');
    const menu=document.getElementById('mobileMenu');
    if(!toggle||!menu)return;
    const close=()=>{menu.hidden=true;toggle.setAttribute('aria-expanded','false')};
    toggle.addEventListener('click',()=>{const open=menu.hidden;menu.hidden=!open;toggle.setAttribute('aria-expanded',String(open))});
    menu.querySelectorAll('a').forEach(a=>a.addEventListener('click',close));
    document.addEventListener('click',e=>{if(!menu.hidden&&!menu.contains(e.target)&&!toggle.contains(e.target))close()});
})();
(function(){
    var LANGS={en:['🇬🇧','EN'],ar:['🇸🇦','عربي'],fr:['🇫🇷','FR'],es:['🇪🇸','ES'],de:['🇩🇪','DE'],it:['🇮🇹','IT'],pt:['🇵🇹','PT'],ru:['🇷🇺','RU'],zh:['🇨🇳','中文'],ja:['🇯🇵','日本語'],tr:['🇹🇷','TR'],hi:['🇮🇳','हिंदी'],ko:['🇰🇷','한국어'],nl:['🇳🇱','NL'],id:['🇮🇩','ID']};
    document.addEventListener('velora:lang-changed',function(e){void(LANGS[e.detail.lang]||LANGS.en)});
})();
</script>
@stack('scripts')
</body>
</html>
