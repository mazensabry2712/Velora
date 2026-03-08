@extends('super-admin.layout')

@section('title', 'Country Pricing')
@section('breadcrumb')
    <span class="text-slate-700 dark:text-slate-200 font-medium">Country Pricing</span>
@endsection

@section('content')
<div>
    {{-- Header --}}
    <div class="mb-8 flex flex-wrap gap-4 justify-between items-center">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">🌍 Country Markets</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">
                Price, currency, payment methods, tax, and language — configured per country.
            </p>
        </div>
        <a href="{{ route('super-admin.country-pricing.create') }}"
           class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-indigo-200 dark:shadow-indigo-900 transition-all hover:-translate-y-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Market
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- Table --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                        <th class="text-left px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Country</th>
                        <th class="text-left px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Price</th>
                        <th class="text-left px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Tax</th>
                        <th class="text-left px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Payment Methods</th>
                        <th class="text-center px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Status</th>
                        <th class="text-right px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($entries as $entry)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors {{ $entry->country_code === 'GLOBAL' ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : '' }}">
                        {{-- Country --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center text-base font-bold
                                            {{ $entry->country_code === 'GLOBAL' ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                                    {{ $entry->country_code === 'GLOBAL' ? '🌍' : $entry->country_code }}
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        {{ $entry->country_name }}
                                        @if($entry->country_code === 'GLOBAL')
                                        <span class="text-xs bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-full font-medium">Fallback</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-400 flex items-center gap-2 mt-0.5">
                                        {{ $entry->country_code }}
                                        @if(isset($settings[$entry->country_code]) && $settings[$entry->country_code]->default_language)
                                        <span class="text-slate-300 dark:text-slate-600">·</span>
                                        <span class="font-mono uppercase">{{ $settings[$entry->country_code]->default_language }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Price --}}
                        <td class="px-5 py-4">
                            <div class="font-bold text-slate-900 dark:text-white text-base">{{ $entry->formattedPrice() }}</div>
                            <div class="text-xs text-slate-400">/ month</div>
                        </td>

                        {{-- Tax --}}
                        <td class="px-5 py-4">
                            @php $tax = $taxes[$entry->country_code] ?? null; @endphp
                            @if($tax && $tax->is_active && $tax->tax_percentage > 0)
                                <span class="inline-flex items-center gap-1 text-xs font-semibold bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700 px-2.5 py-1 rounded-lg">
                                    {{ $tax->tax_name }} {{ number_format($tax->tax_percentage, 0) }}%
                                </span>
                            @else
                                <span class="text-xs text-slate-400">No tax</span>
                            @endif
                        </td>

                        {{-- Payment Methods --}}
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($entry->payment_methods ?? [] as $method)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium
                                             bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    {{ \App\Models\CountryPricing::paymentMethodLabel($method) }}
                                </span>
                                @endforeach
                            </div>
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-4 text-center">
                            <button
                                onclick="toggleStatus({{ $entry->id }}, this)"
                                data-active="{{ $entry->is_active ? '1' : '0' }}"
                                data-global="{{ $entry->country_code === 'GLOBAL' ? '1' : '0' }}"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none
                                       {{ $entry->is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform
                                             {{ $entry->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('super-admin.country-pricing.edit', $entry) }}"
                                   class="p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors"
                                   title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @if($entry->country_code !== 'GLOBAL')
                                <form method="POST" action="{{ route('super-admin.country-pricing.destroy', $entry) }}"
                                      onsubmit="return confirm('Delete pricing for {{ $entry->country_code }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-slate-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                            title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($entries->hasPages())
        <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700">
            {{ $entries->links() }}
        </div>
        @endif

        @if($entries->isEmpty())
        <div class="text-center py-20 text-slate-400">
            <div class="text-5xl mb-4">🌍</div>
            <p class="text-lg font-semibold text-slate-600 dark:text-slate-300">No country markets configured</p>
            <p class="text-sm mt-1">Add a GLOBAL fallback first, then add countries as needed.</p>
            <a href="{{ route('super-admin.country-pricing.create') }}"
               class="mt-6 inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-xl transition-colors">
                Add First Market
            </a>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
async function toggleStatus(id, btn) {
    if (btn.dataset.global === '1' && btn.dataset.active === '1') {
        alert('The GLOBAL fallback record cannot be disabled.');
        return;
    }

    btn.disabled = true;
    try {
        const res = await fetch(`/super-admin/country-pricing/${id}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        });
        const data = await res.json();
        if (!res.ok) { alert(data.error || 'Error'); btn.disabled = false; return; }

        btn.dataset.active = data.is_active ? '1' : '0';
        btn.className = btn.className.replace(/bg-(emerald|slate)-\S+/g, '');
        btn.classList.add(data.is_active ? 'bg-emerald-500' : 'bg-slate-300', data.is_active ? '' : 'dark:bg-slate-600');
        const dot = btn.querySelector('span');
        dot.classList.toggle('translate-x-6', data.is_active);
        dot.classList.toggle('translate-x-1', !data.is_active);
    } catch {
        alert('Network error.');
    }
    btn.disabled = false;
}
</script>
@endpush
@endsection
