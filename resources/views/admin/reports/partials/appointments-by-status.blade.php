<!-- Appointments Report -->
<div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Appointments Report') }}</h3>
    @if($appointmentsByStatus->isNotEmpty())
        <div class="space-y-3">
            @foreach($appointmentsByStatus as $item)
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700 rounded">
                    <div class="flex items-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($item->status === 'confirmed') bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200
                            @elseif($item->status === 'pending') bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200
                            @elseif($item->status === 'cancelled') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                            @else bg-slate-100 dark:bg-slate-600 text-slate-800 dark:text-slate-200
                            @endif">
                            @if($item->status === 'confirmed') {{ __('Confirmed') }}
                            @elseif($item->status === 'pending') {{ __('Pending') }}
                            @elseif($item->status === 'cancelled') {{ __('Cancelled') }}
                            @else {{ $item->status }}
                            @endif
                        </span>
                    </div>
                    <div class="text-left rtl:text-right">
                        <span class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $item->count }}</span>
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
