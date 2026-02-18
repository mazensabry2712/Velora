@extends('super-admin.layout')

@section('title', 'Activity Logs')

@section('content')
<div x-data="activityLogs()" x-init="loadLogs()">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">سجل الأنشطة</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-2">تتبع جميع الأنشطة والتغييرات في النظام</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-6 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">اليوم</p>
                    <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-2" x-text="stats.today"></p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-6 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">هذا الأسبوع</p>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2" x-text="stats.this_week"></p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-6 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">هذا الشهر</p>
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-2" x-text="stats.this_month"></p>
                </div>
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-6 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">الإجمالي</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white mt-2" x-text="pagination.total || 0"></p>
                </div>
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">البحث</label>
                <input type="text" x-model="filters.search" @input.debounce.500ms="loadLogs()"
                       placeholder="ابحث في الوصف..."
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">نوع الإجراء</label>
                <select x-model="filters.action" @change="loadLogs()"
                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="">الكل</option>
                    <option value="created">إنشاء</option>
                    <option value="updated">تحديث</option>
                    <option value="deleted">حذف</option>
                    <option value="logged_in">تسجيل دخول</option>
                    <option value="logged_out">تسجيل خروج</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">من تاريخ</label>
                <input type="date" x-model="filters.date_from" @change="loadLogs()"
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">إلى تاريخ</label>
                <input type="date" x-model="filters.date_to" @change="loadLogs()"
                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>
        </div>

        <div class="mt-4 flex justify-between items-center">
            <button @click="clearFilters()" class="text-sm text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400">
                إعادة تعيين الفلاتر
            </button>
            <button @click="clearOldLogs()" class="bg-red-100 dark:bg-red-900 hover:bg-red-200 dark:hover:bg-red-800 text-red-600 dark:text-red-400 px-4 py-2 rounded-lg text-sm font-medium transition">
                حذف السجلات القديمة (أكثر من 90 يوم)
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Logs Table -->
    <div x-show="!loading" x-cloak class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700">
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
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div class="text-sm text-slate-600 dark:text-slate-400">
                عرض <span x-text="pagination.from"></span> إلى <span x-text="pagination.to"></span> من <span x-text="pagination.total"></span>
            </div>
            <div class="flex items-center space-x-2 space-x-reverse">
                <button @click="loadPage(pagination.current_page - 1)" :disabled="!pagination.prev_page_url"
                        class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700">
                    السابق
                </button>
                <span class="text-sm text-slate-600 dark:text-slate-400">
                    صفحة <span x-text="pagination.current_page"></span> من <span x-text="pagination.last_page"></span>
                </span>
                <button @click="loadPage(pagination.current_page + 1)" :disabled="!pagination.next_page_url"
                        class="px-3 py-1 border border-slate-300 dark:border-slate-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700">
                    التالي
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
