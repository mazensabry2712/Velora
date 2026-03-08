@extends('super-admin.layout')

@section('title', 'Promo Codes')
@section('breadcrumb')
    <span class="text-slate-700 dark:text-slate-200 font-medium">Promo Codes</span>
@endsection

@section('content')
<div x-data="{ showForm: {{ $errors->any() ? 'true' : 'false' }} }">

    {{-- ── Header ────────────────────────────────────────────────────────── --}}
    <div class="mb-8 flex flex-wrap gap-4 justify-between items-center">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">🎟️ Promo Codes</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">
                Create and manage discount codes that users can apply at sign-up.
            </p>
        </div>
        <button @click="showForm = !showForm"
                class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg shadow-indigo-200 dark:shadow-indigo-900 transition-all hover:-translate-y-0.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span x-text="showForm ? 'Cancel' : 'New Code'"></span>
        </button>
    </div>

    {{-- ── Flash messages ─────────────────────────────────────────────── --}}
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

    {{-- ── Create Form (collapsible) ───────────────────────────────────── --}}
    <div x-show="showForm" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Create New Promo Code</h2>

            <form method="POST" action="{{ route('super-admin.promo-codes.store') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

                    {{-- Code --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="code" value="{{ strtoupper(old('code')) }}"
                               placeholder="e.g. LAUNCH50"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 uppercase font-mono text-sm"
                               oninput="this.value = this.value.toUpperCase()" required>
                        @error('code')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Discount Type --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Discount Type <span class="text-red-500">*</span>
                        </label>
                        <select name="discount_type"
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="percent" {{ old('discount_type') === 'fixed' ? '' : 'selected' }}>Percent (%)</option>
                            <option value="fixed"   {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                        </select>
                        @error('discount_type')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Discount Value --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Discount Value <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="discount_value" value="{{ old('discount_value') }}"
                               placeholder="e.g. 20" min="0" max="100000" step="0.01"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                               required>
                        @error('discount_value')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Max Uses --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Max Uses <span class="text-slate-400 font-normal text-xs">(leave blank = unlimited)</span>
                        </label>
                        <input type="number" name="max_uses" value="{{ old('max_uses') }}"
                               placeholder="e.g. 100" min="1"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('max_uses')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Expires At --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Expires At <span class="text-slate-400 font-normal text-xs">(optional)</span>
                        </label>
                        <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('expires_at')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                            Notes <span class="text-slate-400 font-normal text-xs">(optional)</span>
                        </label>
                        <input type="text" name="notes" value="{{ old('notes') }}"
                               placeholder="Internal note..."
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        @error('notes')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- Active toggle --}}
                <label class="flex items-center gap-3 cursor-pointer w-fit">
                    <div class="relative">
                        <input type="hidden"   name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 dark:bg-slate-600 rounded-full peer-checked:bg-indigo-600 transition-colors"></div>
                        <div class="absolute top-0.5 start-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></div>
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Active immediately</span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold px-6 py-2.5 rounded-xl shadow-md shadow-indigo-200 dark:shadow-indigo-900 transition-all hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Create Code
                    </button>
                    <button type="button" @click="showForm = false"
                            class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-medium text-sm hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Table ────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                        <th class="text-left px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Code</th>
                        <th class="text-left px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Discount</th>
                        <th class="text-center px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Usage</th>
                        <th class="text-left px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Expires</th>
                        <th class="text-left px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Notes</th>
                        <th class="text-center px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Status</th>
                        <th class="text-right px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($codes as $code)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">

                        {{-- Code --}}
                        <td class="px-5 py-4">
                            <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-1 rounded-lg text-xs tracking-wider">
                                {{ $code->code }}
                            </span>
                        </td>

                        {{-- Discount --}}
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1 font-semibold text-slate-900 dark:text-white">
                                @if($code->discount_type === 'percent')
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ number_format($code->discount_value, 0) }}%</span>
                                    <span class="text-xs text-slate-400">off</span>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400">${{ number_format($code->discount_value, 2) }}</span>
                                    <span class="text-xs text-slate-400">fixed</span>
                                @endif
                            </span>
                        </td>

                        {{-- Usage --}}
                        <td class="px-5 py-4 text-center">
                            <span class="text-slate-700 dark:text-slate-300 font-medium">{{ $code->used_count }}</span>
                            <span class="text-slate-400">/</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ $code->max_uses ?? '∞' }}</span>
                        </td>

                        {{-- Expires --}}
                        <td class="px-5 py-4 text-slate-600 dark:text-slate-400 text-xs">
                            @if($code->expires_at)
                                <span class="{{ $code->expires_at->isPast() ? 'text-red-500 dark:text-red-400 font-medium' : '' }}">
                                    {{ $code->expires_at->format('d M Y, H:i') }}
                                    @if($code->expires_at->isPast())
                                        <span class="block text-red-400 text-[10px]">Expired</span>
                                    @elseif($code->expires_at->diffInDays(now()) < 7)
                                        <span class="block text-amber-500 text-[10px]">expires {{ $code->expires_at->diffForHumans() }}</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>

                        {{-- Notes --}}
                        <td class="px-5 py-4 text-slate-500 dark:text-slate-400 text-xs max-w-[180px] truncate">
                            {{ $code->notes ?? '—' }}
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-4 text-center">
                            @php $valid = $code->isValid(); @endphp
                            @if($code->is_active && $valid)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0 animate-pulse"></span>
                                    Active
                                </span>
                            @elseif($code->is_active && !$valid)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                                    Exhausted
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 flex-shrink-0"></span>
                                    Inactive
                                </span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">

                                {{-- Toggle active --}}
                                <form method="POST" action="{{ route('super-admin.promo-codes.toggle', $code->id) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            title="{{ $code->is_active ? 'Deactivate' : 'Activate' }}"
                                            class="p-1.5 rounded-lg {{ $code->is_active ? 'text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20' : 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/20' }} transition-colors">
                                        @if($code->is_active)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @endif
                                    </button>
                                </form>

                                {{-- Delete --}}
                                <form method="POST" action="{{ route('super-admin.promo-codes.destroy', $code->id) }}"
                                      onsubmit="return confirm('Delete code {{ $code->code }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            title="Delete"
                                            class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-slate-400">
                                <svg class="w-12 h-12 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                </svg>
                                <p class="text-sm font-medium">No promo codes yet</p>
                                <button @click="showForm = true"
                                        class="text-indigo-600 dark:text-indigo-400 text-sm font-medium hover:underline">
                                    Create your first one →
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($codes->hasPages())
        <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700">
            {{ $codes->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
