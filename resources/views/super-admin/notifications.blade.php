@extends('super-admin.layout')

@section('title', 'إشعارات النظام')
@section('breadcrumb')<span class="text-slate-700 dark:text-slate-200 font-medium">إشعارات النظام</span>@endsection

@section('content')
<div x-data="systemNotifications()" x-init="loadNotifications()">

    <!-- Header -->
    <div class="mb-8 flex flex-wrap gap-4 justify-between items-center">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">إشعارات النظام</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">إرسال إشعارات لجميع الشركات أو شركات محددة</p>
        </div>
        <button @click="openAddModal()"
                class="flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-indigo-200 dark:shadow-indigo-900 transition-all hover:-translate-y-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            إنشاء إشعار جديد
        </button>
    </div>

    <!-- Skeleton Loading -->
    <div x-show="loading" class="space-y-4">
        <template x-for="i in 4" :key="i">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1 space-y-3">
                        <div class="h-5 w-20 bg-slate-200 dark:bg-slate-700 rounded-full skeleton"></div>
                        <div class="h-5 w-2/3 bg-slate-200 dark:bg-slate-700 rounded skeleton"></div>
                        <div class="h-4 w-full bg-slate-200 dark:bg-slate-700 rounded skeleton"></div>
                        <div class="h-4 w-1/2 bg-slate-200 dark:bg-slate-700 rounded skeleton"></div>
                    </div>
                    <div class="flex gap-2 mr-4">
                        <div class="h-9 w-9 bg-slate-200 dark:bg-slate-700 rounded-lg skeleton"></div>
                        <div class="h-9 w-9 bg-slate-200 dark:bg-slate-700 rounded-lg skeleton"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Notifications List -->
    <div x-show="!loading" x-cloak class="space-y-4">
        <template x-for="notification in notifications" :key="notification.id">
            <div class="card-animate bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 transition-all hover:shadow-md group"
                 :class="{
                    'border-r-4 border-r-blue-500':   notification.type === 'info',
                    'border-r-4 border-r-emerald-500': notification.type === 'success',
                    'border-r-4 border-r-amber-500':   notification.type === 'warning',
                    'border-r-4 border-r-red-500':     notification.type === 'danger'
                 }">
                <div class="flex items-start justify-between gap-4">
                    <!-- Type Icon -->
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                         :class="{
                            'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400':     notification.type === 'info',
                            'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400': notification.type === 'success',
                            'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400': notification.type === 'warning',
                            'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400':         notification.type === 'danger'
                         }">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <!-- Top row: badge + status -->
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full"
                                  :class="{
                                    'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300':     notification.type === 'info',
                                    'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300': notification.type === 'success',
                                    'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300': notification.type === 'warning',
                                    'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300':         notification.type === 'danger'
                                  }" x-text="getTypeLabel(notification.type)"></span>
                            <span x-show="notification.is_sent"
                                  class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                تم الإرسال
                            </span>
                            <span x-show="!notification.is_sent"
                                  class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                بانتظار
                            </span>
                        </div>

                        <h3 class="font-bold text-slate-900 dark:text-white mb-1" x-text="notification.title"></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-3" x-text="notification.message"></p>

                        <div class="flex items-center gap-4 text-xs text-slate-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span x-text="notification.target === 'all' ? 'جميع الشركات' : 'شركات محددة (' + (notification.tenant_ids ? notification.tenant_ids.length : 0) + ')'"></span>
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span x-text="formatDate(notification.created_at)"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <button x-show="!notification.is_sent" @click="sendNotification(notification.id)"
                                class="p-2 text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition tooltip" data-tip="إرسال الآن">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                        <button @click="deleteNotification(notification.id)"
                                class="p-2 text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition tooltip" data-tip="حذف">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="notifications.length === 0" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 py-16 text-center">
            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700/50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-700 dark:text-slate-300">لا توجد إشعارات</p>
            <p class="text-sm text-slate-400 mt-1">أنشئ إشعارًا جديدًا لإرساله للشركات</p>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.last_page > 1" class="flex items-center justify-center gap-2 mt-6">
            <button @click="loadPage(pagination.current_page - 1)" :disabled="!pagination.prev_page_url"
                    class="px-4 py-2 text-sm font-medium border border-slate-200 dark:border-slate-600 rounded-xl disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                السابق
            </button>
            <span class="text-sm text-slate-500 dark:text-slate-400 px-3">
                <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
            </span>
            <button @click="loadPage(pagination.current_page + 1)" :disabled="!pagination.next_page_url"
                    class="px-4 py-2 text-sm font-medium border border-slate-200 dark:border-slate-600 rounded-xl disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                التالي
            </button>
        </div>
    </div>

    <!-- Add Notification Modal -->
    <div x-show="showModal" x-cloak @keydown.escape.window="showModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>

        <div x-show="showModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700 sticky top-0 bg-white dark:bg-slate-800 z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">إنشاء إشعار جديد</h3>
                </div>
                <button @click="showModal = false" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form @submit.prevent="createNotification()">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">عنوان الإشعار <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newNotification.title" required
                               class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white transition"
                               placeholder="عنوان الإشعار">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">نص الرسالة <span class="text-red-500">*</span></label>
                        <textarea x-model="newNotification.message" required rows="4"
                                  class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white transition resize-none"
                                  placeholder="نص الرسالة..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">نوع الإشعار <span class="text-red-500">*</span></label>
                            <select x-model="newNotification.type" required
                                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
                                <option value="info">💙 معلومات</option>
                                <option value="success">💚 نجاح</option>
                                <option value="warning">🟡 تحذير</option>
                                <option value="danger">🔴 خطر</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">المستهدفون <span class="text-red-500">*</span></label>
                            <select x-model="newNotification.target" required
                                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
                                <option value="all">🌐 جميع الشركات</option>
                                <option value="specific">🎯 شركات محددة</option>
                            </select>
                        </div>
                    </div>

                    <div x-show="newNotification.target === 'specific'">
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">معرفات الشركات</label>
                        <input type="text" x-model="tenantIdsText" placeholder="tenant-uuid-1, tenant-uuid-2"
                               class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white transition font-mono">
                        <p class="text-xs text-slate-400 mt-1">أدخل معرفات الشركات مفصولة بفواصل</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">جدولة الإرسال <span class="text-slate-400 font-normal">(اختياري)</span></label>
                        <input type="datetime-local" x-model="newNotification.scheduled_at"
                               class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-slate-50 dark:bg-slate-700/50 text-slate-900 dark:text-white transition">
                        <p class="text-xs text-slate-400 mt-1">اتركه فارغاً للإرسال فوراً</p>
                    </div>
                </div>

                <div class="flex gap-3 justify-end p-6 pt-0">
                    <button type="button" @click="showModal = false"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl transition">
                        إلغاء
                    </button>
                        <button type="submit" :disabled="submitting"
                    <button type="submit" :disabled="submitting"
                            class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="submitting ? 'جاري الإنشاء...' : 'إنشاء وإرسال'"></span>
                    </button>
                </div>
            </form>
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
