<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $businessName = $businessName ?? tenant()->name ?? config('app.name');
        $businessLogo = $businessLogo ?? null;
        $businessHost = request()->getHost();
        $tenantLogo = $businessLogo ? asset('storage/' . $businessLogo) : global_asset('logo-bais.png');
        $languages = is_array($availableLanguages ?? null) ? $availableLanguages : ['ar', 'en'];
        $languageNames = ['ar'=>'العربية','de'=>'Deutsch','en'=>'English','es'=>'Español','fr'=>'Français','hi'=>'हिन्दी','id'=>'Bahasa Indonesia','it'=>'Italiano','ja'=>'日本語','ko'=>'한국어','nl'=>'Nederlands','pt'=>'Português','ru'=>'Русский','tr'=>'Türkçe','zh'=>'中文'];
    @endphp
    <title>{{ __('Book Appointment') }} · {{ $businessName }}</title>
    <link rel="stylesheet" href="{{ global_asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ global_asset('css/velora-public.css') }}">
    <link rel="stylesheet" href="{{ global_asset('css/velora-booking.css') }}">
</head>
<body class="booking-page">
<div class="booking-shell">
    <header class="booking-header">
        <a class="booking-brand" href="{{ url('/') }}" aria-label="{{ $businessName }}">
            <img class="booking-logo" src="{{ $tenantLogo }}" alt="{{ $businessName }}" onerror="this.onerror=null;this.src='{{ global_asset('logo-bais.png') }}';">
            <span class="booking-brand-copy"><strong>{{ $businessName }}</strong><span>{{ $businessHost }}</span></span>
        </a>
        <div class="booking-controls">
            <button id="dark-mode-toggle" class="booking-control" type="button" aria-label="{{ __('Toggle Dark Mode') }}"><span id="darkModeIcon">🌙</span></button>
            @if(count($languages) > 1)
                <label class="booking-control booking-language"><span aria-hidden="true">◎</span>
                    <select aria-label="{{ __('Language') }}" onchange="changeLanguage(this.value)">
                        @foreach($languages as $code)
                            @if(isset($languageNames[$code]))<option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $languageNames[$code] }}</option>@endif
                        @endforeach
                    </select>
                </label>
            @endif
        </div>
    </header>

    <section class="booking-hero">
        <span class="booking-eyebrow">{{ __('Online booking') }}</span>
        <h1>{{ __('Book your appointment') }}</h1>
        <p>{{ __('Choose a service, pick a time, and confirm your appointment in a few simple steps.') }}</p>
    </section>

    <main class="booking-grid">
        <section class="booking-main">
            <div id="loadingBanner" class="booking-alert info hidden" role="status" aria-live="polite"></div>
            <div id="errorMessage" class="booking-alert error hidden" role="alert"><strong>{{ __('Booking failed') }}</strong><span id="errorText"></span></div>

            <nav class="booking-progress" aria-label="{{ __('Booking progress') }}">
                <div class="booking-progress-item active" data-step="1"><b>1</b><span>{{ __('Service') }}</span></div>
                <div class="booking-progress-item" data-step="2"><b>2</b><span>{{ __('Specialist') }}</span></div>
                <div class="booking-progress-item" data-step="3"><b>3</b><span>{{ __('Date & time') }}</span></div>
                <div class="booking-progress-item" data-step="4"><b>4</b><span>{{ __('Your details') }}</span></div>
            </nav>

            <form id="bookingForm" novalidate>
                @csrf
                <select id="service_id" name="service_id" class="booking-hidden-field" aria-hidden="true" tabindex="-1"></select>
                <select id="staff_id" name="staff_id" class="booking-hidden-field" aria-hidden="true" tabindex="-1"></select>
                <select id="appointment_date" name="appointment_date" class="booking-hidden-field" aria-hidden="true" tabindex="-1"></select>
                <select id="appointment_time" name="appointment_time" class="booking-hidden-field" aria-hidden="true" tabindex="-1"></select>

                <section class="booking-step active" data-step="1">
                    <div class="booking-step-head"><span class="booking-step-number">1</span><div><h2>{{ __('Choose a service') }}</h2><p>{{ __('Start with the service you want to book.') }}</p></div></div>
                    <div id="serviceCards" class="booking-cards" aria-live="polite"><div class="booking-empty">{{ __('Loading services...') }}</div></div>
                </section>

                <section class="booking-step" data-step="2">
                    <div class="booking-step-head"><span class="booking-step-number">2</span><div><h2>{{ __('Choose a specialist') }}</h2><p>{{ __('Choose a specific specialist or let us find the earliest suitable option.') }}</p></div></div>
                    <div id="staffCards" class="booking-cards" aria-live="polite"></div>
                    <div class="booking-actions"><button type="button" class="booking-btn secondary" data-back-to="1">{{ __('Back') }}</button></div>
                </section>

                <section id="bookingStepDate" class="booking-step" data-step="3">
                    <div class="booking-step-head"><span class="booking-step-number">3</span><div><h2>{{ __('Choose date & time') }}</h2><p>{{ __('Only genuinely available times are shown.') }}</p></div></div>
                    <div class="booking-date-picker"><span>{{ __('Choose a date') }}</span><div id="dateChoices" class="booking-dates" role="listbox" aria-label="{{ __('Available dates') }}"></div></div>
                    <div class="booking-time-head"><strong>{{ __('Available times') }}</strong><span>{{ __('Select a time that works for you.') }}</span></div>
                    <div id="timeOptions" class="booking-slots" role="listbox" aria-label="{{ __('Available times') }}"><div class="booking-empty">{{ __('Choose a date to see available times.') }}</div></div>
                    <div class="booking-actions"><button type="button" class="booking-btn secondary" data-back-to="2">{{ __('Back') }}</button></div>
                </section>

                <section id="bookingStepDetails" class="booking-step" data-step="4">
                    <div class="booking-step-head"><span class="booking-step-number">4</span><div><h2>{{ __('Your details') }}</h2><p>{{ __('Tell us who the appointment is for.') }}</p></div></div>
                    <div class="booking-fields">
                        <div class="booking-field booking-full"><label for="name">{{ __('Full name') }} *</label><input id="name" name="name" autocomplete="name" required placeholder="{{ __('Enter your full name') }}"></div>
                        <div class="booking-field"><label for="phone">{{ __('Phone number') }} *</label><input id="phone" name="phone" type="tel" autocomplete="tel" required placeholder="{{ __('Enter your phone number') }}"></div>
                        <div class="booking-field"><label for="email">{{ __('Email') }} *</label><input id="email" name="email" type="email" autocomplete="email" required placeholder="{{ __('Enter your email') }}"></div>
                        <div class="booking-field booking-full"><label for="notes">{{ __('Notes') }}</label><textarea id="notes" name="notes" rows="4" maxlength="1000" placeholder="{{ __('Anything else should we know?') }}"></textarea></div>
                    </div>
                    <div class="booking-review">
                        <h3>{{ __('Review your appointment') }}</h3>
                        <div class="booking-review-row"><span>{{ __('Service') }}</span><strong id="reviewService">—</strong></div>
                        <div class="booking-review-row"><span>{{ __('Specialist') }}</span><strong id="reviewStaff">—</strong></div>
                        <div class="booking-review-row"><span>{{ __('Date') }}</span><strong id="reviewDate">—</strong></div>
                        <div class="booking-review-row"><span>{{ __('Time') }}</span><strong id="reviewTime">—</strong></div>
                    </div>
                    <div class="booking-actions"><button type="button" class="booking-btn secondary" data-back-to="3">{{ __('Back') }}</button><button type="submit" id="submitBtn" class="booking-btn primary" disabled>{{ __('Confirm appointment') }} <span aria-hidden="true">✓</span></button></div>
                </section>
            </form>
        </section>

        <aside class="booking-summary" aria-label="{{ __('Appointment summary') }}">
            <div class="booking-summary-status"><i></i>{{ __('Live availability') }}</div>
            <h2>{{ __('Your appointment') }}</h2>
            <p>{{ __('Your choices will appear here as you build your appointment.') }}</p>
            <div class="booking-summary-row"><span>{{ __('Service') }}</span><strong id="summaryService">—</strong></div>
            <div class="booking-summary-row"><span>{{ __('Specialist') }}</span><strong id="summaryStaff">—</strong></div>
            <div class="booking-summary-row"><span>{{ __('Date') }}</span><strong id="summaryDate">—</strong></div>
            <div class="booking-summary-row"><span>{{ __('Time') }}</span><strong id="summaryTime">—</strong></div>
            <a class="booking-track" href="{{ route('customer.queue.status') }}"><span>{{ __('Already booked? Track your appointment') }}</span><span aria-hidden="true">→</span></a>
        </aside>
    </main>

    <footer class="booking-footer"><a href="{{ url('/') }}">{{ __('Back to website') }}</a><span>{{ $businessName }}</span></footer>
</div>
<script src="{{ global_asset('js/velora-booking-v3.js') }}"></script>
</body>
</html>