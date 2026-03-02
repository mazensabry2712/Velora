@extends('super-admin.layout')

@section('title', $country->exists ? 'تعديل الدولة' : 'إضافة دولة')
@section('breadcrumb')
    <a href="{{ route('super-admin.countries.index') }}" class="text-indigo-500 hover:underline">إدارة الدول</a>
    <span class="mx-2 text-slate-400">/</span>
    <span class="text-slate-700 dark:text-slate-200 font-medium">{{ $country->exists ? 'تعديل' : 'إضافة' }}</span>
@endsection

@section('content')
<div class="max-w-2xl">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
            {{ $country->exists ? 'تعديل دولة: ' . $country->country_name : 'إضافة دولة جديدة' }}
        </h1>
    </div>

    @if($errors->any())
        <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm border border-red-200 dark:border-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $country->exists ? route('super-admin.countries.update', $country) : route('super-admin.countries.store') }}"
          class="space-y-6">
        @csrf
        @if($country->exists) @method('PUT') @endif

        <!-- Country Info -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 space-y-4">
            <h2 class="font-semibold text-slate-700 dark:text-slate-200 border-b border-slate-100 dark:border-slate-700 pb-3">معلومات الدولة</h2>

            @unless($country->exists)
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">رمز الدولة (ISO 3166-1) <span class="text-red-500">*</span></label>
                <input type="text" name="country_code" value="{{ old('country_code', $country->country_code) }}"
                    maxlength="2" placeholder="مثال: SA"
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            @endunless

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">اسم الدولة <span class="text-red-500">*</span></label>
                <input type="text" name="country_name" value="{{ old('country_name', $country->country_name) }}"
                    placeholder="مثال: المملكة العربية السعودية"
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">اللغة الافتراضية <span class="text-red-500">*</span></label>
                <select name="default_language"
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['en'=>'English','ar'=>'العربية','fr'=>'Français','es'=>'Español','de'=>'Deutsch','it'=>'Italiano','pt'=>'Português','ru'=>'Русский','zh'=>'中文','ja'=>'日本語','tr'=>'Türkçe','hi'=>'हिंदी','ko'=>'한국어','nl'=>'Nederlands','id'=>'Bahasa Indonesia'] as $code => $label)
                        <option value="{{ $code }}" {{ old('default_language', $country->default_language) === $code ? 'selected' : '' }}>{{ $label }} ({{ $code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">العملة الافتراضية (ISO 4217) <span class="text-red-500">*</span></label>
                <input type="text" name="default_currency" value="{{ old('default_currency', $country->default_currency) }}"
                    maxlength="3" placeholder="مثال: SAR"
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                    {{ old('is_active', $country->is_active ?? true) ? 'checked' : '' }}
                    class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                <label for="is_active" class="text-sm font-medium text-slate-700 dark:text-slate-300">دولة نشطة (تُطبّق الإعدادات تلقائياً)</label>
            </div>
        </div>

        <!-- Tax -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 space-y-4">
            <h2 class="font-semibold text-slate-700 dark:text-slate-200 border-b border-slate-100 dark:border-slate-700 pb-3">الضريبة / VAT (اختياري)</h2>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">اسم الضريبة</label>
                <input type="text" name="tax_name" value="{{ old('tax_name', $tax->tax_name ?? 'VAT') }}"
                    placeholder="VAT"
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">نسبة الضريبة % (0 = لا ضريبة)</label>
                <input type="number" step="0.01" min="0" max="100" name="tax_percentage"
                    value="{{ old('tax_percentage', $tax->tax_percentage ?? '') }}"
                    placeholder="مثال: 15"
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="tax_active" value="0">
                <input type="checkbox" id="tax_active" name="tax_active" value="1"
                    {{ old('tax_active', $tax->is_active ?? true) ? 'checked' : '' }}
                    class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                <label for="tax_active" class="text-sm font-medium text-slate-700 dark:text-slate-300">تفعيل الضريبة لهذه الدولة</label>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center gap-3">
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2.5 rounded-xl transition-colors text-sm">
                {{ $country->exists ? 'حفظ التعديلات' : 'إضافة الدولة' }}
            </button>
            <a href="{{ route('super-admin.countries.index') }}"
               class="text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-medium px-6 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 transition-colors text-sm">
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection
