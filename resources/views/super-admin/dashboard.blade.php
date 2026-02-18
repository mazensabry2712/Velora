@extends('super-admin.layout')

@section('title', 'Super Admin Dashboard')

@section('content')
<div x-data="dashboard()" x-init="loadDashboard()">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">لوحة التحكم الرئيسية</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-2">إدارة جميع الشركات والنظام</p>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Statistics Cards -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Tenants -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-6 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">إجمالي الشركات</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white mt-2" x-text="stats.total_tenants"></p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Tenants -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-6 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">الشركات النشطة</p>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2" x-text="stats.active_tenants"></p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Tenants This Month -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-6 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">هذا الشهر</p>
                    <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400 mt-2" x-text="stats.tenants_this_month"></p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Inactive Tenants -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-6 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 dark:text-slate-400">الشركات غير النشطة</p>
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-2" x-text="stats.inactive_tenants"></p>
                </div>
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Tenants -->
    <div x-show="!loading" x-cloak class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">أحدث الشركات</h2>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">اسم الشركة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">الدومين</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">الحالة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">تاريخ الإنشاء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <template x-for="tenant in recentTenants" :key="tenant.id">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-4 py-3 text-sm text-slate-900 dark:text-white" x-text="tenant.name"></td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400" x-text="tenant.domain"></td>
                                <td class="px-4 py-3">
                                    <span :class="tenant.active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300'"
                                          class="px-2 py-1 text-xs font-semibold rounded-full"
                                          x-text="tenant.active ? 'نشط' : 'غير نشط'">
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-400" x-text="formatDate(tenant.created_at)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function dashboard() {
    return {
        loading: true,
        stats: {
            total_tenants: 0,
            active_tenants: 0,
            inactive_tenants: 0,
            tenants_this_month: 0
        },
        recentTenants: [],

        async loadDashboard() {
            try {
                const response = await fetch('/api/super-admin/dashboard', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'include'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.stats.total_tenants = data.data.total_tenants;
                    this.stats.active_tenants = data.data.active_tenants;
                    this.stats.inactive_tenants = data.data.inactive_tenants;
                    this.recentTenants = data.data.recent_tenants;
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
            } finally {
                this.loading = false;
            }
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString('ar-EG');
        }
    }
}
</script>
@endpush
