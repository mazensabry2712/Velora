<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $isRtl  = in_array($locale, ['ar', 'he', 'fa']);
    $businessSettings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
    $businessName = $businessSettings?->business_name ?? tenant()->name ?? config('app.name');
    if (is_array($businessName)) {
        $businessName = $businessName[$locale] ?? $businessName['en'] ?? (is_array(reset($businessName)) ? config('app.name') : reset($businessName)) ?? config('app.name');
    }
    if (is_object($businessName)) {
        $businessName = $businessName->{$locale} ?? $businessName->en ?? config('app.name');
    }
    $businessName = is_scalar($businessName) ? (string) $businessName : config('app.name');
    $businessLogo = $businessSettings?->logo ?? null;
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageTitle = $__env->yieldContent('title');
        if (is_array($pageTitle) || is_object($pageTitle)) { $pageTitle = ''; }
        $pageTitle = (string)($pageTitle ?: 'Admin');
    @endphp
    <title>{{ $pageTitle }} - {{ $businessName }}</title>

    <!-- Dark mode: run BEFORE render to avoid flash -->
    <script>
        (function () {
            var saved = localStorage.getItem('adminDarkMode');
            if (saved === 'true' || (saved === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        [x-cloak] { display: none !important; }
        *, body { font-family: 'Tajawal', sans-serif; }

        /* Scroll progress */
        #admin-scroll-progress {
            position: fixed; top: 0; inset-inline: 0; height: 3px; z-index: 9999;
            background: linear-gradient(to left, #6366f1, #8b5cf6, #06b6d4);
            transform-origin: right;
            transition: transform 0.1s linear;
        }
        /* Page enter */
        @keyframes pageIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .admin-page-enter { animation: pageIn 0.4s ease-out both; }

        /* Card animate */
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .card-animate { animation: cardIn 0.5s ease-out both; }
        .card-delay-1 { animation-delay: .05s; }
        .card-delay-2 { animation-delay: .1s; }
        .card-delay-3 { animation-delay: .15s; }
        .card-delay-4 { animation-delay: .2s; }

        /* Toast */
        @keyframes toastIn  { from { opacity:0; transform: translateY(20px) scale(.95); } to { opacity:1; transform:none; } }
        @keyframes toastOut { from { opacity:1; } to { opacity:0; transform: translateY(10px) scale(.95); } }
        .toast-enter { animation: toastIn  .3s ease-out both; }
        .toast-exit  { animation: toastOut .25s ease-in  both; }

        /* Mobile menu slide */
        @keyframes menuSlide {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .mobile-menu-open { animation: menuSlide .2s ease-out both; }

        /* Back-to-top ring */
        @keyframes ripple {
            0%   { transform: scale(1); opacity: .6; }
            100% { transform: scale(1.7); opacity: 0; }
        }
        .back-top-ring::before {
            content: ''; position: absolute; inset: 0;
            border-radius: 9999px; border: 2px solid #6366f1;
            animation: ripple 1.5s infinite;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: #6366f1; }
        .dark ::-webkit-scrollbar-thumb { background: #475569; }

        /* Focus rings */
        *:focus-visible { outline: 2px solid #6366f1; outline-offset: 2px; border-radius: 6px; }

        /* Smooth table hover */
        tbody tr { transition: background 0.15s ease; }

        /* Counter */
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .count-animate { animation: countUp .6s ease-out both; }
    </style>

    @stack('styles')
</head>
<body class="bg-slate-50 dark:bg-slate-900 min-h-screen antialiased">

    <!-- Scroll Progress Bar -->
    <div id="admin-scroll-progress"></div>

    <!-- ═══════════════════════════════════════ NAVBAR ═══════════════════════════════════════ -->
    @php
        $adminNavLinks = [
            ['url' => '/admin/appointments', 'match' => 'admin/appointments*', 'label' => __('Appointments'),
             'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['url' => '/admin/queue',         'match' => 'admin/queue*',         'label' => __('Queue'),
             'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0'],
            ['url' => '/admin/staff',         'match' => 'admin/staff*',         'label' => __('Staff'),
             'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['url' => '/admin/customers',     'match' => 'admin/customers*',     'label' => __('Customers'),
             'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['url' => '/admin/reports',       'match' => 'admin/reports*',       'label' => __('Reports'),
             'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
            ['url' => '/admin/settings',      'match' => 'admin/settings*',      'label' => __('Settings'),
             'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ];
        if (auth()->user()->isAdminTenant()) {
            $adminNavLinks[] = [
                'url' => route('admin.subscription.index'), 'match' => 'admin/subscription*', 'label' => __('Subscription'),
                'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            ];
            $adminNavLinks[] = [
                'url' => '/admin/assistants', 'match' => 'admin/assistants*', 'label' => __('Assistants'),
                'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m1.636-6.364l.707.707M6 20l6-6 6 6',
            ];
        }
        $supportedLangs = [
            'en'=>'EN','ar'=>'عربي','de'=>'DE','es'=>'ES','fr'=>'FR',
            'hi'=>'HI','id'=>'ID','it'=>'IT','ja'=>'JA','ko'=>'KO',
            'nl'=>'NL','pt'=>'PT','ru'=>'RU','tr'=>'TR','zh'=>'中文',
        ];
        $currentLang = app()->getLocale();
    @endphp

    <nav class="bg-white/95 dark:bg-slate-800/95 backdrop-blur-sm shadow-sm border-b border-slate-200 dark:border-slate-700 sticky top-0 z-40"
         x-data="{ mobileOpen: false, userOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">

                {{-- ══ LEFT: Logo + Nav Links ══ --}}
                <div class="flex items-center gap-1">
                    <!-- Logo -->
                    <a href="/admin/dashboard" class="flex items-center gap-3 group {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                        @if($businessLogo)
                            <img src="{{ asset('storage/' . $businessLogo) }}" alt="{{ $businessName }}"
                                 class="h-9 w-auto rounded-lg shadow-sm">
                        @else
                            <div class="w-9 h-9 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-md group-hover:shadow-indigo-300 dark:group-hover:shadow-indigo-900 transition-shadow">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        <span class="text-base font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent hidden sm:block">
                            {{ $businessName }}
                        </span>
                    </a>

                    <!-- Desktop Nav Links -->
                    <div class="hidden lg:flex items-center gap-0.5">
                        @foreach($adminNavLinks as $link)
                            @php $isActive = request()->is($link['match']); @endphp
                            <a href="{{ $link['url'] }}"
                               class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                                      {{ $isActive
                                        ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                                </svg>
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- ══ RIGHT: Language + User Dropdown + Mobile Toggle ══ --}}
                <div class="flex items-center gap-2">

                    <!-- Language Switcher -->
                    <div class="relative hidden sm:block" x-data="{ langOpen: false }">
                        <button @click="langOpen = !langOpen"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                            <span>{{ strtoupper($currentLang) }}</span>
                            <svg class="w-3 h-3 text-slate-400 transition-transform" :class="langOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="langOpen" @click.away="langOpen = false" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute end-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50 p-2">
                            <div class="grid grid-cols-3 gap-1">
                                @foreach($supportedLangs as $code => $label)
                                    <a href="/change-language/{{ $code }}"
                                       class="flex items-center justify-center py-2 text-xs font-semibold rounded-lg transition-all
                                              {{ $currentLang === $code
                                                ? 'bg-indigo-600 text-white shadow-sm'
                                                : 'text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-700 dark:hover:text-indigo-300' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm hover:bg-slate-100 dark:hover:bg-slate-700 transition group">
                            @if(auth()->user()->avatar ?? false)
                                <img src="{{ auth()->user()->avatar_url }}" alt="Avatar"
                                     class="w-8 h-8 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600">
                            @else
                                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-sm">
                                    <span class="text-white font-bold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                </div>
                            @endif
                            <span class="text-slate-700 dark:text-slate-200 font-medium hidden md:block">{{ auth()->user()->name }}</span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown Panel -->
                        <div x-show="open" @click.away="open = false" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute end-0 mt-2 w-72 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50">

                            {{-- Profile header --}}
                            <div class="relative px-4 py-4 overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-500 to-purple-600"></div>
                                <div class="relative flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center flex-shrink-0 shadow-inner">
                                        @if(auth()->user()->avatar ?? false)
                                            <img src="{{ auth()->user()->avatar_url }}" class="w-full h-full rounded-xl object-cover">
                                        @else
                                            <span class="text-white font-black text-lg">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-white leading-tight truncate">{{ auth()->user()->name }}</p>
                                        <p class="text-xs text-indigo-200 mt-0.5 truncate">{{ auth()->user()->email }}</p>
                                    </div>
                                    <span class="ms-auto flex-shrink-0 text-[10px] font-bold bg-white/20 text-white px-2 py-0.5 rounded-full border border-white/30">
                                        {{ auth()->user()->isAdminTenant() ? 'Admin' : 'Staff' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Profile link --}}
                            <div class="px-3 pt-3 pb-1">
                                <a href="/admin/profile"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-400 transition-all group">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/50 transition-colors flex-shrink-0">
                                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    {{ __('My Profile') }}
                                    <svg class="w-3.5 h-3.5 ms-auto text-slate-300 dark:text-slate-600 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $isRtl ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7' }}"/>
                                    </svg>
                                </a>
                            </div>

                            {{-- Dark Mode toggle --}}
                            <div class="mx-3 my-1 rounded-xl border border-slate-100 dark:border-slate-700">
                                <div class="flex items-center justify-between px-3 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-amber-500 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                            </svg>
                                            <svg class="w-4 h-4 text-indigo-400 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Dark Mode') }}</span>
                                    </div>
                                    <button @click.stop="window.toggleAdminDark()"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none bg-slate-200 dark:bg-indigo-600">
                                        <span class="inline-block h-4 w-4 rounded-full bg-white shadow-sm ring-0 transition-transform duration-200 translate-x-0.5 dark:translate-x-[21px]"></span>
                                    </button>
                                </div>
                            </div>

                            {{-- Logout --}}
                            <div class="px-3 pt-1 pb-3">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all group">
                                        <div class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center flex-shrink-0 group-hover:bg-red-100 dark:group-hover:bg-red-900/40 transition-colors">
                                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                        </div>
                                        {{ __('Logout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Hamburger -->
                    <button @click="mobileOpen = !mobileOpen"
                            class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 dark:text-slate-400 transition">
                        <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileOpen" @click.away="mobileOpen = false" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="lg:hidden border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 pb-4">
            <div class="px-4 pt-3 space-y-1">
                @foreach($adminNavLinks as $link)
                    @php $isActive = request()->is($link['match']); @endphp
                    <a href="{{ $link['url'] }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                              {{ $isActive
                                ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                        </svg>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </nav>
    {{-- ════════════════════════════════════════════════════════════════════════ --}}

    {{-- ── Subscription / Trial Banner ──────────────────────────────────── --}}
    @if(isset($subscriptionBanner))
    @php
        $banner = $subscriptionBanner;
        $isUrgent = $banner['status'] === 'trial' && ($banner['days_left'] ?? 99) <= 3;
        $isGrace  = $banner['status'] === 'grace';
        $bgClass  = match($banner['type'] ?? 'info') {
            'warning' => 'bg-amber-500',
            'danger'  => 'bg-red-600',
            default   => 'bg-indigo-600',
        };
    @endphp
    <div class="{{ $bgClass }} text-white">
        <div class="max-w-7xl mx-auto px-4 py-2 flex flex-wrap items-center justify-between gap-3">

            {{-- Left: message + days pill --}}
            <div class="flex items-center gap-3">
                @if($banner['status'] === 'trial')
                    <span class="bg-white/25 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                        {{ $banner['days_left'] ?? 0 }} {{ __('يوم') }}
                    </span>
                @endif
                <span class="text-sm font-medium">{{ $banner['message'] }}</span>
            </div>

            {{-- Right: action buttons --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                @if(isset($banner['upgrade_url']) && auth()->user()?->isAdminTenant())
                    <a href="{{ $banner['upgrade_url'] }}"
                       class="bg-white text-{{ $isUrgent || $isGrace ? 'red' : 'indigo' }}-600 text-xs font-bold px-4 py-1.5 rounded-full hover:bg-white/90 transition-colors shadow-sm">
                        {{ __('ترقية الآن') }} →
                    </a>
                @endif

                {{-- 7-day extension offer (Day 12, one-time, trial only) --}}
                @if($banner['status'] === 'trial' && ($banner['days_left'] ?? 99) <= 2 && !($banner['trial_extended'] ?? false) && auth()->user()?->isAdminTenant())
                    <form method="POST" action="/billing/extend-trial" class="inline">
                        @csrf
                        <button type="submit"
                                class="bg-white/20 border border-white/40 text-white text-xs font-semibold px-3 py-1.5 rounded-full hover:bg-white/30 transition-colors"
                                onclick="return confirm('{{ __('تمديد 7 أيام إضافية مجانية؟') }}')">
                            {{ __('تمديد 7 أيام') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ── System Notifications from Super Admin ────────────────────────── --}}
    @if(!empty($systemNotifications) && $systemNotifications->isNotEmpty())
    @php
        $notifColors = [
            'info'    => 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200',
            'success' => 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200',
            'warning' => 'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200',
            'danger'  => 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200',
        ];
    @endphp
    @foreach($systemNotifications->take(2) as $sysNotif)
    <div class="border-b {{ $notifColors[$sysNotif->type] ?? $notifColors['info'] }} px-4 py-2.5"
         id="sysnotif-{{ $sysNotif->id }}">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wide opacity-75">{{ $sysNotif->title }}</span>
                <span class="text-sm ml-2">{{ $sysNotif->message }}</span>
            </div>
            <button onclick="document.getElementById('sysnotif-{{ $sysNotif->id }}').remove()"
                    class="flex-shrink-0 text-lg leading-none opacity-60 hover:opacity-100">&times;</button>
        </div>
    </div>
    @endforeach
    @endif

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 admin-page-enter">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">@yield('title')</h1>
                @hasSection('subtitle')
                <p class="text-slate-600 dark:text-slate-400 mt-1">@yield('subtitle')</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @yield('header-actions')
            </div>
        </div>

        <!-- Flash Alerts -->
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Back to Top -->
    <button id="admin-back-to-top"
            onclick="window.scrollTo({top:0,behavior:'smooth'})"
            class="back-top-ring fixed bottom-6 end-6 w-12 h-12 bg-gradient-to-br from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-full shadow-xl flex items-center justify-center z-40 opacity-0 translate-y-4 pointer-events-none transition-all duration-300">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>

    <!-- Toast Container -->
    <div id="admin-toast-container" class="fixed bottom-4 start-4 z-50 space-y-2 pointer-events-none"></div>

    <script>
        // ── Scroll Progress ──────────────────────────────────────────────
        window.addEventListener('scroll', () => {
            const el = document.getElementById('admin-scroll-progress');
            if (!el) return;
            const pct = window.scrollY / (document.documentElement.scrollHeight - window.innerHeight);
            el.style.transform = `scaleX(${Math.min(pct, 1)})`;
        }, { passive: true });

        // ── Back to Top ──────────────────────────────────────────────────
        window.addEventListener('scroll', () => {
            const btn = document.getElementById('admin-back-to-top');
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

        // ── Dark Mode Toggle ─────────────────────────────────────────────
        function toggleAdminDark() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('adminDarkMode', isDark);
        }
        // Legacy alias used by some pages
        function toggleDarkMode() { toggleAdminDark(); }
        function changeLanguage(lang) { window.location.href = '/change-language/' + lang; }

        // ── Toast ────────────────────────────────────────────────────────
        function showToast(message, type = 'success', duration = 3500) {
            const icons = {
                success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                error:   '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
                info:    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
            };
            const colors = { success:'bg-emerald-500', error:'bg-red-500', info:'bg-blue-500', warning:'bg-amber-500' };
            const toast = document.createElement('div');
            toast.className = `toast-enter pointer-events-auto flex items-center gap-3 px-5 py-3.5 rounded-xl shadow-2xl text-white font-medium text-sm ${colors[type] || colors.success}`;
            toast.innerHTML = `
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icons[type]||icons.success}</svg>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ms-auto opacity-70 hover:opacity-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>`;
            document.getElementById('admin-toast-container').appendChild(toast);
            setTimeout(() => {
                toast.classList.replace('toast-enter','toast-exit');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // ── Counter Animation ────────────────────────────────────────────
        function animateCounter(el, target, duration = 800) {
            const startTime = performance.now();
            (function update(now) {
                const progress = Math.min((now - startTime) / duration, 1);
                const ease = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.round(target * ease);
                if (progress < 1) requestAnimationFrame(update);
            })(startTime);
        }
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-counter]').forEach(el => {
                animateCounter(el, parseInt(el.dataset.counter, 10) || 0);
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
