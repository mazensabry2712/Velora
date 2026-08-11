<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        {{ __('Logo') }}
    </h3>

    <div class="flex items-center gap-6 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}">
        <div class="flex-shrink-0">
            <div id="logoPreview" class="w-24 h-24 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center overflow-hidden bg-slate-50 dark:bg-slate-700">
                @if(!empty($settings['logo']))
                    <img src="{{ asset('storage/' . $settings['logo']) }}" alt="Logo" class="w-full h-full object-contain">
                @else
                    <svg class="w-10 h-10 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                @endif
            </div>
        </div>

        <div class="flex-1 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Upload Logo') }}</label>
            <input type="file" name="logo" id="logoInput" accept="image/*"
                class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                onchange="previewLogo(this)">
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ __('Recommended size: 200x200 pixels. Max 2MB.') }}</p>
        </div>
    </div>
</div>
