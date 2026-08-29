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
    @endphp
    <title>{{ __('Queue Status') }} - {{ $businessName }}</title>
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark-mode-enhancements.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-queue.css') }}">
</head>
<body class="vq-page">
    <div class="vq-shell">
        <header class="vq-header" aria-label="{{ $businessName }}">
            <a class="vq-brand" href="{{ route('customer.booking') }}" aria-label="{{ $businessName }}">
                <span class="vq-logo-wrap" aria-hidden="true">
                    <img class="vq-logo" src="{{ $businessLogo ? asset('storage/' . $businessLogo) : asset('logo-bais.png') }}" alt="" onerror="this.classList.add('is-broken')">
                    <span class="vq-fallback-logo"><img src="{{ asset('logo-bais.png') }}" alt=""></span>
                </span>
                <span class="vq-brand-copy">
                    <strong>{{ $businessName }}</strong>
                    <span>{{ $businessHost }}</span>
                </span>
            </a>
            <div class="vq-controls">
                <button type="button" class="vq-button" id="dark-mode-toggle" onclick="toggleDarkMode()" aria-label="{{ __('Toggle Dark Mode') }}">
                    <span id="dark-mode-icon">🌙</span>
                </button>
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
            <div class="vq-kicker">{{ __('Queue') }}</div>
            <h1>{{ __('Check Your Queue Status') }}</h1>
            <p>{{ __('Enter your queue number to see the current status, service, staff member, and queue date.') }}</p>
        </section>

        <main class="vq-card">
            <form id="queueForm" class="vq-form" method="GET" action="{{ route('customer.queue.status') }}">
                <label for="queue_number">{{ __('Enter Your Queue Number') }}</label>
                <div class="vq-form-row">
                    <input id="queue_number" name="queue_number" value="{{ request('queue_number') }}" autocomplete="off" placeholder="A001" required>
                    <button class="vq-submit" type="submit">{{ __('Check Status') }}</button>
                </div>
            </form>

            @if(request('queue_number'))
                <section class="vq-result" aria-live="polite">
                    <div class="vq-number">{{ __('Queue Number') }}</div>
                    <strong id="queue-number-value">{{ request('queue_number') }}</strong>
                    <div id="queue-status-content" class="vq-message">{{ __('Loading...') }}</div>
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

        function changeLanguage(lang) {
            window.location.href = '/change-language/' + encodeURIComponent(lang);
        }

        (function initTheme() {
            const saved = localStorage.getItem('queueDarkMode');
            applyDarkMode(saved === 'true' || (saved === null && window.matchMedia('(prefers-color-scheme: dark)').matches));
        })();

        @if(request('queue_number'))
        fetch('/api/queue/status/{{ rawurlencode(request('queue_number')) }}', {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(async response => {
                const payload = await response.json();
                if (!response.ok || !payload.success || !payload.data) throw new Error(payload.message || @json(__('Queue number not found')));
                return payload.data;
            })
            .then(queue => {
                const target = document.getElementById('queue-status-content');
                if (!target) return;
                target.replaceChildren();

                const badge = document.createElement('span');
                badge.className = 'vq-status ' + (['waiting', 'serving', 'completed'].includes(queue.status) ? queue.status : '');
                badge.textContent = queue.status;
                target.appendChild(badge);

                const meta = document.createElement('div');
                meta.className = 'vq-meta';
                const fields = [
                    [@json(__('Service')), queue.service || '—'],
                    [@json(__('Staff')), queue.staff_name || '—'],
                    [@json(__('Queue Date')), queue.queue_date || '—'],
                    [@json(__('Priority')), queue.is_vip ? @json(__('Priority')) : @json(__('Standard'))],
                ];
                fields.forEach(([label, value]) => {
                    const item = document.createElement('div');
                    const labelEl = document.createElement('span');
                    labelEl.textContent = label;
                    const valueEl = document.createElement('strong');
                    valueEl.textContent = value;
                    item.append(labelEl, valueEl);
                    meta.appendChild(item);
                });
                target.appendChild(meta);
            })
            .catch(() => {
                const target = document.getElementById('queue-status-content');
                if (target) {
                    target.textContent = @json(__('Queue number not found'));
                    target.classList.add('vq-error');
                }
            });
        @endif
    </script>
</body>
</html>
