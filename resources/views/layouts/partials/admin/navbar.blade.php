@php
    $adminNavLinks = \App\Support\AdminNavigation::items();
    $supportedLangs = \App\Support\AdminNavigation::supportedLanguages();
    $currentLang = app()->getLocale();
@endphp

<nav class="bg-white/95 dark:bg-slate-800/95 backdrop-blur-sm shadow-sm border-b border-slate-200 dark:border-slate-700 sticky top-0 z-40"
    x-data="{ mobileOpen: false, userOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- ══ LEFT: Logo + Nav Links ══ --}}
            <div class="flex items-center gap-1">
                <!-- Logo -->
                <a href="/admin/dashboard" class="flex items-center gap-3 group {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                    @if ($businessLogo)
                        <img src="{{ asset('storage/' . $businessLogo) }}" alt="{{ $businessName }}"
                            class="h-9 w-auto rounded-lg shadow-sm">
                    @else
                        <div
                            class="w-9 h-9 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl flex items-center justify-center shadow-md group-hover:shadow-indigo-300 dark:group-hover:shadow-indigo-900 transition-shadow">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif
                    <span
                        class="text-base font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent hidden sm:block">
                        {{ $businessName }}
                    </span>
                </a>

                <!-- Desktop Nav Links -->
                <div class="hidden lg:flex items-center gap-0.5">
                    @foreach ($adminNavLinks as $link)
                        @php $isActive = request()->is($link['match']); @endphp
                        <a href="{{ $link['url'] }}"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                                  {{ $isActive
                                      ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                                      : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="{{ $link['icon'] }}" />
                            </svg>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ══ RIGHT: Language + User Dropdown + Mobile Toggle ══ --}}
            <div class="flex items-center gap-2">

                <!-- Language Switcher -->
                <div class="relative hidden sm:block" x-data="{ langOpen: false }">
                    <button @click="langOpen = !langOpen"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                        <span>{{ strtoupper($currentLang) }}</span>
                        <svg class="w-3 h-3 text-slate-400 transition-transform" :class="langOpen && 'rotate-180'"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="langOpen" @click.away="langOpen = false" x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute end-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50 p-2">
                        <div class="grid grid-cols-3 gap-1">
                            @foreach ($supportedLangs as $code => $label)
                                <a href="/change-language/{{ $code }}"
                                    class="flex items-center justify-center py-2 text-xs font-semibold rounded-lg transition-all
                                          {{ $currentLang === $code
                                              ? 'bg-indigo-600 text-white shadow-sm'
                                              : 'text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/40 hover:text-indigo-700 dark:hover:text-indigo-300' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm hover:bg-slate-100 dark:hover:bg-slate-700 transition group">
                        @if (auth()->user()->avatar ?? false)
                            <img src="{{ auth()->user()->avatar_url }}" alt="Avatar"
                                class="w-8 h-8 rounded-full object-cover border-2 border-slate-200 dark:border-slate-600">
                        @else
                            <div
                                class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-sm">
                                <span
                                    class="text-white font-bold text-sm">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                        @endif
                        <span
                            class="text-slate-700 dark:text-slate-200 font-medium hidden md:block">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open && 'rotate-180'"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown Panel -->
                    <div x-show="open" @click.away="open = false" x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute end-0 mt-2 w-72 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden z-50">

                        {{-- Profile header --}}
                        <div class="relative px-4 py-4 overflow-hidden">
                            <div
                                class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-500 to-purple-600">
                            </div>
                            <div class="relative flex items-center gap-3">
                                <div
                                    class="w-11 h-11 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center flex-shrink-0 shadow-inner">
                                    @if (auth()->user()->avatar ?? false)
                                        <img src="{{ auth()->user()->avatar_url }}"
                                            class="w-full h-full rounded-xl object-cover">
                                    @else
                                        <span
                                            class="text-white font-black text-lg">{{ substr(auth()->user()->name, 0, 1) }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-white leading-tight truncate">
                                        {{ auth()->user()->name }}</p>
                                    <p class="text-xs text-indigo-200 mt-0.5 truncate">{{ auth()->user()->email }}</p>
                                </div>
                                <span
                                    class="ms-auto flex-shrink-0 text-[10px] font-bold bg-white/20 text-white px-2 py-0.5 rounded-full border border-white/30">
                                    {{ auth()->user()->isAdminTenant() ? 'Admin' : 'Staff' }}
                                </span>
                            </div>
                        </div>

                        {{-- Profile link --}}
                        <div class="px-3 pt-3 pb-1">
                            <a href="/admin/profile"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-700 dark:hover:text-indigo-400 transition-all group">
                                <div
                                    class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/50 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4 text-slate-500 dark:text-slate-400 group-hover:text-indigo-600"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                {{ __('My Profile') }}
                                <svg class="w-3.5 h-3.5 ms-auto text-slate-300 dark:text-slate-600 group-hover:text-indigo-400 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="{{ $isRtl ? 'M9 5l7 7-7 7' : 'M15 19l-7-7 7-7' }}" />
                                </svg>
                            </a>
                        </div>

                        {{-- Dark Mode toggle --}}
                        <div class="mx-3 my-1 rounded-xl border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center justify-between px-3 py-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-amber-500 dark:hidden" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                        </svg>
                                        <svg class="w-4 h-4 text-indigo-400 hidden dark:block" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <span
                                        class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('Dark Mode') }}</span>
                                </div>
                                <button @click.stop="window.toggleAdminDark()"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none bg-slate-200 dark:bg-indigo-600">
                                    <span
                                        class="inline-block h-4 w-4 rounded-full bg-white shadow-sm ring-0 transition-transform duration-200 translate-x-0.5 dark:translate-x-[21px]"></span>
                                </button>
                            </div>
                        </div>

                        {{-- Logout --}}
                        <div class="px-3 pt-1 pb-3">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all group">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center flex-shrink-0 group-hover:bg-red-100 dark:group-hover:bg-red-900/40 transition-colors">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </div>
                                    {{ __('Logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Mobile Hamburger -->
                <button @click="mobileOpen = !mobileOpen"
                    class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 dark:text-slate-400 transition">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileOpen" @click.away="mobileOpen = false" x-cloak
        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-2"
        class="lg:hidden border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 pb-4">
        <div class="px-4 pt-3 space-y-1">
            @foreach ($adminNavLinks as $link)
                @php $isActive = request()->is($link['match']); @endphp
                <a href="{{ $link['url'] }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                          {{ $isActive
                              ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400'
                              : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="{{ $link['icon'] }}" />
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</nav>
