@extends('super-admin.layout')

@section('title', __('super-admin.dashboard_title'))
@section('breadcrumb')<span class="text-slate-700 dark:text-slate-200 font-medium">{{ __('super-admin.nav_dashboard') }}</span>@endsection

@section('content')
<div x-data="dashboard()" x-init="init()">

    <!-- Header with Actions -->
    <div class="mb-8 flex flex-wrap gap-4 justify-between items-start">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                {{ __('super-admin.dashboard_h1') }}
                <!-- Live indicator -->
                <span class="flex items-center gap-1 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    {{ __('super-admin.dashboard_live') }}
                </span>
            </h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">
                {{ __('super-admin.dashboard_last_updated') }} <span x-text="lastRefreshed" class="font-medium text-slate-700 dark:text-slate-300">{{ __('super-admin.js_just_now') }}</span>
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-2">

            <!-- Refresh Button -->
            <div class="tooltip-wrapper">
                <button @click="refreshDashboard()"
                        :class="refreshing && 'opacity-60 cursor-not-allowed'"
                        :disabled="refreshing"
                        class="p-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                    <svg :class="refreshing && 'animate-spin'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
                <span class="tooltip-text">{{ __('super-admin.dashboard_refresh_data') }}</span>
            </div>

            <!-- Search Button -->
            <div class="tooltip-wrapper">
                <button @click="showSearch = !showSearch; $nextTick(() => showSearch && $refs.searchInput.focus())"
                        :class="showSearch && 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400'"
                        class="p-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
                <span class="tooltip-text">{{ __('super-admin.dashboard_search_short') }}</span>
            </div>

            <!-- Notifications -->
            <div class="relative tooltip-wrapper" x-data="{ open: false }">
                <button @click="open = !open"
                        :class="open && 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400'"
                        class="relative p-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span x-show="notifications.length > 0"
                          class="badge-pulse absolute -top-1 -right-1 min-w-[18px] h-[18px] text-xs bg-red-500 text-white rounded-full flex items-center justify-center font-bold px-1"
                          x-text="notifications.length"></span>
                </button>
                <span class="tooltip-text">{{ __('super-admin.notif_header') }}</span>

                <!-- Notifications Dropdown -->
                <div x-show="open" @click.away="open = false" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute left-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 z-50 overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 flex justify-between items-center">
                        <h3 class="font-bold text-slate-900 dark:text-white">{{ __('super-admin.notif_header') }}</h3>
                        <span class="text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-full font-medium" x-text="notifications.length + ' {{ __('super-admin.notif_new_badge') }}'"></span>
                    </div>
                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
                        <template x-for="notif in notifications" :key="notif.id">
                            <div class="flex items-start gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition cursor-pointer">
                                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-slate-900 dark:text-white font-medium leading-tight" x-text="notif.message"></p>
                                    <p class="text-xs text-slate-400 mt-0.5" x-text="notif.time"></p>
                                </div>
                            </div>
                        </template>
                        <div x-show="notifications.length === 0" class="p-8 text-center">
                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="text-sm text-slate-500">{{ __('super-admin.notif_none_new') }}</p>
                        </div>
                    </div>
                    <div class="p-3 border-t border-slate-200 dark:border-slate-700">
                        <a href="{{ route('super-admin.notifications') }}" class="block text-center text-sm text-indigo-600 dark:text-indigo-400 font-medium hover:underline">{{ __('super-admin.notif_view_all') }}</a>
                    </div>
                </div>
            </div>

            <!-- Export CSV -->
            <div class="tooltip-wrapper">
                <button @click="exportData()"
                        class="p-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition shadow-sm hover:shadow-indigo-200 dark:hover:shadow-indigo-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </button>
                <span class="tooltip-text">{{ __('super-admin.dashboard_export_csv') }}</span>
            </div>

        </div>
    </div>

    <!-- Quick Search -->
    <div x-show="showSearch" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="mb-6">
        <div class="relative">
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                x-ref="searchInput"
                type="text"
                x-model="searchQuery"
                @input="filterData()"
                @keydown.escape="showSearch = false; searchQuery = ''; filterData()"
                placeholder="{{ __('super-admin.dashboard_search_ph') }}"
                class="w-full pr-10 pl-4 py-3 border border-slate-300 dark:border-slate-600 rounded-xl dark:bg-slate-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm shadow-sm transition"
            />
            <span x-show="searchQuery" class="absolute left-3 top-1/2 -translate-y-1/2 text-xs bg-slate-100 dark:bg-slate-700 text-slate-500 px-2 py-0.5 rounded">
                <span x-text="filteredTenants.length"></span> {{ __('super-admin.dashboard_results') }}
            </span>
        </div>
    </div>

    <!-- Skeleton Loading State -->
    <div x-show="loading" x-cloak>
        <!-- Skeleton stat cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <template x-for="i in 4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow p-6 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div class="space-y-3 flex-1">
                            <div class="skeleton h-3 w-24 rounded"></div>
                            <div class="skeleton h-8 w-16 rounded"></div>
                        </div>
                        <div class="skeleton w-12 h-12 rounded-xl"></div>
                    </div>
                </div>
            </template>
        </div>
        <!-- Skeleton chart cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <template x-for="i in 3">
                <div class="skeleton rounded-2xl h-40"></div>
            </template>
        </div>
        <div class="flex items-center justify-center py-8 text-slate-400 dark:text-slate-500 gap-3">
            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span class="text-sm">{{ __('super-admin.dashboard_loading') }}</span>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <!-- Total Tenants -->
        <div class="card-animate card-delay-1 group bg-white dark:bg-slate-800 rounded-2xl shadow-sm hover:shadow-lg p-6 border border-slate-200 dark:border-slate-700 transition-all duration-300 hover:-translate-y-1 cursor-default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.stat_total_companies') }}</p>
                    <p class="text-4xl font-black text-slate-900 dark:text-white mt-2 tabular-nums" x-text="stats.total_tenants"></p>
                    <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.stat_all_companies') }}</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-md group-hover:shadow-indigo-200 dark:group-hover:shadow-indigo-900 transition-shadow">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5">
                    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-1.5 rounded-full transition-all duration-1000" :style="`width: 100%`"></div>
                </div>
            </div>
        </div>

        <!-- Active Tenants -->
        <div class="card-animate card-delay-2 group bg-white dark:bg-slate-800 rounded-2xl shadow-sm hover:shadow-lg p-6 border border-slate-200 dark:border-slate-700 transition-all duration-300 hover:-translate-y-1 cursor-default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.stat_active_companies') }}</p>
                    <p class="text-4xl font-black text-emerald-600 dark:text-emerald-400 mt-2 tabular-nums" x-text="filteredStats.active_tenants"></p>
                    <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.stat_using_now') }}</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-green-500 rounded-2xl flex items-center justify-center shadow-md group-hover:shadow-emerald-200 dark:group-hover:shadow-emerald-900 transition-shadow">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5">
                    <div class="bg-gradient-to-r from-emerald-500 to-green-500 h-1.5 rounded-full transition-all duration-1000"
                         :style="`width: ${stats.total_tenants > 0 ? Math.round(filteredStats.active_tenants/stats.total_tenants*100) : 0}%`"></div>
                </div>
                <p class="text-xs text-slate-400 mt-1" x-text="stats.total_tenants > 0 ? Math.round(filteredStats.active_tenants/stats.total_tenants*100) + '{{ __('super-admin.stat_pct_of_total') }}' : ''"></p>
            </div>
        </div>

        <!-- Inactive Tenants -->
        <div class="card-animate card-delay-3 group bg-white dark:bg-slate-800 rounded-2xl shadow-sm hover:shadow-lg p-6 border border-slate-200 dark:border-slate-700 transition-all duration-300 hover:-translate-y-1 cursor-default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.stat_inactive_companies') }}</p>
                    <p class="text-4xl font-black text-amber-600 dark:text-amber-400 mt-2 tabular-nums" x-text="filteredStats.inactive_tenants"></p>
                    <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.stat_needs_followup') }}</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-md group-hover:shadow-amber-200 dark:group-hover:shadow-amber-900 transition-shadow">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5">
                    <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-1.5 rounded-full transition-all duration-1000"
                         :style="`width: ${stats.total_tenants > 0 ? Math.round(filteredStats.inactive_tenants/stats.total_tenants*100) : 0}%`"></div>
                </div>
                <p class="text-xs text-slate-400 mt-1" x-text="stats.total_tenants > 0 ? Math.round(filteredStats.inactive_tenants/stats.total_tenants*100) + '{{ __('super-admin.stat_pct_of_total') }}' : ''"></p>
            </div>
        </div>

        <!-- This Month -->
        <div class="card-animate card-delay-4 group bg-white dark:bg-slate-800 rounded-2xl shadow-sm hover:shadow-lg p-6 border border-slate-200 dark:border-slate-700 transition-all duration-300 hover:-translate-y-1 cursor-default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.stat_this_month') }}</p>
                    <p class="text-4xl font-black text-purple-600 dark:text-purple-400 mt-2 tabular-nums" x-text="stats.tenants_this_month"></p>
                    <p class="text-xs text-slate-400 mt-1">{{ __('super-admin.stat_new_company') }}</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl flex items-center justify-center shadow-md group-hover:shadow-purple-200 dark:group-hover:shadow-purple-900 transition-shadow">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700 flex items-center gap-1">
                <svg class="w-4 h-4 text-purple-500" x-show="stats.tenants_this_month > 0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <p class="text-xs" :class="stats.tenants_this_month > 0 ? 'text-purple-500 font-semibold' : 'text-slate-400'">
                    <span x-show="stats.tenants_this_month > 0">{{ __('super-admin.stat_positive_growth') }}</span>
                    <span x-show="stats.tenants_this_month === 0">{{ __('super-admin.stat_no_new_companies') }}</span>
                </p>
            </div>
        </div>
    </div>

    <!-- Mini Charts Section -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Tenants Growth Chart -->
        <div class="card-animate card-delay-1 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl shadow-lg p-6 text-white transition-all hover:shadow-indigo-200 dark:hover:shadow-indigo-900 hover:-translate-y-1 duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold opacity-90">{{ __('super-admin.mini_tenants_growth') }}</h3>
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <div class="space-y-2">
                <p class="text-3xl font-black" x-text="stats.total_tenants"></p>
                <p class="text-sm opacity-80">{{ __('super-admin.mini_tenants_total') }}</p>
                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-white/20">
                    <span class="text-xs bg-white/20 px-2.5 py-1 rounded-full font-medium">
                        <span x-text="stats.tenants_this_month"></span> {{ __('super-admin.mini_this_month_label') }}
                    </span>
                    <span class="text-xs font-medium" x-show="stats.tenants_this_month > 0">{{ __('super-admin.mini_growth_cont') }}</span>
                </div>
            </div>
        </div>

        <!-- Revenue/Subscriptions -->
        <div class="card-animate card-delay-2 bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-lg p-6 text-white transition-all hover:shadow-emerald-200 dark:hover:shadow-emerald-900 hover:-translate-y-1 duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold opacity-90">{{ __('super-admin.mini_active_subs') }}</h3>
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="space-y-2">
                <p class="text-3xl font-black" x-text="stats.active_tenants"></p>
                <p class="text-sm opacity-80">{{ __('super-admin.mini_using_now') }}</p>
                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-white/20">
                    <div class="flex-1">
                        <div class="bg-white/20 rounded-full h-2">
                            <div class="bg-white rounded-full h-2 transition-all duration-700"
                                 :style="`width: ${stats.total_tenants > 0 ? Math.round(stats.active_tenants / stats.total_tenants * 100) : 0}%`">
                            </div>
                        </div>
                    </div>
                    <span class="text-sm font-bold"
                          x-text="stats.total_tenants > 0 ? Math.round(stats.active_tenants / stats.total_tenants * 100) + '%' : '0%'">
                    </span>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="card-animate card-delay-3 bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl shadow-lg p-6 text-white transition-all hover:shadow-purple-200 dark:hover:shadow-purple-900 hover:-translate-y-1 duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold opacity-90">{{ __('super-admin.mini_system_status') }}</h3>
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></span>
                    <p class="text-2xl font-black">{{ __('super-admin.mini_status_active') }}</p>
                </div>
                <p class="text-sm opacity-80">{{ __('super-admin.mini_services_ok') }}</p>
                <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-white/20">
                    <div class="bg-white/10 rounded-xl p-2 text-center">
                        <div class="text-xs opacity-80">{{ __('super-admin.mini_active_label') }}</div>
                        <div class="font-black text-xl" x-text="stats.paid_tenants"></div>
                    </div>
                    <div class="bg-white/10 rounded-xl p-2 text-center">
                        <div class="text-xs opacity-80">{{ __('super-admin.mini_trial_label') }}</div>
                        <div class="font-black text-xl" x-text="stats.trial_tenants"></div>
                    </div>
                    <a href="{{ route('super-admin.upgrade-requests') }}"
                       :class="stats.pending_upgrade_requests > 0
                           ? 'bg-amber-400/30 hover:bg-amber-400/50 ring-1 ring-amber-300/60'
                           : 'bg-white/10 hover:bg-white/20'"
                       class="rounded-xl p-2 text-center transition-all duration-200 group/upg relative">
                        <div class="text-xs opacity-80">{{ __('super-admin.mini_upgrade_reqs') }}</div>
                        <div class="font-black text-xl flex items-center justify-center gap-1">
                            <span x-text="stats.pending_upgrade_requests"
                                  :class="stats.pending_upgrade_requests > 0 ? 'text-amber-200' : ''"></span>
                            <span x-show="stats.pending_upgrade_requests > 0"
                                  class="w-2.5 h-2.5 bg-amber-400 rounded-full animate-pulse inline-block"></span>
                        </div>
                        <!-- Arrow hint on hover -->
                        <svg class="w-3 h-3 mx-auto mt-0.5 opacity-0 group-hover/upg:opacity-70 transition-opacity -rotate-45"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Cards -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <!-- Add New Tenant -->
        <a href="/super-admin/tenants"
           class="card-animate card-delay-5 group block bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 rounded-2xl shadow-lg p-5 text-white transition-all duration-300 hover:shadow-blue-200 dark:hover:shadow-blue-900 hover:-translate-y-1">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-base">{{ __('super-admin.qa_manage_companies') }}</p>
                    <p class="text-xs opacity-80 mt-0.5">{{ __('super-admin.qa_manage_sub') }}</p>
                </div>
                <svg class="w-5 h-5 mr-auto opacity-60 group-hover:translate-x-1 transition-transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <!-- Settings -->
        <a href="/super-admin/settings"
           class="card-animate card-delay-6 group block bg-gradient-to-br from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 rounded-2xl shadow-lg p-5 text-white transition-all duration-300 hover:shadow-orange-200 dark:hover:shadow-orange-900 hover:-translate-y-1">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 group-hover:rotate-45 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-base">{{ __('super-admin.settings_title') }}</p>
                    <p class="text-xs opacity-80 mt-0.5">{{ __('super-admin.qa_settings_sub') }}</p>
                </div>
                <svg class="w-5 h-5 mr-auto opacity-60 group-hover:translate-x-1 transition-transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>

        <!-- Reports -->
        <a href="/super-admin/reports"
           class="card-animate card-delay-7 group block bg-gradient-to-br from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 rounded-2xl shadow-lg p-5 text-white transition-all duration-300 hover:shadow-teal-200 dark:hover:shadow-teal-900 hover:-translate-y-1">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-base">{{ __('super-admin.reports_title') }}</p>
                    <p class="text-xs opacity-80 mt-0.5">{{ __('super-admin.qa_reports_sub') }}</p>
                </div>
                <svg class="w-5 h-5 mr-auto opacity-60 group-hover:translate-x-1 transition-transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
    </div>

    <!-- Recent Activities & Table Grid -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Recent Activities Log (1/3 width) -->
        <div class="lg:col-span-1 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 flex flex-col">
        <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                {{ __('super-admin.recent_activities') }}
            </h2>
            <a href="{{ route('super-admin.activity-logs') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium flex items-center gap-1">
                {{ __('super-admin.view_all') }}
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
        </div>
        <!-- Today / Week mini stats -->
        <div class="grid grid-cols-2 gap-3 p-4 border-b border-slate-100 dark:border-slate-700">
            <div class="bg-slate-50 dark:bg-slate-700/40 rounded-xl px-3 py-2.5 text-center">
                <p class="text-xl font-black text-slate-900 dark:text-white" x-text="activityTodayCount"></p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ __('super-admin.activity_today') }}</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-700/40 rounded-xl px-3 py-2.5 text-center">
                <p class="text-xl font-black text-slate-900 dark:text-white" x-text="activityWeekCount"></p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ __('super-admin.activity_week') }}</p>
            </div>
        </div>
        <div class="p-3">
            <div class="space-y-0.5 max-h-[500px] overflow-y-auto">
                <template x-for="activity in recentActivities" :key="activity.id">
                    <div class="flex items-start gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors group cursor-default">
                        <!-- Icon -->
                        <div :class="activity.type === 'add'
                                        ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400'
                                        : activity.type === 'edit'
                                            ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400'
                                            : 'bg-red-100 dark:bg-red-900/50 text-red-500 dark:text-red-400'"
                             class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg x-show="activity.type === 'add'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <svg x-show="activity.type === 'edit'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <svg x-show="activity.type === 'delete'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <!-- Text -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-slate-800 dark:text-slate-200 font-medium leading-snug line-clamp-2" x-text="activity.message"></p>
                            <div class="flex items-center gap-1.5 mt-1">
                                <template x-if="activity.user">
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-[9px] font-black text-indigo-700 dark:text-indigo-300 flex-shrink-0"
                                          x-text="activity.user.charAt(0).toUpperCase()"></span>
                                </template>
                                <span x-show="activity.user" class="text-xs font-semibold text-indigo-500 dark:text-indigo-400 truncate max-w-[80px]" x-text="activity.user"></span>
                                <span class="text-slate-300 dark:text-slate-600 text-xs">·</span>
                                <span class="text-xs text-slate-400 dark:text-slate-500 whitespace-nowrap" x-text="activity.time"></span>
                            </div>
                        </div>
                    </div>
                </template>
                <!-- Empty activities state -->
                <div x-show="recentActivities.length === 0" class="flex flex-col items-center justify-center py-12 text-slate-400">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm">{{ __('super-admin.no_activities') }}</p>
                </div>
            </div>
        </div>
        </div>{{-- end lg:col-span-1 activities card --}}

        <!-- Recent Tenants Table (2/3 width) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 justify-between items-center">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                    </svg>
                </div>
                {{ __('super-admin.recent_companies') }}
            </h2>

            <!-- Filter by Status - pill style -->
            <div class="flex gap-2 flex-wrap">
                <button @click="statusFilter = 'all'; filterData()"
                        :class="statusFilter === 'all'
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                        class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all">
                    {{ __('super-admin.dash_filter_all') }}
                    <span :class="statusFilter === 'all' ? 'bg-indigo-500' : 'bg-slate-300 dark:bg-slate-500'"
                          class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold text-white"
                          x-text="stats.total_tenants"></span>
                </button>
                <button @click="statusFilter = 'active'; filterData()"
                        :class="statusFilter === 'active'
                            ? 'bg-emerald-600 text-white shadow-sm'
                            : 'bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                        class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all">
                    {{ __('super-admin.dash_filter_active') }}
                    <span :class="statusFilter === 'active' ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-500'"
                          class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold text-white"
                          x-text="stats.active_tenants"></span>
                </button>
                <button @click="statusFilter = 'inactive'; filterData()"
                        :class="statusFilter === 'inactive'
                            ? 'bg-amber-500 text-white shadow-sm'
                            : 'bg-slate-100 dark:bg-slate-700/60 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                        class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all">
                    {{ __('super-admin.dash_filter_inactive') }}
                    <span :class="statusFilter === 'inactive' ? 'bg-amber-400' : 'bg-slate-300 dark:bg-slate-500'"
                          class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold text-white"
                          x-text="stats.inactive_tenants"></span>
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-900">
                    <tr>
                        <th @click="sortBy('name')" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50 select-none transition">
                            <div class="flex items-center gap-1">
                                {{ __('super-admin.dash_name') }}
                                <svg :class="sortField === 'name' ? 'text-indigo-500' : 'text-slate-300 dark:text-slate-600'" class="w-3.5 h-3.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          :d="sortField === 'name' && sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'" />
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.dash_subdomain') }}</th>
                        <th @click="sortBy('status')" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50 select-none transition">
                            <div class="flex items-center gap-1">
                                {{ __('super-admin.tenant_status') }}
                                <svg :class="sortField === 'status' ? 'text-indigo-500' : 'text-slate-300 dark:text-slate-600'" class="w-3.5 h-3.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          :d="sortField === 'status' && sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'" />
                                </svg>
                            </div>
                        </th>
                        <th @click="sortBy('date')" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700/50 select-none transition">
                            <div class="flex items-center gap-1">
                                {{ __('super-admin.tenant_created_at') }}
                                <svg :class="sortField === 'date' ? 'text-indigo-500' : 'text-slate-300 dark:text-slate-600'" class="w-3.5 h-3.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          :d="sortField === 'date' && sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'" />
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super-admin.tenant_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <!-- Empty State -->
                    <tr x-show="paginatedTenants.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-16 h-16 text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-slate-500 dark:text-slate-400 text-lg mb-2">{{ __('super-admin.dash_empty_title') }}</p>
                                <p class="text-slate-400 dark:text-slate-500 text-sm mb-4">{{ __('super-admin.dash_empty_desc') }}</p>
                                <a href="/super-admin/tenants" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                    {{ __('super-admin.qa_manage_companies') }}
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Table Rows -->
                    <template x-for="tenant in paginatedTenants" :key="tenant.id">
                        <tr class="hover:bg-indigo-50/40 dark:hover:bg-slate-700/50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                                         :style="`background: hsl(${tenant.name.charCodeAt(0) * 137 % 360}, 65%, 55%)`"
                                         x-text="tenant.name.charAt(0).toUpperCase()">
                                    </div>
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white" x-text="tenant.name"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap max-w-[150px]">
                                <code class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 px-2 py-1 rounded block truncate" x-text="tenant.subdomain" :title="tenant.subdomain"></code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="tenant.is_active
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800'
                                    : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 ring-1 ring-amber-200 dark:ring-amber-800'"
                                      class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full">
                                    <span :class="tenant.is_active ? 'bg-emerald-500' : 'bg-amber-500'" class="w-1.5 h-1.5 rounded-full"></span>
                                    <span x-text="tenant.is_active ? '{{ __('super-admin.tenant_active') }}' : '{{ __('super-admin.tenant_inactive') }}'"></span>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400" x-text="formatDate(tenant.created_at)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-1">
                                    <div class="tooltip-wrapper">
                                        <a :href="`{{ route('super-admin.tenants') }}?view=${tenant.id}`"
                                           class="p-1.5 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 dark:text-indigo-400 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <span class="tooltip-text">{{ __('super-admin.dash_tooltip_view') }}</span>
                                    </div>
                                    <div class="tooltip-wrapper">
                                        <a :href="`{{ route('super-admin.tenants') }}?edit=${tenant.id}`"
                                           class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 dark:text-emerald-400 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <span class="tooltip-text">{{ __('super-admin.dash_tooltip_edit') }}</span>
                                    </div>
                                    <div class="tooltip-wrapper">
                                        <button @click="confirmDelete(tenant.id, tenant.name)"
                                                class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 dark:text-red-400 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                        <span class="tooltip-text">{{ __('super-admin.dash_tooltip_delete') }}</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="totalPages > 1" class="p-6 border-t border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div class="text-sm text-slate-600 dark:text-slate-400">
                    {{ __('super-admin.dash_pag_show') }} <span x-text="(currentPage - 1) * perPage + 1"></span> {{ __('super-admin.dash_pag_to') }}
                    <span x-text="Math.min(currentPage * perPage, filteredTenants.length)"></span> {{ __('super-admin.dash_pag_of') }}
                    <span x-text="filteredTenants.length"></span> {{ __('super-admin.dash_pag_companies') }}
                </div>
                <div class="flex gap-2">
                    <button @click="currentPage--" :disabled="currentPage === 1"
                            :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-4 py-2 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg">
                        {{ __('super-admin.common_previous') }}
                    </button>
                    <template x-for="page in totalPages" :key="page">
                        <button @click="currentPage = page"
                                :class="currentPage === page ? 'bg-indigo-600 text-white' : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                class="px-4 py-2 rounded-lg"
                                x-text="page"></button>
                    </template>
                    <button @click="currentPage++" :disabled="currentPage === totalPages"
                            :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-4 py-2 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg">
                        {{ __('super-admin.common_next') }}
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="hasError" x-cloak class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-red-200 dark:border-red-900 p-12 text-center">
        <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ __('super-admin.error_load') }}</h3>
        <p class="text-slate-600 dark:text-slate-400 mb-6">{{ __('super-admin.error_load_desc') }}</p>
        <button onclick="location.reload()" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition font-medium">
            {{ __('super-admin.error_reload') }}
        </button>
    </div>


    <div x-show="showShortcuts" x-cloak @click.self="showShortcuts = false" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">{{ __('super-admin.shortcut_title') }}</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ __('super-admin.shortcut_search') }}</span>
                    <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded">/</kbd>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ __('super-admin.shortcut_refresh') }}</span>
                    <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded">R</kbd>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ __('super-admin.shortcut_dark') }}</span>
                    <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded">D</kbd>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ __('super-admin.shortcut_notif') }}</span>
                    <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded">N</kbd>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ __('super-admin.shortcut_export') }}</span>
                    <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded">E</kbd>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ __('super-admin.shortcut_close') }}</span>
                    <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded">ESC</kbd>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ __('super-admin.shortcut_help') }}</span>
                    <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded">?</kbd>
                </div>
            </div>
            <button @click="showShortcuts = false" class="mt-4 w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{{ __('super-admin.shortcut_close') }}</button>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak
         @keydown.escape.window="showDeleteModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
        <!-- Modal -->
        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-6 max-w-sm w-full border border-slate-200 dark:border-slate-700"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white text-center mb-1">{{ __('super-admin.delete_confirm_title') }}</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 text-center mb-6">
                {{ __('super-admin.delete_confirm_msg') }}
                <strong class="text-slate-900 dark:text-white" x-text="deleteTargetName"></strong>?
                <br><span class="text-red-500 font-medium">{{ __('super-admin.delete_irreversible') }}</span>
            </p>
            <div class="flex gap-3">
                <button @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-semibold hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                    {{ __('super-admin.common_cancel') }}
                </button>
                <button @click="deleteTenant(deleteTargetId)"
                        class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold transition shadow-sm">
                    {{ __('super-admin.delete_permanent') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Help Button -->
    <button @click="showShortcuts = true"
            class="fixed bottom-6 left-6 w-12 h-12 bg-white dark:bg-slate-700 hover:bg-indigo-50 dark:hover:bg-slate-600 text-indigo-600 dark:text-indigo-400 rounded-full shadow-xl border border-slate-200 dark:border-slate-600 flex items-center justify-center z-40 transition-all hover:scale-110">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </button>

    <!-- Toast Notifications: handled by global showToast() in layout -->

</div>
@endsection

@push('scripts')
@php
$__tDash = [
    'just_now'       => __('super-admin.js_just_now'),
    'dark_on'        => __('super-admin.js_dark_on'),
    'dark_off'       => __('super-admin.js_dark_off'),
    'refresh_ok'     => __('super-admin.js_refresh_success'),
    'refresh_fail'   => __('super-admin.js_refresh_fail'),
    'delete_ok'      => __('super-admin.js_delete_success'),
    'delete_fail'    => __('super-admin.js_delete_fail'),
    'delete_error'   => __('super-admin.js_delete_error'),
    'export_ok'      => __('super-admin.js_export_success'),
    'csv_name'       => __('super-admin.js_csv_name'),
    'csv_subdomain'  => __('super-admin.js_csv_subdomain'),
    'csv_status'     => __('super-admin.js_csv_status'),
    'csv_date'       => __('super-admin.js_csv_date'),
    'csv_active'     => __('super-admin.js_csv_active'),
    'csv_inactive'   => __('super-admin.js_csv_inactive'),
    'locale'         => app()->getLocale(),
];
@endphp
<script>
const __tDash = @json($__tDash);
function dashboard() {
    // Initialize with server-side data
    const initialStats = @json($stats);

    return {
        loading: false,
        hasError: false,
        stats: {
            total_tenants: initialStats.total_tenants || 0,
            active_tenants: initialStats.active_tenants || 0,
            paid_tenants: initialStats.paid_tenants || 0,
            trial_tenants: initialStats.trial_tenants || 0,
            inactive_tenants: initialStats.inactive_tenants || 0,
            tenants_this_month: initialStats.tenants_this_month || 0,
            pending_upgrade_requests: initialStats.pending_upgrade_requests || 0
        },
        filteredStats: {
            total_tenants: initialStats.total_tenants || 0,
            active_tenants: initialStats.active_tenants || 0,
            trial_tenants: initialStats.trial_tenants || 0,
            inactive_tenants: initialStats.inactive_tenants || 0
        },
        recentTenants: initialStats.recent_tenants || [],
        filteredTenants: initialStats.recent_tenants || [],
        paginatedTenants: [],

        // Recent Activities - pre-loaded server-side
        recentActivities: (initialStats.recent_activities || []).map(a => ({
            id: a.id,
            type: a.type || 'edit',
            message: a.message || a.description || '',
            user: a.user || '',
            time: new Date(a.time || a.created_at).toLocaleString(__tDash.locale, { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short' })
        })),
        activityTodayCount: initialStats.activity_today || 0,
        activityWeekCount: initialStats.activity_week || 0,

        // Pagination
        currentPage: 1,
        perPage: 10,
        totalPages: 1,

        // Filtering & Sorting
        statusFilter: 'all',
        sortField: 'date',
        sortDirection: 'desc',

        // Notifications - loaded from API on init
        notifications: [],

        searchQuery: '',
        showSearch: false,
        isDarkMode: false,
        showShortcuts: false,

        // Delete confirmation
        showDeleteModal: false,
        deleteTargetId: null,
        deleteTargetName: '',

        // Refresh
        refreshing: false,
        lastRefreshed: '{{ __('super-admin.js_just_now') }}',

        init() {
            this.filterData();
            this.updatePagination();
            this.initKeyboardShortcuts();
            this.initDarkMode();
            this.listenToToastEvents();
            this.updateLastRefreshed();
            // Activities are pre-loaded server-side; only load notifications via API
            this.loadNotifications();
            // Listen for dark mode change from nav button
            window.addEventListener('dark-mode-changed', (e) => {
                this.isDarkMode = e.detail.isDark;
            });
        },

        async loadActivities() {
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/api/super-admin/dashboard/activity-summary', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                if (!res.ok) {
                    console.warn('Activity API returned:', res.status);
                    return;
                }
                const data = await res.json();
                if (data.success && Array.isArray(data.data.recent)) {
                    this.activityTodayCount = data.data.today_count || 0;
                    this.activityWeekCount  = data.data.week_count  || 0;
                    this.recentActivities = data.data.recent.map(a => ({
                        id:      a.id,
                        type:    a.type || (['created','login','register','add'].includes(a.action) ? 'add' : (a.action === 'deleted' ? 'delete' : 'edit')),
                        message: a.description || a.message || '',
                        user:    a.user || '',
                        time:    new Date(a.created_at).toLocaleString(__tDash.locale, { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short' })
                    }));
                }
            } catch (e) {
                console.warn('loadActivities error:', e);
            }
        },

        async loadNotifications() {
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/api/super-admin/notifications', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                const data = await res.json();
                if (data.success && data.data) {
                    const items = Array.isArray(data.data) ? data.data : (data.data.data || []);
                    this.notifications = items.slice(0, 5).map(n => ({
                        id: n.id,
                        message: n.message || n.title,
                        time: new Date(n.created_at).toLocaleString(__tDash.locale, { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'short' })
                    }));
                }
            } catch (e) { /* silent fail */ }
        },

        initDarkMode() {
            this.isDarkMode = document.documentElement.classList.contains('dark');
        },

        toggleDarkMode() {
            this.isDarkMode = !this.isDarkMode;
            document.documentElement.classList.toggle('dark', this.isDarkMode);
            localStorage.setItem('darkMode', this.isDarkMode);
            window.dispatchEvent(new CustomEvent('dark-mode-changed', { detail: { isDark: this.isDarkMode } }));
            showToast(this.isDarkMode ? __tDash.dark_on : __tDash.dark_off, 'info');
        },

        updateLastRefreshed() {
            const now = new Date();
            this.lastRefreshed = now.toLocaleTimeString(__tDash.locale, { hour: '2-digit', minute: '2-digit' });
        },

        async refreshDashboard() {
            if (this.refreshing) return;
            this.refreshing = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/api/super-admin/dashboard', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                const data = await res.json();
                if (data.success && data.data) {
                    const d = data.data;
                    this.stats.total_tenants     = d.total_tenants     ?? this.stats.total_tenants;
                    this.stats.active_tenants    = d.active_tenants    ?? this.stats.active_tenants;
                    this.stats.paid_tenants      = d.paid_tenants      ?? this.stats.paid_tenants;
                    this.stats.trial_tenants     = d.trial_tenants     ?? this.stats.trial_tenants;
                    this.stats.inactive_tenants  = d.inactive_tenants  ?? this.stats.inactive_tenants;
                    this.stats.tenants_this_month= d.tenants_this_month?? this.stats.tenants_this_month;
                    this.stats.pending_upgrade_requests = d.pending_upgrade_requests ?? this.stats.pending_upgrade_requests;
                    this.recentTenants = d.recent_tenants || this.recentTenants;
                    this.filterData();
                }
                await Promise.all([this.loadActivities(), this.loadNotifications()]);
                this.updateLastRefreshed();
                showToast(__tDash.refresh_ok, 'success');
            } catch (e) {
                showToast(__tDash.refresh_fail, 'error');
            } finally {
                this.refreshing = false;
            }
        },



        initKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Search: /
                if (e.key === '/' && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    this.showSearch = !this.showSearch;
                }

                // Refresh: R
                if (e.key === 'r' && !e.ctrlKey && !e.metaKey && !['INPUT','TEXTAREA'].includes(document.activeElement.tagName)) {
                    e.preventDefault();
                    this.refreshDashboard();
                }
                // Dark Mode: D
                if (e.key === 'd' && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    this.toggleDarkMode();
                }
                // Notifications: N
                if (e.key === 'n' && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    // Toggle notifications dropdown
                }
                // Export: E
                if (e.key === 'e' && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    this.exportData();
                }
                // Close/Escape
                if (e.key === 'Escape') {
                    this.showShortcuts = false;
                    this.showSearch = false;
                }
                // Help: ?
                if (e.key === '?') {
                    e.preventDefault();
                    this.showShortcuts = !this.showShortcuts;
                }
            });
        },



        sortBy(field) {
            if (this.sortField === field) {
                this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDirection = 'desc';
            }

            this.filteredTenants.sort((a, b) => {
                let aVal, bVal;

                if (field === 'name') {
                    aVal = a.name.toLowerCase();
                    bVal = b.name.toLowerCase();
                } else if (field === 'status') {
                    aVal = a.is_active ? 1 : 0;
                    bVal = b.is_active ? 1 : 0;
                } else if (field === 'date') {
                    aVal = new Date(a.created_at);
                    bVal = new Date(b.created_at);
                }

                if (this.sortDirection === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });

            this.updatePagination();
        },

        filterData() {
            // Apply search filter
            let filtered = this.recentTenants;

            if (this.searchQuery) {
                filtered = filtered.filter(tenant =>
                    tenant.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                    tenant.subdomain.toLowerCase().includes(this.searchQuery.toLowerCase())
                );
            }

            // Apply status filter
            if (this.statusFilter === 'active') {
                filtered = filtered.filter(t => t.is_active);
            } else if (this.statusFilter === 'inactive') {
                filtered = filtered.filter(t => !t.is_active);
            }

            this.filteredTenants = filtered;
            this.updateFilteredStats();
            this.currentPage = 1;
            this.updatePagination();
        },

        updatePagination() {
            this.totalPages = Math.ceil(this.filteredTenants.length / this.perPage);
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            this.paginatedTenants = this.filteredTenants.slice(start, end);
        },

        updateFilteredStats() {
            const hasFilter = this.searchQuery || this.statusFilter !== 'all';
            this.filteredStats.total_tenants    = hasFilter ? this.filteredTenants.length : this.stats.total_tenants;
            this.filteredStats.active_tenants   = hasFilter ? this.filteredTenants.filter(t => t.is_active).length : this.stats.active_tenants;
            this.filteredStats.inactive_tenants = hasFilter ? this.filteredTenants.filter(t => !t.is_active).length : this.stats.inactive_tenants;
            this.filteredStats.trial_tenants    = this.stats.trial_tenants; // Global stat — not per-filtered-row
            this.updatePagination();
        },

        confirmDelete(id, name) {
            this.deleteTargetId = id;
            this.deleteTargetName = name;
            this.showDeleteModal = true;
        },

        async deleteTenant(id) {
            this.showDeleteModal = false;
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            try {
                const res = await fetch(`/api/super-admin/tenants/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                if (res.ok) {
                    this.recentTenants = this.recentTenants.filter(t => t.id !== id);
                    this.stats.total_tenants = Math.max(0, this.stats.total_tenants - 1);
                    this.filterData();
                    showToast(__tDash.delete_ok, 'success');
                } else {
                    const err = await res.json().catch(() => ({}));
                    showToast(err.message || __tDash.delete_fail, 'error');
                }
            } catch (e) {
                showToast(__tDash.delete_error, 'error');
            }
        },

        exportData() {
            const csvContent = [
                [__tDash.csv_name, __tDash.csv_subdomain, __tDash.csv_status, __tDash.csv_date],
                ...this.filteredTenants.map(t => [
                    t.name,
                    t.subdomain,
                    t.is_active ? __tDash.csv_active : __tDash.csv_inactive,
                    this.formatDate(t.created_at)
                ])
            ].map(row => row.join(',')).join('\n');

            const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `tenants_${new Date().toISOString().split('T')[0]}.csv`;
            link.click();
            this.showSuccess(__tDash.export_ok);
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString(__tDash.locale);
        },

        listenToToastEvents() {
            window.addEventListener('show-toast', (event) => {
                showToast(event.detail.message, event.detail.type || 'success');
            });
        },

        showSuccess(message) { showToast(message, 'success'); },
        showError(message)   { showToast(message, 'error');   },
    }
}
</script>
@endpush
