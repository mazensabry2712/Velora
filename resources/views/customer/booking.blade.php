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
        $tenantLogoUrl = $businessLogo ? asset('storage/' . $businessLogo) : null;
        $fallbackLogoUrl = global_asset('logo-bais.png');
        $languageNames = ['ar'=>'العربية','de'=>'Deutsch','en'=>'English','es'=>'Español','fr'=>'Français','hi'=>'हिन्दी','id'=>'Bahasa Indonesia','it'=>'Italiano','ja'=>'日本語','ko'=>'한국어','nl'=>'Nederlands','pt'=>'Português','ru'=>'Русский','tr'=>'Türkçe','zh'=>'中文'];
    @endphp
    <title>{{ __('Book Appointment') }} - {{ $businessName }}</title>
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-booking.css') }}">
</head>
<body class="vb-page vb-final">
    <div class="vb2-shell">
        <header class="vb2-header" aria-label="{{ $businessName }}">
            <a class="vb2-brand" href="{{ url('/') }}" aria-label="{{ $businessName }}">
                <span class="vb2-logo-wrap" aria-hidden="true">
                    @if ($tenantLogoUrl)
                        <img class="vb2-logo" src="{{ $tenantLogoUrl }}" alt="" onerror="this.src='{{ $fallbackLogoUrl }}'; this.removeAttribute('onerror');">
                    @else
                        <img class="vb2-logo" src="{{ $fallbackLogoUrl }}" alt="">
                    @endif
                </span>
                <span class="vb2-brand-copy"><strong>{{ $businessName }}</strong><span>{{ $businessHost }}</span></span>
            </a>
            <div class="vb2-controls">
                <button type="button" id="dark-mode-toggle" class="vb2-icon-button" onclick="toggleDarkMode()" aria-label="{{ __('Toggle Dark Mode') }}"><span id="dark-mode-icon">🌙</span></button>
                @php $supportedLanguages = is_array($availableLanguages) ? $availableLanguages : ['ar', 'en']; @endphp
                @if(count($supportedLanguages) > 1)
                    <label class="vb2-language" title="{{ __('Language') }}">
                        <span class="vb-globe" aria-hidden="true">◎</span>
                        <select aria-label="{{ __('Language') }}" onchange="changeLanguage(this.value)">
                            @foreach ($supportedLanguages as $code)
                                @if(isset($languageNames[$code]))
                                    <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $languageNames[$code] }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>
        </header>

        <section class="vb2-intro">
            <div class="vb2-kicker">{{ __('Online booking') }}</div>
            <h1>{{ __('Book your appointment') }}</h1>
            <p>{{ __('Choose a service, pick a time, and confirm in a few simple steps.') }}</p>
        </section>

        <main class="vb2-card">
            <div id="loadingBanner" class="vb2-alert vb2-alert-info hidden" role="status" aria-live="polite"></div>
            <div id="errorMessage" class="vb2-alert vb2-alert-error hidden" role="alert"><strong>{{ __('Booking Failed') }}</strong><span id="errorText"></span></div>

            <nav class="vb-final-v2-progress" aria-label="{{ __('Booking progress') }}">
                <div class="vb-final-v2-progress-item is-active" data-step="1"><b>1</b><em>{{ __('Service') }}</em></div><div class="vb-final-v2-progress-line"></div>
                <div class="vb-final-v2-progress-item" data-step="2"><b>2</b><em>{{ __('Staff') }}</em></div><div class="vb-final-v2-progress-line"></div>
                <div class="vb-final-v2-progress-item" data-step="3"><b>3</b><em>{{ __('Date & Time') }}</em></div><div class="vb-final-v2-progress-line"></div>
                <div class="vb-final-v2-progress-item" data-step="4"><b>4</b><em>{{ __('Details') }}</em></div>
            </nav>

            <form id="bookingForm" novalidate>
                @csrf
                <section class="vb2-step vb-final-v2-active" data-step="2" data-step-panel="1">
                    <div class="vb-final-v2-panel-head"><span>1</span><div><h2>{{ __('Choose a service') }}</h2><p>{{ __('Start with what you would like to book.') }}</p></div></div>
                    <div id="serviceCards" class="vb-final-v2-grid" aria-live="polite">
                        <div class="vb-final-v2-empty">{{ __('Loading services...') }}</div>
                    </div>
                    <div class="vb-final-fallback">
                        <label for="service_id">{{ __('Service') }}</label>
                        <select id="service_id" name="service_id" required><option value="">{{ __('Loading services...') }}</option></select>
                    </div>
                    <div class="vb-final-v2-actions"><button type="button" class="vb-final-v2-btn primary" id="serviceContinue" disabled>{{ __('Continue') }} →</button></div>
                </section>

                <section id="staffSection" class="vb2-step" data-step="3" data-step-panel="2" hidden>
                    <div class="vb-final-v2-panel-head"><span>2</span><div><h2>{{ __('Choose a specialist') }}</h2><p>{{ __('Pick someone specific or let us find the first available option.') }}</p></div></div>
                    <div id="staffCards" class="vb-final-v2-grid" aria-live="polite"></div>
                    <div class="vb-final-fallback"><label for="staff_id">{{ __('Staff member') }}</label><select id="staff_id" name="staff_id" required><option value="">{{ __('Select Staff Member') }}</option></select></div>
                    <div class="vb-final-v2-actions"><button type="button" class="vb-final-v2-btn secondary" data-back="1">{{ __('Back') }}</button><button type="button" class="vb-final-v2-btn primary" id="staffContinue" disabled>{{ __('Continue') }} →</button></div>
                </section>

                <section id="dateSection" class="vb2-step" data-step="4" data-step-panel="3" hidden>
                    <div class="vb-final-v2-panel-head"><span>3</span><div><h2>{{ __('Choose date & time') }}</h2><p>{{ __('We only show appointments that are actually available.') }}</p></div></div>
                    <div class="vb-final-v2-date">
                        <label for="appointment_date">{{ __('Date') }}</label>
                        <input id="appointment_date" name="appointment_date" type="date" required min="{{ now()->toDateString() }}">
                        <span class="vb-field-hint">{{ __('Select a date to see available times.') }}</span>
                    </div>
                    <div id="timeSectionFinal">
                        <div class="vb-final-v2-slot-heading"><div><strong>{{ __('Available times') }}</strong><span>{{ __('Pick the time that works best for you.') }}</span></div></div>
                        <div id="vbFinalSlots" class="vb-final-v2-slot-grid" role="listbox" aria-label="{{ __('Available times') }}"></div>
                    </div>
                    <div class="vb-final-fallback"><label for="appointment_time">{{ __('Appointment time') }}</label><select id="appointment_time" name="appointment_time" required><option value="">{{ __('Select time') }}</option></select></div>
                    <div class="vb-final-v2-actions"><button type="button" class="vb-final-v2-btn secondary" data-back="2">{{ __('Back') }}</button><button type="button" class="vb-final-v2-btn primary" id="dateContinue" disabled>{{ __('Continue') }} →</button></div>
                </section>

                <section class="vb2-step" data-step="1" data-step-panel="4" hidden>
                    <div class="vb-final-v2-panel-head"><span>4</span><div><h2>{{ __('Your details') }}</h2><p>{{ __('Tell us who the appointment is for.') }}</p></div></div>
                    <div class="vb2-fields vb2-fields-personal">
                        <div class="vb2-field vb2-field-full"><label for="name">{{ __('Full Name') }} *</label><input id="name" name="name" required autocomplete="name" inputmode="text" placeholder="{{ __('Enter your full name') }}"></div>
                        <div class="vb2-field"><label for="phone">{{ __('Phone Number') }} *</label><input id="phone" name="phone" type="tel" required autocomplete="tel" inputmode="tel" placeholder="{{ __('Enter your phone number') }}"></div>
                        <div class="vb2-field"><label for="email">{{ __('Email') }} *</label><input id="email" name="email" type="email" required autocomplete="email" inputmode="email" placeholder="{{ __('Enter your email') }}"></div>
                    </div>
                    <div class="vb-final-v2-review">
                        <h3>{{ __('Review your appointment') }}</h3>
                        <div><span>{{ __('Service') }}</span><strong id="reviewService">—</strong></div>
                        <div><span>{{ __('Staff') }}</span><strong id="reviewStaff">—</strong></div>
                        <div><span>{{ __('Date') }}</span><strong id="reviewDate">—</strong></div>
                        <div><span>{{ __('Time') }}</span><strong id="reviewTime">—</strong></div>
                    </div>
                    <div class="vb2-field vb2-field-full"><label for="notes">{{ __('Additional notes') }}</label><textarea id="notes" name="notes" rows="4" maxlength="1000" placeholder="{{ __('Anything else we should know?') }}"></textarea></div>
                    <div class="vb-final-v2-actions"><button type="button" class="vb-final-v2-btn secondary" data-back="3">{{ __('Back') }}</button><button type="submit" id="submitBtn" class="vb-final-v2-btn primary" disabled>{{ __('Confirm appointment') }} ✓</button></div>
                </section>
            </form>

            <aside class="vb-final-v2-summary">
                <div class="vb-final-v2-live"><span></span><em>{{ __('Live availability') }}</em></div>
                <h2>{{ __('Your appointment') }}</h2>
                <p>{{ __('Your selections stay visible here as you book.') }}</p>
                <dl>
                    <div><dt>{{ __('Service') }}</dt><dd id="vbSummaryService">—</dd></div>
                    <div><dt>{{ __('Staff') }}</dt><dd id="vbSummaryStaff">—</dd></div>
                    <div><dt>{{ __('Date') }}</dt><dd id="vbSummaryDate">—</dd></div>
                    <div><dt>{{ __('Time') }}</dt><dd id="vbSummaryTime">—</dd></div>
                </dl>
                <a href="{{ route('customer.queue.status') }}">{{ __('Already booked? Track your appointment') }} <span aria-hidden="true">→</span></a>
            </aside>
        </main>

        <footer class="vb2-footer"><span>{{ $businessName }}</span><span>{{ date('Y') }}</span></footer>
    </div>
    <script src="{{ asset('js/dark-mode-booking.js') }}"></script>
    <script>
        window.changeLanguage = window.changeLanguage || function (lang) { window.location.href = '/change-language/' + encodeURIComponent(lang); };
    </script>
</body>
</html>
