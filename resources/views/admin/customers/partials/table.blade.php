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
