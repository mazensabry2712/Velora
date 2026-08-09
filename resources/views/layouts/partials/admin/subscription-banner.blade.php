{{-- Relies on $subscriptionBanner, which is shared into every admin view
     (e.g. from a view composer / middleware) rather than passed explicitly. --}}
@if(isset($subscriptionBanner))
    @php
        $banner = $subscriptionBanner;
        $isUrgent = $banner['status'] === 'trial' && ($banner['days_left'] ?? 99) <= 3;
        $isGrace  = $banner['status'] === 'grace';
        $bgClass  = match($banner['type'] ?? 'info') {
            'warning' => 'bg-amber-500',
            'danger'  => 'bg-red-600',
            default   => 'bg-indigo-600',
        };
    @endphp
    <div class="{{ $bgClass }} text-white">
        <div class="max-w-7xl mx-auto px-4 py-2 flex flex-wrap items-center justify-between gap-3">

            {{-- Left: message + days pill --}}
            <div class="flex items-center gap-3">
                @if($banner['status'] === 'trial')
                    <span class="bg-white/25 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                        {{ $banner['days_left'] ?? 0 }} {{ __('يوم') }}
                    </span>
                @endif
                <span class="text-sm font-medium">{{ $banner['message'] }}</span>
            </div>

            {{-- Right: action buttons --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                @if(isset($banner['upgrade_url']) && auth()->user()?->isAdminTenant())
                    <a href="{{ $banner['upgrade_url'] }}"
                       class="bg-white text-{{ $isUrgent || $isGrace ? 'red' : 'indigo' }}-600 text-xs font-bold px-4 py-1.5 rounded-full hover:bg-white/90 transition-colors shadow-sm">
                        {{ __('ترقية الآن') }} →
                    </a>
                @endif

                {{-- 7-day extension offer (Day 12, one-time, trial only) --}}
                @if($banner['status'] === 'trial' && ($banner['days_left'] ?? 99) <= 2 && !($banner['trial_extended'] ?? false) && auth()->user()?->isAdminTenant())
                    <form method="POST" action="/billing/extend-trial" class="inline">
                        @csrf
                        <button type="submit"
                                class="bg-white/20 border border-white/40 text-white text-xs font-semibold px-3 py-1.5 rounded-full hover:bg-white/30 transition-colors"
                                onclick="return confirm('{{ __('تمديد 7 أيام إضافية مجانية؟') }}')">
                            {{ __('تمديد 7 أيام') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endif
