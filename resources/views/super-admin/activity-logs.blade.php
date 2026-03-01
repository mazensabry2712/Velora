@extends('super-admin.layout')

@section('title', 'سجل الأنشطة')
@section('breadcrumb')<span class="text-slate-700 dark:text-slate-200 font-medium">سجل الأنشطة</span>@endsection

@section('content')
<div x-data="activityLogs()" x-init="loadLogs()">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">سجل الأنشطة</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">تتبع جميع الأنشطة والتغييرات في النظام</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="card-animate card-delay-1 bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-6 border border-slate-200 dark:border-slate-700 hover:-translate-y-1 hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">اليوم</p>
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
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">هذا الأسبوع</p>
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
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">هذا الشهر</p>
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
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">الإجمالي</p>
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
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">البحث</label>
                <input type="text" x-model="filters.search" @input.debounce.500ms="loadLogs()"
                       placeholder="ابحث في الوصف..."
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">نوع الإجراء</label>
                <select x-model="filters.action" @change="loadLogs()"
                        class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
                    <option value="">الكل</option>
                    <option value="created">إنشاء</option>
                    <option value="updated">تحديث</option>
                    <option value="deleted">حذف</option>
                    <option value="logged_in">تسجيل دخول</option>
                    <option value="logged_out">تسجيل خروج</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">من تاريخ</label>
                <input type="date" x-model="filters.date_from" @change="loadLogs()"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">إلى تاريخ</label>
                <input type="date" x-model="filters.date_to" @change="loadLogs()"
                       class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
            </div>
        </div>

        <div class="mt-4 flex flex-wrap justify-between items-center gap-3">
            <button @click="clearFilters()" class="flex items-center gap-1.5 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                إعادة تعيين الفلاتر
            </button>
            <button @click="clearOldLogs()" class="flex items-center gap-1.5 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 px-4 py-2 rounded-xl text-sm font-semibold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                حذف السجلات القديمة (أكثر من 90 يوم)
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
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">المستخدم</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">الإجراء</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">الوصف</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">IP Address</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">التاريخ</th>
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
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 flex flex-wrap gap-3 items-center justify-between">
            <div class="text-sm text-slate-500 dark:text-slate-400">
                عرض <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="pagination.from"></span> إلى <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="pagination.to"></span> من <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="pagination.total"></span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="loadPage(pagination.current_page - 1)" :disabled="!pagination.prev_page_url"
                        class="px-4 py-2 text-sm font-semibold border border-slate-200 dark:border-slate-600 rounded-xl disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                    ← السابق
                </button>
                <span class="text-sm text-slate-500 dark:text-slate-400 px-2">
                    <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
                </span>
                <button @click="loadPage(pagination.current_page + 1)" :disabled="!pagination.next_page_url"
                        class="px-4 py-2 text-sm font-semibold border border-slate-200 dark:border-slate-600 rounded-xl disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition">
                    التالي →
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function activityLogs() {
    return {
        loading: true,
        logs: [],
        stats: {
            today: 0,
            this_week: 0,
            this_month: 0
        },
        filters: {
            search: '',
            action: '',
            date_from: '',
            date_to: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
            prev_page_url: null,
            next_page_url: null
        },

        async loadLogs(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: page,
                    ...this.filters
                });

                const response = await fetch(`/api/super-admin/activity-logs?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'include'
                });

                const data = await response.json();
                if (data.success) {
                    this.logs = data.data.data;
                    this.pagination = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        from: data.data.from,
                        to: data.data.to,
                        total: data.data.total,
                        prev_page_url: data.data.prev_page_url,
                        next_page_url: data.data.next_page_url
                    };
                }

                // Load statistics
                await this.loadStats();
            } catch (error) {
                console.error('Error loading logs:', error);
                showToast('فشل تحميل السجلات', 'error');
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
                    credentials: 'include'
                });

                const data = await response.json();
                if (data.success) {
                    this.stats = data.data;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        loadPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadLogs(page);
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
            if (!confirm('هل أنت متأكد من حذف جميع السجلات الأقدم من 90 يوم؟')) {
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
                    credentials: 'include',
                    body: JSON.stringify({ days: 90 })
                });

                const data = await response.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    await this.loadLogs();
                }
            } catch (error) {
                console.error('Error clearing logs:', error);
                showToast('حدث خطأ أثناء الحذف', 'error');
            }
        },

        getActionLabel(action) {
            const labels = {
                'created': 'إنشاء',
                'updated': 'تحديث',
                'deleted': 'حذف',
                'logged_in': 'دخول',
                'logged_out': 'خروج',
                'assigned_subscription': 'تعيين اشتراك',
                'reset_password': 'إعادة تعيين كلمة المرور'
            };
            return labels[action] || action;
        },

        formatDate(date) {
            return new Date(date).toLocaleString('ar-EG', {
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
