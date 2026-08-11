@php
    $statusMeta = [
        'confirmed' => ['label' => __('Confirmed'), 'class' => 'bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200'],
        'pending'   => ['label' => __('Pending'),   'class' => 'bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200'],
        'cancelled' => ['label' => __('Cancelled'), 'class' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'],
    ];
@endphp

<div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Appointments Report') }}</h3>
    @if($appointmentsByStatus->isNotEmpty())
        <div class="space-y-3">
            @foreach($appointmentsByStatus as $item)
                @php $meta = $statusMeta[$item->status] ?? null; @endphp
                <div class="flex justify-between items-center p-3 bg-slate-50 dark:bg-slate-700 rounded">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $meta['class'] ?? 'bg-slate-100 dark:bg-slate-600 text-slate-800 dark:text-slate-200' }}">
                        {{ $meta['label'] ?? $item->status }}
                    </span>
                    <div>
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
