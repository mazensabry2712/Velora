@extends('super-admin.layout')

@section('title', 'إدارة الدول')
@section('breadcrumb')<span class="text-slate-700 dark:text-slate-200 font-medium">إدارة الدول</span>@endsection

@section('content')
<div>
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">إدارة الدول</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">تحديد اللغة والعملة الافتراضية لكل دولة</p>
        </div>
        <a href="{{ route('super-admin.countries.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2.5 rounded-xl transition-colors text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            إضافة دولة
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-sm border border-emerald-200 dark:border-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <!-- Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-600 dark:text-slate-300">الدولة</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-600 dark:text-slate-300">الرمز</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-600 dark:text-slate-300">اللغة</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-600 dark:text-slate-300">العملة</th>
                        <th class="px-5 py-3.5 text-center font-semibold text-slate-600 dark:text-slate-300">الحالة</th>
                        <th class="px-5 py-3.5 text-center font-semibold text-slate-600 dark:text-slate-300">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($countries as $country)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors">
                        <td class="px-5 py-3.5 font-medium text-slate-900 dark:text-white">{{ $country->country_name }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200">
                                {{ $country->country_code }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300 uppercase">{{ $country->default_language }}</td>
                        <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300">{{ $country->default_currency }}</td>
                        <td class="px-5 py-3.5 text-center">
                            @if($country->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">نشط</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500">معطّل</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('super-admin.countries.edit', $country) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 transition-colors">
                                    تعديل
                                </a>
                                <form method="POST" action="{{ route('super-admin.countries.destroy', $country) }}" onsubmit="return confirm('حذف الدولة؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 transition-colors">
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-slate-400 dark:text-slate-500">
                            لا توجد دول مضافة بعد.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($countries->hasPages())
        <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700">
            {{ $countries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
