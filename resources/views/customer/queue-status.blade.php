<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $branding = \App\Support\TenantBranding::resolve();
        $businessName = $branding['name'];
        $businessLogo = $branding['logo'];
        $businessHost = request()->getHost();
        $settings = \App\Models\Setting::where('tenant_id', tenant()->id)->first();
        $availableLanguages = $settings?->available_languages ?? config('localizer.supported_locales', ['ar', 'en']);
        $lookup = request('ref') ?: request('queue_number');
    @endphp
    <title>{{ request('ref') ? __('Appointment Confirmed') : __('Queue Status') }} - {{ $businessName }}</title>
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark-mode-enhancements.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-queue.css') }}">
    <style>
        body.vq-page { min-height:100vh; }
        .vq-lookup { margin-bottom:18px; }
        .vq-hero-status { display:none; text-align:center; padding:8px 0 20px; }
        .vq-hero-status.is-confirmation { display:block; }
        .vq-check { width:62px; height:62px; margin:0 auto 14px; display:grid; place-items:center; border-radius:20px; background:rgba(0,212,163,.12); color:#00a982; font-size:30px; font-weight:800; }
        .vq-hero-status h2 { margin:0 0 7px; font-size:clamp(24px,4vw,34px); }
        .vq-hero-status p { margin:0; color:var(--velora-text-muted); }
        .vq-result-grid { display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:18px; }
        .vq-result-card { border:1px solid var(--velora-line); border-radius:22px; padding:22px; background:var(--velora-surface); box-shadow:0 24px 70px rgba(13,18,38,.07); }
        .vq-result-card h2 { margin:0 0 16px; font-size:18px; }
        .vq-detail-row { display:flex; justify-content:space-between; gap:16px; padding:12px 0; border-bottom:1px solid var(--velora-line); }
        .vq-detail-row:last-child { border-bottom:0; }
        .vq-detail-row span { color:var(--velora-text-muted); font-size:12px; }
        .vq-detail-row strong { text-align:end; font-size:13px; }
        .vq-ticket { text-align:center; padding:20px; border-radius:20px; background:linear-gradient(180deg,color-mix(in srgb,var(--velora-surface) 98%,var(--velora-primary-blue)),var(--velora-surface-muted)); border:1px solid rgba(0,108,255,.14); }
        .vq-ticket small { display:block; color:var(--velora-text-muted); text-transform:uppercase; letter-spacing:.12em; font-size:10px; font-weight:800; }
        .vq-ticket strong { display:block; margin:6px 0; font-size:56px; line-height:1; background:var(--velora-gradient); background-clip:text; -webkit-background-clip:text; color:transparent; }
        .vq-reference { margin-top:12px; font:700 12px/1.2 ui-monospace,SFMono-Regular,Menlo,monospace; letter-spacing:.08em; }
        .vq-people { margin-top:12px; padding-top:12px; border-top:1px solid var(--velora-line); color:var(--velora-text-muted); font-size:12px; }
        .vq-action-row { display:flex; flex-wrap:wrap; gap:10px; margin-top:18px; }
        .vq-secondary-link { display:inline-flex; align-items:center; justify-content:center; min-height:48px; padding:0 16px; border-radius:14px; border:1px solid var(--velora-line); background:var(--velora-surface-muted); color:var(--velora-text); text-decoration:none; font-weight:700; font-size:13px; }
        .vq-loading { color:var(--velora-text-muted); text-align:center; padding:12px 0; }
        .vq-not-found { color:#b42318; }
        @media (max-width:760px){ .vq-result-grid{grid-template-columns:1fr}.vq-result-card{padding:18px;border-radius:20px}.vq-action-row>*{flex:1 1 100%} }
        html.dark .vq-result-card, body.dark .vq-result-card{background:#0D1226;border-color:#252E45}.dark .vq-ticket{background:#10172A}
    </style>
</head>
<body class="vq-page">
    <div class="vq-shell">
        <header class="vq-header" aria-label="{{ $businessName }}">
            <a class="vq-brand" href="{{ route('customer.booking') }}" aria-label="{{ $businessName }}">
                <span class="vq-logo-wrap" aria-hidden="true">
                    <img class="vq-logo" src="{{ $businessLogo ? asset('storage/' . $businessLogo) : global_asset('logo-bais.png') }}" alt="" onerror="this.classList.add('is-broken')">
                    <span class="vq-fallback-logo"><img src="{{ global_asset('logo-bais.png') }}" alt=""></span>
                </span>
                <span class="vq-brand-copy"><strong>{{ $businessName }}</strong><span>{{ $businessHost }}</span></span>
            </a>
            <div class="vq-controls">
                <button type="button" class="vq-button" id="dark-mode-toggle" onclick="toggleDarkMode()" aria-label="{{ __('Toggle Dark Mode') }}"><span id="dark-mode-icon">🌙</span></button>
                @php
                    $supported = array_values(array_intersect(config('localizer.supported_locales', ['ar', 'en']), is_array($availableLanguages) ? $availableLanguages : ['ar', 'en']));
                @endphp
                @if(count($supported) > 1)
                    <select class="vq-language" aria-label="{{ __('Language') }}" onchange="changeLanguage(this.value)">
                        @foreach($supported as $code)
                            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ strtoupper($code) }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </header>

        <section class="vq-intro">
            <div class="vq-kicker">{{ request('ref') ? __('Appointment') : __('Queue') }}</div>
            <h1>{{ request('ref') ? __('Your appointment is confirmed') : __('Check Your Queue Status') }}</h1>
            <p>{{ request('ref') ? __('Your booking reference opens the same live page you can use to follow your appointment.') : __('Enter your booking reference or queue number to see the appointment and queue status.') }}</p>
        </section>

        <main class="vq-card">
            <form id="queueForm" class="vq-form vq-lookup" method="GET" action="{{ route('customer.queue.status') }}">
                <label for="lookup">{{ __('Booking Reference or Queue Number') }}</label>
                <div class="vq-form-row">
                    <input id="lookup" name="{{ request('ref') ? 'ref' : 'queue_number' }}" value="{{ $lookup }}" autocomplete="off" placeholder="VL-AB12CD34" required>
                    <button class="vq-submit" type="submit">{{ __('View') }}</button>
                </div>
            </form>

            @if($lookup)
                <section id="confirmationHero" class="vq-hero-status" aria-live="polite">
                    <div class="vq-check" aria-hidden="true">✓</div>
                    <h2>{{ __('Appointment confirmed') }}</h2>
                    <p>{{ __('Keep this reference. It can be used to return to this page at any time.') }}</p>
                </section>

                <section class="vq-result-grid" id="queueResult" aria-live="polite">
                    <div class="vq-result-card">
                        <h2>{{ __('Appointment details') }}</h2>
                        <div id="queue-status-content"><div class="vq-loading">{{ __('Loading...') }}</div></div>
                        <div class="vq-action-row">
                            <a class="vq-secondary-link" href="{{ route('customer.booking') }}">{{ __('Book another appointment') }}</a>
                        </div>
                    </div>
                    <aside class="vq-result-card">
                        <h2>{{ __('Your ticket') }}</h2>
                        <div class="vq-ticket">
                            <small>{{ __('Queue number') }}</small>
                            <strong id="queue-number-value">—</strong>
                            <div class="vq-reference" id="reference-value">{{ $lookup }}</div>
                            <div class="vq-people" id="people-ahead"></div>
                        </div>
                    </aside>
                </section>
            @endif
        </main>

        <footer class="vq-footer">
            <a href="{{ route('customer.booking') }}">← {{ __('Back to Booking') }}</a>
            <span>{{ date('Y') }}</span>
        </footer>
    </div>

    <script>
        function applyDarkMode(isDark) {
            document.documentElement.classList.toggle('dark', isDark);
            const icon = document.getElementById('dark-mode-icon');
            if (icon) icon.textContent = isDark ? '☀️' : '🌙';
        }
        function toggleDarkMode() {
            const isDark = !document.documentElement.classList.contains('dark');
            localStorage.setItem('queueDarkMode', String(isDark));
            applyDarkMode(isDark);
        }
        function changeLanguage(lang) { window.location.href = '/change-language/' + encodeURIComponent(lang); }
        (function initTheme() {
            const saved = localStorage.getItem('queueDarkMode');
            applyDarkMode(saved === 'true' || (saved === null && window.matchMedia('(prefers-color-scheme: dark)').matches));
        })();

        @if($lookup)
        const lookupValue = @json($lookup);
        fetch('/api/queue/status/' + encodeURIComponent(lookupValue), {
            headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
        })
            .then(async response => {
                const payload = await response.json();
                if (!response.ok || !payload.success || !payload.data) throw new Error(payload.message || @json(__('Queue number not found')));
                return payload.data;
            })
            .then(queue => {
                const target = document.getElementById('queue-status-content');
                const queueNumber = document.getElementById('queue-number-value');
                const reference = document.getElementById('reference-value');
                const ahead = document.getElementById('people-ahead');
                if (queueNumber) queueNumber.textContent = queue.queue_number || '—';
                if (reference && queue.reference) reference.textContent = queue.reference;
                if (ahead) {
                    ahead.textContent = queue.people_ahead === null || queue.people_ahead === undefined
                        ? ''
                        : `${queue.people_ahead} {{ __('people ahead') }}`;
                }
                if (!target) return;
                target.replaceChildren();
                const fields = [
                    [@json(__('Customer')), queue.customer_name || @json(__('Guest'))],
                    [@json(__('Service')), queue.service || '—'],
                    [@json(__('Staff')), queue.staff_name || '—'],
                    [@json(__('Date')), queue.appointment_date || queue.queue_date || '—'],
                    [@json(__('Time')), queue.appointment_time || '—'],
                    [@json(__('Duration')), queue.duration_minutes ? `${queue.duration_minutes} min` : '—'],
                    [@json(__('Status')), queue.status || '—'],
                ];
                fields.forEach(([label, value]) => {
                    const row = document.createElement('div');
                    row.className = 'vq-detail-row';
                    const l = document.createElement('span'); l.textContent = label;
                    const v = document.createElement('strong'); v.textContent = value;
                    row.append(l, v); target.appendChild(row);
                });
                if (lookupValue.toUpperCase().startsWith('VL-')) {
                    document.getElementById('confirmationHero')?.classList.add('is-confirmation');
                }
            })
            .catch(error => {
                const target = document.getElementById('queue-status-content');
                if (target) { target.textContent = error.message || @json(__('Queue number not found')); target.classList.add('vq-not-found'); }
            });
        @endif
    </script>
</body>
</html>
