@extends('layouts.admin')

@section('title', __('Dashboard'))
@section('subtitle', __('Welcome back! Here\'s what\'s happening today.'))

@section('content')
    <!-- Subscription Info - Compact Banner -->
    @if($subscriptionInfo)
    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-xl shadow-sm p-4 mb-6 text-white">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="px-3 py-1 bg-white/20 rounded-lg backdrop-blur-sm font-semibold text-sm">
                    {{ ucfirst($subscriptionInfo['status']) }}
                </div>
                <span class="text-sm font-medium">{{ $subscriptionInfo['plan_name'] }} Plan</span>
                <span class="text-sm opacity-90">• {{ $subscriptionInfo['days_remaining'] }} {{ __('days left') }}</span>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span title="{{ __('Users') }}">👥 {{ $subscriptionInfo['limits']['users']['current'] }}/{{ $subscriptionInfo['limits']['users']['max'] }}</span>
                <span title="{{ __('Appointments This Month') }}">📅 {{ $subscriptionInfo['limits']['appointments']['current'] }}/{{ $subscriptionInfo['limits']['appointments']['max'] }}</span>
                <a href="/admin/subscription" class="px-4 py-2 bg-white text-indigo-600 rounded-lg font-medium hover:bg-white/90 transition-colors">
                    {{ __('Upgrade') }}
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Essential Stats - 4 Cards Only -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Total Appointments') }}</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-slate-100 mt-1">{{ $stats['total_appointments'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                @php
                    $change = $stats['appointments_change'] ?? 0;
                    $isPositive = $change >= 0;
                @endphp
                <span class="font-medium {{ $isPositive ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $isPositive ? '+' : '' }}{{ $change }}%
                </span>
                <span class="text-slate-500 dark:text-slate-400 {{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }}">{{ __('from last week') }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Confirmed') }}</p>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['confirmed'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ $stats['confirmed'] ?? 0 }}</span>
                <span class="text-slate-500 dark:text-slate-400 {{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }}">{{ __('ready for today') }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('In Queue') }}</p>
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ $stats['queue'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-amber-600 dark:text-amber-400 font-medium">{{ $stats['queue'] ?? 0 }}</span>
                <span class="text-slate-500 dark:text-slate-400 {{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }}">{{ __('waiting now') }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Customers') }}</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $stats['customers'] ?? 0 }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-sm">
                <span class="text-purple-600 dark:text-purple-400 font-medium">+{{ $stats['new_customers_this_week'] ?? 0 }}</span>
                <span class="text-slate-500 dark:text-slate-400 {{ app()->getLocale() === 'ar' ? 'mr-2' : 'ml-2' }}">{{ __('new this week') }}</span>
            </div>
        </div>
    </div>

    <!--Today's Focus Widget -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-800 p-6 mb-8">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    🎯 {{ __('Today\'s Focus') }}
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ __('Quick overview of what needs your attention') }}</p>
            </div>
            <span class="text-2xl">{{ app()->getLocale() === 'ar' ? \Carbon\Carbon::now()->locale('ar')->isoFormat('dddd، D MMMM') : date('l, M d') }}</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-emerald-200 dark:border-emerald-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $stats['confirmed'] ?? 0 }}</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('Confirmed today') }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $stats['queue'] ?? 0 }}</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('Waiting in queue') }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $stats['total_staff'] ?? 0 }}</p>
                        <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('Active staff') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions - 3 Primary Buttons -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <a href="/admin/appointments/create" class="group relative bg-gradient-to-br from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 rounded-xl shadow-lg hover:shadow-xl transition-all p-6 text-white overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <span class="text-sm opacity-80">→</span>
                </div>
                <h3 class="text-xl font-bold mb-1">{{ __('New Appointment') }}</h3>
                <p class="text-sm opacity-90">{{ __('Book a new appointment') }}</p>
            </div>
        </a>

        <a href="/admin/queue" class="group relative bg-gradient-to-br from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 rounded-xl shadow-lg hover:shadow-xl transition-all p-6 text-white overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="text-sm opacity-80">→</span>
                </div>
                <h3 class="text-xl font-bold mb-1">{{ __('Manage Queue') }}</h3>
                <p class="text-sm opacity-90">{{ __('View current waiting list') }}</p>
            </div>
        </a>

        <a href="/admin/reports" class="group relative bg-gradient-to-br from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 rounded-xl shadow-lg hover:shadow-xl transition-all p-6 text-white overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <span class="text-sm opacity-80">→</span>
                </div>
                <h3 class="text-xl font-bold mb-1">{{ __('View Reports') }}</h3>
                <p class="text-sm opacity-90">{{ __('Analytics & insights') }}</p>
            </div>
        </a>
    </div>

    <!-- Analytics Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 mb-8">
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="flex -mb-px">
                <button onclick="showTab('overview')" id="tab-overview" class="tab-button active px-6 py-4 text-sm font-medium border-b-2 border-indigo-600 text-indigo-600 dark:text-indigo-400">
                    📊 {{ __('Overview') }}
                </button>
                <button onclick="showTab('distribution')" id="tab-distribution" class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300">
                    📈 {{ __('Distribution') }}
                </button>
                <button onclick="showTab('team')" id="tab-team" class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300">
                    👥 {{ __('Team Stats') }}
                </button>
            </nav>
        </div>
        <div class="p-6">
            <!-- Overview Tab -->
            <div id="content-overview" class="tab-content">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Appointments Trend') }} ({{ __('Last 7 Days') }})</h3>
                <div class="h-64">
                    <canvas id="appointmentsChart"></canvas>
                </div>
            </div>

            <!-- Distribution Tab -->
            <div id="content-distribution" class="tab-content hidden">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Appointment Status Distribution') }}</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Team Tab -->
            <div id="content-team" class="tab-content hidden">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Staff Performance') }} ({{ __('This Month') }})</h3>
                <div class="space-y-4">
                    @if(count($staffPerformance) > 0)
                        @foreach($staffPerformance as $staff)
                        <div class="flex items-center gap-3">
                            @if($staff['avatar'])
                                <img src="{{ $staff['avatar'] }}" alt="{{ $staff['name'] }}" class="w-10 h-10 rounded-full object-cover">
                            @else
                                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-purple-600 dark:text-purple-400 font-bold">{{ substr($staff['name'], 0, 1) }}</span>
                                </div>
                            @endif
                            <div class="flex-1">
                                <p class="font-medium text-slate-900 dark:text-slate-100">{{ $staff['name'] }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="flex-1 bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                        <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ $staff['rate'] }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-600 dark:text-slate-400">{{ $staff['completed'] }}/{{ $staff['total'] }} ({{ $staff['rate'] }}%)</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-center text-slate-500 dark:text-slate-400 py-8">{{ __('No staff data') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Quick Links -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="/admin/staff" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-100 dark:border-slate-700 p-4 hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-600 transition-all text-center">
            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('Staff') }}</p>
        </a>

        <a href="/admin/settings" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-100 dark:border-slate-700 p-4 hover:shadow-md hover:border-slate-300 dark:hover:border-slate-600 transition-all text-center">
            <div class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('Settings') }}</p>
        </a>

        <a href="/admin/subscription" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-100 dark:border-slate-700 p-4 hover:shadow-md hover:border-purple-200 dark:hover:border-purple-600 transition-all text-center">
            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('Subscription') }}</p>
        </a>

        @if(auth()->user()->isAdminTenant())
        <a href="/admin/assistants" class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-100 dark:border-slate-700 p-4 hover:shadow-md hover:border-teal-200 dark:hover:border-teal-600 transition-all text-center">
            <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900 rounded-lg flex items-center justify-center mx-auto mb-2">
                <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ __('Assistants') }}</p>
        </a>
        @endif
    </div>

    <!-- Today's Schedule & Top Services -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Top Services -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Top Services') }}</h3>
            </div>
            <div class="p-5">
                @if(count($topServices) > 0)
                    @php $maxTotal = $topServices->first()->total ?? 1; @endphp
                    <div class="space-y-4">
                        @foreach($topServices as $index => $service)
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">#{{ $index + 1 }}</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-slate-900 dark:text-slate-100">{{ $service->name }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="flex-1 bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ ($service->total / $maxTotal) * 100 }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ $service->total }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-slate-500 dark:text-slate-400 py-8">{{ __('No services data') }}</p>
                @endif
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Recent Activities') }}</h3>
            </div>
            <div class="p-5 max-h-96 overflow-y-auto">
                @if(count($recentActivities) > 0)
                    <div class="space-y-3">
                        @foreach($recentActivities as $activity)
                        <div class="flex items-start gap-3 text-sm">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{
                                $activity['type'] === 'completed' ? 'bg-emerald-100 dark:bg-emerald-900' :
                                ($activity['type'] === 'confirmed' ? 'bg-blue-100 dark:bg-blue-900' :
                                ($activity['type'] === 'cancelled' ? 'bg-red-100 dark:bg-red-900' : 'bg-slate-100 dark:bg-slate-700'))
                            }}">
                                @if($activity['type'] === 'completed')
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @elseif($activity['type'] === 'confirmed')
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                @elseif($activity['type'] === 'cancelled')
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-slate-900 dark:text-slate-100 font-medium">{{ $activity['description'] }}</p>
                                <p class="text-slate-500 dark:text-slate-400 text-xs">{{ $activity['customer'] }} • {{ $activity['time'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-center text-slate-500 dark:text-slate-400 py-8">{{ __('No recent activities') }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Customers -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 mb-8">
        <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Recent Customers') }}</h3>
            <a href="/admin/customers" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('View all') }} →</a>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-700">
            @if(isset($recentCustomers) && count($recentCustomers) > 0)
                @foreach($recentCustomers as $customer)
                <div class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors">
                    @if($customer['avatar'])
                        <img src="{{ $customer['avatar'] }}" alt="{{ $customer['name'] }}" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                    @else
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-purple-600 dark:text-purple-400 font-bold">{{ substr($customer['name'], 0, 1) }}</span>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-900 dark:text-slate-100 truncate">{{ $customer['name'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ $customer['email'] }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $customer['appointments_count'] }} {{ __('appts') }}</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">{{ $customer['joined'] }}</p>
                    </div>
                </div>
                @endforeach
            @else
                <p class="text-center text-slate-500 dark:text-slate-400 py-8">{{ __('No customers yet') }}</p>
            @endif
        </div>
    </div>

    <!-- Today's Appointments & Queue -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Today\'s Appointments') }}</h3>
                <a href="/admin/appointments" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('View all') }} →</a>
            </div>
            <div class="p-5 max-h-96 overflow-y-auto">
                @if(isset($todayAppointments) && count($todayAppointments) > 0)
                    <div class="space-y-3">
                        @foreach($todayAppointments->take(5) as $appointment)
                        <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                                <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ substr($appointment->customer?->name ?? '?', 0, 1) }}</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-slate-900 dark:text-slate-100">{{ $appointment->customer?->name ?? __('Unknown') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $appointment->service_name ?? $appointment->service_type ?? 'N/A' }}</p>
                            </div>
                            <span class="text-sm text-slate-600 dark:text-slate-400">{{ $appointment->time_slot ?? 'N/A' }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-slate-500 dark:text-slate-400">{{ __('No appointments for today') }}</p>
                        <a href="/admin/appointments" class="text-indigo-600 dark:text-indigo-400 text-sm hover:underline mt-2 inline-block">{{ __('Create new appointment') }}</a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Current Queue -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ __('Current Queue') }}</h3>
                <a href="/admin/queue" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">{{ __('Manage') }} →</a>
            </div>
            <div class="p-5 max-h-96 overflow-y-auto">
                @if(isset($currentQueue) && count($currentQueue) > 0)
                    <div class="space-y-3">
                        @foreach($currentQueue->take(5) as $queue)
                        <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-700 rounded-lg">
                            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900 rounded-full flex items-center justify-center">
                                <span class="text-amber-600 dark:text-amber-400 font-bold">#{{ $queue->queue_number ?? 0 }}</span>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-slate-900 dark:text-slate-100">{{ optional($queue->appointment)->customer?->name ?? $queue->customer_name ?? __('Walk-in') }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ ucfirst($queue->status ?? 'waiting') }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-slate-500 dark:text-slate-400">{{ __('No one in queue') }}</p>
                        <a href="/admin/queue" class="text-indigo-600 dark:text-indigo-400 text-sm hover:underline mt-2 inline-block">{{ __('Add to queue') }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Tab Switching
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });

    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-indigo-600', 'text-indigo-600', 'dark:text-indigo-400');
        button.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
    });

    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');

    // Add active class to clicked button
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.add('active', 'border-indigo-600', 'text-indigo-600', 'dark:text-indigo-400');
    activeButton.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
}

document.addEventListener('DOMContentLoaded', function() {
    // Appointments Trend Chart
    const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
    new Chart(appointmentsCtx, {
        type: 'line',
        data: {
            labels: @json($chartData['labels']),
            datasets: [{
                label: '{{ __("Appointments") }}',
                data: @json($chartData['appointments']),
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['{{ __("Pending") }}', '{{ __("Confirmed") }}', '{{ __("Completed") }}', '{{ __("Cancelled") }}'],
            datasets: [{
                data: [
                    @json($statusDistribution['pending']),
                    @json($statusDistribution['confirmed']),
                    @json($statusDistribution['completed']),
                    @json($statusDistribution['cancelled'])
                ],
                backgroundColor: [
                    'rgb(251, 191, 36)',
                    'rgb(59, 130, 246)',
                    'rgb(16, 185, 129)',
                    'rgb(239, 68, 68)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>
@endpush
