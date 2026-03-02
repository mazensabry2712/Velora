<!DOCTYPE html>
@php
    $landingLocale = session('central_locale', 'en');
    app()->setLocale($landingLocale);
    $isRtl = $landingLocale === 'ar';
@endphp
<html lang="{{ $landingLocale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="{{ $metaDescription ?? 'Velora — The all-in-one appointment & queue management platform for modern businesses. Start your 14-day free trial today.' }}" />
    <meta name="theme-color" content="#6C63FF" />

    <title>{{ $pageTitle ?? 'Velora — Smart Booking & Queue Management' }}</title>

    <!-- Open Graph -->
    <meta property="og:title"       content="{{ $pageTitle ?? 'Velora' }}" />
    <meta property="og:description" content="{{ $metaDescription ?? 'Smart booking platform for modern businesses.' }}" />
    <meta property="og:image"       content="{{ asset('images/og-image.png') }}" />
    <meta property="og:url"         content="{{ url()->current() }}" />
    <meta property="og:type"        content="website" />

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    <!-- Tailwind CDN (prod: compile with Vite) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50:  '#f0eeff',
                            100: '#e4e0ff',
                            200: '#ccc5ff',
                            300: '#aa9eff',
                            400: '#8b76ff',
                            500: '#6C63FF',
                            600: '#5b4ff7',
                            700: '#4d3de3',
                            800: '#4032bc',
                            900: '#362e98',
                            950: '#211c5e',
                        },
                        surface: '#0f0e1a',
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                        'hero-glow': 'radial-gradient(ellipse 80% 60% at 50% -10%, #6C63FF44 0%, transparent 70%)',
                    },
                    animation: {
                        'fade-up':    'fadeUp 0.6s ease both',
                        'pulse-slow': 'pulse 4s ease-in-out infinite',
                        'float':      'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeUp: {
                            '0%':   { opacity: '0', transform: 'translateY(24px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%':      { transform: 'translateY(-12px)' },
                        },
                    },
                },
            },
        };
    </script>

    <style>
        body { font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
        .gradient-text {
            background: linear-gradient(135deg, #6C63FF 0%, #a78bfa 50%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glass-light {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(108, 99, 255, 0.15);
        }
        .nav-blur {
            background: rgba(15, 14, 26, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        }
        .btn-primary {
            background: linear-gradient(135deg, #6C63FF 0%, #8b76ff 100%);
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.4);
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(108, 99, 255, 0.55);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 60px rgba(108, 99, 255, 0.2);
        }
        [data-aos] { opacity: 0; }
        .aos-animate { opacity: 1; }
    </style>

    @stack('styles')
</head>
<body class="bg-surface text-white antialiased overflow-x-hidden">

<!-- ══ NAVBAR ════════════════════════════════════════════════════════════ -->
<nav class="nav-blur fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="{{ route('landing') }}" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg btn-primary flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xl font-bold text-white tracking-tight">Velora</span>
            </a>

            <!-- Desktop Nav -->
            <div class="hidden md:flex items-center gap-8">
                <a href="{{ route('landing') }}#features" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('landing.nav_features') }}</a>
                <a href="{{ route('landing') }}#how-it-works" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('landing.nav_how_it_works') }}</a>
                <a href="{{ route('pricing') }}" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('landing.nav_pricing') }}</a>
                <a href="#testimonials" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('landing.nav_testimonials') }}</a>
                <a href="#faq" class="text-sm text-gray-400 hover:text-white transition-colors">{{ __('landing.nav_faq') }}</a>
            </div>

            <!-- CTA Buttons -->
            <div class="flex items-center gap-3">
                <a href="{{ route('super-admin.login') }}"
                   class="hidden sm:inline-flex text-sm text-gray-300 hover:text-white transition-colors px-3 py-1.5">
                    {{ __('landing.nav_sign_in') }}
                </a>
                <a href="{{ route('signup') }}"
                   class="btn-primary text-sm font-semibold text-white px-3 sm:px-5 py-2.5 rounded-xl inline-flex items-center gap-2">
                    <span class="hidden sm:inline">{{ __('landing.nav_start_trial') }}</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>

                <!-- Language Switcher -->
                @php
                    $landingLangs = [
                        'en' => ['flag' => '🇬🇧', 'label' => 'EN'],
                        'ar' => ['flag' => '🇸🇦', 'label' => 'عربي'],
                        'fr' => ['flag' => '🇫🇷', 'label' => 'FR'],
                        'es' => ['flag' => '🇪🇸', 'label' => 'ES'],
                        'de' => ['flag' => '🇩🇪', 'label' => 'DE'],
                        'it' => ['flag' => '🇮🇹', 'label' => 'IT'],
                        'pt' => ['flag' => '🇵🇹', 'label' => 'PT'],
                        'ru' => ['flag' => '🇷🇺', 'label' => 'RU'],
                        'zh' => ['flag' => '🇨🇳', 'label' => '中文'],
                        'ja' => ['flag' => '🇯🇵', 'label' => '日本語'],
                        'tr' => ['flag' => '🇹🇷', 'label' => 'TR'],
                        'hi' => ['flag' => '🇮🇳', 'label' => 'हिंदी'],
                        'ko' => ['flag' => '🇰🇷', 'label' => '한국어'],
                        'nl' => ['flag' => '🇳🇱', 'label' => 'NL'],
                        'id' => ['flag' => '🇮🇩', 'label' => 'ID'],
                    ];
                    $curLandingLang = $landingLangs[$landingLocale] ?? $landingLangs['en'];
                @endphp
                <div class="relative hidden sm:block" id="landingLangWrapper">
                    <button onclick="document.getElementById('landingLangMenu').classList.toggle('hidden')"
                        class="flex items-center gap-1.5 text-xs font-medium text-gray-300 hover:text-white px-2.5 py-1.5 rounded-lg border border-white/10 hover:border-white/20 transition-all bg-white/5 hover:bg-white/10">
                        <span>{{ $curLandingLang['flag'] }}</span>
                        <span>{{ $curLandingLang['label'] }}</span>
                        <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="landingLangMenu"
                        class="hidden absolute {{ $isRtl ? 'left-0' : 'right-0' }} mt-2 w-36 bg-[#1a1830] border border-white/10 rounded-xl shadow-xl py-1 z-50">
                        @foreach($landingLangs as $code => $lang)
                        <a href="{{ route('landing.lang', $code) }}"
                            class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-white/5 transition-colors {{ $landingLocale === $code ? 'text-brand-400 font-semibold' : 'text-gray-300' }}">
                            <span>{{ $lang['flag'] }}</span>
                            <span>{{ $lang['label'] }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>

                <!-- Mobile menu toggle -->
                <button id="menuToggle" class="md:hidden p-2 rounded-lg text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Nav -->
        <div id="mobileMenu" class="md:hidden hidden pb-5 border-t border-white/5 mt-2 pt-4">
            <div class="flex flex-col gap-1">
                <a href="{{ route('landing') }}#features"    class="text-sm text-gray-400 hover:text-white px-3 py-2.5 rounded-lg hover:bg-white/5 transition-colors">{{ __('landing.nav_features') }}</a>
                <a href="{{ route('landing') }}#how-it-works" class="text-sm text-gray-400 hover:text-white px-3 py-2.5 rounded-lg hover:bg-white/5 transition-colors">{{ __('landing.nav_how_it_works') }}</a>
                <a href="{{ route('pricing') }}"             class="text-sm text-gray-400 hover:text-white px-3 py-2.5 rounded-lg hover:bg-white/5 transition-colors">{{ __('landing.nav_pricing') }}</a>
                <a href="#testimonials"                      class="text-sm text-gray-400 hover:text-white px-3 py-2.5 rounded-lg hover:bg-white/5 transition-colors">{{ __('landing.nav_testimonials') }}</a>
                <a href="#faq"                               class="text-sm text-gray-400 hover:text-white px-3 py-2.5 rounded-lg hover:bg-white/5 transition-colors">{{ __('landing.nav_faq') }}</a>
                <a href="{{ route('central.login') }}"   class="text-sm text-gray-300 hover:text-white px-3 py-2.5 rounded-lg hover:bg-white/5 transition-colors">{{ __('landing.nav_sign_in') }}</a>
                <a href="{{ route('signup') }}"
                   class="btn-primary text-sm font-semibold text-white px-5 py-3 rounded-xl text-center mt-2">
                    {{ __('landing.nav_start_trial') }}
                </a>
                {{-- Mobile Language Switcher --}}
                <div class="mt-3 pt-3 border-t border-white/5">
                    <p class="text-xs text-gray-600 uppercase tracking-wider px-1 mb-2">Language</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($landingLangs as $code => $lang)
                        <a href="{{ route('landing.lang', $code) }}"
                           class="flex items-center gap-1 px-2 py-1.5 rounded-lg text-xs transition-colors {{ $landingLocale === $code ? 'bg-brand-500/20 text-brand-400 font-semibold border border-brand-500/40' : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white border border-transparent' }}">
                            <span>{{ $lang['flag'] }}</span><span>{{ $lang['label'] }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- ══ MAIN CONTENT ══════════════════════════════════════════════════════ -->
<main>
    @yield('content')
</main>

<!-- ══ FOOTER ════════════════════════════════════════════════════════════ -->
<footer class="border-t border-white/10 bg-surface">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-8 mb-12">
            <!-- Brand -->
            <div class="col-span-2">
                <a href="{{ route('landing') }}" class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg btn-primary flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold">Velora</span>
                </a>
                <p class="text-gray-400 text-sm leading-relaxed max-w-xs">
                    {{ __('landing.footer_tagline') }}
                </p>
                <div class="flex gap-3 mt-4">
                    <a href="#" class="w-8 h-8 rounded-lg glass flex items-center justify-center text-gray-400 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
                    </a>
                    <a href="#" class="w-8 h-8 rounded-lg glass flex items-center justify-center text-gray-400 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                </div>
            </div>

            <!-- Product -->
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">{{ __('landing.footer_product') }}</h4>
                <ul class="space-y-2.5 text-sm text-gray-400">
                    <li><a href="{{ route('landing') }}#features" class="hover:text-white transition-colors">{{ __('landing.footer_features') }}</a></li>
                    <li><a href="{{ route('pricing') }}"           class="hover:text-white transition-colors">{{ __('landing.footer_pricing') }}</a></li>
                    <li><a href="#"                                class="hover:text-white transition-colors">{{ __('landing.footer_changelog') }}</a></li>
                    <li><a href="#"                                class="hover:text-white transition-colors">{{ __('landing.footer_roadmap') }}</a></li>
                </ul>
            </div>

            <!-- Company -->
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">{{ __('landing.footer_company') }}</h4>
                <ul class="space-y-2.5 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-white transition-colors">{{ __('landing.footer_about') }}</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">{{ __('landing.footer_blog') }}</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">{{ __('landing.footer_careers') }}</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">{{ __('landing.footer_contact') }}</a></li>
                </ul>
            </div>

            <!-- Legal -->
            <div>
                <h4 class="text-sm font-semibold text-white mb-4">{{ __('landing.footer_legal') }}</h4>
                <ul class="space-y-2.5 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-white transition-colors">{{ __('landing.footer_privacy') }}</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">{{ __('landing.footer_terms') }}</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">{{ __('landing.footer_cookie') }}</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">{{ __('landing.footer_gdpr') }}</a></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-gray-500 text-sm">© {{ date('Y') }} Velora. {{ __('landing.footer_rights') }}</p>
            <div class="flex items-center gap-2 text-gray-500 text-sm">
                <span>{{ __('landing.footer_available') }}</span>
                <div class="flex gap-1">
                    @foreach(['�🇧','🇸🇦','🇫🇷','🇪🇸','🇩🇪','🇧🇷','🇯🇵','🇨🇳','🇹🇷','🇮🇳','🇰🇷','🇳🇱','🇮🇩'] as $flag)
                        <span class="text-base">{{ $flag }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Mobile menu script -->
<script>
    document.getElementById('menuToggle')?.addEventListener('click', () => {
        document.getElementById('mobileMenu')?.classList.toggle('hidden');
    });

    // Close language dropdown on outside click
    document.addEventListener('click', function(e) {
        var wrapper = document.getElementById('landingLangWrapper');
        var menu    = document.getElementById('landingLangMenu');
        if (wrapper && menu && !wrapper.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
</script>

@stack('scripts')
</body>
</html>
