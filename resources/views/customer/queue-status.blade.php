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
    <title>{{ __('Queue Status') }} - {{ $businessName }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/dark-mode-enhancements.css">
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script>
        (function() {
            const savedMode = localStorage.getItem('queueDarkMode');
            if (savedMode === 'true' || (savedMode === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else if (savedMode === 'false') {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>
<body class="bg-gradient-to-br from-slate-50 to-indigo-100 dark:from-slate-900 dark:to-slate-800 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8 text-center">
            @if($businessLogo)
                <img src="{{ Storage::url($businessLogo) }}" alt="{{ $businessName }}" class="h-16 mx-auto mb-4">
            @endif
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ __('Queue Status') }}</h1>
            <p class="text-slate-600 dark:text-slate-400">{{ $businessName }}</p>
        </div>

        <!-- Queue Status Card -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg p-8">
                <div class="text-center">
                    <div class="mb-6">
                        <svg class="w-20 h-20 mx-auto text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                        {{ __('Check Your Queue Status') }}
                    </h2>

                    <form method="GET" action="{{ route('customer.queue.status') }}" class="mt-6">
                        <div class="mb-4">
                            <label for="queue_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                {{ __('Enter Your Queue Number') }}
                            </label>
                            <input type="text"
                                   id="queue_number"
                                   name="queue_number"
                                   class="w-full px-4 py-3 text-center text-2xl font-bold border border-slate-300 dark:border-slate-600
                                          rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-slate-700 dark:text-white"
                                   placeholder="A001"
                                   required>
                        </div>

                        <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-3
                                       rounded-lg shadow-lg transition duration-200 transform hover:scale-105">
                            {{ __('Check Status') }}
                        </button>
                    </form>

                    @if(request('queue_number'))
                        <div class="mt-8 p-6 bg-slate-50 dark:bg-slate-700 rounded-lg">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                                {{ __('Queue Number') }}: {{ request('queue_number') }}
                            </h3>
                            <div id="queue-status" class="text-slate-700 dark:text-slate-300">
                                {{ __('Loading...') }}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-8 text-center">
                    <a href="{{ route('customer.booking') }}"
                       class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">
                        ← {{ __('Back to Booking') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dark Mode Toggle - Queue Status Page
        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('queueDarkMode', String(isDark));
            updateDarkModeIcon();
        }

        function updateDarkModeIcon() {
            const icon = document.getElementById('dark-mode-icon');
            if (document.documentElement.classList.contains('dark')) {
                icon.textContent = '☀️';
            } else {
                icon.textContent = '🌙';
            }
        }

        // Check queue status via API
        @if(request('queue_number'))
        fetch('/api/queue/status/{{ request('queue_number') }}')
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('queue-status');
                if (data.success && data.data) {
                    const queue = data.data;
                    statusDiv.innerHTML = `
                        <div class="space-y-3">
                            <p class="text-lg"><strong>{{ __('Status') }}:</strong>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold
                                    ${queue.status === 'waiting' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' : ''}
                                    ${queue.status === 'serving' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ''}
                                    ${queue.status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ''}">
                                    ${queue.status}
                                </span>
                            </p>
                            <p><strong>{{ __('Customer') }}:</strong> ${queue.customer_name}</p>
                            <p><strong>{{ __('Service') }}:</strong> ${queue.service}</p>
                            ${queue.staff_name ? '<p><strong>{{ __("Staff") }}:</strong> ' + queue.staff_name + '</p>' : ''}
                        </div>
                    `;
                } else {
                    statusDiv.innerHTML = '<p class="text-red-600 dark:text-red-400">{{ __("Queue number not found") }}</p>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('queue-status').innerHTML =
                    '<p class="text-red-600 dark:text-red-400">{{ __("Error loading queue status") }}</p>';
            });
        @endif

        // Update icon on load
        updateDarkModeIcon();
    </script>
</body>
</html>
