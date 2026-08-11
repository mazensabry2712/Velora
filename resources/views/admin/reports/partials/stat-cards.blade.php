@php
    $cards = [
        [
            'label' => __('Total Appointments'),
            'value' => $stats['total_appointments'],
            'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'bg' => 'bg-indigo-500 dark:bg-indigo-600',
        ],
        [
            'label' => __('Confirmed Appointments'),
            'value' => $stats['confirmed_appointments'],
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'bg' => 'bg-emerald-500 dark:bg-emerald-600',
        ],
        [
            'label' => __('Pending Appointments'),
            'value' => $stats['pending_appointments'],
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'bg' => 'bg-amber-500 dark:bg-amber-600',
        ],
        [
            'label' => __('Total Customers'),
            'value' => $stats['total_customers'],
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            'bg' => 'bg-purple-500 dark:bg-purple-600',
        ],
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    @foreach($cards as $card)
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 {{ $card['bg'] }} rounded-md p-3">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"></path>
                </svg>
            </div>
            <div class="ms-4">
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $card['value'] }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>
