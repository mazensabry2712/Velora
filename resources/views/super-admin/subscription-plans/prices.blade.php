@extends('super-admin.layout')

@section('title', 'أسعار الخطة: ' . $plan->name)
@section('breadcrumb')
    <a href="{{ route('super-admin.subscription-plans') }}" class="text-indigo-500 hover:underline">خطط الاشتراك</a>
    <span class="mx-2 text-slate-400">/</span>
    <span class="text-slate-700 dark:text-slate-200 font-medium">أسعار: {{ $plan->name }}</span>
@endsection

@section('content')
<div x-data="{ showForm: false, editId: null, formData: { country_code:'', currency:'USD', amount:'', stripe_price_id:'', is_default:false, is_active:true } }">

    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">أسعار خطة: {{ $plan->name }}</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">تحديد سعر مخصص لكل دولة / عملة</p>
        </div>
        <button @click="showForm = !showForm"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl transition-colors text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            إضافة سعر
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-sm border border-emerald-200 dark:border-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <!-- Add Price Form (collapsible) -->
    <div x-show="showForm" x-cloak x-transition
         class="mb-6 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="font-semibold text-slate-700 dark:text-slate-200 mb-4">إضافة سعر جديد</h3>
        <form method="POST" action="{{ route('super-admin.plan-prices.store', $plan) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">رمز الدولة (فارغ = افتراضي)</label>
                <input type="text" name="country_code" maxlength="2" placeholder="مثال: DE"
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">العملة <span class="text-red-500">*</span></label>
                <input type="text" name="currency" maxlength="3" placeholder="EUR" required
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">السعر <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="amount" placeholder="29.99" required
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Stripe Price ID</label>
                <input type="text" name="stripe_price_id" placeholder="price_xxx"
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex items-center gap-4 sm:col-span-2">
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                    <input type="hidden" name="is_default" value="0">
                    <input type="checkbox" name="is_default" value="1" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                    سعر افتراضي (fallback لجميع الدول)
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                    نشط
                </label>
            </div>
            <div class="sm:col-span-2 flex gap-3">
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-5 py-2 rounded-xl text-sm transition-colors">
                    حفظ السعر
                </button>
                <button type="button" @click="showForm = false"
                    class="text-slate-600 dark:text-slate-300 font-medium px-5 py-2 rounded-xl border border-slate-200 dark:border-slate-600 text-sm transition-colors">
                    إلغاء
                </button>
            </div>
        </form>
    </div>

    <!-- Prices Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-600 dark:text-slate-300">الدولة</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-600 dark:text-slate-300">العملة</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-600 dark:text-slate-300">السعر</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-600 dark:text-slate-300">Stripe Price ID</th>
                        <th class="px-5 py-3.5 text-center font-semibold text-slate-600 dark:text-slate-300">نوع</th>
                        <th class="px-5 py-3.5 text-center font-semibold text-slate-600 dark:text-slate-300">الحالة</th>
                        <th class="px-5 py-3.5 text-center font-semibold text-slate-600 dark:text-slate-300">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($prices as $price)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors">
                        <td class="px-5 py-3.5">
                            @if($price->country_code)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                    {{ $price->country_code }}
                                </span>
                            @else
                                <span class="text-slate-400 text-xs">— عالمي —</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 font-mono text-slate-700 dark:text-slate-300">{{ $price->currency }}</td>
                        <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-white">{{ number_format($price->amount, 2) }}</td>
                        <td class="px-5 py-3.5 font-mono text-xs text-slate-500 dark:text-slate-400 max-w-[160px] truncate">
                            {{ $price->stripe_price_id ?: '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if($price->is_default)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">افتراضي</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500">دولة</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            @if($price->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">نشط</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500">معطّل</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <form method="POST" action="{{ route('super-admin.plan-prices.destroy', [$plan, $price]) }}"
                                  onsubmit="return confirm('حذف هذا السعر؟')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 transition-colors">
                                    حذف
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-slate-400 dark:text-slate-500">
                            لا توجد أسعار مضافة. أضف سعراً افتراضياً أولاً.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($prices->hasPages())
        <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700">
            {{ $prices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
