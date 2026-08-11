<!-- Service Types Report -->
<div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Services Report') }}</h3>
    @if($serviceTypes->isNotEmpty())
        <div class="space-y-3">
            @foreach($serviceTypes as $service)
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700 rounded">
                    <span class="text-slate-700 dark:text-slate-200">{{ $service->service_type ?? __('Unspecified') }}</span>
                    <div class="text-left rtl:text-right">
                        <span class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $service->count }}</span>
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
