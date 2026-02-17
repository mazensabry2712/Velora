<nav class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center">
                    <span class="text-2xl font-bold text-blue-600">{{ tenant()->name ?? 'Booking' }}</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:flex items-center space-x-8 {{ app()->getLocale() === 'ar' ? 'space-x-reverse' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'text-blue-600 bg-blue-50' : '' }}">
                    {{ __('Dashboard') }}
                </a>
                <a href="{{ route('admin.appointments') }}" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.appointments*') ? 'text-blue-600 bg-blue-50' : '' }}">
                    {{ __('Appointments') }}
                </a>
                <a href="{{ route('admin.queue') }}" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.queue') ? 'text-blue-600 bg-blue-50' : '' }}">
                    {{ __('Queue') }}
                </a>
                <a href="{{ route('admin.reports') }}" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.reports') ? 'text-blue-600 bg-blue-50' : '' }}">
                    {{ __('Reports') }}
                </a>
            </div>

            <!-- User Menu -->
            <div class="flex items-center space-x-4 {{ app()->getLocale() === 'ar' ? 'space-x-reverse' : '' }}">
                <!-- Language Switcher -->
                <div class="relative">
                    <button onclick="toggleLanguageMenu()" class="flex items-center text-gray-700 hover:text-blue-600">
                        <svg class="w-5 h-5 {{ app()->getLocale() === 'ar' ? 'ml-1' : 'mr-1' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                        </svg>
                        <span>{{ strtoupper(app()->getLocale()) }}</span>
                    </button>
                    <div id="languageMenu" class="hidden absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-32 bg-white rounded-md shadow-lg py-1 z-10">
                        <a href="?locale=en" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">English</a>
                        <a href="?locale=ar" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">العربية</a>
                    </div>
                </div>

                <!-- User Dropdown (only if authenticated) -->
                @auth
                <div class="relative">
                    <button onclick="toggleUserMenu()" class="flex items-center text-gray-700 hover:text-blue-600">
                        <span class="{{ app()->getLocale() === 'ar' ? 'ml-2' : 'mr-2' }}">{{ auth()->user()->name }}</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="userMenu" class="hidden absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Profile') }}</a>
                        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Settings') }}</a>
                        <form method="POST" action="{{ route('logout') }}" class="block">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                {{ __('Logout') }}
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <!-- Login button for guests -->
                <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    {{ __('messages.login') }}
                </a>
                @endauth
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button onclick="toggleMobileMenu()" class="text-gray-700 hover:text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobileMenu" class="hidden md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">
                {{ __('Dashboard') }}
            </a>
            <a href="{{ route('admin.appointments') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">
                {{ __('Appointments') }}
            </a>
            <a href="{{ route('admin.queue') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">
                {{ __('Queue') }}
            </a>
            <a href="{{ route('admin.reports') }}" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">
                {{ __('Reports') }}
            </a>
        </div>
    </div>
</nav>

<script>
function toggleLanguageMenu() {
    document.getElementById('languageMenu').classList.toggle('hidden');
}

function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('hidden');
}

function toggleMobileMenu() {
    document.getElementById('mobileMenu').classList.toggle('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const languageMenu = document.getElementById('languageMenu');
    const userMenu = document.getElementById('userMenu');

    if (!event.target.closest('.relative')) {
        languageMenu.classList.add('hidden');
        userMenu.classList.add('hidden');
    }
});
</script>
