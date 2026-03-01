<!DOCTYPE html>
@php
    $isArabic = app()->getLocale() === 'ar';
@endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isArabic ? 'إدارة العملاء' : 'Manage Customers' }} - {{ tenant()->name }}</title>
    <script>
        (function() {
            if (localStorage.getItem('darkMode') === 'true' ||
                (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <style>
        .stat-card { @apply bg-white rounded-xl shadow-sm border border-slate-100 p-4 hover:shadow-md transition-shadow dark:bg-slate-800 dark:border-slate-700; }
        .filter-input { @apply px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-slate-700 dark:border-slate-600 dark:text-slate-100; }
        .dark .filter-input { background-color: #334155 !important; color: #f1f5f9 !important; border-color: #475569 !important; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 min-h-screen" x-data="customersApp()" x-init="init()">

    @include('partials.admin-nav')

    <div class="p-6 max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    {{ $isArabic ? 'إدارة العملاء' : 'Customer Management' }}
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                    {{ $isArabic ? 'عرض وإدارة جميع العملاء المسجلين' : 'View and manage all registered customers' }}
                </p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-6 flex flex-wrap gap-3 items-center">
            <input type="text" x-model="search" @input.debounce.400ms="fetchCustomers()"
                placeholder="{{ $isArabic ? 'ابحث بالاسم أو الإيميل أو التليفون...' : 'Search by name, email or phone…' }}"
                class="filter-input flex-1 min-w-48">

            <select x-model="vipFilter" @change="fetchCustomers()" class="filter-input">
                <option value="">{{ $isArabic ? 'الكل' : 'All' }}</option>
                <option value="1">{{ $isArabic ? 'VIP فقط' : 'VIP only' }}</option>
                <option value="0">{{ $isArabic ? 'غير VIP' : 'Non-VIP' }}</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-750">
                        <th class="px-4 py-3 text-start">{{ $isArabic ? 'الاسم' : 'Name' }}</th>
                        <th class="px-4 py-3 text-start">{{ $isArabic ? 'البريد / التليفون' : 'Email / Phone' }}</th>
                        <th class="px-4 py-3 text-center">{{ $isArabic ? 'المواعيد' : 'Appointments' }}</th>
                        <th class="px-4 py-3 text-center">VIP</th>
                        <th class="px-4 py-3 text-center">{{ $isArabic ? 'إجراءات' : 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="c in customers" :key="c.id">
                        <tr class="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-xs"
                                         x-text="c.name?.charAt(0)?.toUpperCase()"></div>
                                    <span class="font-medium text-slate-900 dark:text-white" x-text="c.name"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400">
                                <div x-text="c.email"></div>
                                <div x-text="c.phone" class="text-xs text-slate-400"></div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold px-2 py-1 rounded-full"
                                      x-text="c.appointments_count ?? 0"></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="toggleVip(c)"
                                    :class="c.is_vip ? 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'"
                                    class="text-xs font-semibold px-2 py-1 rounded-full transition-colors hover:opacity-80">
                                    <span x-text="c.is_vip ? '⭐ VIP' : '—'"></span>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="viewCustomer(c.id)"
                                    class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200 text-xs font-medium me-3">
                                    {{ $isArabic ? 'عرض' : 'View' }}
                                </button>
                                <button @click="deleteCustomer(c)"
                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200 text-xs font-medium">
                                    {{ $isArabic ? 'حذف' : 'Delete' }}
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="loading">
                        <td colspan="5" class="text-center py-10 text-slate-400">
                            <svg class="animate-spin h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </td>
                    </tr>
                    <tr x-show="!loading && customers.length === 0">
                        <td colspan="5" class="text-center py-10 text-slate-400 dark:text-slate-500">
                            {{ $isArabic ? 'لا يوجد عملاء' : 'No customers found' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-between mt-4 text-sm text-slate-500 dark:text-slate-400" x-show="totalPages > 1">
            <span x-text="`{{ $isArabic ? 'الصفحة' : 'Page' }} ${page} {{ $isArabic ? 'من' : 'of' }} ${totalPages}`"></span>
            <div class="flex gap-2">
                <button @click="page--; fetchCustomers()" :disabled="page === 1"
                    class="px-3 py-1 rounded border border-slate-200 dark:border-slate-600 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    {{ $isArabic ? 'السابق' : 'Prev' }}
                </button>
                <button @click="page++; fetchCustomers()" :disabled="page === totalPages"
                    class="px-3 py-1 rounded border border-slate-200 dark:border-slate-600 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    {{ $isArabic ? 'التالي' : 'Next' }}
                </button>
            </div>
        </div>
    </div>

    {{-- Customer Detail Modal --}}
    <div x-show="showModal" x-transition class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white" x-text="selectedCustomer?.name"></h2>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">✕</button>
            </div>
            <div class="p-6" x-show="selectedCustomer">
                {{-- Stats --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6" x-show="customerStats">
                    <div class="bg-slate-50 dark:bg-slate-700 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-slate-900 dark:text-white" x-text="customerStats?.total_appointments ?? 0"></div>
                        <div class="text-xs text-slate-500">{{ $isArabic ? 'إجمالي المواعيد' : 'Total' }}</div>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/30 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400" x-text="customerStats?.completed ?? 0"></div>
                        <div class="text-xs text-slate-500">{{ $isArabic ? 'مكتملة' : 'Completed' }}</div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-red-500 dark:text-red-400" x-text="customerStats?.cancelled ?? 0"></div>
                        <div class="text-xs text-slate-500">{{ $isArabic ? 'ملغية' : 'Cancelled' }}</div>
                    </div>
                    <div class="bg-amber-50 dark:bg-amber-900/30 rounded-lg p-3 text-center">
                        <div class="text-2xl font-bold text-amber-500 dark:text-amber-400" x-text="customerStats?.avg_rating ? '⭐ ' + customerStats.avg_rating : '—'"></div>
                        <div class="text-xs text-slate-500">{{ $isArabic ? 'متوسط التقييم' : 'Avg Rating' }}</div>
                    </div>
                </div>
                {{-- Recent Appointments --}}
                <h3 class="font-semibold text-slate-700 dark:text-slate-300 mb-3 text-sm uppercase tracking-wide">
                    {{ $isArabic ? 'المواعيد الأخيرة' : 'Recent Appointments' }}
                </h3>
                <div class="space-y-2">
                    <template x-for="apt in customerAppointments" :key="apt.id">
                        <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-700 rounded-lg px-3 py-2 text-sm">
                            <span class="text-slate-700 dark:text-slate-300" x-text="apt.date"></span>
                            <span class="text-slate-500 dark:text-slate-400" x-text="apt.service?.name ?? apt.service_type"></span>
                            <span :class="{
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300': apt.status === 'completed',
                                'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300': apt.status === 'cancelled',
                                'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300': apt.status === 'confirmed',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300': apt.status === 'pending',
                            }" class="text-xs font-semibold px-2 py-0.5 rounded-full" x-text="apt.status"></span>
                        </div>
                    </template>
                    <div x-show="customerAppointments.length === 0" class="text-center text-slate-400 py-4 text-sm">
                        {{ $isArabic ? 'لا توجد مواعيد' : 'No appointments yet' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function customersApp() {
            return {
                customers: [],
                loading: false,
                search: '',
                vipFilter: '',
                page: 1,
                totalPages: 1,
                showModal: false,
                selectedCustomer: null,
                customerStats: null,
                customerAppointments: [],

                init() {
                    this.fetchCustomers();
                },

                async fetchCustomers() {
                    this.loading = true;
                    const params = new URLSearchParams({
                        page: this.page,
                        per_page: 20,
                        ...(this.search && { search: this.search }),
                        ...(this.vipFilter !== '' && { is_vip: this.vipFilter }),
                    });
                    try {
                        const res = await fetch(`{{ route('admin.api.customers.index') }}?${params}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.customers   = data.data;
                            this.totalPages  = data.pages ?? 1;
                        }
                    } catch(e) { console.error(e); }
                    this.loading = false;
                },

                async toggleVip(customer) {
                    try {
                        const res = await fetch(`/admin/api/customers/${customer.id}/vip`, {
                            method: 'PUT',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        });
                        const data = await res.json();
                        if (data.success) customer.is_vip = data.is_vip;
                    } catch(e) { console.error(e); }
                },

                async viewCustomer(id) {
                    this.selectedCustomer = null;
                    this.customerStats = null;
                    this.customerAppointments = [];
                    this.showModal = true;
                    try {
                        const [profileRes, aptsRes] = await Promise.all([
                            fetch(`/admin/api/customers/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
                            fetch(`/admin/api/customers/${id}/appointments?per_page=10`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }),
                        ]);
                        const profile = await profileRes.json();
                        const apts    = await aptsRes.json();
                        if (profile.success) {
                            this.selectedCustomer = profile.data;
                            this.customerStats    = profile.stats;
                        }
                        if (apts.success) this.customerAppointments = apts.data;
                    } catch(e) { console.error(e); }
                },

                async deleteCustomer(customer) {
                    const confirmMsg = `{{ $isArabic ? 'هل أنت متأكد من حذف العميل' : 'Are you sure you want to delete' }} ${customer.name}?`;
                    if (!confirm(confirmMsg)) return;
                    try {
                        const res = await fetch(`/admin/api/customers/${customer.id}`, {
                            method: 'DELETE',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                        });
                        const data = await res.json();
                        if (data.success) this.customers = this.customers.filter(c => c.id !== customer.id);
                    } catch(e) { console.error(e); }
                },
            };
        }
    </script>
</body>
</html>
