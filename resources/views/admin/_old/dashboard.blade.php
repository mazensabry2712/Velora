<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Dashboard') }} - {{ tenant()->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-900">{{ tenant()->name }}</h1>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Language Switcher -->
                    <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5">
                        <button onclick="changeLanguage('en')"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-all duration-200 {{ app()->getLocale() === 'en' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                            EN
                        </button>
                        <button onclick="changeLanguage('ar')"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-all duration-200 {{ app()->getLocale() === 'ar' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                            عربي
                        </button>
                    </div>
                    <!-- Profile Link -->
                    <a href="/admin/profile" class="flex items-center gap-2 text-gray-700 hover:text-gray-900">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="w-8 h-8 rounded-full object-cover">
                        @else
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-600 font-bold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <span>{{ auth()->user()->name }}</span>
                    </a>
                    <span class="text-sm text-gray-500">({{ auth()->user()->role?->name }})</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800">
                            {{ __('Logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('Dashboard') }}</h2>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg p-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">{{ __('Total Appointments') }}</p>
                        <p class="text-3xl font-bold text-gray-900">0</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">{{ __('Confirmed Appointments') }}</p>
                        <p class="text-3xl font-bold text-green-600">0</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">{{ __('Waiting Queue') }}</p>
                        <p class="text-3xl font-bold text-yellow-600">0</p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">{{ __('Customers') }}</p>
                        <p class="text-3xl font-bold text-purple-600">0</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <a href="/admin/appointments" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Manage Appointments') }}</h3>
                <p class="text-gray-600 text-sm">{{ __('View and manage all appointments') }}</p>
            </a>

            <a href="/admin/queue" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Manage Queue') }}</h3>
                <p class="text-gray-600 text-sm">{{ __('Track and manage the waiting queue') }}</p>
            </a>

            <a href="/admin/staff" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Manage Staff') }}</h3>
                <p class="text-gray-600 text-sm">{{ __('Add and manage staff members') }}</p>
            </a>

            <a href="/admin/reports" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Reports') }}</h3>
                <p class="text-gray-600 text-sm">{{ __('View reports and statistics') }}</p>
            </a>

            <a href="/admin/settings" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Settings') }}</h3>
                <p class="text-gray-600 text-sm">{{ __('Manage services, time slots and working days') }}</p>
            </a>

            @if(auth()->user()->isAdminTenant())
            <a href="/admin/assistants" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('Assistants') }}</h3>
                <p class="text-gray-600 text-sm">{{ __('Manage assistants and their permissions') }}</p>
            </a>
            @endif

        </div>

        <!-- Welcome Message -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">{{ __('Welcome') }} {{ auth()->user()->name }}! 👋</h3>
            <p class="text-blue-800">{{ __('You are logged in as') }} <strong>{{ auth()->user()->role?->name }}</strong></p>
            <p class="text-blue-700 text-sm mt-2">{{ __('You can now manage the system through the menus above.') }}</p>
        </div>
    </main>

    <script>
        function changeLanguage(lang) {
            window.location.href = '/change-language/' + lang;
        }
    </script>
</body>
</html>
