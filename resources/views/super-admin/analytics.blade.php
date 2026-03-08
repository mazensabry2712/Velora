@extends('super-admin.layout')
@section('title', 'التحليلات')
@section('breadcrumb')<span class="text-slate-700 dark:text-slate-200 font-medium">التحليلات</span>@endsection

@section('content')
{{-- ── Outer wrapper: tab state ─────────────────────────────────── --}}
<div x-data="{ activeTab: '{{ request('tab', 'kpis') }}' }">

    {{-- Page Header + Tab Switcher --}}
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">التحليلات</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">KPIs · الإيرادات · النمو</p>
        </div>
        {{-- Tab Pills --}}
        <div class="flex gap-1 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <button @click="activeTab='kpis'"
                    :class="activeTab==='kpis'
                        ? 'bg-white dark:bg-slate-700 shadow text-indigo-600 dark:text-indigo-400'
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                KPIs
            </button>
            <button @click="activeTab='reports'"
                    :class="activeTab==='reports'
                        ? 'bg-white dark:bg-slate-700 shadow text-indigo-600 dark:text-indigo-400'
                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                    class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ __('super-admin.nav_reports') }}
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         TAB 1 – KPIs
    ═══════════════════════════════════════════════════════════ --}}
    <div x-show="activeTab === 'kpis'" x-cloak
         x-data="kpiDashboard()" x-init="loadData()">

        {{-- Actions row --}}
        <div class="flex items-center justify-end gap-3 mb-6">
            <a href="/super-admin/kpis/export.csv"
               class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
            <button @click="loadData()"
                    class="inline-flex items-center gap-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-sm px-4 py-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
        </div>

        {{-- Loading skeleton --}}
        <div x-show="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @for($i=0;$i<8;$i++)
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 animate-pulse border border-slate-200 dark:border-slate-700">
                <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded w-2/3 mb-3"></div>
                <div class="h-8 bg-slate-200 dark:bg-slate-700 rounded w-1/2"></div>
            </div>
            @endfor
        </div>

        {{-- KPI Grid --}}
        <div x-show="!loading" x-cloak class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 shadow-sm border border-slate-200 dark:border-slate-700">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">MRR</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1" x-text="fmt.currency(data.mrr)">—</p>
                <p class="text-xs text-slate-400 mt-1">Monthly Recurring Revenue</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 shadow-sm border border-slate-200 dark:border-slate-700">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">ARPU</p>
                <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1" x-text="fmt.currency(data.arpu)">—</p>
                <p class="text-xs text-slate-400 mt-1">Avg Revenue Per User</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 shadow-sm border border-slate-200 dark:border-slate-700">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Trial Signups (30d)</p>
                <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-1" x-text="data.trial_signups_30d ?? '—'">—</p>
                <p class="text-xs text-slate-400 mt-1" x-text="(data.trial_total_active ?? 0) + ' active now'"></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 shadow-sm border border-slate-200 dark:border-slate-700">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Churn Rate (30d)</p>
                <p class="text-3xl font-bold mt-1"
                   :class="(data.churn_rate_30d ?? 0) > 10 ? 'text-red-600' : 'text-emerald-600'"
                   x-text="fmt.pct(data.churn_rate_30d)">—</p>
                <p class="text-xs text-slate-400 mt-1">Cancellations / Active</p>
            </div>
        </div>

        {{-- Conversion Funnel --}}
        <div x-show="!loading" x-cloak class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700 mt-6">
            <h2 class="text-base font-bold text-slate-900 dark:text-white mb-5">Trial → Paid Conversion Funnel</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-4xl font-bold text-slate-900 dark:text-white" x-text="data.trial_signups_30d ?? 0">0</div>
                    <div class="text-sm text-slate-500 mt-1">Signed Up</div>
                    <div class="h-2 bg-indigo-600 rounded-full mt-3 mx-auto" style="width:100%;max-width:80px"></div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-indigo-600" x-text="data.activated_count ?? 0">0</div>
                    <div class="text-sm text-slate-500 mt-1">Activated Trial</div>
                    <div class="text-xs text-indigo-500 font-semibold" x-text="fmt.pct(data.activated_rate)"></div>
                    <div class="h-2 bg-indigo-400 rounded-full mt-3 mx-auto" :style="`width:${Math.min(data.activated_rate ?? 0, 100)}%;max-width:80px`"></div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-violet-600" x-text="data.aha_reached_count ?? 0">0</div>
                    <div class="text-sm text-slate-500 mt-1">Aha Moment ✨</div>
                    <div class="text-xs text-violet-500 font-semibold" x-text="fmt.pct(data.aha_rate)"></div>
                    <div class="h-2 bg-violet-400 rounded-full mt-3 mx-auto" :style="`width:${Math.min(data.aha_rate ?? 0, 100)}%;max-width:80px`"></div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-emerald-600" x-text="data.converted_count ?? 0">0</div>
                    <div class="text-sm text-slate-500 mt-1">Converted to Paid</div>
                    <div class="text-xs text-emerald-600 font-semibold" x-text="fmt.pct(data.trial_to_paid_rate)"></div>
                    <div class="h-2 bg-emerald-500 rounded-full mt-3 mx-auto" :style="`width:${Math.min(data.trial_to_paid_rate ?? 0, 100)}%;max-width:80px`"></div>
                </div>
            </div>
            <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl">
                <p class="text-sm text-amber-800 dark:text-amber-300">
                    <strong>Target:</strong> Trial → Paid ≥ <strong>35%</strong>.
                    Current: <span class="font-bold" x-text="fmt.pct(data.trial_to_paid_rate)"></span>
                    <span x-show="(data.trial_to_paid_rate ?? 0) < 35" class="text-red-600 font-semibold"> ↓ below target</span>
                    <span x-show="(data.trial_to_paid_rate ?? 0) >= 35" class="text-emerald-600 font-semibold"> ✓ on target</span>
                </p>
            </div>
        </div>

        {{-- Nudge Funnel --}}
        <div x-show="!loading" x-cloak class="bg-white dark:bg-slate-800 rounded-2xl p-6 shadow-sm border border-slate-200 dark:border-slate-700 mt-6">
            <h2 class="text-base font-bold text-slate-900 dark:text-white mb-5">Email Nudge Delivery</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <template x-for="day in [1,3,7,12]" :key="day">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-slate-700 dark:text-slate-300"
                             x-text="data.nudge_stats ? (data.nudge_stats['day'+day+'_sent'] ?? 0) : '—'"></div>
                        <div class="text-sm text-slate-500 mt-1" x-text="'Day '+day+' Nudge'"></div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Error --}}
        <div x-show="error" x-cloak class="mt-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 text-red-700 dark:text-red-300 text-sm">
            <strong>Error loading data:</strong> <span x-text="error"></span>
        </div>
    </div>{{-- /KPIs tab --}}

    {{-- ═══════════════════════════════════════════════════════════
         TAB 2 – Reports
    ═══════════════════════════════════════════════════════════ --}}
    <div x-show="activeTab === 'reports'" x-cloak
         x-data="reports()" x-init="loadReports()">

        {{-- Loading State --}}
        <div x-show="loading" class="space-y-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex flex-wrap gap-4">
                    <div class="h-10 w-36 bg-slate-200 dark:bg-slate-700 rounded-xl skeleton"></div>
                    <div class="h-10 w-36 bg-slate-200 dark:bg-slate-700 rounded-xl skeleton"></div>
                    <div class="h-10 w-24 bg-slate-200 dark:bg-slate-700 rounded-xl skeleton"></div>
                    <div class="h-10 w-28 bg-slate-200 dark:bg-slate-700 rounded-xl skeleton"></div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <template x-for="i in 4" :key="i">
                    <div class="h-32 bg-slate-200 dark:bg-slate-700 rounded-2xl skeleton"></div>
                </template>
            </div>
        </div>

        {{-- Reports Content --}}
        <div x-show="!loading" x-cloak>

            {{-- Date Filter --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-5 mb-8 border border-slate-200 dark:border-slate-700">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">{{ __('super-admin.reports_date_from') }}</label>
                        <input type="date" x-model="filters.date_from"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-slate-700/50 dark:text-white transition">
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">{{ __('super-admin.reports_date_to') }}</label>
                        <input type="date" x-model="filters.date_to"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-slate-700/50 dark:text-white transition">
                    </div>
                    <button @click="loadReports()"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white rounded-xl transition hover:-translate-y-0.5 shadow-md shadow-indigo-200 dark:shadow-indigo-900">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        {{ __('super-admin.reports_apply') }}
                    </button>
                    <button @click="exportReport('excel')"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white rounded-xl transition hover:-translate-y-0.5 shadow-md shadow-emerald-200 dark:shadow-emerald-900">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ __('super-admin.reports_export_excel') }}
                    </button>
                </div>
            </div>

            {{-- Overview Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card-animate card-delay-1 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-lg p-6 text-white hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold opacity-90">{{ __('super-admin.reports_total_revenue') }}</h3>
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black" x-text="'$' + formatNumber(stats.total_revenue)"></p>
                    <p class="text-sm opacity-80 mt-1" x-text="getGrowthText(stats.revenue_growth)"></p>
                </div>
                <div class="card-animate card-delay-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg p-6 text-white hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold opacity-90">{{ __('super-admin.reports_active_tenants') }}</h3>
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black" x-text="stats.active_tenants"></p>
                    <p class="text-sm opacity-80 mt-1" x-text="stats.total_tenants + ' ' + __tReports.from_total"></p>
                </div>
                <div class="card-animate card-delay-3 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl shadow-lg p-6 text-white hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold opacity-90">{{ __('super-admin.reports_active_subs_card') }}</h3>
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black" x-text="stats.active_subscriptions"></p>
                    <p class="text-sm opacity-80 mt-1" x-text="stats.trial_subscriptions + ' ' + __tReports.in_trial"></p>
                </div>
                <div class="card-animate card-delay-4 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-lg p-6 text-white hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold opacity-90">{{ __('super-admin.reports_avg_revenue') }}</h3>
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-black" x-text="'$' + formatNumber(stats.average_revenue)"></p>
                    <p class="text-sm opacity-80 mt-1">{{ __('super-admin.reports_per_company') }}</p>
                </div>
            </div>

            {{-- Charts Row --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ __('super-admin.reports_monthly_revenue') }}</h2>
                    <div class="h-64"><canvas id="revenueChart"></canvas></div>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ __('super-admin.reports_company_growth') }}</h2>
                    <div class="h-64"><canvas id="tenantsChart"></canvas></div>
                </div>
            </div>

            {{-- Plan Performance --}}
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 mb-8 border border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ __('super-admin.reports_plan_performance') }}</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">{{ __('super-admin.reports_plan_col') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">{{ __('super-admin.reports_subscribers_col') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">{{ __('super-admin.reports_monthly_rev_col') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">{{ __('super-admin.reports_annual_rev_col') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">{{ __('super-admin.reports_conversion_col') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <template x-for="plan in planPerformance" :key="plan.id">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-900 dark:text-white" x-text="plan.name"></div>
                                        <div class="text-sm text-slate-500" x-text="'$' + plan.price + __tReports.per_month"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm text-slate-900 dark:text-white font-semibold" x-text="plan.subscribers"></span></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm text-emerald-600 dark:text-emerald-400 font-semibold" x-text="'$' + formatNumber(plan.monthly_revenue)"></span></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm text-indigo-600 dark:text-indigo-400 font-semibold" x-text="'$' + formatNumber(plan.annual_revenue)"></span></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-16 bg-slate-200 dark:bg-slate-600 rounded-full h-2 mr-2">
                                                <div class="bg-indigo-600 h-2 rounded-full" :style="'width: ' + plan.conversion_rate + '%'"></div>
                                            </div>
                                            <span class="text-sm text-slate-900 dark:text-white" x-text="plan.conversion_rate + '%'"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Bottom row --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Top Tenants --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ __('super-admin.reports_top_companies') }}</h2>
                    <div class="space-y-4">
                        <template x-for="(tenant, index) in topTenants" :key="tenant.id">
                            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                                        <span class="text-indigo-600 dark:text-indigo-400 font-bold" x-text="index + 1"></span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-white" x-text="tenant.name"></p>
                                        <p class="text-sm text-slate-500" x-text="tenant.plan_name"></p>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400" x-text="tenant.activity_count + ' ' + __tReports.activity_label"></p>
                                    <p class="text-xs text-slate-500" x-text="tenant.users_count + ' ' + __tReports.user_label"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                {{-- Activity Distribution --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-6">{{ __('super-admin.reports_activity_dist') }}</h2>
                    <div class="space-y-4">
                        <template x-for="activity in activityDistribution" :key="activity.type">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300" x-text="activity.label"></span>
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white" x-text="activity.count"></span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-600 rounded-full h-3">
                                    <div class="h-3 rounded-full transition-all duration-300"
                                         :class="activity.color"
                                         :style="'width: ' + activity.percentage + '%'"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>{{-- /reports content --}}
    </div>{{-- /reports tab --}}

</div>{{-- /outer wrapper --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@php
$__tReports = [
    'from_total'       => __('super-admin.reports_from_total'),
    'in_trial'         => __('super-admin.reports_in_trial'),
    'activity_label'   => __('super-admin.reports_activity_label'),
    'user_label'       => __('super-admin.reports_user_label'),
    'chart_revenue'    => __('super-admin.reports_chart_revenue'),
    'chart_tenants'    => __('super-admin.reports_chart_tenants'),
    'per_month'        => __('super-admin.reports_per_month'),
    'activity_created' => __('super-admin.reports_activity_created'),
    'activity_updated' => __('super-admin.reports_activity_updated'),
    'activity_deleted' => __('super-admin.reports_activity_deleted'),
    'activity_login'   => __('super-admin.reports_activity_login'),
    'growth_pct'       => __('super-admin.reports_growth_pct'),
    'export_alert'     => __('super-admin.reports_export_alert'),
];
@endphp
<script>
const __tReports = @json($__tReports);

function kpiDashboard() {
    return {
        loading: true,
        error: null,
        data: {},
        fmt: {
            currency: (v) => v != null ? `${Number(v).toLocaleString('en-SA', {maximumFractionDigits:0})} SAR` : '—',
            pct:      (v) => v != null ? `${Number(v).toFixed(1)}%` : '—',
        },
        loadData() {
            this.loading = true;
            this.error   = null;
            fetch('/super-admin/api/dashboard/revenue-metrics', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]')?.content }
            })
            .then(r => r.json())
            .then(json => {
                if (json.success) { this.data = json.data; }
                else { this.error = json.message || 'Unknown error'; }
            })
            .catch(e => { this.error = e.message; })
            .finally(() => { this.loading = false; });
        }
    };
}

function reports() {
    return {
        loading: true,
        filters: {
            date_from: new Date(new Date().setMonth(new Date().getMonth() - 6)).toISOString().split('T')[0],
            date_to: new Date().toISOString().split('T')[0]
        },
        stats: {
            total_revenue: 0, revenue_growth: 0, active_tenants: 0,
            total_tenants: 0, active_subscriptions: 0, trial_subscriptions: 0, average_revenue: 0
        },
        planPerformance: [],
        topTenants: [],
        activityDistribution: [],
        revenueChart: null,
        tenantsChart: null,

        async loadReports() {
            this.loading = true;
            try {
                const [dashboardData, growthData, activityData] = await Promise.all([
                    fetch('/api/super-admin/dashboard/subscription-stats', { credentials: 'include' }).then(r => r.json()),
                    fetch('/api/super-admin/dashboard/growth-metrics',     { credentials: 'include' }).then(r => r.json()),
                    fetch('/api/super-admin/dashboard/activity-summary',   { credentials: 'include' }).then(r => r.json()),
                ]);

                this.stats = {
                    total_revenue:         dashboardData.total_revenue || 0,
                    revenue_growth:        12.5,
                    active_tenants:        growthData.current_tenants || 0,
                    total_tenants:         growthData.total_tenants || 0,
                    active_subscriptions:  dashboardData.active_subscriptions || 0,
                    trial_subscriptions:   dashboardData.trial_subscriptions || 0,
                    average_revenue:       dashboardData.total_revenue / (growthData.current_tenants || 1)
                };

                this.planPerformance = (dashboardData.plans || []).map(plan => ({
                    ...plan,
                    monthly_revenue:  plan.price * plan.active_subscriptions,
                    annual_revenue:   plan.price * plan.active_subscriptions * 12,
                    conversion_rate:  Math.min(100, (plan.active_subscriptions / (growthData.current_tenants || 1) * 100).toFixed(1))
                }));

                this.topTenants = [
                    { id: 1, name: 'Company Alpha',    plan_name: 'Professional', activity_count: 245, users_count: 15 },
                    { id: 2, name: 'Tech Solutions',   plan_name: 'Enterprise',   activity_count: 198, users_count: 28 },
                    { id: 3, name: 'Innovation Hub',   plan_name: 'Basic',        activity_count: 156, users_count: 8  },
                    { id: 4, name: 'Success Corp',     plan_name: 'Professional', activity_count: 134, users_count: 12 },
                    { id: 5, name: 'Expert House',     plan_name: 'Basic',        activity_count: 98,  users_count: 5  }
                ];

                this.activityDistribution = [
                    { type: 'created', label: __tReports.activity_created, count: activityData.today      || 0, percentage: 35, color: 'bg-emerald-500' },
                    { type: 'updated', label: __tReports.activity_updated, count: activityData.this_week  || 0, percentage: 45, color: 'bg-blue-500'    },
                    { type: 'deleted', label: __tReports.activity_deleted, count: 23,                          percentage: 10, color: 'bg-red-500'     },
                    { type: 'login',   label: __tReports.activity_login,   count: activityData.this_month || 0, percentage: 60, color: 'bg-amber-500'  }
                ];

                await this.$nextTick();
                this.drawCharts(growthData);
            } catch (error) {
                console.error('Error loading reports:', error);
            } finally {
                this.loading = false;
            }
        },

        drawCharts(growthData) {
            if (this.revenueChart) this.revenueChart.destroy();
            if (this.tenantsChart) this.tenantsChart.destroy();

            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                this.revenueChart = new Chart(revenueCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: growthData.months || [],
                        datasets: [{ label: __tReports.chart_revenue, data: growthData.revenue || [],
                            borderColor: 'rgb(16, 185, 129)', backgroundColor: 'rgba(16, 185, 129, 0.1)', tension: 0.4, fill: true }]
                    },
                    options: { responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                });
            }

            const tenantsCtx = document.getElementById('tenantsChart');
            if (tenantsCtx) {
                this.tenantsChart = new Chart(tenantsCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: growthData.months || [],
                        datasets: [{ label: __tReports.chart_tenants, data: growthData.tenants || [],
                            backgroundColor: 'rgba(99, 102, 241, 0.8)', borderColor: 'rgb(99, 102, 241)', borderWidth: 1 }]
                    },
                    options: { responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
                });
            }
        },

        formatNumber(num) {
            return (num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        getGrowthText(growth) {
            return (growth >= 0 ? '+' : '') + growth + __tReports.growth_pct;
        },

        exportReport(format) {
            alert(__tReports.export_alert + ' ' + format);
        }
    };
}
</script>
@endpush
