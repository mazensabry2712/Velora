@extends('super-admin.layout')

@section('title', 'System Notifications')

@section('content')
<div x-data="systemNotifications()" x-init="loadNotifications()">

    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white">إشعارات النظام</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-2">إرسال إشعارات لجميع الشركات أو شركات محددة</p>
        </div>
        <button @click="openAddModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3 rounded-lg shadow-lg transition duration-200 transform hover:scale-105">
            <span class="flex items-center space-x-2 space-x-reverse">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span>إنشاء إشعار جديد</span>
            </span>
        </button>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Notifications List -->
    <div x-show="!loading" x-cloak class="space-y-4">
        <template x-for="notification in notifications" :key="notification.id">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Type Badge -->
                        <span :class="{
                            'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300': notification.type === 'info',
                            'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-300': notification.type === 'success',
                            'bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-300': notification.type === 'warning',
                            'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-300': notification.type === 'danger'
                        }" class="px-3 py-1 text-xs font-semibold rounded-full" x-text="getTypeLabel(notification.type)"></span>

                        <!-- Title -->
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-3 mb-2" x-text="notification.title"></h3>

                        <!-- Message -->
                        <p class="text-slate-600 dark:text-slate-400 mb-4" x-text="notification.message"></p>

                        <!-- Meta Info -->
                        <div class="flex items-center space-x-6 space-x-reverse text-sm text-slate-500 dark:text-slate-400">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span x-text="notification.target === 'all' ? 'جميع الشركات' : 'شركات محددة (' + (notification.tenant_ids ? notification.tenant_ids.length : 0) + ')'"></span>
                            </div>

                            <div class="flex items-center space-x-2 space-x-reverse">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span x-text="formatDate(notification.created_at)"></span>
                            </div>

                            <div x-show="notification.is_sent" class="flex items-center space-x-2 space-x-reverse text-emerald-600 dark:text-emerald-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>تم الإرسال</span>
                            </div>

                            <div x-show="!notification.is_sent" class="flex items-center space-x-2 space-x-reverse text-amber-600 dark:text-amber-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>قيد الانتظار</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center space-x-2 space-x-reverse mr-4">
                        <button x-show="!notification.is_sent" @click="sendNotification(notification.id)"
                                class="bg-emerald-100 dark:bg-emerald-900 hover:bg-emerald-200 dark:hover:bg-emerald-800 text-emerald-600 dark:text-emerald-400 p-2 rounded-lg transition"
                                title="إرسال الآن">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>

                        <button @click="deleteNotification(notification.id)"
                                class="bg-red-100 dark:bg-red-900 hover:bg-red-200 dark:hover:bg-red-800 text-red-600 dark:text-red-400 p-2 rounded-lg transition"
                                title="حذف">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="notifications.length === 0" class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <p class="text-slate-600 dark:text-slate-400">لا توجد إشعارات حتى الآن</p>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.last_page > 1" class="flex items-center justify-center space-x-2 space-x-reverse mt-6">
            <button @click="loadPage(pagination.current_page - 1)" :disabled="!pagination.prev_page_url"
                    class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700">
                السابق
            </button>
            <span class="text-sm text-slate-600 dark:text-slate-400">
                صفحة <span x-text="pagination.current_page"></span> من <span x-text="pagination.last_page"></span>
            </span>
            <button @click="loadPage(pagination.current_page + 1)" :disabled="!pagination.next_page_url"
                    class="px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700">
                التالي
            </button>
        </div>
    </div>

    <!-- Add Notification Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showModal" @click="showModal = false" x-transition class="fixed inset-0 bg-slate-900 bg-opacity-75"></div>

            <div x-show="showModal" x-transition
                 class="inline-block bg-white dark:bg-slate-800 rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:max-w-2xl sm:w-full">

                <form @submit.prevent="createNotification()">
                    <div class="px-6 py-5">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">إنشاء إشعار جديد</h3>

                        <div class="space-y-4">
                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">عنوان الإشعار *</label>
                                <input type="text" x-model="newNotification.title" required
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>

                            <!-- Message -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">نص الرسالة *</label>
                                <textarea x-model="newNotification.message" required rows="4"
                                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
                            </div>

                            <!-- Type -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">نوع الإشعار *</label>
                                <select x-model="newNotification.type" required
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="info">معلومات</option>
                                    <option value="success">نجاح</option>
                                    <option value="warning">تحذير</option>
                                    <option value="danger">خطر</option>
                                </select>
                            </div>

                            <!-- Target -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">المستهدفون *</label>
                                <select x-model="newNotification.target" required
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="all">جميع الشركات</option>
                                    <option value="specific">شركات محددة</option>
                                </select>
                            </div>

                            <!-- Tenant IDs (if specific) -->
                            <div x-show="newNotification.target === 'specific'">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">معرفات الشركات (مفصولة بفواصل)</label>
                                <input type="text" x-model="tenantIdsText" placeholder="tenant-uuid-1, tenant-uuid-2"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">أدخل معرفات الشركات مفصولة بفواصل</p>
                            </div>

                            <!-- Scheduled At -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">جدولة الإرسال (اختياري)</label>
                                <input type="datetime-local" x-model="newNotification.scheduled_at"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">اترك فارغاً للإرسال فوراً</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-900 px-6 py-4 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" @click="showModal = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                            إلغاء
                        </button>
                        <button type="submit" :disabled="submitting"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg disabled:opacity-50">
                            <span x-show="!submitting">إنشاء وإرسال</span>
                            <span x-show="submitting">جاري الإنشاء...</span>
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
function systemNotifications() {
    return {
        loading: true,
        submitting: false,
        notifications: [],
        showModal: false,
        tenantIdsText: '',
        newNotification: {
            title: '',
            message: '',
            type: 'info',
            target: 'all',
            tenant_ids: [],
            scheduled_at: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            prev_page_url: null,
            next_page_url: null
        },

        async loadNotifications(page = 1) {
            this.loading = true;
            try {
                const response = await fetch(`/api/super-admin/notifications?page=${page}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'include'
                });

                const data = await response.json();
                if (data.success) {
                    this.notifications = data.data.data;
                    this.pagination = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        prev_page_url: data.data.prev_page_url,
                        next_page_url: data.data.next_page_url
                    };
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
                showToast('فشل تحميل الإشعارات', 'error');
            } finally {
                this.loading = false;
            }
        },

        openAddModal() {
            this.newNotification = {
                title: '',
                message: '',
                type: 'info',
                target: 'all',
                tenant_ids: [],
                scheduled_at: ''
            };
            this.tenantIdsText = '';
            this.showModal = true;
        },

        async createNotification() {
            this.submitting = true;

            // Parse tenant IDs if specific
            if (this.newNotification.target === 'specific') {
                this.newNotification.tenant_ids = this.tenantIdsText.split(',').map(id => id.trim()).filter(id => id);
            }

            try {
                const response = await fetch('/api/super-admin/notifications', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(this.newNotification)
                });

                const data = await response.json();
                if (data.success) {
                    this.showModal = false;
                    await this.loadNotifications();
                    showToast('تم إنشاء الإشعار بنجاح!', 'success');
                } else {
                    showToast(data.message || 'حدث خطأ', 'error');
                }
            } catch (error) {
                console.error('Error creating notification:', error);
                showToast('حدث خطأ أثناء الإنشاء', 'error');
            } finally {
                this.submitting = false;
            }
        },

        async sendNotification(id) {
            if (!confirm('هل تريد إرسال هذا الإشعار الآن؟')) {
                return;
            }

            try {
                const response = await fetch(`/api/super-admin/notifications/${id}/send`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadNotifications();
                    showToast('تم إرسال الإشعار بنجاح', 'success');
                } else {
                    showToast(data.message || 'حدث خطأ', 'error');
                }
            } catch (error) {
                console.error('Error sending notification:', error);
                showToast('حدث خطأ أثناء الإرسال', 'error');
            }
        },

        async deleteNotification(id) {
            if (!confirm('هل أنت متأكد من حذف هذا الإشعار؟')) {
                return;
            }

            try {
                const response = await fetch(`/api/super-admin/notifications/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadNotifications();
                    showToast('تم حذف الإشعار بنجاح', 'success');
                }
            } catch (error) {
                console.error('Error deleting notification:', error);
                showToast('حدث خطأ أثناء الحذف', 'error');
            }
        },

        loadPage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadNotifications(page);
            }
        },

        getTypeLabel(type) {
            const labels = {
                'info': 'معلومات',
                'success': 'نجاح',
                'warning': 'تحذير',
                'danger': 'خطر'
            };
            return labels[type] || type;
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
