<div x-data="{ period: '{{ $period }}' }" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">

    <form method="GET" action="{{ route('admin.reports') }}"
        class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <select name="period" x-model="period" @change="$el.form.submit()"
            class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="today" @selected($period === 'today')>{{ __('Today') }}</option>
            <option value="week" @selected($period === 'week')>{{ __('This Week') }}</option>
            <option value="month" @selected($period === 'month')>{{ __('This Month') }}</option>
            <option value="year" @selected($period === 'year')>{{ __('This Year') }}</option>
            <option value="all" @selected($period === 'all')>{{ __('All Time') }}</option>
            <option value="custom" @selected($period === 'custom')>{{ __('Custom Range') }}</option>
        </select>

        <template x-if="period === 'custom'">
            <div class="flex items-center gap-2">
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <span class="text-slate-400 text-sm">{{ __('to') }}</span>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit"
                    class="px-3 py-2 bg-slate-100 dark:bg-slate-700 dark:text-slate-200 rounded-lg text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    {{ __('Apply') }}
                </button>
            </div>
        </template>
    </form>

    <a href="{{ route('admin.reports.export.appointments', ['period' => $period, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
        class="w-full sm:w-auto justify-center bg-indigo-600 dark:bg-indigo-700 text-white px-4 py-2.5 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 flex items-center gap-2 text-sm font-medium transition-colors shadow-sm">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
        </svg>
        {{ __('Export to Excel') }}
    </a>
</div>
