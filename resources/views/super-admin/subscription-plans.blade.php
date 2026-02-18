@extends('super-admin.layout')

@section('title', 'Subscription Plans')

@section('content')
<div x-data="plansManager()" x-init="loadPlans()">

    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">خطط الاشتراك</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-2">إدارة خطط الاشتراك والتسعير</p>
        </div>
        <button @click="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-lg shadow-lg transition duration-200 transform hover:scale-105">
            <span class="flex items-center space-x-2 space-x-reverse">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>إضافة خطة جديدة</span>
            </span>
        </button>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Plans Grid -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="plan in plans" :key="plan.id">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border-2 transition duration-200 hover:shadow-xl"
                 :class="plan.is_popular ? 'border-indigo-500' : 'border-slate-200 dark:border-slate-700'">

                <!-- Popular Badge -->
                <div x-show="plan.is_popular" class="bg-indigo-500 text-white text-center py-2 rounded-t-lg font-semibold">
                    🌟 الأكثر شعبية
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
                        <span class="text-slate-600 dark:text-slate-400">/شهريًا</span>
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
                    <div class="bg-slate-50 dark:bg-slate-900 rounded-lg p-4 mb-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">المشتركين النشطين:</span>
                            <span class="font-semibold text-slate-900 dark:text-white" x-text="plan.active_subscriptions || 0"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">الحالة:</span>
                            <span :class="plan.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                                  class="font-semibold" x-text="plan.is_active ? 'نشطة' : 'غير نشطة'"></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <button @click="editPlan(plan)" class="flex-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-900 dark:text-white font-semibold px-4 py-2 rounded-lg transition">
                            تعديل
                        </button>
                        <button @click="toggleStatus(plan.id, plan.is_active)"
                                :class="plan.is_active ? 'bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-300 hover:bg-amber-200' : 'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-200'"
                                class="flex-1 font-semibold px-4 py-2 rounded-lg transition">
                            <span x-text="plan.is_active ? 'تعطيل' : 'تفعيل'"></span>
                        </button>
                        <button @click="deletePlan(plan.id)" class="bg-red-100 dark:bg-red-900 hover:bg-red-200 dark:hover:bg-red-800 text-red-600 dark:text-red-400 p-2 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Add/Edit Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showModal" @click="showModal = false" x-transition class="fixed inset-0 bg-slate-900 bg-opacity-75"></div>

            <div x-show="showModal" x-transition
                 class="inline-block bg-white dark:bg-slate-800 rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:max-w-2xl sm:w-full max-h-[90vh] overflow-y-auto">

                <form @submit.prevent="savePlan()">
                    <div class="px-6 py-5">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-6" x-text="editingPlan ? 'تعديل الخطة' : 'إضافة خطة جديدة'"></h3>

                        <div class="space-y-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">اسم الخطة *</label>
                                <input type="text" x-model="formData.name" required
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">الوصف</label>
                                <textarea x-model="formData.description" rows="2"
                                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
                            </div>

                            <!-- Price & Billing Cycle -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">السعر ($) *</label>
                                    <input type="number" step="0.01" x-model="formData.price" required
                                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">الدورة *</label>
                                    <select x-model="formData.billing_cycle" required
                                            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                        <option value="monthly">شهري</option>
                                        <option value="yearly">سنوي</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Limits -->
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">عدد المستخدمين</label>
                                    <input type="number" x-model="formData.max_users" placeholder="غير محدود"
                                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">المواعيد شهريًا</label>
                                    <input type="number" x-model="formData.max_appointments" placeholder="غير محدود"
                                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">المساحة (MB)</label>
                                    <input type="number" x-model="formData.storage_limit" placeholder="غير محدود"
                                           class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                </div>
                            </div>

                            <!-- Trial Days -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">أيام التجربة المجانية</label>
                                <input type="number" x-model="formData.trial_days"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>

                            <!-- Features -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">الميزات (سطر لكل ميزة)</label>
                                <textarea x-model="featuresText" rows="4" placeholder="ميزة 1&#10;ميزة 2&#10;ميزة 3"
                                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
                            </div>

                            <!-- Checkboxes -->
                            <div class="flex items-center space-x-6 space-x-reverse">
                                <label class="flex items-center space-x-2 space-x-reverse">
                                    <input type="checkbox" x-model="formData.is_popular" class="w-4 h-4 text-indigo-600 border-slate-300 rounded">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">خطة مميزة</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-900 px-6 py-4 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" @click="showModal = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                            إلغاء
                        </button>
                        <button type="submit" :disabled="submitting"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg transition disabled:opacity-50">
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
                    showToast(data.message || 'حدث خطأ', 'error');
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
                }
            } catch (error) {
                console.error('Error toggling status:', error);
            }
        },

        async deletePlan(id) {
            if (!confirm('هل أنت متأكد من حذف هذه الخطة؟')) {
                return;
            }

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
            }
        }
    }
}
</script>
@endpush
