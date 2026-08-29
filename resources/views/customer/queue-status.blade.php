<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
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
    <title>{{ __('Queue Status') }} - {{ $businessName }}</title>
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark-mode-enhancements.css') }}">
    <style>
        .vq-page{min-height:100vh;background:var(--velora-bg,#F7F9FC);color:var(--velora-text,#0D1226);padding:24px 14px;}
        .vq-shell{width:min(820px,100%);margin:0 auto;}
        .vq-header{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 16px;border:1px solid var(--velora-line,#E5E7EB);border-radius:18px;background:var(--velora-surface,#fff);box-shadow:0 14px 36px rgba(13,18,38,.06);}
        .vq-brand{display:flex;align-items:center;gap:12px;color:inherit;text-decoration:none;min-width:0;}
        .vq-logo{width:48px;height:48px;object-fit:contain;border-radius:14px;background:var(--velora-surface-muted,#F5F7FA);padding:7px;}
        .vq-brand-copy{min-width:0}.vq-brand-copy strong,.vq-brand-copy span{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.vq-brand-copy strong{font-size:15px}.vq-brand-copy span{margin-top:3px;color:var(--velora-text-muted,#687084);font-size:10px;}
        .vq-controls{display:flex;align-items:center;gap:8px}.vq-button,.vq-language{height:40px;border:1px solid var(--velora-line,#E5E7EB);border-radius:11px;background:var(--velora-surface-muted,#F5F7FA);color:inherit;padding:0 11px;font:inherit;cursor:pointer}.vq-language{max-width:130px;}
        .vq-intro{padding:48px 4px 22px}.vq-kicker{font-size:10px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:var(--velora-primary-blue,#006CFF)}.vq-intro h1{margin:9px 0 0;font-size:clamp(30px,5vw,46px);line-height:1.02;letter-spacing:-.04em}.vq-intro p{margin:12px 0 0;color:var(--velora-text-muted,#687084);font-size:14px;line-height:1.75;max-width:620px}
        .vq-card{padding:28px;border:1px solid var(--velora-line,#E5E7EB);border-radius:24px;background:var(--velora-surface,#fff);box-shadow:0 24px 70px rgba(13,18,38,.08)}
        .vq-form label{display:block;margin-bottom:8px;font-size:12px;font-weight:800}.vq-form-row{display:flex;gap:10px}.vq-form input{flex:1;min-width:0;height:54px;border:1px solid var(--velora-line,#E5E7EB);border-radius:14px;background:var(--velora-surface-muted,#F5F7FA);color:inherit;padding:0 15px;font:inherit;font-size:16px;outline:none}.vq-form input:focus{border-color:rgba(0,108,255,.45);box-shadow:0 0 0 4px rgba(0,108,255,.08)}.vq-submit{height:54px;border:0;border-radius:14px;padding:0 20px;background:var(--velora-gradient,linear-gradient(135deg,#006CFF,#6D46FF));color:#fff;font:inherit;font-weight:800;cursor:pointer}
        .vq-result{margin-top:20px;padding:20px;border:1px solid var(--velora-line,#E5E7EB);border-radius:18px;background:var(--velora-surface-muted,#F5F7FA)}.vq-number{font-size:12px;color:var(--velora-text-muted,#687084)}.vq-number strong{display:block;margin-top:4px;font-size:30px;color:var(--velora-text,#0D1226)}.vq-status{display:inline-flex;margin-top:14px;padding:7px 10px;border-radius:999px;font-size:11px;font-weight:900}.vq-status.waiting{background:#FFF4D7;color:#8A5A00}.vq-status.serving{background:#E2F0FF;color:#075EAA}.vq-status.completed{background:#E2FAF2;color:#087451}.vq-meta{margin-top:16px;display:grid;grid-template-columns:1fr 1fr;gap:10px}.vq-meta div{padding:12px;border:1px solid var(--velora-line,#E5E7EB);border-radius:12px;background:var(--velora-surface,#fff)}.vq-meta span{display:block;color:var(--velora-text-muted,#687084);font-size:10px}.vq-meta strong{display:block;margin-top:4px;font-size:13px}.vq-message{margin-top:12px;font-size:12px;color:var(--velora-text-muted,#687084)}.vq-error{color:#B42318}.vq-footer{padding:18px 4px 0;display:flex;justify-content:space-between;gap:10px;align-items:center;color:var(--velora-text-muted,#687084);font-size:11px}.vq-footer a{color:var(--velora-primary-blue,#006CFF);font-weight:800;text-decoration:none}
        html.dark .vq-page,body.dark .vq-page{background:#080B18;color:#F8FAFC}html.dark .vq-header,body.dark .vq-header,html.dark .vq-card,body.dark .vq-card{background:#0D1226;border-color:#252E45}html.dark .vq-button,body.dark .vq-button,html.dark .vq-language,body.dark .vq-language,html.dark .vq-form input,body.dark .vq-form input,html.dark .vq-result,body.dark .vq-result,html.dark .vq-meta div,body.dark .vq-meta div{background:#10172A;border-color:#252E45;color:#F8FAFC}html.dark .vq-brand-copy span,body.dark .vq-brand-copy span,html.dark .vq-intro p,body.dark .vq-intro p,html.dark .vq-number,body.dark .vq-number,html.dark .vq-message,body.dark .vq-message,html.dark .vq-footer,body.dark .vq-footer{color:#A7B0C0}html.dark .vq-number strong,body.dark .vq-number strong{color:#F8FAFC}
        @media(max-width:640px){.vq-page{padding:12px 10px}.vq-header{align-items:flex-start}.vq-brand-copy span{max-width:160px}.vq-controls{flex-shrink:0}.vq-language{width:82px;padding:0 7px}.vq-intro{padding-top:34px}.vq-card{padding:20px 16px}.vq-form-row{flex-direction:column}.vq-submit{width:100%}.vq-meta{grid-template-columns:1fr}.vq-footer{flex-direction:column;align-items:flex-start}}
    </style>
</head>
<body class="vq-page">
    <div class="vq-shell">
        <header class="vq-header" aria-label="{{ $businessName }}">
            <a class="vq-brand" href="{{ url('/') }}" aria-label="{{ $businessName }}">
                <img class="vq-logo" src="{{ $businessLogo ? asset('storage/' . $businessLogo) : asset('logo-bais.png') }}" alt="">
                <span class="vq-brand-copy">
                    <strong>{{ $businessName }}</strong>
                    <span>{{ request()->getHost() }}</span>
                </span>
            </a>
            <div class="vq-controls">
                <button type="button" class="vq-button" id="dark-mode-toggle" onclick="toggleDarkMode()" aria-label="{{ __('Toggle Dark Mode') }}">
                    <span id="dark-mode-icon">🌙</span>
                </button>
                <select class="vq-language" aria-label="{{ __('Language') }}" onchange="changeLanguage(this.value)">
                    @php $availableLanguages = $businessSettings?->available_languages ?? config('localizer.supported_locales', ['ar', 'en']); @endphp
                    @foreach ((array) $availableLanguages as $code)
                        <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ strtoupper($code) }}</option>
                    @endforeach
                </select>
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
            <span>Velora · {{ date('Y') }}</span>
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
                const service = document.createElement('div');
                const serviceLabel = document.createElement('span');
                serviceLabel.textContent = @json(__('Service'));
                const serviceValue = document.createElement('strong');
                serviceValue.textContent = queue.service || '—';
                service.append(serviceLabel, serviceValue);
                meta.appendChild(service);

                const staff = document.createElement('div');
                const staffLabel = document.createElement('span');
                staffLabel.textContent = @json(__('Staff'));
                const staffValue = document.createElement('strong');
                staffValue.textContent = queue.staff_name || '—';
                staff.append(staffLabel, staffValue);
                meta.appendChild(staff);

                const date = document.createElement('div');
                const dateLabel = document.createElement('span');
                dateLabel.textContent = @json(__('Queue Date'));
                const dateValue = document.createElement('strong');
                dateValue.textContent = queue.queue_date || '—';
                date.append(dateLabel, dateValue);
                meta.appendChild(date);

                const vip = document.createElement('div');
                const vipLabel = document.createElement('span');
                vipLabel.textContent = @json(__('Priority'));
                const vipValue = document.createElement('strong');
                vipValue.textContent = queue.is_vip ? @json(__('Priority')) : @json(__('Standard'));
                vip.append(vipLabel, vipValue);
                meta.appendChild(vip);

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
