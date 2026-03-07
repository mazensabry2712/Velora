@extends('super-admin.layout')
@section('title', 'Revenue KPIs')

@section('content')
<div class="space-y-6" x-data="kpiDashboard()" x-init="loadData()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Revenue KPI Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Trial funnel · MRR · Churn · Conversion rates</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="/super-admin/kpis/export.csv"
               class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
                ↓ Export CSV
            </a>
            <button @click="loadData()"
                    class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                ↻ Refresh
            </button>
        </div>
    </div>

    {{-- Loading skeleton --}}
    <div x-show="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @for($i=0;$i<8;$i++)
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 animate-pulse">
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/3 mb-3"></div>
            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
        @endfor
    </div>

    {{-- KPI Grid --}}
    <div x-show="!loading" x-cloak class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- MRR --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">MRR</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" x-text="fmt.currency(data.mrr)">—</p>
            <p class="text-xs text-gray-400 mt-1">Monthly Recurring Revenue</p>
        </div>

        {{-- ARPU --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">ARPU</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" x-text="fmt.currency(data.arpu)">—</p>
            <p class="text-xs text-gray-400 mt-1">Avg Revenue Per User</p>
        </div>

        {{-- Trial Signups (30d) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Trial Signups (30d)</p>
            <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-1" x-text="data.trial_signups_30d ?? '—'">—</p>
            <p class="text-xs text-gray-400 mt-1" x-text="(data.trial_total_active ?? 0) + ' active now'"></p>
        </div>

        {{-- Churn Rate --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Churn Rate (30d)</p>
            <p class="text-3xl font-bold mt-1"
               :class="(data.churn_rate_30d ?? 0) > 10 ? 'text-red-600' : 'text-emerald-600'"
               x-text="fmt.pct(data.churn_rate_30d)">—</p>
            <p class="text-xs text-gray-400 mt-1">Cancellations / Active</p>
        </div>

    </div>

    {{-- Conversion Funnel --}}
    <div x-show="!loading" x-cloak class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
        <h2 class="text-base font-bold text-gray-900 dark:text-white mb-5">Trial → Paid Conversion Funnel</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

            <div class="text-center">
                <div class="text-4xl font-bold text-gray-900 dark:text-white" x-text="data.trial_signups_30d ?? 0">0</div>
                <div class="text-sm text-gray-500 mt-1">Signed Up</div>
                <div class="h-2 bg-indigo-600 rounded-full mt-3 mx-auto" style="width:100%;max-width:80px"></div>
            </div>

            <div class="text-center">
                <div class="text-4xl font-bold text-indigo-600" x-text="data.activated_count ?? 0">0</div>
                <div class="text-sm text-gray-500 mt-1">Activated Trial</div>
                <div class="text-xs text-indigo-500 font-semibold" x-text="fmt.pct(data.activated_rate)"></div>
                <div class="h-2 bg-indigo-400 rounded-full mt-3 mx-auto" :style="`width:${Math.min(data.activated_rate ?? 0, 100)}%;max-width:80px`"></div>
            </div>

            <div class="text-center">
                <div class="text-4xl font-bold text-violet-600" x-text="data.aha_reached_count ?? 0">0</div>
                <div class="text-sm text-gray-500 mt-1">Aha Moment ✨</div>
                <div class="text-xs text-violet-500 font-semibold" x-text="fmt.pct(data.aha_rate)"></div>
                <div class="h-2 bg-violet-400 rounded-full mt-3 mx-auto" :style="`width:${Math.min(data.aha_rate ?? 0, 100)}%;max-width:80px`"></div>
            </div>

            <div class="text-center">
                <div class="text-4xl font-bold text-emerald-600" x-text="data.converted_count ?? 0">0</div>
                <div class="text-sm text-gray-500 mt-1">Converted to Paid</div>
                <div class="text-xs text-emerald-600 font-semibold" x-text="fmt.pct(data.trial_to_paid_rate)"></div>
                <div class="h-2 bg-emerald-500 rounded-full mt-3 mx-auto" :style="`width:${Math.min(data.trial_to_paid_rate ?? 0, 100)}%;max-width:80px`"></div>
            </div>

        </div>

        {{-- Target annotation --}}
        <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg">
            <p class="text-sm text-amber-800 dark:text-amber-300">
                <strong>Target:</strong> Trial → Paid ≥ <strong>35%</strong>.
                Current: <span class="font-bold" x-text="fmt.pct(data.trial_to_paid_rate)"></span>
                <span x-show="(data.trial_to_paid_rate ?? 0) < 35" class="text-red-600 font-semibold"> ↓ below target</span>
                <span x-show="(data.trial_to_paid_rate ?? 0) >= 35" class="text-emerald-600 font-semibold"> ✓ on target</span>
            </p>
        </div>
    </div>

    {{-- Nudge Funnel --}}
    <div x-show="!loading" x-cloak class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
        <h2 class="text-base font-bold text-gray-900 dark:text-white mb-5">Email Nudge Delivery</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <template x-for="day in [1,3,7,12]" :key="day">
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-700 dark:text-gray-300"
                         x-text="data.nudge_stats ? (data.nudge_stats['day'+day+'_sent'] ?? 0) : '—'"></div>
                    <div class="text-sm text-gray-500 mt-1" x-text="'Day '+day+' Nudge'"></div>
                </div>
            </template>
        </div>
    </div>

    {{-- Error state --}}
    <div x-show="error" x-cloak class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm">
        <strong>Error loading data:</strong> <span x-text="error"></span>
    </div>

</div>
@endsection

@push('scripts')
<script>
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
</script>
@endpush
