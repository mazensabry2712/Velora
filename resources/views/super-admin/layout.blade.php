<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $isRtl  = in_array($locale, ['ar', 'he', 'fa']);
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin Dashboard')</title>
    <!-- Prevent dark mode flash -->
    <script>
        (function() {
            var dm = localStorage.getItem('darkMode');
            if (dm === 'true' || (dm === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Tajawal', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        // Brand color matching Velora landing page
                        indigo: {
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
                },
            },
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }

        /* Fonts: Arabic Tajawal + Latin Plus Jakarta Sans */
        *, body { font-family: 'Tajawal', 'Plus Jakarta Sans', 'Inter', sans-serif; }
        :not([lang='ar']) * { font-family: 'Plus Jakarta Sans', 'Tajawal', 'Inter', sans-serif; }

        /* Scroll progress bar */
        #scroll-progress {
            position: fixed; top: 0; right: 0; left: 0; height: 3px; z-index: 9999;
            background: linear-gradient(to left, #6C63FF, #8b76ff, #38bdf8);
            transform-origin: right;
            transition: transform 0.1s linear;
        }

        /* Page fade-in */
        @keyframes pageIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .page-enter { animation: pageIn 0.4s ease-out both; }

        /* Staggered card animations */
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .card-animate { animation: cardIn 0.5s ease-out both; }
        .card-delay-1 { animation-delay: 0.05s; }
        .card-delay-2 { animation-delay: 0.1s; }
        .card-delay-3 { animation-delay: 0.15s; }
        .card-delay-4 { animation-delay: 0.2s; }
        .card-delay-5 { animation-delay: 0.25s; }
        .card-delay-6 { animation-delay: 0.3s; }
        .card-delay-7 { animation-delay: 0.35s; }

        /* Stat number counter */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .count-animate { animation: countUp 0.6s ease-out both; }

        /* Pulsing badge */
        @keyframes badgePulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239,68,68,.4); }
            50%       { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(239,68,68,0); }
        }
        .badge-pulse { animation: badgePulse 1.8s infinite; }

        /* Slide-down search */
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); max-height: 0; }
            to   { opacity: 1; transform: translateY(0); max-height: 100px; }
        }
        .slide-search { animation: slideDown 0.25s ease-out both; }

        /* Nav active underline */
        .nav-link-active::after {
            content: ''; display: block; height: 3px;
            background: linear-gradient(to left, #6C63FF, #8b76ff);
            border-radius: 9999px; margin-top: 2px;
        }

        /* Tooltip */
        .tooltip-wrapper { position: relative; }
        .tooltip-wrapper .tooltip-text {
            visibility: hidden; opacity: 0;
            background: #1e293b; color: #f1f5f9;
            text-align: center; border-radius: 6px;
            padding: 4px 10px; font-size: 12px;
            position: absolute; z-index: 100;
            bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%);
            white-space: nowrap; transition: opacity 0.2s;
            pointer-events: none;
        }
        .tooltip-wrapper:hover .tooltip-text { visibility: visible; opacity: 1; }

        /* Back-to-top pulse ring */
        @keyframes ripple {
            0%  { transform: scale(1); opacity: 0.6; }
            100%{ transform: scale(1.7); opacity: 0; }
        }
        .back-top-ring::before {
            content: ''; position: absolute; inset: 0;
            border-radius: 9999px; border: 2px solid #6C63FF;
            animation: ripple 1.5s infinite;
        }

        /* Skeleton shimmer */
        @keyframes shimmer {
            0%   { background-position: -600px 0; }
            100% { background-position: 600px 0; }
        }
        .skeleton {
            background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 600px 100%;
            animation: shimmer 1.4s infinite linear;
            border-radius: 8px;
        }
        .dark .skeleton {
            background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%);
            background-size: 600px 100%;
        }

        /* Smooth table row hover */
        tbody tr { transition: background 0.15s ease; }

        /* Focus rings */
        *:focus-visible { outline: 2px solid #6C63FF; outline-offset: 2px; border-radius: 6px; }

        /* Landing page glass + gradient utilities */
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(108, 99, 255, 0.15);
        }
        .gradient-text {
            background: linear-gradient(135deg, #6C63FF 0%, #aa9eff 50%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .brand-glow {
            box-shadow: 0 8px 30px rgba(108, 99, 255, 0.35);
        }
        .brand-glow:hover {
            box-shadow: 0 12px 40px rgba(108, 99, 255, 0.5);
        }

        /* Toast improved */
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes toastOut {
            from { opacity: 1; transform: translateY(0) scale(1); }
            to   { opacity: 0; transform: translateY(10px) scale(0.95); }
        }
        .toast-enter { animation: toastIn 0.3s ease-out both; }
        .toast-exit  { animation: toastOut 0.25s ease-in both; }

        /* Mobile menu slide */
        @keyframes menuSlide {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .mobile-menu-open { animation: menuSlide 0.2s ease-out both; }

        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #6366f1; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-surface min-h-screen antialiased">

    <!-- Scroll Progress Bar -->
    <div id="scroll-progress"></div>

    <!-- Navigation -->
    <nav class="bg-white/95 dark:bg-[#0c0b18]/90 backdrop-blur-md shadow-sm border-b border-slate-200 dark:border-white/[0.07] sticky top-0 z-40" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center gap-4">
                    <a href="{{ route('super-admin.dashboard') }}" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-md group-hover:shadow-indigo-300 dark:group-hover:shadow-indigo-900 transition-shadow">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <span class="text-xl font-bold gradient-text hidden sm:block" style="font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.01em">Super Admin</span>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                @php
                    $billingActive = request()->routeIs(
                        'super-admin.subscription-plans',
                        'super-admin.country-pricing.*',
                        'super-admin.promo-codes.*',
                        'super-admin.upgrade-requests'
                    );
                    $systemActive = request()->routeIs(
                        'super-admin.activity-logs',
                        'super-admin.notifications',
                        'super-admin.analytics',
                        'super-admin.reports',
                        'super-admin.kpis',
                        'super-admin.settings'
                    );
                @endphp
                <div class="hidden lg:flex lg:items-center lg:gap-0.5">

                    {{-- Dashboard --}}
                    @php $isActive = request()->routeIs('super-admin.dashboard'); @endphp
                    <a href="{{ route('super-admin.dashboard') }}"
                       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                              {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        {{ __('super-admin.nav_dashboard') }}
                    </a>

                    {{-- Companies --}}
                    @php $isActive = request()->routeIs('super-admin.tenants'); @endphp
                    <a href="{{ route('super-admin.tenants') }}"
                       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                              {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                        </svg>
                        {{ __('super-admin.nav_companies') }}
                    </a>

                    {{-- ── البلينج والأسعار (Dropdown) ───────────── --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @keydown.escape="open = false"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                                       {{ $billingActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            البلينج والأسعار
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-cloak @click.away="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute top-full mt-1.5 start-0 w-52 bg-white dark:bg-[#12101f] rounded-xl shadow-xl border border-slate-200 dark:border-white/10 overflow-hidden z-50 py-1">

                            @php
                                $billingLinks = [
                                    ['route' => 'super-admin.subscription-plans',    'label' => __('super-admin.nav_subscriptions'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                                    ['route' => 'super-admin.country-pricing.index', 'label' => 'الأسواق والأسعار',                  'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                                    ['route' => 'super-admin.promo-codes.index',     'label' => 'Promo Codes',                        'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
                                    ['route' => 'super-admin.upgrade-requests',      'label' => 'طلبات الترقية',                      'icon' => 'M7 11l5-5m0 0l5 5m-5-5v12'],
                                ];
                            @endphp
                            @foreach($billingLinks as $link)
                                @php $isActive = request()->routeIs($link['route']); @endphp
                                <a href="{{ route($link['route']) }}" @click="open = false"
                                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium transition-colors
                                          {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/60 hover:text-slate-900 dark:hover:text-white' }}">
                                    <svg class="w-4 h-4 flex-shrink-0 {{ $isActive ? 'text-indigo-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                                    </svg>
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- ── النظام (Dropdown) ───────────────────────── --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @keydown.escape="open = false"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                                       {{ $systemActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            النظام
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-cloak @click.away="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute top-full mt-1.5 start-0 w-48 bg-white dark:bg-[#12101f] rounded-xl shadow-xl border border-slate-200 dark:border-white/10 overflow-hidden z-50 py-1">

                            @php
                                $systemLinks = [
                                    ['route' => 'super-admin.activity-logs',  'label' => __('super-admin.nav_logs'),          'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                                    ['route' => 'super-admin.notifications',   'label' => __('super-admin.nav_notifications'), 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                                    ['route' => 'super-admin.analytics',       'label' => 'التحليلات',                         'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                                    ['route' => 'super-admin.settings',        'label' => __('super-admin.nav_settings'),      'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                                ];
                            @endphp
                            @foreach($systemLinks as $link)
                                @php $isActive = request()->routeIs($link['route']); @endphp
                                <a href="{{ route($link['route']) }}" @click="open = false"
                                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm font-medium transition-colors
                                          {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/60 hover:text-slate-900 dark:hover:text-white' }}">
                                    <svg class="w-4 h-4 flex-shrink-0 {{ $isActive ? 'text-indigo-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                                    </svg>
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                </div>

                <!-- Right: User menu + mobile toggle -->
                @php
                    $langNames  = ['en'=>'EN','ar'=>'AR','fr'=>'FR','es'=>'ES','de'=>'DE','it'=>'IT','pt'=>'PT','ru'=>'RU','zh'=>'中文','ja'=>'日本語','tr'=>'TR','hi'=>'HI','ko'=>'한국어','nl'=>'NL','id'=>'ID'];
                    $currentLang = app()->getLocale();
                @endphp
                <div class="flex items-center gap-2">

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm hover:bg-slate-100 dark:hover:bg-slate-700 transition group">
                            <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-sm">
                                <span class="text-white font-bold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                            <span class="text-slate-700 dark:text-slate-200 font-medium hidden md:block">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- User Dropdown -->
                        <div x-show="open" x-cloak @click.away="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute end-0 mt-2 w-72 bg-white dark:bg-[#12101f] rounded-2xl shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden z-50">

                            {{-- ── Profile Header ── --}}
                            <div class="relative px-4 py-4 overflow-hidden">
                                <div class="absolute inset-0" style="background: linear-gradient(135deg, #4d3de3 0%, #6C63FF 60%, #8b76ff 100%)"></div>
                                <div class="relative flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center flex-shrink-0 shadow-inner">
                                        <span class="text-white font-black text-lg">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-white leading-tight truncate">{{ auth()->user()->name }}</p>
                                        <p class="text-xs text-indigo-200 mt-0.5 truncate">{{ auth()->user()->email }}</p>
                                    </div>
                                    <span class="ms-auto flex-shrink-0 text-[10px] font-bold bg-white/25 text-white px-2.5 py-0.5 rounded-full border border-white/40 tracking-wide uppercase" style="font-family: 'Plus Jakarta Sans', sans-serif">
                                        Super Admin
                                    </span>
                                </div>
                            </div>

                            {{-- ── Settings Link ── --}}
                            <div class="px-3 pt-3 pb-1">
                                <a href="{{ route('super-admin.settings') }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-400 transition-all group">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/50 transition-colors flex-shrink-0">
                                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    {{ __('super-admin.profile_settings') }}
                                    <svg class="w-3.5 h-3.5 ms-auto text-slate-300 dark:text-slate-600 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </a>
                            </div>

                            {{-- ── Dark Mode ── --}}
                            <div class="mx-3 my-1 rounded-xl border border-slate-100 dark:border-slate-700">
                                <div class="flex items-center justify-between px-3 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        {{-- Icon reflects CURRENT mode: moon=dark active, sun=light active --}}
                                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-indigo-900/50 flex items-center justify-center flex-shrink-0">
                                            {{-- Sun: visible in LIGHT mode --}}
                                            <svg class="w-4 h-4 text-amber-500 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            {{-- Moon: visible in DARK mode --}}
                                            <svg class="w-4 h-4 text-indigo-400 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super-admin.nav_toggle_mode') }}</span>
                                    </div>
                                    <button onclick="toggleNavDark()" dir="ltr"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none bg-slate-200 dark:bg-indigo-600 cursor-pointer">
                                        <span class="inline-block h-4 w-4 rounded-full bg-white shadow-sm ring-0 transition-transform duration-200 translate-x-0.5 dark:translate-x-[21px]"></span>
                                    </button>
                                </div>
                            </div>

                            {{-- ── Language Grid ── --}}
                            <div class="mx-3 my-1 rounded-xl border border-slate-100 dark:border-slate-700 overflow-hidden">
                                <div class="flex items-center justify-between px-3 py-2 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-700/30">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                                        </svg>
                                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('super-admin.nav_language') }}</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-100 dark:bg-indigo-900/50 px-2 py-0.5 rounded-full">
                                        {{ strtoupper($currentLang) }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-5 gap-1 p-2">
                                    @foreach($langNames as $code => $label)
                                        <a href="{{ route('super-admin.lang', $code) }}"
                                           title="{{ $label }}"
                                           class="flex items-center justify-center py-1.5 text-[11px] font-semibold rounded-lg transition-all
                                                  {{ $currentLang === $code
                                                     ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-300 dark:shadow-indigo-900'
                                                     : 'text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-700 dark:hover:text-indigo-300' }}">
                                            {{ $label }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            {{-- ── Sign Out ── --}}
                            <div class="px-3 pt-1 pb-3">
                                <form method="POST" action="{{ route('super-admin.logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center flex-shrink-0 group-hover:bg-red-100 dark:group-hover:bg-red-900/40 transition-colors">
                                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                        </div>
                                        {{ __('super-admin.profile_signout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Hamburger -->
                    <button @click="mobileOpen = !mobileOpen"
                            class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 dark:text-slate-400 transition">
                        <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg x-show="mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div x-show="mobileOpen" @click.away="mobileOpen = false" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="lg:hidden border-t border-slate-200 dark:border-white/[0.07] bg-white dark:bg-[#0c0b18] pb-4">
            <div class="px-4 pt-3 space-y-0.5">

                {{-- Dashboard --}}
                @php $isActive = request()->routeIs('super-admin.dashboard'); @endphp
                <a href="{{ route('super-admin.dashboard') }}" @click="mobileOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    {{ __('super-admin.nav_dashboard') }}
                </a>

                {{-- Companies --}}
                @php $isActive = request()->routeIs('super-admin.tenants'); @endphp
                <a href="{{ route('super-admin.tenants') }}" @click="mobileOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                    {{ __('super-admin.nav_companies') }}
                </a>

                {{-- Billing group label --}}
                <p class="px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">البلينج والأسعار</p>
                @foreach([
                    ['route' => 'super-admin.subscription-plans',    'label' => __('super-admin.nav_subscriptions'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    ['route' => 'super-admin.country-pricing.index', 'label' => 'الأسواق والأسعار',                  'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['route' => 'super-admin.promo-codes.index',     'label' => 'Promo Codes',                        'icon' => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
                    ['route' => 'super-admin.upgrade-requests',      'label' => 'طلبات الترقية',                      'icon' => 'M7 11l5-5m0 0l5 5m-5-5v12'],
                ] as $link)
                    @php $isActive = request()->routeIs($link['route']); @endphp
                    <a href="{{ route($link['route']) }}" @click="mobileOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition ps-7 {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/></svg>
                        {{ $link['label'] }}
                    </a>
                @endforeach

                {{-- System group label --}}
                <p class="px-3 pt-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">النظام</p>
                @foreach([
                    ['route' => 'super-admin.activity-logs',  'label' => __('super-admin.nav_logs'),          'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                    ['route' => 'super-admin.notifications',   'label' => __('super-admin.nav_notifications'), 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                    ['route' => 'super-admin.analytics',       'label' => 'التحليلات',                         'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['route' => 'super-admin.settings',        'label' => __('super-admin.nav_settings'),      'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                ] as $link)
                    @php $isActive = request()->routeIs($link['route']); @endphp
                    <a href="{{ route($link['route']) }}" @click="mobileOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition ps-7 {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/></svg>
                        {{ $link['label'] }}
                    </a>
                @endforeach

            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    @hasSection('breadcrumb')
    <div class="bg-white dark:bg-[#0c0b18] border-b border-slate-200 dark:border-white/[0.07]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5">
            <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <a href="{{ route('super-admin.dashboard') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    {{ __('super-admin.nav_dashboard') }}
                </a>
                <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                @yield('breadcrumb')
            </nav>
        </div>
    </div>
    @endif

    <!-- Page Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 page-enter">
        @yield('content')
    </main>

    <!-- Back to Top Button -->
    <button id="back-to-top"
            onclick="window.scrollTo({top:0,behavior:'smooth'})"
            class="back-top-ring fixed bottom-6 right-6 w-12 h-12 bg-gradient-to-br from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-full shadow-xl flex items-center justify-center z-40 transition-all duration-300 opacity-0 translate-y-4 pointer-events-none"
            style="transition: opacity .3s, transform .3s;">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
    </button>

    <!-- Global Toast Container -->
    <div id="toast-container" class="fixed bottom-4 left-4 z-50 space-y-2 pointer-events-none"></div>

    <!-- Scripts -->
    @stack('scripts')

    <script>
        // ── Scroll Progress Bar ──────────────────────────────
        window.addEventListener('scroll', () => {
            const el = document.getElementById('scroll-progress');
            if (!el) return;
            const scrolled = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            el.style.transform = `scaleX(${Math.min(scrolled / 100, 1)})`;
        }, { passive: true });

        // ── Back to Top ──────────────────────────────────────
        window.addEventListener('scroll', () => {
            const btn = document.getElementById('back-to-top');
            if (!btn) return;
            if (window.scrollY > 300) {
                btn.style.opacity = '1';
                btn.style.transform = 'translateY(0)';
                btn.style.pointerEvents = 'auto';
            } else {
                btn.style.opacity = '0';
                btn.style.transform = 'translateY(16px)';
                btn.style.pointerEvents = 'none';
            }
        }, { passive: true });

        // ── Dark mode toggle (nav button) ────────────────────
        function toggleNavDark() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            // Sync Alpine components
            window.dispatchEvent(new CustomEvent('dark-mode-changed', { detail: { isDark } }));
        }

        // ── Global Toast ──────────────────────────────────────
        function showToast(message, type = 'success', duration = 3500) {
            const icons = {
                success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                error:   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
                info:    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
            };
            const colors = {
                success: 'bg-emerald-500',
                error:   'bg-red-500',
                info:    'bg-blue-500',
                warning: 'bg-amber-500',
            };
            const toast = document.createElement('div');
            toast.className = `toast-enter pointer-events-auto flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-2xl text-white font-medium text-sm ${colors[type] || colors.success}`;
            toast.style.fontFamily = "'Tajawal', sans-serif";
            toast.innerHTML = `
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icons[type] || icons.success}</svg>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="mr-auto opacity-70 hover:opacity-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>`;
            const container = document.getElementById('toast-container');
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('toast-enter');
                toast.classList.add('toast-exit');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // ── Counter Animation ──────────────────────────────────
        function animateCounter(el, target, duration = 800) {
            const start = 0;
            const startTime = performance.now();
            function update(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const ease = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.round(start + (target - start) * ease);
                if (progress < 1) requestAnimationFrame(update);
            }
            requestAnimationFrame(update);
        }

        // Init counters on page load
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-counter]').forEach(el => {
                const val = parseInt(el.dataset.counter, 10) || 0;
                animateCounter(el, val);
            });
        });
    </script>
</body>
</html>
