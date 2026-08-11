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
