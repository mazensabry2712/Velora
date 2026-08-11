<div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Staff Performance') }}</h3>
    @if($staffPerformance->isNotEmpty())
        <div class="space-y-3">
            @foreach($staffPerformance as $staff)
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700 rounded">
                    <div>
                        <p class="font-medium text-slate-900 dark:text-slate-100">{{ $staff->name }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $staff->role?->name ?? __('Staff Member') }}</p>
                    </div>
                    <div>
                        <span class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $staff->appointments_count }}</span>
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('bookings') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-slate-500 dark:text-slate-400">{{ __('Not enough data to display this report') }}</p>
        </div>
    @endif
</div>
