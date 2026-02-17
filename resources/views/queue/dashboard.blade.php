<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $businessSettings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
        $businessName = $businessSettings->business_name ?? tenant()->name ?? config('app.name');
        $businessLogo = $businessSettings->logo ?? null;
    @endphp
    <title>{{ __('Queue Dashboard') }} - {{ $businessName }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/dark-mode-enhancements.css">
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>

    <!-- Dark Mode Prevention Script - يمنع وميض الوضع الفاتح -->
    <script>
        // يتم تنفيذ هذا الكود فوراً قبل عرض الصفحة
        (function() {
            if (localStorage.getItem('darkMode') === 'true' ||
                (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <style>
        @keyframes pulse-grow {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }
        .pulse-grow {
            animation: pulse-grow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-indigo-100 dark:from-slate-900 dark:to-slate-800 min-h-screen">
    <div class="container mx-auto px-3 sm:px-4 py-4 sm:py-8">
        <!-- Header -->
        <div class="mb-6 sm:mb-8">
            <!-- Language & Dark Mode Switcher -->
            <div class="flex justify-end items-center gap-3 mb-4">
                <!-- Dark Mode Toggle -->
                <button onclick="toggleDarkMode()"
                    class="p-2 rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors shadow-sm"
                    title="{{ __('Toggle Dark Mode') }}">
                    <span id="dark-mode-icon" class="text-xl">🌙</span>
                </button>

                <!-- Language Switcher -->
                <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 p-1 shadow-sm">
                    <button onclick="changeLanguage('en')"
                        class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium rounded-md transition-all duration-200 {{ app()->getLocale() === 'en' ? 'bg-indigo-600 dark:bg-indigo-500 text-white shadow-sm' : 'text-slate-600 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white' }}">
                        EN
                    </button>
                    <button onclick="changeLanguage('ar')"
                        class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium rounded-md transition-all duration-200 {{ app()->getLocale() === 'ar' ? 'bg-indigo-600 dark:bg-indigo-500 text-white shadow-sm' : 'text-slate-600 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white' }}">
                        عربي
                    </button>
                </div>
            </div>

            <div class="text-center">
                @if($businessLogo)
                    <img src="{{ asset('storage/' . $businessLogo) }}" alt="{{ $businessName }}" class="h-16 sm:h-20 w-auto mx-auto mb-3 sm:mb-4">
                @endif
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-800 dark:text-white mb-1 sm:mb-2">{{ $businessName }}</h1>
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300">{{ __('Queue Dashboard') }}</p>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 sm:mt-2">{{ __('Updates automatically every 10 minutes') }}</p>
            </div>
        </div>

        <!-- Now Serving -->
        <div class="max-w-4xl mx-auto mb-6 sm:mb-8">
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-500 dark:to-indigo-600 rounded-xl sm:rounded-2xl shadow-xl sm:shadow-2xl p-4 sm:p-6 md:p-8 text-white text-center">
                <h2 class="text-lg sm:text-xl md:text-2xl font-semibold mb-2 sm:mb-4">{{ __('NOW SERVING') }}</h2>
                <div id="currentServing" class="text-5xl sm:text-6xl md:text-7xl font-bold mb-2 sm:mb-4 pulse-grow">
                    ---
                </div>
                <p id="currentName" class="text-base sm:text-lg md:text-xl opacity-90">{{ __('Please wait...') }}</p>
            </div>
        </div>

        <!-- Next in Queue (Numbers Only) -->
        <div class="max-w-4xl mx-auto mb-6 sm:mb-8">
            <div class="bg-white dark:bg-slate-800/95 rounded-lg sm:rounded-xl shadow-md sm:shadow-lg dark:shadow-slate-900/50 p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-semibold text-slate-800 dark:text-white mb-3 sm:mb-4 text-center">{{ __('Coming Up Next') }}</h3>
                <div id="nextQueue" class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-4">
                    <!-- Will be populated by JavaScript -->
                </div>

                <!-- Empty State for Next -->
                <div id="nextEmptyState" class="text-center py-4 text-slate-400 dark:text-slate-500 hidden">
                    <p class="text-sm">{{ __('No upcoming') }}</p>
                </div>
            </div>
        </div>

        <!-- Queue Count -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white dark:bg-slate-800/95 rounded-lg sm:rounded-xl shadow-md sm:shadow-lg dark:shadow-slate-900/50 p-4 sm:p-6 text-center">
                <h3 class="text-base sm:text-lg font-semibold text-slate-800 dark:text-white mb-3">{{ __('Waiting Queue') }}</h3>
                <div id="totalWaiting" class="text-4xl sm:text-5xl font-bold text-indigo-600 dark:text-indigo-400 mb-2">0</div>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('waiting') }}</p>
            </div>
        </div>

        <!-- Book Appointment Link -->
        <div class="text-center mt-6 sm:mt-8">
            <a href="{{ route('customer.booking') }}" class="inline-block bg-indigo-600 dark:bg-indigo-500 hover:bg-indigo-700 dark:hover:bg-indigo-600 text-white font-semibold py-2.5 sm:py-3 px-6 sm:px-8 text-sm sm:text-base rounded-lg transition duration-200 transform hover:scale-105">
                {{ __('Book New Appointment') }}
            </a>
        </div>
    </div>

    <script>
        let pollingInterval;

        async function loadQueueData() {
            try {
                const response = await fetch('/api/queue', {
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    updateCurrentServing(data.data.current);
                    updateWaitingQueue(data.data.queues);
                }
            } catch (error) {
                console.error('Error loading queue:', error);
            }
        }

        function updateCurrentServing(current) {
            const servingNumber = document.getElementById('currentServing');
            const servingName = document.getElementById('currentName');

            if (current) {
                servingNumber.textContent = current.queue_number.toString().padStart(3, '0');
                servingName.textContent = '{{ __("Please wait...") }}';
            } else {
                servingNumber.textContent = '---';
                servingName.textContent = '{{ __("No one is being served") }}';
            }
        }

        function updateWaitingQueue(queues) {
            const waitingList = queues.filter(q => q.status === 'waiting');
            const nextQueue = waitingList.slice(0, 4);

            // Update next 4 - numbers only
            const nextQueueEl = document.getElementById('nextQueue');
            const nextEmptyState = document.getElementById('nextEmptyState');

            if (nextQueue.length > 0) {
                let nextHTML = '';
                nextQueue.forEach(queue => {
                    nextHTML += `
                        <div class="text-center p-3 sm:p-4 bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/40 dark:to-indigo-800/40 rounded-lg border border-indigo-200 dark:border-indigo-700/50">
                            <div class="text-2xl sm:text-3xl font-bold text-indigo-600 dark:text-indigo-300">${String(queue.queue_number).padStart(3, '0')}</div>
                        </div>
                    `;
                });
                nextQueueEl.innerHTML = nextHTML;
                nextQueueEl.classList.remove('hidden');
                nextEmptyState.classList.add('hidden');
            } else {
                nextQueueEl.classList.add('hidden');
                nextEmptyState.classList.remove('hidden');
            }

            // Update total waiting count
            document.getElementById('totalWaiting').textContent = waitingList.length;
        }

        // Start polling
        function startPolling() {
            loadQueueData(); // Load immediately
            pollingInterval = setInterval(loadQueueData, 600000); // Then every 10 minutes
        }

        // Stop polling when page is hidden
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(pollingInterval);
            } else {
                startPolling();
            }
        });

        // Start on load
        window.addEventListener('DOMContentLoaded', startPolling);

        // Cleanup on unload
        window.addEventListener('beforeunload', () => {
            clearInterval(pollingInterval);
        });

        // Change Language Function
        function changeLanguage(lang) {
            window.location.href = '/change-language/' + lang;
        }
    </script>
    <script src="/js/dark-mode.js"></script>
</body>
</html>
