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
    <title>{{ __('Book Appointment') }} - {{ $businessName }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/dark-mode-enhancements.css">
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <!-- Dark Mode Prevention Script - Booking Page Only -->
    <script>
        // يتم تنفيذ هذا الكود فوراً قبل عرض الصفحة - خاص بصفحة الحجز
        (function() {
            const savedMode = localStorage.getItem('bookingDarkMode');
            if (savedMode === 'true' || (savedMode === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else if (savedMode === 'false') {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>
<body class="bg-gradient-to-br from-slate-50 to-indigo-100 dark:from-slate-900 dark:to-slate-800 min-h-screen">
    <!-- DEBUG INFO (temporary) -->
    @php
        echo "<!-- DEBUG: availableLanguages = " . json_encode($availableLanguages ?? 'NOT SET') . " -->";
        echo "<!-- DEBUG: count = " . (isset($availableLanguages) ? count($availableLanguages) : 'N/A') . " -->";
    @endphp

    <div class="container mx-auto px-3 sm:px-4 py-4 sm:py-8">
        <!-- Header with Language Switcher -->
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
                @if(isset($availableLanguages) && is_array($availableLanguages) && count($availableLanguages) > 1)
                @php
                    $languageLabels = [
                        'en' => 'EN',
                        'ar' => 'عربي',
                        'fr' => 'FR',
                        'es' => 'ES',
                        'de' => 'DE',
                        'it' => 'IT',
                        'pt' => 'PT',
                        'ru' => 'RU',
                        'zh' => '中文',
                        'ja' => '日本語',
                    ];
                @endphp
                <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 p-1 shadow-sm {{ count($availableLanguages) > 4 ? 'flex-wrap' : '' }}">
                    @foreach($availableLanguages as $langCode)
                        @if(isset($languageLabels[$langCode]))
                        <button onclick="changeLanguage('{{ $langCode }}')"
                            class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium rounded-md transition-all duration-200 {{ app()->getLocale() === $langCode ? 'bg-indigo-600 dark:bg-indigo-500 text-white shadow-sm' : 'text-slate-600 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white' }}">
                            {{ $languageLabels[$langCode] }}
                        </button>
                        @endif
                    @endforeach
                </div>
                @else
                <!-- DEBUG: Language switcher NOT displayed -->
                <!-- Reason: {{ !isset($availableLanguages) ? 'Variable not set' : (!is_array($availableLanguages) ? 'Not an array' : (count($availableLanguages) <= 1 ? 'Only ' . count($availableLanguages) . ' language' : 'Unknown')) }} -->
                @endif
            </div>

            <div class="text-center">
                @if($businessLogo)
                    <img src="{{ asset('storage/' . $businessLogo) }}" alt="{{ $businessName }}" class="h-16 sm:h-20 w-auto mx-auto mb-3 sm:mb-4">
                @endif
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-800 dark:text-white mb-1 sm:mb-2">{{ $businessName }}</h1>
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300">{{ __('Book your appointment online') }}</p>
            </div>
        </div>

        <!-- Booking Form -->
        <div class="max-w-2xl mx-auto bg-white dark:bg-slate-800/90 rounded-xl sm:rounded-2xl shadow-lg sm:shadow-xl p-4 sm:p-6 md:p-8 dark:shadow-slate-900/50">
            <form id="bookingForm" class="space-y-4 sm:space-y-6">
                @csrf

                <!-- Customer Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5 sm:mb-2">
                        {{ __('Full Name') }} <span class="text-red-500 dark:text-red-400">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-400"
                        placeholder="{{ __('Enter your full name') }}">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5 sm:mb-2">
                        {{ __('Email') }} <span class="text-red-500 dark:text-red-400">*</span>
                    </label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-400"
                        placeholder="{{ __('Enter your email') }}">
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5 sm:mb-2">
                        {{ __('Phone Number') }} <span class="text-red-500 dark:text-red-400">*</span>
                    </label>
                    <input type="tel" id="phone" name="phone" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-400"
                        placeholder="{{ __('Enter your phone number') }}">
                </div>

                <!-- Step 1: Service Type -->
                <div>
                    <label for="service_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5 sm:mb-2">
                        {{ __('Service Type') }} <span class="text-red-500 dark:text-red-400">*</span>
                    </label>
                    <select id="service_id" name="service_id" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100">
                        <option value="">{{ __('Select Service Type') }}</option>
                        <!-- Will be populated dynamically -->
                    </select>
                </div>

                <!-- Step 2: Staff (appears after selecting service) -->
                <div id="staffSection" class="hidden">
                    <label for="staff_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5 sm:mb-2">
                        {{ __('Select Staff') }} <span class="text-red-500 dark:text-red-400">*</span>
                    </label>
                    <select id="staff_id" name="staff_id" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100">
                        <option value="">{{ __('Select Staff Member') }}</option>
                        <!-- Will be populated dynamically based on service -->
                    </select>
                </div>

                <!-- Step 3: Date (appears after selecting staff) -->
                <div id="dateSection" class="hidden">
                    <label for="appointment_date" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5 sm:mb-2">
                        {{ __('Appointment Date') }} <span class="text-red-500 dark:text-red-400">*</span>
                    </label>
                    <input type="date" id="appointment_date" name="appointment_date" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100"
                        min="{{ date('Y-m-d') }}">
                    <p id="availableDays" class="mt-1 text-sm text-slate-500 dark:text-slate-400"></p>
                </div>

                <!-- Step 4: Time (appears after selecting date) -->
                <div id="timeSection" class="hidden">
                    <label for="appointment_time" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5 sm:mb-2">
                        {{ __('Appointment Time') }} <span class="text-red-500 dark:text-red-400">*</span>
                    </label>
                    <select id="appointment_time" name="appointment_time" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100">
                        <option value="">{{ __('Select time') }}</option>
                        <!-- Will be populated dynamically based on staff schedule -->
                    </select>
                </div>

                <!-- Notes -->
                <div id="notesSection" class="hidden">
                    <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1.5 sm:mb-2">
                        {{ __('Additional Notes') }}
                    </label>
                    <textarea id="notes" name="notes" rows="3"
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-400"
                        placeholder="{{ __('Any special requests or notes...') }}"></textarea>
                </div>

                <!-- Hidden field to add to queue automatically -->
                <input type="hidden" name="add_to_queue" value="1">

                <!-- Submit Button -->
                <button type="submit" id="submitBtn"
                    class="hidden w-full bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-semibold py-3 sm:py-4 px-4 sm:px-6 text-sm sm:text-base rounded-lg transition duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:ring-offset-2">
                    {{ __('Book Appointment') }}
                </button>
            </form>

            <!-- Success Message -->
            <div id="successMessage" class="hidden mt-4 sm:mt-6 p-3 sm:p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-500 dark:text-emerald-400 flex-shrink-0 {{ app()->getLocale() === 'ar' ? 'ml-2 sm:ml-3' : 'mr-2 sm:mr-3' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="font-semibold text-emerald-800 dark:text-emerald-300 text-sm sm:text-base">{{ __('Appointment Booked Successfully!') }}</p>
                        <p class="text-xs sm:text-sm text-emerald-700 dark:text-emerald-400 mt-1">{{ __('You will receive a confirmation email shortly.') }}</p>

                        <!-- Queue Number Display -->
                        <div id="queueNumberDisplay" class="hidden mt-3 p-3 bg-white dark:bg-slate-800 rounded-lg border border-emerald-200 dark:border-emerald-700">
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mb-1">{{ __('Your Queue Number') }}:</p>
                            <p class="text-2xl sm:text-3xl font-bold text-indigo-600 dark:text-indigo-400" id="queueNumberText"></p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                                {{ __('Save this number to check your queue status') }}
                                <a href="/queue/status" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">{{ __('here') }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="hidden mt-4 sm:mt-6 p-3 sm:p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 text-red-500 dark:text-red-400 flex-shrink-0 {{ app()->getLocale() === 'ar' ? 'ml-2 sm:ml-3' : 'mr-2 sm:mr-3' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <div>
                        <p class="font-semibold text-red-800 dark:text-red-300 text-sm sm:text-base">{{ __('Booking Failed') }}</p>
                        <p class="text-xs sm:text-sm text-red-700 dark:text-red-400" id="errorText"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Queue Status Link -->
        <div class="text-center mt-6 sm:mt-8">
            <a href="{{ route('queue.status') }}" class="text-sm sm:text-base text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">
                {{ __('Check Queue Status') }} →
            </a>
        </div>
    </div>

    <script>
        const currentLang = '{{ app()->getLocale() }}';
        let staffSchedules = [];

        // Load services on page load
        async function loadServices() {
            try {
                const response = await fetch('/api/booking/services');
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    const serviceSelect = document.getElementById('service_id');
                    serviceSelect.innerHTML = '<option value="">{{ __('Select Service Type') }}</option>';

                    data.data.forEach(service => {
                        const option = document.createElement('option');
                        option.value = service.id;
                        option.textContent = currentLang === 'ar' && service.name_ar ? service.name_ar : service.name;
                        if (service.duration) {
                            option.textContent += ` (${service.duration} {{ __('min') }})`;
                        }
                        serviceSelect.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading services:', error);
            }
        }

        // When service is selected, load staff
        document.getElementById('service_id').addEventListener('change', async function() {
            const serviceId = this.value;

            // Hide subsequent sections
            document.getElementById('staffSection').classList.add('hidden');
            document.getElementById('dateSection').classList.add('hidden');
            document.getElementById('timeSection').classList.add('hidden');
            document.getElementById('notesSection').classList.add('hidden');
            document.getElementById('submitBtn').classList.add('hidden');

            if (!serviceId) return;

            try {
                const response = await fetch(`/api/booking/staff/by-service/${serviceId}`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    const staffSelect = document.getElementById('staff_id');
                    staffSelect.innerHTML = '<option value="">{{ __('Select Staff Member') }}</option>';

                    data.data.forEach(staff => {
                        const option = document.createElement('option');
                        option.value = staff.id;
                        option.textContent = staff.name;
                        staffSelect.appendChild(option);
                    });

                    document.getElementById('staffSection').classList.remove('hidden');
                } else {
                    alert('{{ __('No staff available for this service') }}');
                }
            } catch (error) {
                console.error('Error loading staff:', error);
            }
        });

        // When staff is selected, load their schedule
        document.getElementById('staff_id').addEventListener('change', async function() {
            const staffId = this.value;

            // Hide subsequent sections
            document.getElementById('dateSection').classList.add('hidden');
            document.getElementById('timeSection').classList.add('hidden');
            document.getElementById('notesSection').classList.add('hidden');
            document.getElementById('submitBtn').classList.add('hidden');

            if (!staffId) return;

            try {
                const response = await fetch(`/api/booking/staff/${staffId}/schedule`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    staffSchedules = data.data;

                    // Show available days hint
                    const dayNames = {
                        0: currentLang === 'ar' ? 'الأحد' : 'Sunday',
                        1: currentLang === 'ar' ? 'الإثنين' : 'Monday',
                        2: currentLang === 'ar' ? 'الثلاثاء' : 'Tuesday',
                        3: currentLang === 'ar' ? 'الأربعاء' : 'Wednesday',
                        4: currentLang === 'ar' ? 'الخميس' : 'Thursday',
                        5: currentLang === 'ar' ? 'الجمعة' : 'Friday',
                        6: currentLang === 'ar' ? 'السبت' : 'Saturday'
                    };

                    const availableDaysText = staffSchedules.map(s => dayNames[s.day_of_week]).join(', ');
                    document.getElementById('availableDays').textContent =
                        '{{ __('Available days') }}: ' + availableDaysText;

                    document.getElementById('dateSection').classList.remove('hidden');
                    document.getElementById('appointment_date').value = '';
                } else {
                    alert('{{ __('This staff member has no available schedule') }}');
                }
            } catch (error) {
                console.error('Error loading schedule:', error);
            }
        });

        // When date is selected, show available times
        document.getElementById('appointment_date').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const dayOfWeek = selectedDate.getDay();

            // Find schedule for this day
            const schedule = staffSchedules.find(s => s.day_of_week === dayOfWeek);

            if (!schedule) {
                this.setCustomValidity('{{ __('This staff member is not available on this day') }}');
                this.reportValidity();
                document.getElementById('timeSection').classList.add('hidden');
                document.getElementById('notesSection').classList.add('hidden');
                document.getElementById('submitBtn').classList.add('hidden');
                return;
            }

            this.setCustomValidity('');

            // Populate time dropdown
            const timeSelect = document.getElementById('appointment_time');
            timeSelect.innerHTML = '<option value="">{{ __('Select time') }}</option>';

            // Generate time slots based on schedule
            const startTime = schedule.start_time;
            const endTime = schedule.end_time;

            let current = new Date(`2000-01-01 ${startTime}`);
            const end = new Date(`2000-01-01 ${endTime}`);

            while (current < end) {
                const timeValue = current.toTimeString().substring(0, 5);
                const timeDisplay = current.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });

                const option = document.createElement('option');
                option.value = timeValue;
                option.textContent = timeDisplay;
                timeSelect.appendChild(option);

                // Add 30 minutes
                current.setMinutes(current.getMinutes() + 30);
            }

            document.getElementById('timeSection').classList.remove('hidden');
        });

        // When time is selected, show notes and submit button
        document.getElementById('appointment_time').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('notesSection').classList.remove('hidden');
                document.getElementById('submitBtn').classList.remove('hidden');
            } else {
                document.getElementById('notesSection').classList.add('hidden');
                document.getElementById('submitBtn').classList.add('hidden');
            }
        });

        // Handle form submission
        document.getElementById('bookingForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const successMsg = document.getElementById('successMessage');
            const errorMsg = document.getElementById('errorMessage');

            // Reset messages
            successMsg.classList.add('hidden');
            errorMsg.classList.add('hidden');

            // Disable button
            submitBtn.disabled = true;
            submitBtn.textContent = '{{ __('Booking...') }}';

            try {
                const formData = new FormData(e.target);

                const response = await fetch('/api/appointments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        customer_name: formData.get('name'),
                        customer_email: formData.get('email'),
                        customer_phone: formData.get('phone'),
                        appointment_date: formData.get('appointment_date'),
                        appointment_time: formData.get('appointment_time'),
                        staff_id: formData.get('staff_id'),
                        service_id: formData.get('service_id'),
                        notes: formData.get('notes'),
                        add_to_queue: true,
                        queue_date: formData.get('appointment_date')
                    })
                });

                const data = await response.json();

                console.log('Response data:', data);

                if (response.ok && data.success) {
                    successMsg.classList.remove('hidden');

                    // Display queue number if available
                    console.log('Queue data:', data.data?.queue);
                    if (data.data && data.data.queue && data.data.queue.queue_number) {
                        console.log('Queue number:', data.data.queue.queue_number);
                        document.getElementById('queueNumberDisplay').classList.remove('hidden');
                        document.getElementById('queueNumberText').textContent = data.data.queue.queue_number;
                    } else {
                        console.log('No queue number found in response');
                    }

                    e.target.reset();

                    // Hide all sections
                    document.getElementById('staffSection').classList.add('hidden');
                    document.getElementById('dateSection').classList.add('hidden');
                    document.getElementById('timeSection').classList.add('hidden');
                    document.getElementById('notesSection').classList.add('hidden');
                    document.getElementById('submitBtn').classList.add('hidden');

                    // Scroll to success message
                    successMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    errorMsg.classList.remove('hidden');
                    document.getElementById('errorText').textContent = data.message || '{{ __('An error occurred. Please try again.') }}';
                }
            } catch (error) {
                errorMsg.classList.remove('hidden');
                document.getElementById('errorText').textContent = '{{ __('An error occurred. Please try again.') }}';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '{{ __('Book Appointment') }}';
            }
        });

        // Load services on page load
        loadServices();

        // Change Language Function
        function changeLanguage(lang) {
            window.location.href = '/change-language/' + lang;
        }
    </script>
    <script src="/js/dark-mode-booking.js"></script>
</body>
</html>
