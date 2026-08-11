<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'he', 'fa']);

    $branding = \App\Support\TenantBranding::resolve();
    $businessName = $branding['name'];
    $businessLogo = $branding['logo'];
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageTitle = $__env->yieldContent('title');
        if (is_array($pageTitle) || is_object($pageTitle)) {
            $pageTitle = '';
        }
        $pageTitle = (string) ($pageTitle ?: 'Admin');
    @endphp
    <title>{{ $pageTitle }} - {{ $businessName }}</title>

    @include('layouts.partials.admin.dark-mode-init')

    <script>
        // Must run BEFORE the Tailwind CDN script below. Tailwind's Play CDN
        // compiles its stylesheet the instant it loads, using whatever
        // window.tailwind.config it finds at that moment. Setting the config
        // in a script tag AFTER the CDN <script src> (as this used to do) is
        // too late — Tailwind had already locked in darkMode:'media' (its
        // default) by then, so dark: utilities followed the OS color-scheme
        // preference instead of the .dark class, and the in-app toggle could
        // turn dark mode on (if it happened to match the OS setting) but
        // could never turn it back off.
        window.tailwind = window.tailwind || {};
        window.tailwind.config = {
            darkMode: 'class'
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800;900&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <link rel="stylesheet" href="{{ asset('css/admin-layout.css') }}">

    @stack('styles')
</head>

<body class="bg-slate-50 dark:bg-slate-900 min-h-screen antialiased">

    <!-- Scroll Progress Bar -->
    <div id="admin-scroll-progress"></div>

    @include('layouts.partials.admin.navbar', [
        'isRtl' => $isRtl,
        'businessName' => $businessName,
        'businessLogo' => $businessLogo,
    ])

    @include('layouts.partials.admin.subscription-banner')

    @include('layouts.partials.admin.system-notifications')

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
        @if (session('success'))
            <div
                class="mb-6 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-xl p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Back to Top -->
    <button id="admin-back-to-top" onclick="window.scrollTo({top:0,behavior:'smooth'})"
        class="back-top-ring fixed bottom-6 end-6 w-12 h-12 bg-gradient-to-br from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-full shadow-xl flex items-center justify-center z-40 opacity-0 translate-y-4 pointer-events-none transition-all duration-300">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
    </button>

    <!-- Toast Container -->
    <div id="admin-toast-container" class="fixed bottom-4 start-4 z-50 space-y-2 pointer-events-none"></div>

    <script src="{{ asset('js/admin-layout.js') }}"></script>
    @stack('scripts')
</body>

</html>
