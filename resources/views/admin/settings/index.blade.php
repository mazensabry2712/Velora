<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Settings') }} - {{ tenant()->name }}</title>

    <!-- Prevent Flash of White Content -->
    <script>
        (function() {
            if (localStorage.getItem('darkMode') === 'true' ||
                (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [dir="rtl"] input, [dir="rtl"] textarea {
            text-align: right;
        }
        [dir="rtl"] input[type="url"], [dir="rtl"] input[type="email"], [dir="rtl"] input[type="tel"] {
            text-align: left;
            direction: ltr;
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
    @include('partials.admin-nav')

    <!-- Page Header -->
    <header class="bg-white dark:bg-slate-800 shadow-sm">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ __('Settings') }}</h2>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('Manage your business information and social media links') }}</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="mb-6 bg-emerald-50 dark:bg-emerald-900 border border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200 rounded-lg p-4">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 rounded-lg p-4">
            {{ session('error') }}
        </div>
        @endif

        <form id="settingsForm" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Business Information -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    {{ __('Business Information') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Business Name -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Business Name') }}</label>
                        <input type="text" name="business_name" value="{{ $settings['business_name'] ?? tenant()->name }}"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                            placeholder="{{ __('Enter business name') }}">
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Phone Number') }}</label>
                        <input type="tel" name="phone" value="{{ $settings['phone'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                            placeholder="+966 5xxxxxxxx">
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Email') }}</label>
                        <input type="email" name="email" value="{{ $settings['email'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                            placeholder="contact@example.com">
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('Address') }}</label>
                        <input type="text" name="address" value="{{ $settings['address'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                            placeholder="{{ __('Enter your business address') }}">
                    </div>
                </div>
            </div>

            <!-- Available Languages -->
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

            <!-- Logo -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{ __('Logo') }}
                </h3>

                <div class="flex items-center gap-6 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}">
                    <!-- Current Logo Preview -->
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

            <!-- Social Media Links -->
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 p-6">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                    </svg>
                    {{ __('Social Media') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- WhatsApp -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
                            <svg class="w-5 h-5 text-emerald-500 dark:text-emerald-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            {{ __('WhatsApp') }}
                        </label>
                        <input type="text" name="whatsapp" value="{{ $settings['whatsapp'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                            placeholder="+966 5xxxxxxxx" dir="ltr">
                    </div>

                    <!-- Facebook -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            {{ __('Facebook') }}
                        </label>
                        <input type="url" name="facebook" value="{{ $settings['facebook'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                            placeholder="https://facebook.com/yourpage" dir="ltr">
                    </div>

                    <!-- Instagram -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
                            <svg class="w-5 h-5 text-pink-500 dark:text-pink-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                            </svg>
                            {{ __('Instagram') }}
                        </label>
                        <input type="url" name="instagram" value="{{ $settings['instagram'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                            placeholder="https://instagram.com/yourpage" dir="ltr">
                    </div>

                    <!-- Twitter/X -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
                            <svg class="w-5 h-5 text-slate-900 dark:text-slate-100" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            {{ __('Twitter/X') }}
                        </label>
                        <input type="url" name="twitter" value="{{ $settings['twitter'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                            placeholder="https://x.com/yourpage" dir="ltr">
                    </div>

                    <!-- TikTok -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                            </svg>
                            {{ __('TikTok') }}
                        </label>
                        <input type="url" name="tiktok" value="{{ $settings['tiktok'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                            placeholder="https://tiktok.com/@yourpage" dir="ltr">
                    </div>

                    <!-- Snapchat -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse justify-end' : '' }}">
                            <svg class="w-5 h-5 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/>
                            </svg>
                            {{ __('Snapchat') }}
                        </label>
                        <input type="text" name="snapchat" value="{{ $settings['snapchat'] ?? '' }}"
                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 focus:border-indigo-500 dark:focus:border-indigo-600"
                            placeholder="{{ __('Snapchat username') }}" dir="ltr">
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex {{ app()->getLocale() === 'ar' ? 'justify-start' : 'justify-end' }} gap-4">
                <button type="submit" id="saveBtn" class="px-6 py-3 bg-indigo-600 dark:bg-indigo-700 text-white rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    {{ __('Save Settings') }}
                </button>
            </div>
        </form>
    </main>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Preview logo before upload
        function previewLogo(input) {
            const preview = document.getElementById('logoPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview" class="w-full h-full object-contain">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Handle form submit
        document.getElementById('settingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('saveBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> {{ __("Saving...") }}';
            btn.disabled = true;

            try {
                const formData = new FormData(e.target);

                // Debug: Log selected languages
                const languages = formData.getAll('available_languages[]');
                console.log('Selected languages:', languages);

                const response = await fetch('/admin/api/settings', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });

                const result = await response.json();
                console.log('Save result:', result);

                if (result.success) {
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'fixed top-4 {{ app()->getLocale() === "ar" ? "left-4" : "right-4" }} bg-emerald-600 dark:bg-emerald-700 text-white px-6 py-3 rounded-lg shadow-lg z-50';
                    alert.innerHTML = '✓ {{ __("Settings saved successfully!") }}';
                    document.body.appendChild(alert);
                    setTimeout(() => alert.remove(), 3000);
                } else {
                    alert(result.message || '{{ __("Error occurred") }}');
                }
            } catch (error) {
                alert('{{ __("Error occurred") }}');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    </script>
    <script src="/js/dark-mode.js"></script>
</body>
</html>
