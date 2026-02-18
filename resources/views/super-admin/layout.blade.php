<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900">

    <!-- Navigation -->
    <nav class="bg-white dark:bg-slate-800 shadow-lg border-b border-slate-200 dark:border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-slate-900 dark:text-white">Super Admin</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:mr-6 space-x-8 space-x-reverse">
                    <a href="{{ route('super-admin.dashboard') }}"
                       class="text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('super-admin.dashboard') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400' : '' }}">
                        لوحة التحكم
                    </a>
                    <a href="{{ route('super-admin.tenants') }}"
                       class="text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('super-admin.tenants') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400' : '' }}">
                        الشركات
                    </a>
                    <a href="{{ route('super-admin.subscription-plans') }}"
                       class="text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('super-admin.subscription-plans') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400' : '' }}">
                        الاشتراكات
                    </a>
                    <a href="{{ route('super-admin.activity-logs') }}"
                       class="text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('super-admin.activity-logs') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400' : '' }}">
                        السجلات
                    </a>
                    <a href="{{ route('super-admin.notifications') }}"
                       class="text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('super-admin.notifications') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400' : '' }}">
                        الإشعارات
                    </a>
                    <a href="{{ route('super-admin.reports') }}"
                       class="text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('super-admin.reports') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400' : '' }}">
                        التقارير
                    </a>
                    <a href="{{ route('super-admin.settings') }}"
                       class="text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('super-admin.settings') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400' : '' }}">
                        الإعدادات
                    </a>
                </div>

                <!-- User Menu -->
                <div class="flex items-center" x-data="{ open: false }">
                    <div class="relative">
                        <button @click="open = !open" class="flex items-center space-x-3 space-x-reverse text-sm focus:outline-none">
                            <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                            <span class="text-slate-900 dark:text-white font-medium hidden md:block">{{ auth()->user()->name }}</span>
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="open"
                             @click.away="open = false"
                             x-transition
                             class="absolute left-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-slate-200 dark:border-slate-700 py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-200 dark:border-slate-700">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('super-admin.logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-right px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                    تسجيل الخروج
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed top-4 left-4 z-50 space-y-2"></div>

    <!-- Scripts -->
    @stack('scripts')

    <script>
        // Toast notification function
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `flex items-center p-4 rounded-lg shadow-lg transform transition-all duration-300 ${
                type === 'success' ? 'bg-emerald-500' :
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            } text-white`;
            toast.innerHTML = `
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>${message}</span>
            `;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
