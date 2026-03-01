@extends('super-admin.layout')

@section('title', 'خطط الاشتراك')
@section('breadcrumb')<span class="text-slate-700 dark:text-slate-200 font-medium">خطط الاشتراك</span>@endsection

@section('content')
<div x-data="plansManager()" x-init="loadPlans()">

    <!-- Header -->
    <div class="mb-8 flex flex-wrap gap-4 justify-between items-center">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">خطط الاشتراك</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">إدارة خطط الاشتراك والتسعير</p>
        </div>
        <button @click="openAddModal()"
                class="flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-indigo-200 dark:shadow-indigo-900 transition-all hover:-translate-y-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            إضافة خطة جديدة
        </button>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="i in 3" :key="i">
            <div class="h-64 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 skeleton"></div>
        </template>
    </div>

    <!-- Plans Grid -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="plan in plans" :key="plan.id">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border-2 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                 :class="plan.is_popular ? 'border-indigo-400 dark:border-indigo-500' : 'border-slate-200 dark:border-slate-700'">

                <!-- Popular Badge -->
                <div x-show="plan.is_popular" class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white text-center py-2 rounded-t-2xl text-sm font-bold tracking-wide">
                    ✨ الأكثر شعبية
                </div>

                <div class="p-6">
                    <!-- Plan Header -->
                    <div class="text-center mb-6">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2" x-text="plan.name"></h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400" x-text="plan.description"></p>
                    </div>

                    <!-- Price -->
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold text-indigo-600 dark:text-indigo-400">$<span x-text="plan.price"></span></span>
                        <span class="text-slate-600 dark:text-slate-400" x-text="plan.billing_cycle === 'yearly' ? '/سنويًا' : '/شهريًا'"></span>
                        <p class="text-sm text-emerald-600 dark:text-emerald-400 mt-2" x-show="plan.trial_days > 0">
                            <span x-text="plan.trial_days"></span> يوم تجربة مجانية
                        </p>
                    </div>

                    <!-- Features -->
                    <div class="mb-6 space-y-3">
                        <template x-for="feature in plan.features" :key="feature">
                            <div class="flex items-center space-x-2 space-x-reverse text-sm text-slate-700 dark:text-slate-300">
                                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-text="feature"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Stats -->
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 mb-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">المشتركين النشطين:</span>
                            <span class="font-bold text-slate-900 dark:text-white" x-text="plan.active_subscriptions || 0"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500 dark:text-slate-400">الحالة:</span>
                            <span :class="plan.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'"
                                  class="font-bold" x-text="plan.is_active ? 'نشطة' : 'غير نشطة'"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button @click="editPlan(plan)" class="flex-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold px-4 py-2 rounded-xl text-sm transition">
                            تعديل
                        </button>
                        <button @click="toggleStatus(plan.id, plan.is_active)"
                                :class="plan.is_active ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 hover:bg-amber-200' : 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200'"
                                class="flex-1 font-semibold px-4 py-2 rounded-xl text-sm transition">
                            <span x-text="plan.is_active ? 'تعطيل' : 'تفعيل'"></span>
                        </button>
                        <button @click="confirmDeletePlan(plan.id, plan.name)" class="bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 text-red-500 dark:text-red-400 p-2 rounded-xl transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="!loading && plans.length === 0" x-cloak
             class="col-span-3 bg-white dark:bg-slate-800 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 p-16 text-center">
            <svg class="w-16 h-16 text-slate-300 dark:text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-lg font-semibold text-slate-600 dark:text-slate-400 mb-1">لا توجد خطط بعد</p>
            <p class="text-sm text-slate-400 dark:text-slate-500 mb-5">أضف أول خطة اشتراك للنظام</p>
            <button @click="openAddModal()"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                إضافة خطة جديدة
            </button>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak @keydown.escape.window="showDeleteModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
        <div x-show="showDeleteModal"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="w-14 h-14 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">حذف الخطة</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">هل أنت متأكد من حذف خطة "<span class="font-semibold text-slate-700 dark:text-slate-200" x-text="deleteTargetName"></span>"? لا يمكن التراجع.</p>
            <div class="flex gap-3">
                <button @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl transition">إلغاء</button>
                <button @click="deletePlan()"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition">حذف</button>
            </div>
        </div>
    </div>
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showModal" @click="showModal = false" x-transition class="fixed inset-0 bg-slate-900 bg-opacity-75"></div>

            <div x-show="showModal" x-transition
                 class="inline-block bg-white dark:bg-slate-800 rounded-2xl text-right overflow-hidden shadow-2xl transform transition-all sm:max-w-2xl sm:w-full max-h-[90vh] overflow-y-auto">

                <form @submit.prevent="savePlan()">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white" x-text="editingPlan ? 'تعديل الخطة' : 'إضافة خطة جديدة'"></h3>
                    </div>

                    <div class="px-6 py-5">
                        <div class="space-y-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">اسم الخطة *</label>
                                <input type="text" x-model="formData.name" required
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 dark:text-white transition">
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">الوصف</label>
                                <textarea x-model="formData.description" rows="2"
                                          class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 dark:text-white transition"></textarea>
                            </div>

                            <!-- Price & Billing Cycle -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">السعر ($) *</label>
                                    <input type="number" step="0.01" x-model="formData.price" required
                                           class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 dark:text-white transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">الدورة *</label>
                                    <select x-model="formData.billing_cycle" required
                                            class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 dark:text-white transition">
                                        <option value="monthly">شهري</option>
                                        <option value="yearly">سنوي</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Limits -->
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">عدد المستخدمين</label>
                                    <input type="number" x-model="formData.max_users" placeholder="غير محدود"
                                           class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 dark:text-white transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">المواعيد شهريًا</label>
                                    <input type="number" x-model="formData.max_appointments" placeholder="غير محدود"
                                           class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 dark:text-white transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">المساحة (MB)</label>
                                    <input type="number" x-model="formData.storage_limit" placeholder="غير محدود"
                                           class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 dark:text-white transition">
                                </div>
                            </div>

                            <!-- Trial Days -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">أيام التجربة المجانية</label>
                                <input type="number" x-model="formData.trial_days"
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 dark:text-white transition">
                            </div>

                            <!-- Features -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">الميزات (سطر لكل ميزة)</label>
                                <textarea x-model="featuresText" rows="4" placeholder="ميزة 1&#10;ميزة 2&#10;ميزة 3"
                                          class="w-full px-3 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-slate-700/50 dark:text-white transition"></textarea>
                            </div>

                            <!-- Checkboxes -->
                            <div class="flex items-center gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" x-model="formData.is_popular" class="w-4 h-4 text-indigo-600 border-slate-300 rounded">
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">خطة مميزة</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="showModal = false"
                                class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-xl transition">
                            إلغاء
                        </button>
                        <button type="submit" :disabled="submitting"
                                class="flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold px-6 py-2.5 rounded-xl transition shadow-md shadow-indigo-200 dark:shadow-indigo-900 disabled:opacity-50">
                            <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-show="!submitting" x-text="editingPlan ? 'تحديث' : 'إضافة'"></span>
                            <span x-show="submitting">جاري الحفظ...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function plansManager() {
    return {
        loading: true,
        submitting: false,
        plans: [],
        showModal: false,
        showDeleteModal: false,
        deleteTargetId: null,
        deleteTargetName: '',
        editingPlan: null,
        featuresText: '',
        formData: {
            name: '',
            description: '',
            price: '',
            billing_cycle: 'monthly',
            max_users: '',
            max_appointments: '',
            storage_limit: '',
            trial_days: 14,
            features: [],
            is_popular: false,
        },

        async loadPlans() {
            try {
                const response = await fetch('/api/super-admin/subscription-plans', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'include'
                });

                const data = await response.json();
                if (data.success) {
                    this.plans = data.data;
                }
            } catch (error) {
                console.error('Error loading plans:', error);
                showToast('فشل تحميل الخطط', 'error');
            } finally {
                this.loading = false;
            }
        },

        openAddModal() {
            this.editingPlan = null;
            this.formData = {
                name: '',
                description: '',
                price: '',
                billing_cycle: 'monthly',
                max_users: '',
                max_appointments: '',
                storage_limit: '',
                trial_days: 14,
                features: [],
                is_popular: false,
            };
            this.featuresText = '';
            this.showModal = true;
        },

        editPlan(plan) {
            this.editingPlan = plan;
            this.formData = {
                name: plan.name,
                description: plan.description,
                price: plan.price,
                billing_cycle: plan.billing_cycle,
                max_users: plan.max_users || '',
                max_appointments: plan.max_appointments || '',
                storage_limit: plan.storage_limit || '',
                trial_days: plan.trial_days,
                features: plan.features || [],
                is_popular: plan.is_popular,
            };
            this.featuresText = (plan.features || []).join('\n');
            this.showModal = true;
        },

        async savePlan() {
            this.submitting = true;

            // Convert features text to array
            this.formData.features = this.featuresText.split('\n').filter(f => f.trim());

            try {
                const url = this.editingPlan
                    ? `/api/super-admin/subscription-plans/${this.editingPlan.id}`
                    : '/api/super-admin/subscription-plans';

                const method = this.editingPlan ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(this.formData)
                });

                const data = await response.json();

                if (data.success) {
                    this.showModal = false;
                    await this.loadPlans();
                    showToast(this.editingPlan ? 'تم تحديث الخطة بنجاح!' : 'تم إضافة الخطة بنجاح!', 'success');
                } else {
                    const errMsg = data.errors
                        ? Object.values(data.errors).flat().join(' • ')
                        : (data.message || 'حدث خطأ');
                    showToast(errMsg, 'error');
                }
            } catch (error) {
                console.error('Error saving plan:', error);
                showToast('حدث خطأ أثناء الحفظ', 'error');
            } finally {
                this.submitting = false;
            }
        },

        async toggleStatus(id, currentStatus) {
            try {
                const response = await fetch(`/api/super-admin/subscription-plans/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadPlans();
                    showToast('تم تحديث حالة الخطة', 'success');
                } else {
                    showToast(data.message || 'فشل تحديث الحالة', 'error');
                }
            } catch (error) {
                console.error('Error toggling status:', error);
                showToast('حدث خطأ أثناء تحديث الحالة', 'error');
            }
        },

        confirmDeletePlan(id, name) {
            this.deleteTargetId = id;
            this.deleteTargetName = name;
            this.showDeleteModal = true;
        },

        async deletePlan() {
            const id = this.deleteTargetId;
            this.showDeleteModal = false;
            try {
                const response = await fetch(`/api/super-admin/subscription-plans/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadPlans();
                    showToast('تم حذف الخطة بنجاح', 'success');
                } else {
                    showToast(data.message || 'لا يمكن حذف الخطة', 'error');
                }
            } catch (error) {
                console.error('Error deleting plan:', error);
                showToast('حدث خطأ أثناء الحذف', 'error');
            }
        }
    }
}
</script>
@endpush
