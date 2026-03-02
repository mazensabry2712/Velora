@extends('super-admin.layout')

@section('title', __('super-admin.logs_title'))
@section('breadcrumb')<span class="text-slate-700 dark:text-slate-200 font-medium">{{ __('super-admin.logs_title') }}</span>@endsection

@section('content')
<div x-data="activityLogs()">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">{{ __('super-admin.logs_title') }}</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">{{ __('super-admin.logs_subtitle') }}</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="card-animate card-delay-1 bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 border border-slate-200 dark:border-slate-700 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.logs_today') }}</p>
                    <p class="text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-2" x-text="stats.today"></p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="card-animate card-delay-2 bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 border border-slate-200 dark:border-slate-700 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.logs_this_week') }}</p>
                    <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2" x-text="stats.this_week"></p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="card-animate card-delay-3 bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 border border-slate-200 dark:border-slate-700 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.logs_this_month') }}</p>
                    <p class="text-3xl font-black text-amber-600 dark:text-amber-400 mt-2" x-text="stats.this_month"></p>
                </div>
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="card-animate card-delay-4 bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 border border-slate-200 dark:border-slate-700 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ __('super-admin.logs_total') }}</p>
                    <p class="text-3xl font-black text-slate-900 dark:text-white mt-2" x-text="pagination.total || 0"></p>
                </div>
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">{{ __('super-admin.logs_search') }}</label>
                <input type="text" x-model="filters.search" @input.debounce.500ms="loadLogs()"
                       placeholder="{{ __('super-admin.logs_search_ph') }}"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">{{ __('super-admin.logs_action_type') }}</label>
                <select x-model="filters.action" @change="loadLogs()"
                        class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
                    <option value="">{{ __('super-admin.logs_all') }}</option>
                    <option value="created">{{ __('super-admin.logs_created') }}</option>
                    <option value="updated">{{ __('super-admin.logs_updated') }}</option>
                    <option value="deleted">{{ __('super-admin.logs_deleted') }}</option>
                    <option value="logged_in">{{ __('super-admin.logs_login') }}</option>
                    <option value="logged_out">{{ __('super-admin.logs_logout') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">{{ __('super-admin.logs_date_from') }}</label>
                <input type="date" x-model="filters.date_from" @change="loadLogs()"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">{{ __('super-admin.logs_date_to') }}</label>
                <input type="date" x-model="filters.date_to" @change="loadLogs()"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
            </div>
        </div>

        <div class="mt-4 flex flex-wrap justify-between items-center gap-3">
            <button @click="clearFilters()" class="flex items-center gap-1.5 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ __('super-admin.logs_reset_filters') }}
            </button>
            <button @click="clearOldLogs()" class="flex items-center gap-1.5 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 px-4 py-2 rounded-xl text-sm font-semibold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                {{ __('super-admin.logs_clear_old') }}
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="space-y-3">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
                <div class="h-5 w-48 skeleton bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
            </div>
            <template x-for="i in 8" :key="i">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 last:border-0 flex gap-4">
                    <div class="h-4 w-32 skeleton bg-slate-200 dark:bg-slate-700 rounded"></div>
                    <div class="h-4 w-20 skeleton bg-slate-200 dark:bg-slate-700 rounded-full"></div>
                    <div class="h-4 flex-1 skeleton bg-slate-200 dark:bg-slate-700 rounded"></div>
                    <div class="h-4 w-24 skeleton bg-slate-200 dark:bg-slate-700 rounded"></div>
                </div>
            </template>
        </div>
    </div>

    <!-- Logs Table -->
    <div x-show="!loading" x-cloak class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">{{ __('super-admin.logs_col_user') }}</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">{{ __('super-admin.logs_col_action') }}</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">{{ __('super-admin.logs_col_description') }}</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">{{ __('super-admin.logs_col_ip') }}</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">{{ __('super-admin.logs_col_date') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4 text-sm text-slate-900 dark:text-white">
                                <span x-text="log.user ? log.user.name : 'System'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="{
                                    'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-300': log.action === 'created',
                                    'bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-300': log.action === 'updated',
                                    'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-300': log.action === 'deleted',
                                    'bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-300': log.action === 'logged_in' || log.action === 'logged_out'
                                }" class="px-2 py-1 text-xs font-semibold rounded-full" x-text="getActionLabel(log.action)"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                <span x-text="log.description"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                <span x-text="log.ip_address || '-'"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                <span x-text="formatDate(log.created_at)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.total > 0" class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 flex flex-wrap gap-3 items-center justify-between">

            <!-- Per-page + info -->
            <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <span>{{ __('super-admin.pagination_show') }}</span>
                <select x-model.number="perPage" @change="loadLogs(1)"
                        class="border border-slate-200 dark:border-slate-600 rounded-lg px-2 py-1 text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>{{ __('super-admin.pagination_per_page') }}</span>
                <span class="hidden sm:inline text-slate-300 dark:text-slate-600 mx-1">|</span>
                <span class="hidden sm:inline" x-text="`${pagination.from}–${pagination.to} ${__t.of_word} ${pagination.total}`"></span>
            </div>

            <!-- Page buttons -->
            <div class="flex items-center gap-1">
                <!-- First page -->
                <button @click="loadPage(1)" :disabled="pagination.current_page === 1"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
                <!-- Prev -->
                <button @click="loadPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>

                <!-- Page numbers -->
                <template x-for="page in pagination.last_page" :key="page">
                    <span x-show="page === 1 || page === pagination.last_page || Math.abs(page - pagination.current_page) <= 1">
                        <span x-show="page === pagination.current_page - 1 && pagination.current_page - 2 > 1"
                              class="px-1 text-slate-400 dark:text-slate-500 text-sm select-none">…</span>
                        <button @click="loadPage(page)"
                                :class="pagination.current_page === page
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                                class="min-w-[32px] h-8 px-1.5 rounded-lg text-sm font-medium transition"
                                x-text="page"></button>
                        <span x-show="page === pagination.current_page + 1 && pagination.current_page + 2 < pagination.last_page"
                              class="px-1 text-slate-400 dark:text-slate-500 text-sm select-none">…</span>
                    </span>
                </template>

                <!-- Next -->
                <button @click="loadPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <!-- Last page -->
                <button @click="loadPage(pagination.last_page)" :disabled="pagination.current_page === pagination.last_page"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 disabled:opacity-30 disabled:cursor-not-allowed transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@php
$__t = [
    'toast_login'    => __('super-admin.toast_login_again'),
    'toast_fail'     => __('super-admin.toast_logs_fail'),
    'toast_error'    => __('super-admin.toast_logs_error'),
    'clear_confirm'  => __('super-admin.logs_clear_confirm'),
    'delete_error'   => __('super-admin.toast_delete_error'),
    'action_created' => __('super-admin.logs_created'),
    'action_updated' => __('super-admin.logs_updated'),
    'action_deleted' => __('super-admin.logs_deleted'),
    'action_login'   => __('super-admin.logs_login'),
    'action_logout'  => __('super-admin.logs_logout'),
    'action_assign'  => __('super-admin.logs_action_assign'),
    'action_reset'   => __('super-admin.logs_action_reset_pw'),
    'of_word'        => __('super-admin.common_of'),
    'locale'         => app()->getLocale(),
];
@endphp
<script>
const __t = @json($__t);
function activityLogs() {
    // Pre-loaded server-side data
    const initialLogs  = @json($logs->items());
    const initialMeta  = @json($meta);
    const initialStats = @json($stats);

    return {
        loading: false,
        logs: initialLogs,
        stats: {
            today:      initialStats.today      || 0,
            this_week:  initialStats.this_week  || 0,
            this_month: initialStats.this_month || 0
        },
        filters: {
            search: '',
            action: '',
            date_from: '',
            date_to: ''
        },
        perPage: 5,
        pagination: {
            current_page:  initialMeta.current_page  || 1,
            last_page:     initialMeta.last_page     || 1,
            from:          initialMeta.from          || 0,
            to:            initialMeta.to            || 0,
            total:         initialMeta.total         || 0,
            prev_page_url: initialMeta.prev_page_url || null,
            next_page_url: initialMeta.next_page_url || null
        },

        async loadLogs(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({ page: page, per_page: this.perPage });
                // Only add non-empty filters
                Object.entries(this.filters).forEach(([k,v]) => { if (v) params.append(k, v); });

                const response = await fetch(`/api/super-admin/activity-logs?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin'
                });

                if (response.status === 401) {
                    showToast(__t.toast_login, 'error');
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    this.logs = data.data.data;
                    this.pagination = {
                        current_page:  data.data.current_page,
                        last_page:     data.data.last_page,
                        from:          data.data.from || 0,
                        to:            data.data.to || 0,
                        total:         data.data.total,
                        prev_page_url: data.data.prev_page_url,
                        next_page_url: data.data.next_page_url
                    };
                    // Refresh stats in background
                    this.loadStats();
                } else {
                    showToast(__t.toast_fail, 'error');
                }
            } catch (error) {
                console.error('Error loading logs:', error);
                showToast(__t.toast_error, 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const response = await fetch('/api/super-admin/activity-logs/statistics', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'same-origin'
                });
                if (!response.ok) return;
                const data = await response.json();
                if (data.success) {
                    this.stats.today      = data.data.today      || 0;
                    this.stats.this_week  = data.data.this_week  || 0;
                    this.stats.this_month = data.data.this_month || 0;
                }
            } catch (error) {
                console.warn('Stats load error:', error);
            }
        },

        loadPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadLogs(page);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        clearFilters() {
            this.filters = {
                search: '',
                action: '',
                date_from: '',
                date_to: ''
            };
            this.loadLogs();
        },

        async clearOldLogs() {
            if (!confirm(__t.clear_confirm)) {
                return;
            }

            try {
                const response = await fetch('/api/super-admin/activity-logs/clear-old', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ days: 90 })
                });

                const data = await response.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    await this.loadLogs();
                }
            } catch (error) {
                console.error('Error clearing logs:', error);
                showToast(__t.delete_error, 'error');
            }
        },

        getActionLabel(action) {
            const labels = {
                'created':              __t.action_created,
                'updated':              __t.action_updated,
                'deleted':              __t.action_deleted,
                'logged_in':            __t.action_login,
                'logged_out':           __t.action_logout,
                'assigned_subscription':__t.action_assign,
                'reset_password':       __t.action_reset
            };
            return labels[action] || action;
        },

        formatDate(date) {
            return new Date(date).toLocaleString(__t.locale, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>
@endpush
