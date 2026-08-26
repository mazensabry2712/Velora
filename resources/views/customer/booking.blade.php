<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $businessSettings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
        $businessName = $businessSettings->business_name ?? tenant()->name ?? config('app.name');
        if (is_array($businessName)) {
            $businessName = $businessName[app()->getLocale()] ?? $businessName['en'] ?? reset($businessName) ?? config('app.name');
        }
        $businessName = (string) $businessName;
        $businessLogo = $businessSettings->logo ?? null;
    @endphp
    <title>{{ __('Book Appointment') }} - {{ $businessName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link rel="stylesheet" href="/css/dark-mode-enhancements.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-100 text-slate-900 dark:from-slate-950 dark:to-slate-900 dark:text-white">
    <div class="mx-auto w-full max-w-3xl px-4 py-5 sm:py-8">
        <header class="mb-6 text-center sm:mb-8">
            <div class="mb-4 flex items-center justify-end gap-2">
                <button type="button" onclick="toggleDarkMode()" class="rounded-lg border border-slate-200 bg-white p-2 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700" aria-label="{{ __('Toggle Dark Mode') }}">
                    <span id="dark-mode-icon">🌙</span>
                </button>
                @if (is_array($availableLanguages) && count($availableLanguages) > 1)
                    <div class="flex flex-wrap gap-1 rounded-lg border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        @php $labels = ['en'=>'EN','ar'=>'عربي','fr'=>'FR','es'=>'ES','de'=>'DE','it'=>'IT','pt'=>'PT','ru'=>'RU','zh'=>'中文','ja'=>'日本']; @endphp
                        @foreach ($availableLanguages as $code)
                            @if (isset($labels[$code]))
                                <button type="button" onclick="changeLanguage('{{ $code }}')" class="rounded-md px-3 py-1.5 text-xs font-semibold {{ app()->getLocale() === $code ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700' }}">{{ $labels[$code] }}</button>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($businessLogo)
                <img src="{{ asset('storage/' . $businessLogo) }}" alt="{{ $businessName }}" class="mx-auto mb-3 h-16 w-auto object-contain sm:h-20">
            @endif
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $businessName }}</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300 sm:text-base">{{ __('Book your appointment online') }}</p>
        </header>

        <main class="rounded-2xl bg-white/95 p-4 shadow-xl ring-1 ring-slate-200 backdrop-blur dark:bg-slate-900/95 dark:ring-slate-800 sm:p-6 md:p-8">
            <div id="loadingBanner" class="mb-4 hidden rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/50 dark:text-indigo-200"></div>
            <div id="errorMessage" class="mb-4 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                <strong>{{ __('Booking Failed') }}</strong>
                <span id="errorText" class="block mt-1"></span>
            </div>

            <form id="bookingForm" class="space-y-5">
                @csrf

                <section>
                    <div class="mb-3 flex items-center justify-between">
                        <div><span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">1</span><h2 class="text-lg font-semibold">{{ __('Your Details') }}</h2></div>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('Required fields are marked *') }}</span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="mb-1.5 block text-sm font-medium">{{ __('Full Name') }} *</label>
                            <input id="name" name="name" required autocomplete="name" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none ring-indigo-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-800" placeholder="{{ __('Enter your full name') }}">
                        </div>
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium">{{ __('Email') }} *</label>
                            <input id="email" name="email" type="email" required autocomplete="email" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none ring-indigo-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-800" placeholder="{{ __('Enter your email') }}">
                        </div>
                        <div>
                            <label for="phone" class="mb-1.5 block text-sm font-medium">{{ __('Phone Number') }} *</label>
                            <input id="phone" name="phone" type="tel" required autocomplete="tel" inputmode="tel" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none ring-indigo-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-800" placeholder="{{ __('Enter your phone number') }}">
                        </div>
                    </div>
                </section>

                <section class="border-t border-slate-200 pt-5 dark:border-slate-800">
                    <div class="mb-3"><span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">2</span><h2 class="text-lg font-semibold">{{ __('Choose Service') }}</h2></div>
                    <label for="service_id" class="mb-1.5 block text-sm font-medium">{{ __('Service Type') }} *</label>
                    <select id="service_id" name="service_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none ring-indigo-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-800">
                        <option value="">{{ __('Loading services...') }}</option>
                    </select>
                    <p id="serviceHint" class="mt-1.5 hidden text-xs text-slate-500 dark:text-slate-400"></p>
                </section>

                <section id="staffSection" class="hidden border-t border-slate-200 pt-5 dark:border-slate-800">
                    <div class="mb-3"><span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">3</span><h2 class="text-lg font-semibold">{{ __('Choose Staff') }}</h2></div>
                    <label for="staff_id" class="mb-1.5 block text-sm font-medium">{{ __('Select Staff') }} *</label>
                    <select id="staff_id" name="staff_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none ring-indigo-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-800">
                        <option value="">{{ __('Select Staff Member') }}</option>
                    </select>
                </section>

                <section id="dateSection" class="hidden border-t border-slate-200 pt-5 dark:border-slate-800">
                    <div class="mb-3"><span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">4</span><h2 class="text-lg font-semibold">{{ __('Choose Date') }}</h2></div>
                    <label for="appointment_date" class="mb-1.5 block text-sm font-medium">{{ __('Appointment Date') }} *</label>
                    <input id="appointment_date" name="appointment_date" type="date" required min="{{ now()->toDateString() }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none ring-indigo-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-800">
                    <p id="dateHint" class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Select a date to see live availability.') }}</p>
                </section>

                <section id="timeSection" class="hidden border-t border-slate-200 pt-5 dark:border-slate-800">
                    <div class="mb-3"><span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">5</span><h2 class="text-lg font-semibold">{{ __('Choose Time') }}</h2></div>
                    <label for="appointment_time" class="mb-1.5 block text-sm font-medium">{{ __('Appointment Time') }} *</label>
                    <select id="appointment_time" name="appointment_time" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none ring-indigo-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-800">
                        <option value="">{{ __('Select time') }}</option>
                    </select>
                    <p id="timeHint" class="mt-2 text-xs text-slate-500 dark:text-slate-400"></p>
                </section>

                <section id="notesSection" class="hidden border-t border-slate-200 pt-5 dark:border-slate-800">
                    <div class="mb-3"><span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">6</span><h2 class="text-lg font-semibold">{{ __('Final Details') }}</h2></div>
                    <label for="notes" class="mb-1.5 block text-sm font-medium">{{ __('Additional Notes') }}</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="1000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none ring-indigo-500 focus:ring-2 dark:border-slate-700 dark:bg-slate-800" placeholder="{{ __('Any special requests or notes...') }}"></textarea>
                </section>

                <button type="submit" id="submitBtn" disabled class="hidden w-full rounded-lg bg-indigo-600 px-4 py-3.5 font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    {{ __('Book Appointment') }}
                </button>
            </form>

            <section id="successMessage" class="hidden mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/30">
                <div class="flex gap-3">
                    <div class="mt-0.5 text-xl">✅</div>
                    <div class="flex-1">
                        <h2 class="font-bold text-emerald-800 dark:text-emerald-200">{{ __('Appointment Booked Successfully!') }}</h2>
                        <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">{{ __('Keep the queue number below so you can follow your turn.') }}</p>
                        <div id="queueNumberDisplay" class="mt-4 rounded-lg bg-white p-4 text-center shadow-sm dark:bg-slate-900">
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">{{ __('Your Queue Number') }}</p>
                            <p id="queueNumberText" class="mt-1 text-4xl font-black text-indigo-600 dark:text-indigo-400"></p>
                            <a href="{{ route('customer.queue.status') }}" class="mt-2 inline-block text-sm font-semibold text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Check Queue Status') }} →</a>
                        </div>
                        <button type="button" onclick="window.location.reload()" class="mt-4 w-full rounded-lg border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 dark:border-emerald-800 dark:bg-slate-900 dark:text-emerald-200">{{ __('Book another appointment') }}</button>
                    </div>
                </div>
            </section>
        </main>

        <footer class="mt-6 text-center">
            <a href="{{ route('customer.queue.status') }}" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">{{ __('Check Queue Status') }} →</a>
        </footer>
    </div>

    <script>
        const currentLang = @json(app()->getLocale());
        const defaultTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || @json(config('app.timezone'));
        const els = {
            form: document.getElementById('bookingForm'),
            service: document.getElementById('service_id'),
            serviceHint: document.getElementById('serviceHint'),
            staffSection: document.getElementById('staffSection'),
            staff: document.getElementById('staff_id'),
            dateSection: document.getElementById('dateSection'),
            date: document.getElementById('appointment_date'),
            dateHint: document.getElementById('dateHint'),
            timeSection: document.getElementById('timeSection'),
            time: document.getElementById('appointment_time'),
            timeHint: document.getElementById('timeHint'),
            notesSection: document.getElementById('notesSection'),
            submit: document.getElementById('submitBtn'),
            loading: document.getElementById('loadingBanner'),
            error: document.getElementById('errorMessage'),
            errorText: document.getElementById('errorText'),
            success: document.getElementById('successMessage'),
            queue: document.getElementById('queueNumberText')
        };

        const text = {
            selectService: @json(__('Select Service Type')),
            loadingServices: @json(__('Loading services...')),
            selectStaff: @json(__('Select Staff Member')),
            loadingStaff: @json(__('Loading staff...')),
            selectDate: @json(__('Select a date to see live availability.')),
            loadingSlots: @json(__('Checking available times...')),
            noSlots: @json(__('No times are available for this date. Please choose another date.')),
            selectTime: @json(__('Select time')),
            chooseTime: @json(__('Choose an available time to continue.')),
            noStaff: @json(__('No staff members are currently available for this service.')),
            network: @json(__('Unable to load availability. Please try again.')),
            booking: @json(__('Booking...')),
            book: @json(__('Book Appointment')),
        };

        function setLoading(message) {
            els.loading.textContent = message;
            els.loading.classList.remove('hidden');
        }

        function clearLoading() {
            els.loading.classList.add('hidden');
        }

        function showError(message) {
            els.errorText.textContent = message;
            els.error.classList.remove('hidden');
        }

        function hideError() {
            els.error.classList.add('hidden');
            els.errorText.textContent = '';
        }

        function resetFrom(level) {
            hideError();
            if (level <= 1) {
                els.staffSection.classList.add('hidden');
                els.staff.innerHTML = `<option value="">${text.selectStaff}</option>`;
                els.dateSection.classList.add('hidden');
                els.date.value = '';
            }
            if (level <= 2) {
                els.timeSection.classList.add('hidden');
                els.time.innerHTML = `<option value="">${text.selectTime}</option>`;
                els.notesSection.classList.add('hidden');
                els.submit.disabled = true;
            }
        }

        async function loadServices() {
            setLoading(text.loadingServices);
            try {
                const response = await fetch('/api/booking/services', { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();
                if (!response.ok || !payload.success) throw new Error(payload.message || text.network);

                els.service.innerHTML = `<option value="">${text.selectService}</option>`;
                payload.data.forEach(service => {
                    const option = document.createElement('option');
                    option.value = service.id;
                    const name = currentLang === 'ar' && service.name_ar ? service.name_ar : service.name;
                    const duration = service.duration_minutes || service.duration;
                    option.textContent = duration ? `${name} (${duration} min)` : name;
                    els.service.appendChild(option);
                });

                if (!payload.data.length) {
                    showError(@json(__('No online-bookable services are available right now.')));
                }
            } catch (error) {
                showError(text.network);
            } finally {
                clearLoading();
            }
        }

        els.service.addEventListener('change', async () => {
            resetFrom(1);
            if (!els.service.value) return;

            setLoading(text.loadingStaff);
            try {
                const response = await fetch(`/api/booking/staff/by-service/${encodeURIComponent(els.service.value)}`, { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();
                if (!response.ok || !payload.success) throw new Error(payload.message || text.network);

                if (!payload.data.length) {
                    showError(text.noStaff);
                    return;
                }

                els.staff.innerHTML = `<option value="">${text.selectStaff}</option>`;
                payload.data.forEach(staff => {
                    const option = document.createElement('option');
                    option.value = staff.id;
                    option.textContent = staff.name;
                    els.staff.appendChild(option);
                });
                els.staffSection.classList.remove('hidden');
            } catch (error) {
                showError(text.network);
            } finally {
                clearLoading();
            }
        });

        els.staff.addEventListener('change', () => {
            resetFrom(2);
            if (!els.staff.value) return;
            els.dateSection.classList.remove('hidden');
            els.date.focus();
        });

        els.date.addEventListener('change', async () => {
            resetFrom(2);
            if (!els.date.value || !els.staff.value || !els.service.value) return;

            setLoading(text.loadingSlots);
            els.dateHint.textContent = text.loadingSlots;
            try {
                const params = new URLSearchParams({
                    date: els.date.value,
                    staff_id: els.staff.value,
                    service_id: els.service.value,
                    timezone: defaultTimezone,
                });
                const response = await fetch(`/api/booking/available-timeslots?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
                const payload = await response.json();
                if (!response.ok || !payload.success) throw new Error(payload.message || text.network);

                els.time.innerHTML = `<option value="">${text.selectTime}</option>`;
                (payload.data || []).forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.start_time;
                    option.textContent = slot.label || slot.start_time;
                    els.time.appendChild(option);
                });

                els.timeSection.classList.remove('hidden');
                els.dateHint.textContent = payload.data?.length ? text.chooseTime : text.noSlots;
                if (!payload.data?.length) {
                    els.time.innerHTML = `<option value="">${text.noSlots}</option>`;
                }
            } catch (error) {
                els.dateHint.textContent = text.network;
                showError(text.network);
            } finally {
                clearLoading();
            }
        });

        els.time.addEventListener('change', () => {
            els.notesSection.classList.toggle('hidden', !els.time.value);
            els.submit.classList.toggle('hidden', !els.time.value);
            els.submit.disabled = !els.time.value;
        });

        els.form.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideError();
            els.submit.disabled = true;
            els.submit.textContent = text.booking;

            const form = new FormData(event.currentTarget);
            const body = {
                customer_name: form.get('name'),
                customer_email: form.get('email'),
                customer_phone: form.get('phone'),
                appointment_date: form.get('appointment_date'),
                appointment_time: form.get('appointment_time'),
                staff_id: form.get('staff_id'),
                service_id: form.get('service_id'),
                notes: form.get('notes') || null,
                timezone: defaultTimezone,
            };

            try {
                const response = await fetch('/api/appointments', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(body),
                });
                const payload = await response.json();

                if (!response.ok || !payload.success) {
                    let message = payload.message || @json(__('An error occurred. Please try again.'));
                    if (payload.errors) {
                        const errors = Object.values(payload.errors).flat();
                        if (errors.length) message = errors.join(' ');
                    }
                    if (payload.reason) message += ` ${payload.reason}`;
                    showError(message);
                    return;
                }

                els.form.classList.add('hidden');
                els.success.classList.remove('hidden');
                els.queue.textContent = payload.data?.queue?.queue_number || payload.data?.queue_number || '—';
                els.success.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } catch (error) {
                showError(@json(__('Unable to complete the booking right now. Please try again.')));
            } finally {
                els.submit.disabled = false;
                els.submit.textContent = text.book;
            }
        });

        function changeLanguage(lang) {
            window.location.href = '/change-language/' + encodeURIComponent(lang);
        }

        loadServices();
    </script>
    <script src="/js/dark-mode-booking.js"></script>
</body>
</html>
