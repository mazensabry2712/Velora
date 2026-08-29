<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $branding = \App\Support\TenantBranding::resolve();
        $businessName = $branding['name'] ?? tenant()->name ?? config('app.name');
        $businessLogo = $branding['logo'] ?? null;
        $logoUrl = $businessLogo ? asset('storage/' . $businessLogo) : global_asset('logo-bais.png');
        $languages = is_array($availableLanguages ?? null) ? $availableLanguages : ['ar', 'en'];
        $languageNames = ['ar'=>'العربية','de'=>'Deutsch','en'=>'English','es'=>'Español','fr'=>'Français','hi'=>'हिन्दी','id'=>'Bahasa Indonesia','it'=>'Italiano','ja'=>'日本語','ko'=>'한국어','nl'=>'Nederlands','pt'=>'Português','ru'=>'Русский','tr'=>'Türkçe','zh'=>'中文'];
        $lookup = request('ref') ?: request('queue_number');
    @endphp
    <title>{{ __('Queue status') }} · {{ $businessName }}</title>
    <link rel="stylesheet" href="{{ global_asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ global_asset('css/velora-public.css') }}">
    <link rel="stylesheet" href="{{ global_asset('css/velora-queue.css') }}">
</head>
<body class="queue-page">
<div class="queue-shell">
    <header class="queue-header">
        <a class="queue-brand" href="{{ route('customer.booking') }}" aria-label="{{ $businessName }}">
            <img src="{{ $logoUrl }}" alt="{{ $businessName }}" class="queue-logo" onerror="this.onerror=null;this.src='{{ global_asset('logo-bais.png') }}';">
            <span class="queue-brand-text"><strong>{{ $businessName }}</strong><span>{{ request()->getHost() }}</span></span>
        </a>
        <div class="queue-tools">
            <button type="button" class="queue-icon" id="queueTheme" aria-label="{{ __('Toggle Dark Mode') }}"><span id="queueThemeIcon">🌙</span></button>
            @if(count($languages) > 1)
                <label class="queue-language"><span aria-hidden="true">◎</span><select onchange="changeLanguage(this.value)" aria-label="{{ __('Language') }}">
                    @foreach($languages as $code)
                        @if(isset($languageNames[$code]))<option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $languageNames[$code] }}</option>@endif
                    @endforeach
                </select></label>
            @endif
        </div>
    </header>

    <section class="queue-hero">
        <span class="queue-eyebrow">{{ __('Appointment tracking') }}</span>
        <h1>{{ $lookup ? __('Your appointment') : __('Track your appointment') }}</h1>
        <p>{{ __('Use the booking reference you received to see your appointment and live queue position.') }}</p>
    </section>

    <main class="queue-content">
        <section class="queue-lookup-card">
            <form id="queueForm" action="{{ route('customer.queue.status') }}" method="GET">
                <label for="lookup">{{ __('Booking reference') }}</label>
                <div class="queue-lookup-row">
                    <input id="lookup" name="ref" value="{{ $lookup }}" autocomplete="off" placeholder="VL-AB12CD34" required>
                    <button type="submit">{{ __('Track appointment') }}</button>
                </div>
                <small>{{ __('The reference is shown in your booking confirmation.') }}</small>
            </form>
        </section>

        <div id="queueLoading" class="queue-alert queue-alert-info hidden" role="status">{{ __('Loading your appointment...') }}</div>
        <div id="queueError" class="queue-alert queue-alert-error hidden" role="alert"></div>

        <section id="queueResult" class="queue-result hidden" aria-live="polite">
            <div class="queue-ticket">
                <div class="queue-status-line"><span id="queueStatusDot"></span><span id="queueStatusText">{{ __('Waiting') }}</span></div>
                <span class="queue-ticket-label">{{ __('Queue number') }}</span>
                <strong id="queueNumber">—</strong>
                <span class="queue-reference" id="queueReference">—</span>
                <div class="queue-metrics">
                    <div><strong id="peopleAhead">0</strong><span>{{ __('people ahead') }}</span></div>
                    <div><strong id="waitTime">0</strong><span>{{ __('min estimated wait') }}</span></div>
                </div>
            </div>

            <div class="queue-details">
                <div class="queue-details-head"><div><span>{{ __('Appointment') }}</span><h2>{{ __('Your booking details') }}</h2></div><button type="button" id="queueRefresh" class="queue-refresh">↻ {{ __('Refresh') }}</button></div>
                <dl>
                    <div><dt>{{ __('Customer') }}</dt><dd id="customerName">—</dd></div>
                    <div><dt>{{ __('Service') }}</dt><dd id="serviceName">—</dd></div>
                    <div><dt>{{ __('Specialist') }}</dt><dd id="staffName">—</dd></div>
                    <div><dt>{{ __('Date') }}</dt><dd id="appointmentDate">—</dd></div>
                    <div><dt>{{ __('Time') }}</dt><dd id="appointmentTime">—</dd></div>
                    <div><dt>{{ __('Duration') }}</dt><dd id="duration">—</dd></div>
                </dl>
                <div class="queue-actions"><button type="button" class="queue-copy" id="copyReference">{{ __('Copy reference') }}</button><a href="{{ route('customer.booking') }}">{{ __('Book another appointment') }}</a></div>
            </div>
        </section>
    </main>

    <footer class="queue-footer"><a href="{{ route('customer.booking') }}">← {{ __('Back to booking') }}</a><span>{{ $businessName }}</span></footer>
</div>
<script src="{{ global_asset('js/velora-queue-v3.js') }}"></script>
</body>
</html>
