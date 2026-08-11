@php
    $queueRows = [
        ['label' => __('Waiting'), 'value' => $queueStats['waiting'], 'bg' => 'bg-indigo-50 dark:bg-indigo-900', 'text' => 'text-indigo-600 dark:text-indigo-400'],
        ['label' => __('Being Served'), 'value' => $queueStats['serving'], 'bg' => 'bg-emerald-50 dark:bg-emerald-900', 'text' => 'text-emerald-600 dark:text-emerald-400'],
        ['label' => __('Completed'), 'value' => $queueStats['completed'], 'bg' => 'bg-slate-50 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-400'],
        ['label' => __('Priority Customers'), 'value' => $queueStats['priority'], 'bg' => 'bg-amber-50 dark:bg-amber-900', 'text' => 'text-amber-600 dark:text-amber-400'],
    ];
@endphp

<div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">{{ __('Queue Statistics') }}</h3>
    <div class="space-y-4">
        @foreach($queueRows as $row)
        <div class="flex justify-between items-center p-3 {{ $row['bg'] }} rounded">
            <span class="text-slate-700 dark:text-slate-200">{{ $row['label'] }}</span>
            <span class="text-2xl font-bold {{ $row['text'] }}">{{ $row['value'] }}</span>
        </div>
        @endforeach
    </div>
</div>
