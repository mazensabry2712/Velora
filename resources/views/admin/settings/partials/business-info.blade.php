<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
        </svg>
        {{ __('Business Information') }}
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Business Name') }}</label>
            <input type="text" name="business_name" value="{{ $settings['business_name'] ?? tenant()->name }}"
                class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                placeholder="{{ __('Enter business name') }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Business Name (Arabic)') }}</label>
            <input type="text" name="business_name_ar" value="{{ $settings['business_name_ar'] ?? '' }}" dir="rtl"
                class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                placeholder="{{ __('اسم النشاط التجاري') }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Phone Number') }}</label>
            <input type="tel" name="phone" value="{{ $settings['phone'] ?? '' }}"
                class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                placeholder="+966 5xxxxxxxx" dir="ltr">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Email') }}</label>
            <input type="email" name="email" value="{{ $settings['email'] ?? '' }}"
                class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                placeholder="contact@example.com" dir="ltr">
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Address') }}</label>
            <input type="text" name="address" value="{{ $settings['address'] ?? '' }}"
                class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                placeholder="{{ __('Enter your business address') }}">
        </div>
    </div>
</div>
