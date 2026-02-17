<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('My Queue Status') }} - {{ tenant()->name }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="/css/dark-mode-enhancements.css">

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
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse-slow {
            animation: pulse-slow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-indigo-100 dark:from-slate-900 dark:to-slate-800 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Dark Mode Toggle -->
        <div class="flex justify-end mb-4">
            <button onclick="toggleDarkMode()"
                class="p-2 rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors shadow-sm"
                title="{{ __('Toggle Dark Mode') }}">
                <span id="dark-mode-icon" class="text-xl">🌙</span>
            </button>
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-slate-800 dark:text-white mb-2">{{ tenant()->name }}</h1>
            <p class="text-slate-600 dark:text-slate-300">{{ __('Check Your Queue Status') }}</p>
        </div>

        <!-- Queue Status Card -->
        <div class="max-w-2xl mx-auto">

            @if(request()->has('queue_number'))
                <!-- Queue Status Display -->
                <div id="queueStatusCard" class="bg-white dark:bg-slate-800/95 rounded-2xl shadow-xl dark:shadow-slate-900/50 p-8 mb-6">
                    <div class="text-center">
                        <div class="mb-6">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-indigo-100 dark:bg-indigo-900/50 rounded-full mb-4">
                                <svg class="w-10 h-10 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">{{ __('Your Queue Number') }}</h2>
                            <div class="text-6xl font-bold text-indigo-600 dark:text-indigo-400 mb-4" id="queueNumber">
                                <span class="pulse-slow">--</span>
                            </div>
                        </div>

                        <div class="border-t border-slate-200 dark:border-slate-600 pt-6 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600 dark:text-slate-300">{{ __('Status') }}:</span>
                                <span id="queueStatus" class="px-4 py-2 bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 rounded-full font-semibold">
                                    {{ __('Waiting') }}
                                </span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-slate-600 dark:text-slate-300">{{ __('People Ahead') }}:</span>
                                <span id="peopleAhead" class="text-2xl font-bold text-slate-800 dark:text-white">--</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-slate-600 dark:text-slate-300">{{ __('Estimated Wait Time') }}:</span>
                                <span id="estimatedTime" class="text-xl font-semibold text-slate-800 dark:text-white">--</span>
                            </div>
                        </div>

                        <div id="currentlyServingCard" class="mt-6 p-4 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg border border-emerald-200 dark:border-emerald-700">
                            <p class="text-sm text-slate-600 dark:text-slate-300 mb-1">{{ __('Currently Serving') }}</p>
                            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400" id="currentlyServing">--</p>
                        </div>
                    </div>

                    <!-- Alert Message -->
                    <div id="alertMessage" class="hidden mt-6 p-4 rounded-lg">
                        <p class="font-semibold"></p>
                    </div>
                </div>
            @else
                <!-- Search Form -->
                <div class="bg-white dark:bg-slate-800/95 rounded-2xl shadow-xl dark:shadow-slate-900/50 p-8">
                    <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-6 text-center">
                        {{ __('Check Your Queue Status') }}
                    </h2>

                    <form id="queueSearchForm" class="space-y-6">
                        @csrf
                        <div>
                            <label for="queue_number_input" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                                {{ __('Enter Your Queue Number') }}
                            </label>
                            <input type="number"
                                id="queue_number_input"
                                name="queue_number"
                                required
                                class="w-full px-4 py-3 text-center text-2xl font-bold border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-400"
                                placeholder="000">
                        </div>

                        <button type="submit"
                            class="w-full bg-indigo-600 dark:bg-indigo-500 text-white py-3 px-6 rounded-lg font-semibold hover:bg-indigo-700 dark:hover:bg-indigo-600 transition duration-200 shadow-lg hover:shadow-xl">
                            {{ __('Check Status') }}
                        </button>
                    </form>
                </div>
            @endif

            <!-- Back Button -->
            <div class="text-center mt-6">
                <a href="{{ route('customer.booking') }}"
                    class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    {{ __('Back to Booking') }}
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(request()->has('queue_number'))
                const queueNumber = {{ request('queue_number') }};
                fetchQueueStatus(queueNumber);

                // Auto-refresh every 10 seconds
                setInterval(() => fetchQueueStatus(queueNumber), 10000);
            @else
                // Handle search form
                document.getElementById('queueSearchForm')?.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const queueNumber = document.getElementById('queue_number_input').value;
                    window.location.href = `{{ route('customer.my-queue') }}?queue_number=${queueNumber}`;
                });
            @endif
        });

        function fetchQueueStatus(queueNumber) {
            fetch(`/api/queue/status/${queueNumber}`, {
                headers: {
                    'X-Tenant': '{{ tenant()->id }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateQueueDisplay(data.data);
                } else {
                    showError(data.message || '{{ __("Queue not found") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('{{ __("Error fetching queue status") }}');
            });
        }

        function updateQueueDisplay(queueData) {
            // Update queue number
            document.getElementById('queueNumber').textContent = queueData.queue_number || '--';

            // Update status
            const statusEl = document.getElementById('queueStatus');
            const statusMap = {
                'Waiting': { text: '{{ __("Waiting") }}', class: 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' },
                'Serving': { text: '{{ __("Your Turn") }}', class: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300' },
                'Served': { text: '{{ __("Completed") }}', class: 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300' },
                'Skipped': { text: '{{ __("Skipped") }}', class: 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' }
            };

            const status = statusMap[queueData.status] || statusMap['Waiting'];
            statusEl.textContent = status.text;
            statusEl.className = `px-4 py-2 rounded-full font-semibold ${status.class}`;

            // Update people ahead
            document.getElementById('peopleAhead').textContent = queueData.people_ahead || 0;

            // Update estimated time
            const estimatedMinutes = queueData.estimated_wait_time || 0;
            document.getElementById('estimatedTime').textContent =
                estimatedMinutes > 0 ? `~${estimatedMinutes} {{ __("minutes") }}` : '{{ __("Soon") }}';

            // Update currently serving
            document.getElementById('currentlyServing').textContent =
                queueData.currently_serving || '--';

            // Show alert if it's your turn
            if (queueData.status === 'Serving') {
                showAlert('{{ __("It\'s your turn! Please proceed to the counter.") }}', 'success');
                // Play notification sound (optional)
                playNotificationSound();
            } else if (queueData.people_ahead <= 1 && queueData.status === 'Waiting') {
                showAlert('{{ __("You\'re next! Please be ready.") }}', 'warning');
            }
        }

        function showAlert(message, type = 'info') {
            const alertEl = document.getElementById('alertMessage');
            const colorMap = {
                'success': 'bg-emerald-100 dark:bg-emerald-900/30 border-emerald-500 dark:border-emerald-700 text-emerald-800 dark:text-emerald-300',
                'warning': 'bg-amber-100 dark:bg-amber-900/30 border-amber-500 dark:border-amber-700 text-amber-800 dark:text-amber-300',
                'error': 'bg-red-100 dark:bg-red-900/30 border-red-500 dark:border-red-700 text-red-800 dark:text-red-300',
                'info': 'bg-indigo-100 dark:bg-indigo-900/30 border-indigo-500 dark:border-indigo-700 text-indigo-800 dark:text-indigo-300'
            };

            alertEl.className = `mt-6 p-4 rounded-lg border-l-4 ${colorMap[type] || colorMap['info']}`;
            alertEl.querySelector('p').textContent = message;
            alertEl.classList.remove('hidden');
        }

        function showError(message) {
            showAlert(message, 'error');
        }

        function playNotificationSound() {
            // Optional: Add notification sound
            try {
                const audio = new Audio('/sounds/notification.mp3');
                audio.play().catch(e => console.log('Audio play failed:', e));
            } catch (e) {
                console.log('Audio not supported');
            }
        }
    </script>
    <script src="/js/dark-mode.js"></script>
</body>
</html>
