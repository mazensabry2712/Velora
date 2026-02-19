<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $businessSettings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
        $businessName = $businessSettings->business_name ?? tenant()->name ?? config('app.name');
        $businessLogo = $businessSettings->logo ?? null;
    @endphp
    <title>@yield('title', __('Admin')) - {{ $businessName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>

    <!-- Dark Mode Prevention Script - Admin Dashboard Only -->
    <script>
        // يتم تنفيذ هذا الكود فوراً قبل عرض الصفحة
        (function() {
            // Clear ALL old dark mode keys completely
            const oldKeys = ['darkMode', 'dark-mode', 'dark_mode'];
            oldKeys.forEach(key => {
                if (localStorage.getItem(key) !== null) {
                    console.log('🧹 Removed old key:', key, '=', localStorage.getItem(key));
                    localStorage.removeItem(key);
                }
            });

            const savedMode = localStorage.getItem('adminDarkMode');
            console.log('🌓 Admin Dark Mode - savedMode:', savedMode, '(type:', typeof savedMode, ')');

            if (savedMode === 'true') {
                document.documentElement.classList.add('dark');
                console.log('✅ Dark mode enabled');
            } else if (savedMode === 'false') {
                document.documentElement.classList.remove('dark');
                console.log('☀️ Light mode enabled (explicitly set)');
            } else if (savedMode === null) {
                // No preference saved, check system preference
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.classList.add('dark');
                    console.log('🌙 System preference: dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    console.log('☀️ System preference: light');
                }
            }
        })();
    </script>

    @stack('styles')
</head>
<body class="bg-slate-50 dark:bg-slate-900 min-h-screen">
    <!-- Top Navigation Bar -->
    <nav class="bg-white dark:bg-slate-800 shadow-sm border-b dark:border-slate-700 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14">
                <!-- Logo & Navigation Links -->
                <div class="flex items-center gap-1">
                    <a href="/admin/dashboard" class="flex items-center gap-2 text-xl font-bold text-indigo-600 dark:text-indigo-400 {{ app()->getLocale() === 'ar' ? 'ml-6' : 'mr-6' }}">
                        @if($businessLogo)
                            <img src="{{ asset('storage/' . $businessLogo) }}" alt="{{ $businessName }}" class="h-8 w-auto">
                        @endif
                        <span>{{ $businessName }}</span>
                    </a>

                    <div class="hidden md:flex items-center">
                        <a href="/admin/appointments" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/appointments*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700/50' }}">
                            {{ __('Appointments') }}
                        </a>
                        <a href="/admin/queue" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/queue*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700/50' }}">
                            {{ __('Queue') }}
                        </a>
                        <a href="/admin/staff" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/staff*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700/50' }}">
                            {{ __('Staff') }}
                        </a>
                        <a href="/admin/reports" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/reports*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700/50' }}">
                            {{ __('Reports') }}
                        </a>
                        <a href="/admin/settings" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/settings*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700/50' }}">
                            {{ __('Settings') }}
                        </a>
                        @if(auth()->user()->isAdminTenant())
                        <a href="{{ route('admin.subscription.index') }}" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/subscription*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700/50' }}">
                            {{ __('Subscription') }}
                        </a>
                        <a href="/admin/assistants" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/assistants*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700/50' }}">
                            {{ __('Assistants') }}
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Right Side: Language + Dark Mode + Profile + Logout -->
                <div class="flex items-center gap-3">
                    <!-- Dark Mode Toggle -->
                    <button onclick="toggleDarkMode()"
                        class="p-2 rounded-lg bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-200 dark:hover:bg-slate-600 transition-all shadow-sm"
                        title="{{ __('Toggle Dark Mode') }}">
                        <span id="dark-mode-icon" class="text-xl">🌙</span>
                    </button>

                    <!-- Language Switcher -->
                    <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-0.5">
                        <button onclick="changeLanguage('en')"
                            class="px-2.5 py-1 text-xs font-medium rounded-md transition-all {{ app()->getLocale() === 'en' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100' }}">
                            EN
                        </button>
                        <button onclick="changeLanguage('ar')"
                            class="px-2.5 py-1 text-xs font-medium rounded-md transition-all {{ app()->getLocale() === 'ar' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100' }}">
                            عربي
                        </button>
                    </div>

                    <!-- Profile -->
                    <a href="/admin/profile" class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="w-8 h-8 rounded-full object-cover border-2 border-slate-200 dark:border-slate-700">
                        @else
                            <div class="w-8 h-8 rounded-full bg-indigo-600 dark:bg-indigo-500 flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <span class="hidden sm:inline text-sm font-medium text-slate-700 dark:text-slate-300">{{ auth()->user()->name }}</span>
                    </a>

                    <!-- Logout -->
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">
                            {{ __('Logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="md:hidden border-t dark:border-slate-700 overflow-x-auto">
            <div class="flex px-4 py-2 gap-1">
                <a href="/admin/appointments" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/appointments*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300' }}">
                    {{ __('Appointments') }}
                </a>
                <a href="/admin/queue" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/queue*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300' }}">
                    {{ __('Queue') }}
                </a>
                <a href="/admin/staff" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/staff*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300' }}">
                    {{ __('Staff') }}
                </a>
                <a href="/admin/reports" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/reports*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300' }}">
                    {{ __('Reports') }}
                </a>
                <a href="/admin/settings" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/settings*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300' }}">
                    {{ __('Settings') }}
                </a>
                @if(auth()->user()->isAdminTenant())
                <a href="{{ route('admin.subscription.index') }}" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/subscription*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300' }}">
                    {{ __('Subscription') }}
                </a>
                <a href="/admin/assistants" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/assistants*') ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-300' }}">
                    {{ __('Assistants') }}
                </a>
                @endif
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
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

        <!-- Alerts -->
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-lg p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Page Content -->
        @yield('content')
    </main>

    <script>
        function changeLanguage(lang) {
            window.location.href = '/change-language/' + lang;
        }
    </script>
    <script src="/js/dark-mode.js?v={{ time() }}"></script>
    @stack('scripts')
</body>
</html>
