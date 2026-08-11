@php
    $allLanguages = [
        'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇬🇧'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦'],
        'fr' => ['name' => 'French', 'native' => 'Français', 'flag' => '🇫🇷'],
        'es' => ['name' => 'Spanish', 'native' => 'Español', 'flag' => '🇪🇸'],
        'de' => ['name' => 'German', 'native' => 'Deutsch', 'flag' => '🇩🇪'],
        'it' => ['name' => 'Italian', 'native' => 'Italiano', 'flag' => '🇮🇹'],
        'pt' => ['name' => 'Portuguese', 'native' => 'Português', 'flag' => '🇵🇹'],
        'ru' => ['name' => 'Russian', 'native' => 'Русский', 'flag' => '🇷🇺'],
        'zh' => ['name' => 'Chinese', 'native' => '中文', 'flag' => '🇨🇳'],
        'ja' => ['name' => 'Japanese', 'native' => '日本語', 'flag' => '🇯🇵'],
    ];
    $selectedLanguages = $settings['available_languages'] ?? ['en', 'ar'];
@endphp

<div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
        </svg>
        {{ __('Available Languages') }}
    </h3>

    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
        {{ __('Select the languages you want to make available for your customers on the booking page') }}
    </p>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        @foreach($allLanguages as $code => $lang)
        <label class="cursor-pointer">
            <input type="checkbox" name="available_languages[]" value="{{ $code }}"
                {{ in_array($code, $selectedLanguages) ? 'checked' : '' }}
                class="peer sr-only">
            <div class="p-3 border-2 border-slate-200 dark:border-slate-700 rounded-lg hover:border-indigo-400 dark:hover:border-indigo-500 peer-checked:border-indigo-600 dark:peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 transition-all">
                <div class="flex flex-col items-center text-center gap-1">
                    <span class="text-2xl">{{ $lang['flag'] }}</span>
                    <span class="text-xs font-medium text-slate-900 dark:text-slate-100">{{ $lang['native'] }}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ $lang['name'] }}</span>
                </div>
            </div>
        </label>
        @endforeach
    </div>

    <p class="text-xs text-slate-500 dark:text-slate-400 mt-3 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
        <strong>{{ __('Note:') }}</strong> {{ __('Customers will only see the languages you select here on the booking page') }}
    </p>
</div>
