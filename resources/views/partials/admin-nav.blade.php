@php
    $isArabic = app()->getLocale() === 'ar';
    $businessSettings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
    $businessName = $businessSettings->business_name ?? tenant()->name ?? config('app.name');
    $businessLogo = $businessSettings->logo ?? null;
@endphp
<!-- Top Navigation Bar -->
<nav class="bg-white dark:bg-slate-900 shadow-sm border-b dark:border-slate-700 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14">
            <!-- Logo & Navigation Links -->
            <div class="flex items-center gap-1">
                <a href="/admin/dashboard" class="flex items-center gap-2 text-xl font-bold text-indigo-600 dark:text-indigo-400 {{ $isArabic ? 'ml-6' : 'mr-6' }}">
                    @if($businessLogo)
                        <img src="{{ asset('storage/' . $businessLogo) }}" alt="{{ $businessName }}" class="h-8 w-auto">
                    @endif
                    <span>{{ $businessName }}</span>
                </a>

                <div class="hidden md:flex items-center">
                    <a href="/admin/appointments" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/appointments*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        {{ __('Appointments') }}
                    </a>
                    <a href="/admin/queue" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/queue*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        {{ __('Queue') }}
                    </a>
                    <a href="/admin/staff" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/staff*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        {{ __('Staff') }}
                    </a>
                    <a href="/admin/customers" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/customers*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        {{ __('Customers') }}
                    </a>
                    <a href="/admin/reports" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/reports*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        {{ __('Reports') }}
                    </a>
                    <a href="/admin/settings" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/settings*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        {{ __('Settings') }}
                    </a>
                    @if(auth()->user()->isAdminTenant())
                    <a href="/admin/assistants" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->is('admin/assistants*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                        {{ __('Assistants') }}
                    </a>
                    @endif
                </div>
            </div>

            <!-- Right Side: Dark Mode + Language + Profile + Logout -->
            <div class="flex items-center gap-3">
                <!-- Dark Mode Toggle -->
                <button onclick="toggleDarkMode()"
                    class="p-2 rounded-lg bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-200 dark:hover:bg-slate-600 transition-all shadow-sm"
                    title="{{ __('Toggle Dark Mode') }}">
                    <span id="dark-mode-icon" class="text-xl">🌙</span>
                </button>

                <!-- Language Switcher -->
                @php
                    $adminLangs = [
                        'en' => ['label' => 'EN',      'flag' => '🇬🇧'],
                        'ar' => ['label' => 'عربي',    'flag' => '🇸🇦'],
                        'fr' => ['label' => 'FR',      'flag' => '🇫🇷'],
                        'es' => ['label' => 'ES',      'flag' => '🇪🇸'],
                        'de' => ['label' => 'DE',      'flag' => '🇩🇪'],
                        'it' => ['label' => 'IT',      'flag' => '🇮🇹'],
                        'pt' => ['label' => 'PT',      'flag' => '🇵🇹'],
                        'ru' => ['label' => 'RU',      'flag' => '🇷🇺'],
                        'zh' => ['label' => '中文',    'flag' => '🇨🇳'],
                        'ja' => ['label' => '日本語',  'flag' => '🇯🇵'],
                    ];
                    $currentLocale = app()->getLocale();
                    $currentLang = $adminLangs[$currentLocale] ?? $adminLangs['en'];
                @endphp
                <div class="relative">
                    <button onclick="document.getElementById('adminLangMenu').classList.toggle('hidden')"
                        id="adminLangBtn"
                        class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-all">
                        <span>{{ $currentLang['flag'] }}</span>
                        <span>{{ $currentLang['label'] }}</span>
                        <svg class="w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="adminLangMenu"
                        class="hidden absolute right-0 mt-1 w-36 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg py-1 z-50">
                        @foreach($adminLangs as $code => $lang)
                        <a href="/change-language/{{ $code }}"
                            class="flex items-center gap-2 px-3 py-1.5 text-xs hover:bg-slate-50 dark:hover:bg-slate-700 {{ $currentLocale === $code ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-slate-700 dark:text-slate-300' }}">
                            <span>{{ $lang['flag'] }}</span>
                            <span>{{ $lang['label'] }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                <script>
                    document.addEventListener('click', function(e) {
                        var btn = document.getElementById('adminLangBtn');
                        var menu = document.getElementById('adminLangMenu');
                        if (btn && menu && !btn.contains(e.target) && !menu.contains(e.target)) {
                            menu.classList.add('hidden');
                        }
                    });
                </script>

                <!-- Profile -->
                <a href="/admin/profile" class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar_url }}" alt="Avatar" class="w-8 h-8 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600">
                    @else
                        <div class="w-8 h-8 rounded-full bg-indigo-600 dark:bg-indigo-500 flex items-center justify-center">
                            <span class="text-white font-bold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <span class="hidden sm:inline text-sm font-medium text-slate-700 dark:text-slate-300">{{ auth()->user()->name }}</span>
                </a>

                <!-- Logout -->
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">
                        {{ __('Logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div class="md:hidden border-t dark:border-slate-700 overflow-x-auto">
        <div class="flex px-4 py-2 gap-1 min-w-max">
            <a href="/admin/appointments" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/appointments*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400' }}">
                {{ __('Appointments') }}
            </a>
            <a href="/admin/queue" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/queue*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400' }}">
                {{ __('Queue') }}
            </a>
            <a href="/admin/staff" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/staff*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400' }}">
                {{ __('Staff') }}
            </a>
            <a href="/admin/customers" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/customers*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400' }}">
                {{ __('Customers') }}
            </a>
            <a href="/admin/reports" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/reports*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400' }}">
                {{ __('Reports') }}
            </a>
            <a href="/admin/settings" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/settings*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400' }}">
                {{ __('Settings') }}
            </a>
            @if(auth()->user()->isAdminTenant())
            <a href="/admin/assistants" class="px-3 py-1.5 text-xs font-medium rounded-lg whitespace-nowrap {{ request()->is('admin/assistants*') ? 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/30' : 'text-slate-600 dark:text-slate-400' }}">
                {{ __('Assistants') }}
            </a>
            @endif
        </div>
    </div>
</nav>
