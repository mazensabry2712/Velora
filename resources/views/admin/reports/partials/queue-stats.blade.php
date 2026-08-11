<!-- Queue Statistics (live snapshot, not scoped to the selected period) -->
<div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Queue Statistics') }}</h3>
    <div class="space-y-4">
        <div class="flex justify-between items-center p-3 bg-indigo-50 dark:bg-indigo-900 rounded">
            <span class="text-slate-700 dark:text-slate-200">{{ __('Waiting') }}</span>
            <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $queueStats['waiting'] }}</span>
        </div>
        <div class="flex justify-between items-center p-3 bg-emerald-50 dark:bg-emerald-900 rounded">
            <span class="text-slate-700 dark:text-slate-200">{{ __('Being Served') }}</span>
            <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $queueStats['serving'] }}</span>
        </div>
        <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700 rounded">
            <span class="text-slate-700 dark:text-slate-200">{{ __('Completed') }}</span>
            <span class="text-2xl font-bold text-slate-600 dark:text-slate-400">{{ $queueStats['completed'] }}</span>
        </div>
        <div class="flex justify-between items-center p-3 bg-amber-50 dark:bg-amber-900 rounded">
            <span class="text-slate-700 dark:text-slate-200">{{ __('Priority Customers') }}</span>
            <span class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $queueStats['priority'] }}</span>
        </div>
    </div>
</div>
