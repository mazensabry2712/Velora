@extends('super-admin.layout')

@section('title', 'System Settings')

@section('content')
<div x-data="systemSettings()" x-init="loadSettings()">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">إعدادات النظام</h1>
        <p class="text-slate-600 dark:text-slate-400 mt-2">إدارة إعدادات وتكوينات النظام العامة</p>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Settings Groups -->
    <div x-show="!loading" x-cloak class="space-y-6">

        <template x-for="(groupSettings, groupName) in settings" :key="groupName">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white capitalize" x-text="getGroupLabel(groupName)"></h2>
                    <button @click="addSettingToGroup(groupName)" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 text-sm font-medium">
                        + إضافة إعداد
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <template x-for="setting in groupSettings" :key="setting.id">
                        <div class="flex items-center space-x-4 space-x-reverse p-4 bg-slate-50 dark:bg-slate-900 rounded-lg">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 space-x-reverse mb-2">
                                    <label class="text-sm font-medium text-slate-900 dark:text-white" x-text="setting.key"></label>
                                    <span class="text-xs px-2 py-1 bg-slate-200 dark:bg-slate-700 rounded text-slate-600 dark:text-slate-400" x-text="setting.type"></span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 mb-2" x-text="setting.description"></p>

                                <!-- String Input -->
                                <input x-show="setting.type === 'string'" type="text" :value="setting.value"
                                       @change="updateSetting(setting.key, $event.target.value, setting.type, groupName)"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">

                                <!-- Number Input -->
                                <input x-show="setting.type === 'number'" type="number" :value="setting.value"
                                       @change="updateSetting(setting.key, $event.target.value, setting.type, groupName)"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">

                                <!-- Boolean Input -->
                                <label x-show="setting.type === 'boolean'" class="flex items-center space-x-2 space-x-reverse cursor-pointer">
                                    <input type="checkbox" :checked="setting.value == '1' || setting.value === true"
                                           @change="updateSetting(setting.key, $event.target.checked ? '1' : '0', setting.type, groupName)"
                                           class="w-4 h-4 text-indigo-600 border-slate-300 rounded">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">مفعّل</span>
                                </label>
                            </div>

                            <button @click="deleteSetting(setting.key)" class="text-red-600 dark:text-red-400 hover:text-red-700 p-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </template>

                    <div x-show="!groupSettings || groupSettings.length === 0" class="text-center py-8 text-slate-500 dark:text-slate-400">
                        لا توجد إعدادات في هذه المجموعة
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="Object.keys(settings).length === 0" class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-slate-600 dark:text-slate-400">لا توجد إعدادات حتى الآن</p>
            <button @click="showAddModal = true" class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg">
                إضافة إعداد
            </button>
        </div>
    </div>

    <!-- Add Setting Modal -->
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showAddModal = false">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="showAddModal" @click="showAddModal = false" x-transition class="fixed inset-0 bg-slate-900 bg-opacity-75"></div>

            <div x-show="showAddModal" x-transition
                 class="inline-block bg-white dark:bg-slate-800 rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">

                <form @submit.prevent="addSetting()">
                    <div class="px-6 py-5">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">إضافة إعداد جديد</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">مفتاح الإعداد *</label>
                                <input type="text" x-model="newSetting.key" required
                                       placeholder="app_name"
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">القيمة *</label>
                                <input type="text" x-model="newSetting.value" required
                                       class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">النوع *</label>
                                <select x-model="newSetting.type" required
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="string">نص</option>
                                    <option value="number">رقم</option>
                                    <option value="boolean">صح/خطأ</option>
                                    <option value="json">JSON</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">المجموعة *</label>
                                <select x-model="newSetting.group" required
                                        class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                    <option value="general">عام</option>
                                    <option value="email">البريد الإلكتروني</option>
                                    <option value="billing">الفوترة</option>
                                    <option value="notifications">الإشعارات</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">الوصف</label>
                                <textarea x-model="newSetting.description" rows="2"
                                          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 dark:bg-slate-900 px-6 py-4 flex justify-end space-x-3 space-x-reverse">
                        <button type="button" @click="showAddModal = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                            إلغاء
                        </button>
                        <button type="submit" :disabled="submitting"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg disabled:opacity-50">
                            <span x-show="!submitting">إضافة</span>
                            <span x-show="submitting">جاري الإضافة...</span>
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
function systemSettings() {
    return {
        loading: true,
        submitting: false,
        settings: {},
        showAddModal: false,
        newSetting: {
            key: '',
            value: '',
            type: 'string',
            group: 'general',
            description: ''
        },

        async loadSettings() {
            try {
                const response = await fetch('/api/super-admin/settings', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'include'
                });

                const data = await response.json();
                if (data.success) {
                    this.settings = data.data;
                }
            } catch (error) {
                console.error('Error loading settings:', error);
                showToast('فشل تحميل الإعدادات', 'error');
            } finally {
                this.loading = false;
            }
        },

        async updateSetting(key, value, type, group) {
            try {
                const response = await fetch('/api/super-admin/settings', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        settings: [{
                            key: key,
                            value: value,
                            type: type,
                            group: group
                        }]
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showToast('تم تحديث الإعداد بنجاح', 'success');
                }
            } catch (error) {
                console.error('Error updating setting:', error);
                showToast('حدث خطأ أثناء التحديث', 'error');
            }
        },

        async addSetting() {
            this.submitting = true;
            try {
                const response = await fetch('/api/super-admin/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify(this.newSetting)
                });

                const data = await response.json();
                if (data.success) {
                    this.showAddModal = false;
                    this.newSetting = { key: '', value: '', type: 'string', group: 'general', description: '' };
                    await this.loadSettings();
                    showToast('تم إضافة الإعداد بنجاح', 'success');
                } else {
                    showToast(data.message || 'حدث خطأ', 'error');
                }
            } catch (error) {
                console.error('Error adding setting:', error);
                showToast('حدث خطأ أثناء الإضافة', 'error');
            } finally {
                this.submitting = false;
            }
        },

        async deleteSetting(key) {
            if (!confirm('هل أنت متأكد من حذف هذا الإعداد؟')) {
                return;
            }

            try {
                const response = await fetch(`/api/super-admin/settings/${key}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadSettings();
                    showToast('تم حذف الإعداد بنجاح', 'success');
                }
            } catch (error) {
                console.error('Error deleting setting:', error);
                showToast('حدث خطأ أثناء الحذف', 'error');
            }
        },

        addSettingToGroup(groupName) {
            this.newSetting.group = groupName;
            this.showAddModal = true;
        },

        getGroupLabel(groupName) {
            const labels = {
                'general': 'إعدادات عامة',
                'email': 'إعدادات البريد الإلكتروني',
                'billing': 'إعدادات الفوترة',
                'notifications': 'إعدادات الإشعارات'
            };
            return labels[groupName] || groupName;
        }
    }
}
</script>
@endpush
