@extends('super-admin.layout')

@section('title', 'إدارة الشركات')

@section('content')
<div x-data="tenantsManager()" x-init="loadTenants()">

    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">إدارة الشركات</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-2">إضافة وإدارة الشركات المسجلة</p>
        </div>
        <button @click="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-lg shadow-lg transition duration-200 transform hover:scale-105">
            <span class="flex items-center space-x-2 space-x-reverse">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>إضافة شركة جديدة</span>
            </span>
        </button>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Tenants List -->
    <div x-show="!loading" x-cloak class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">اسم الشركة</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">الدومين</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">البريد الإلكتروني</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">الحالة</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">تاريخ الإنشاء</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-slate-700 dark:text-slate-300 uppercase">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <template x-for="tenant in tenants" :key="tenant.id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3 space-x-reverse">
                                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center">
                                        <span class="text-indigo-600 dark:text-indigo-400 font-semibold" x-text="tenant.name.charAt(0)"></span>
                                    </div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white" x-text="tenant.name"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400" x-text="tenant.domain"></td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400" x-text="tenant.email || '-'"></td>
                            <td class="px-6 py-4">
                                <button @click="toggleStatus(tenant.id, tenant.active)"
                                        :class="tenant.active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300'"
                                        class="px-3 py-1 text-xs font-semibold rounded-full hover:opacity-80 transition">
                                    <span x-text="tenant.active ? 'نشط' : 'غير نشط'"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400" x-text="formatDate(tenant.created_at)"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <button @click="viewTenant(tenant)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 p-2 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition" title="عرض">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button @click="deleteTenant(tenant.id)" class="text-red-600 dark:text-red-400 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition" title="حذف">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Tenant Modal -->
    <div x-show="showAddModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showAddModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div x-show="showAddModal"
                 @click="showAddModal = false"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-slate-900 bg-opacity-75"></div>

            <!-- Modal -->
            <div x-show="showAddModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <form @submit.prevent="addTenant()">
                    <div class="px-6 py-5">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">إضافة شركة جديدة</h3>

                        <!-- Tenant Name -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">اسم الشركة *</label>
                            <input type="text"
                                   x-model="newTenant.name"
                                   required
                                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                   placeholder="شركة المواعيد">
                        </div>

                        <!-- Domain -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">الدومين *</label>
                            <input type="text"
                                   x-model="newTenant.domain"
                                   required
                                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                   placeholder="company.localhost">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">مثال: company.localhost أو subdomain.yourdomain.com</p>
                        </div>

                        <!-- Email (Optional) -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">البريد الإلكتروني (اختياري)</label>
                            <input type="email"
                                   x-model="newTenant.email"
                                   class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                   placeholder="admin@company.com">
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">إذا لم يتم إدخاله، سيتم توليده تلقائياً</p>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-900 px-6 py-4 flex justify-end space-x-3 space-x-reverse">
                        <button type="button"
                                @click="showAddModal = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                            إلغاء
                        </button>
                        <button type="submit"
                                :disabled="submitting"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg transition disabled:opacity-50">
                            <span x-show="!submitting">إضافة</span>
                            <span x-show="submitting">جاري الإضافة...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Credentials Modal -->
    <div x-show="showCredentialsModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showCredentialsModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showCredentialsModal"
                 @click="showCredentialsModal = false"
                 class="fixed inset-0 transition-opacity bg-slate-900 bg-opacity-75"></div>

            <div x-show="showCredentialsModal"
                 class="inline-block bg-white dark:bg-slate-800 rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                <div class="px-6 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">بيانات الدخول</h3>
                        <button @click="showCredentialsModal = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4 mb-4">
                        <p class="text-sm text-emerald-800 dark:text-emerald-300 mb-2">✅ تم إنشاء الشركة بنجاح!</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400">احفظ هذه البيانات، لن تظهر مرة أخرى.</p>
                    </div>

                    <div class="space-y-3">
                        <div class="bg-slate-100 dark:bg-slate-700 rounded-lg p-4">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">البريد الإلكتروني</p>
                            <p class="text-sm font-mono text-slate-900 dark:text-white" x-text="credentials.email"></p>
                        </div>

                        <div class="bg-slate-100 dark:bg-slate-700 rounded-lg p-4">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">كلمة المرور</p>
                            <p class="text-sm font-mono text-slate-900 dark:text-white" x-text="credentials.password"></p>
                        </div>

                        <div class="bg-slate-100 dark:bg-slate-700 rounded-lg p-4">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">رابط الدخول</p>
                            <a :href="credentials.login_url"
                               target="_blank"
                               class="text-sm font-mono text-indigo-600 dark:text-indigo-400 hover:underline"
                               x-text="credentials.login_url"></a>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-900 px-6 py-4 flex justify-end">
                    <button @click="showCredentialsModal = false"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg transition">
                        تم
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function tenantsManager() {
    return {
        loading: true,
        submitting: false,
        tenants: [],
        showAddModal: false,
        showCredentialsModal: false,
        credentials: {},
        newTenant: {
            name: '',
            domain: '',
            email: ''
        },

        async loadTenants() {
            try {
                const response = await fetch('/api/super-admin/tenants', {
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
                    this.tenants = data.data.data;
                }
            } catch (error) {
                console.error('Error loading tenants:', error);
                showToast('فشل تحميل الشركات', 'error');
            } finally {
                this.loading = false;
            }
        },

        openAddModal() {
            this.newTenant = { name: '', domain: '', email: '' };
            this.showAddModal = true;
        },

        async addTenant() {
            this.submitting = true;
            try {
                const response = await fetch('/api/super-admin/tenants', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(this.newTenant)
                });

                const data = await response.json();

                if (data.success) {
                    this.credentials = data.data.credentials;
                    this.showAddModal = false;
                    this.showCredentialsModal = true;
                    await this.loadTenants();
                    showToast('تم إضافة الشركة بنجاح!', 'success');
                } else {
                    showToast(data.message || 'حدث خطأ', 'error');
                }
            } catch (error) {
                console.error('Error adding tenant:', error);
                showToast('حدث خطأ أثناء إضافة الشركة', 'error');
            } finally {
                this.submitting = false;
            }
        },

        async toggleStatus(id, currentStatus) {
            try {
                const response = await fetch(`/api/super-admin/tenants/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadTenants();
                    showToast('تم تحديث حالة الشركة', 'success');
                }
            } catch (error) {
                console.error('Error toggling status:', error);
            }
        },

        async deleteTenant(id) {
            if (!confirm('هل أنت متأكد من حذف هذه الشركة؟ سيتم حذف جميع البيانات!')) {
                return;
            }

            try {
                const response = await fetch(`/api/super-admin/tenants/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadTenants();
                    showToast('تم حذف الشركة بنجاح', 'success');
                }
            } catch (error) {
                console.error('Error deleting tenant:', error);
            }
        },

        viewTenant(tenant) {
            alert(`معلومات: ${tenant.name}\nالدومين: ${tenant.domain}`);
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString('ar-EG');
        }
    }
}
</script>
@endpush
