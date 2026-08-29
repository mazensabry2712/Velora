<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f7f9fc">
    @php
        $businessName = $businessName ?? tenant()->name ?? config('app.name');
        $businessLogo = $businessLogo ?? null;
        $businessHost = request()->getHost();
    @endphp
    <title>{{ __('Book Appointment') }} - {{ $businessName }}</title>
    <link rel="stylesheet" href="/css/velora-brand.css">
    <link rel="stylesheet" href="/css/velora-public.css">
    <link rel="stylesheet" href="/css/dark-mode-enhancements.css">
    <link rel="stylesheet" href="/css/velora-booking.css">
    <style>
        .vb2-fallback-logo { display:none; }
        .vb2-logo.is-broken { display:none; }
        .vb2-logo.is-broken + .vb2-fallback-logo { display:flex; }
    </style>
</head>
<body class="vb2-page">
    <div class="vb2-shell">
        <header class="vb2-header" aria-label="{{ $businessName }}">
            <a class="vb2-brand" href="{{ url('/') }}" aria-label="{{ $businessName }}">
                <span class="vb2-logo-wrap" aria-hidden="true">
                    <img
                        class="vb2-logo"
                        src="{{ $businessLogo ? asset('storage/' . $businessLogo) : asset('logo-bais.png') }}"
                        alt=""
                        onerror="this.classList.add('is-broken');"
                    >
                    <span class="vb2-fallback-logo">
                        <img src="{{ asset('logo-bais.png') }}" alt="">
                    </span>
                </span>
                <span class="vb2-brand-copy">
                    <strong>{{ $businessName }}</strong>
                    <span>{{ $businessHost }}</span>
                </span>
            </a>

            <div class="vb2-controls">
                <button type="button" id="dark-mode-toggle" class="vb2-icon-button" onclick="toggleDarkMode()" aria-label="{{ __('Toggle Dark Mode') }}" title="{{ __('Toggle Dark Mode') }}">
                    <span id="dark-mode-icon">🌙</span>
                </button>

                @if (is_array($availableLanguages) && count($availableLanguages) > 1)
                    @php
                        $languageNames = [
                            'ar' => 'العربية', 'de' => 'Deutsch', 'en' => 'English', 'es' => 'Español',
                            'fr' => 'Français', 'hi' => 'हिन्दी', 'id' => 'Bahasa Indonesia', 'it' => 'Italiano',
                            'ja' => '日本語', 'ko' => '한국어', 'nl' => 'Nederlands', 'pt' => 'Português',
                            'ru' => 'Русский', 'tr' => 'Türkçe', 'zh' => '中文'
                        ];
                    @endphp
                    <label class="vb2-language" title="{{ __('Language') }}">
                        <span class="vb2-globe" aria-hidden="true">◉</span>
                        <select aria-label="{{ __('Language') }}" onchange="changeLanguage(this.value)">
                            @foreach ($availableLanguages as $code)
                                @if (isset($languageNames[$code]))
                                    <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $languageNames[$code] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>
        </header>

        <section class="vb2-intro">
            <div class="vb2-kicker"><span></span>{{ __('Book Appointment') }}</div>
            <h1>{{ __('Book your appointment online') }}</h1>
            <p>{{ __('Choose a service, find an available time, and confirm your appointment.') }}</p>
        </section>

        <main class="vb2-card">
            <div class="vb2-card-head">
                <div>
                    <span class="vb2-card-eyebrow">{{ $businessName }}</span>
                    <h2>{{ __('Appointment details') }}</h2>
                </div>
                <span class="vb2-secure"><span aria-hidden="true">✓</span> {{ __('Secure') }}</span>
            </div>

            <div id="loadingBanner" class="vb2-alert vb2-alert-info hidden" role="status"></div>
            <div id="errorMessage" class="vb2-alert vb2-alert-error hidden" role="alert">
                <strong>{{ __('Booking Failed') }}</strong>
                <span id="errorText"></span>
            </div>

            <form id="bookingForm">
                @csrf

                <section class="vb2-step" data-step="1">
                    <div class="vb2-step-head">
                        <div class="vb2-step-title">
                            <span class="vb2-step-number">1</span>
                            <div>
                                <span class="vb2-step-label">{{ __('Your Details') }}</span>
                                <span class="vb2-step-subtitle">{{ __('Tell us who the appointment is for.') }}</span>
                            </div>
                        </div>
                        <span class="vb2-required">{{ __('Required fields are marked *') }}</span>
                    </div>
                    <div class="vb2-fields vb2-fields-personal">
                        <div class="vb2-field vb2-field-full">
                            <label for="name">{{ __('Full Name') }} *</label>
                            <input id="name" name="name" required autocomplete="name" inputmode="text" placeholder="{{ __('Enter your full name') }}">
                        </div>
                        <div class="vb2-field">
                            <label for="email">{{ __('Email') }} *</label>
                            <input id="email" name="email" type="email" required autocomplete="email" inputmode="email" placeholder="{{ __('Enter your email') }}">
                        </div>
                        <div class="vb2-field">
                            <label for="phone">{{ __('Phone Number') }} *</label>
                            <input id="phone" name="phone" type="tel" required autocomplete="tel" inputmode="tel" placeholder="{{ __('Enter your phone number') }}">
                        </div>
                    </div>
                </section>

                <section class="vb2-step" data-step="2">
                    <div class="vb2-step-head">
                        <div class="vb2-step-title">
                            <span class="vb2-step-number">2</span>
                            <div>
                                <span class="vb2-step-label">{{ __('Choose Service') }}</span>
                                <span class="vb2-step-subtitle">{{ __('Select what you would like to book.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="vb2-field">
                        <label for="service_id">{{ __('Service Type') }} *</label>
                        <select id="service_id" name="service_id" required>
                            <option value="">{{ __('Loading services...') }}</option>
                        </select>
                        <p id="serviceHint" class="vb2-hint hidden"></p>
                    </div>
                </section>

                <section id="staffSection" class="vb2-step hidden" data-step="3">
                    <div class="vb2-step-head">
                        <div class="vb2-step-title">
                            <span class="vb2-step-number">3</span>
                            <div>
                                <span class="vb2-step-label">{{ __('Choose Staff') }}</span>
                                <span class="vb2-step-subtitle">{{ __('Choose your preferred staff member.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="vb2-field">
                        <label for="staff_id">{{ __('Select Staff') }} *</label>
                        <select id="staff_id" name="staff_id" required>
                            <option value="">{{ __('Select Staff Member') }}</option>
                        </select>
                    </div>
                </section>

                <section id="dateSection" class="vb2-step hidden" data-step="4">
                    <div class="vb2-step-head">
                        <div class="vb2-step-title">
                            <span class="vb2-step-number">4</span>
                            <div>
                                <span class="vb2-step-label">{{ __('Choose Date') }}</span>
                                <span class="vb2-step-subtitle">{{ __('Pick the day that works for you.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="vb2-field">
                        <label for="appointment_date">{{ __('Appointment Date') }} *</label>
                        <input id="appointment_date" name="appointment_date" type="date" required min="{{ now()->toDateString() }}">
                        <p id="dateHint" class="vb2-hint">{{ __('Select a date to see live availability.') }}</p>
                    </div>
                </section>

                <section id="timeSection" class="vb2-step hidden" data-step="5">
                    <div class="vb2-step-head">
                        <div class="vb2-step-title">
                            <span class="vb2-step-number">5</span>
                            <div>
                                <span class="vb2-step-label">{{ __('Choose Time') }}</span>
                                <span class="vb2-step-subtitle">{{ __('Only available times are shown.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="vb2-field">
                        <label for="appointment_time">{{ __('Appointment Time') }} *</label>
                        <select id="appointment_time" name="appointment_time" required>
                            <option value="">{{ __('Select time') }}</option>
                        </select>
                        <p id="timeHint" class="vb2-hint"></p>
                    </div>
                </section>

                <section id="notesSection" class="vb2-step hidden" data-step="6">
                    <div class="vb2-step-head">
                        <div class="vb2-step-title">
                            <span class="vb2-step-number">6</span>
                            <div>
                                <span class="vb2-step-label">{{ __('Final Details') }}</span>
                                <span class="vb2-step-subtitle">{{ __('Anything else we should know?') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="vb2-field">
                        <label for="notes">{{ __('Additional Notes') }}</label>
                        <textarea id="notes" name="notes" rows="4" maxlength="1000" placeholder="{{ __('Any special requests or notes...') }}"></textarea>
                    </div>
                </section>

                <div class="vb2-submit-wrap">
                    <button type="submit" id="submitBtn" disabled class="vb2-submit hidden">
                        <span>{{ __('Book Appointment') }}</span>
                        <span aria-hidden="true">→</span>
                    </button>
                    <p class="vb2-submit-note">{{ __('Your appointment will be confirmed instantly after submission.') }}</p>
                </div>
            </form>

            <section id="successMessage" class="vb2-success hidden">
                <div class="vb2-success-icon" aria-hidden="true">✓</div>
                <div class="vb2-success-copy">
                    <span class="vb2-card-eyebrow">{{ __('Confirmed') }}</span>
                    <h2>{{ __('Appointment Booked Successfully!') }}</h2>
                    <p>{{ __('Keep the queue number below so you can follow your turn.') }}</p>
                    <div id="queueNumberDisplay" class="vb2-queue-card">
                        <span>{{ __('Your Queue Number') }}</span>
                        <strong id="queueNumberText">—</strong>
                        <a href="{{ route('customer.queue.status') }}">{{ __('Check Queue Status') }} →</a>
                    </div>
                    <button type="button" onclick="window.location.reload()" class="vb2-secondary-action">{{ __('Book another appointment') }}</button>
                </div>
            </section>
        </main>

        <footer class="vb2-footer">
            <a href="{{ route('customer.queue.status') }}">{{ __('Check Queue Status') }} →</a>
            <span>{{ date('Y') }}</span>
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
                els.submit.classList.add('hidden');
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
                    const name = service.name_localized || (currentLang === 'ar' && service.name_ar ? service.name_ar : service.name);
                    const duration = service.duration_minutes || service.duration;
                    option.textContent = duration ? `${name} (${duration} min)` : name;
                    els.service.appendChild(option);
                });

                if (!payload.data.length) showError(@json(__('No online-bookable services are available right now.')));
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
                els.staff.focus();
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
                if (!payload.data?.length) els.time.innerHTML = `<option value="">${text.noSlots}</option>`;
                else els.time.focus();
            } catch (error) {
                els.dateHint.textContent = text.network;
                showError(text.network);
            } finally {
                clearLoading();
            }
        });

        els.time.addEventListener('change', () => {
            const hasTime = Boolean(els.time.value);
            els.notesSection.classList.toggle('hidden', !hasTime);
            els.submit.classList.toggle('hidden', !hasTime);
            els.submit.disabled = !hasTime;
            if (hasTime) els.notes.focus();
        });

        els.form.addEventListener('submit', async (event) => {
            event.preventDefault();
            hideError();
            els.submit.disabled = true;
            els.submit.querySelector('span:first-child').textContent = text.booking;

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
                els.submit.querySelector('span:first-child').textContent = text.book;
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
