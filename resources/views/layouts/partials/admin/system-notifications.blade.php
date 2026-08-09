{{-- Relies on $systemNotifications, shared into every admin view. --}}
@if(!empty($systemNotifications) && $systemNotifications->isNotEmpty())
    @php
        $notifColors = [
            'info'    => 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200',
            'success' => 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200',
            'warning' => 'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200',
            'danger'  => 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200',
        ];
    @endphp
    @foreach($systemNotifications->take(2) as $sysNotif)
        <div class="border-b {{ $notifColors[$sysNotif->type] ?? $notifColors['info'] }} px-4 py-2.5"
             id="sysnotif-{{ $sysNotif->id }}">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wide opacity-75">{{ $sysNotif->title }}</span>
                    <span class="text-sm ml-2">{{ $sysNotif->message }}</span>
                </div>
                <button onclick="document.getElementById('sysnotif-{{ $sysNotif->id }}').remove()"
                        class="flex-shrink-0 text-lg leading-none opacity-60 hover:opacity-100">&times;</button>
            </div>
        </div>
    @endforeach
@endif
