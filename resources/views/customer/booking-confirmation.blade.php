<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f7f9fc">
    <title>{{ __('Appointment Confirmed') }} - {{ tenant()->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/velora-brand.css') }}">
    <link rel="stylesheet" href="{{ asset('css/velora-public.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dark-mode-enhancements.css') }}">
    <style>
        :root { --confirm-accent: var(--velora-primary-blue); --confirm-purple: var(--velora-primary-purple); }
        body { margin:0; min-height:100vh; background:var(--velora-bg); color:var(--velora-text); }
        .vc-page { max-width:980px; margin:0 auto; padding:24px 18px 48px; }
        .vc-header { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 16px; border:1px solid var(--velora-line); border-radius:20px; background:color-mix(in srgb,var(--velora-surface) 94%,transparent); backdrop-filter:blur(16px); }
        .vc-brand { display:flex; align-items:center; gap:12px; min-width:0; color:inherit; text-decoration:none; }
        .vc-logo { width:48px; height:48px; border-radius:14px; object-fit:contain; padding:5px; background:var(--velora-surface-muted); border:1px solid var(--velora-line); }
        .vc-brand strong,.vc-brand span { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .vc-brand strong { font-size:15px; }
        .vc-brand span { font-size:11px; color:var(--velora-text-muted); margin-top:2px; }
        .vc-main { display:grid; gap:18px; margin-top:24px; }
        .vc-hero { text-align:center; padding:12px 8px 4px; }
        .vc-check { width:68px; height:68px; margin:0 auto 16px; display:grid; place-items:center; border-radius:22px; background:rgba(0,212,163,.12); color:#00a982; font-size:32px; font-weight:800; }
        .vc-eyebrow { text-transform:uppercase; letter-spacing:.14em; font-size:11px; color:var(--confirm-accent); font-weight:800; }
        .vc-hero h1 { margin:8px 0 8px; font-size:clamp(28px,5vw,46px); line-height:1.05; }
        .vc-hero p { margin:0; color:var(--velora-text-muted); }
        .vc-grid { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:18px; align-items:start; }
        .vc-card { border:1px solid var(--velora-line); border-radius:24px; background:var(--velora-surface); box-shadow:0 24px 70px rgba(13,18,38,.08); padding:24px; }
        .vc-card h2 { margin:0 0 18px; font-size:18px; }
        .vc-ticket { text-align:center; padding:24px; border:1px solid rgba(0,108,255,.12); border-radius:20px; background:linear-gradient(180deg,color-mix(in srgb,var(--velora-surface) 98%,var(--velora-primary-blue)),var(--velora-surface-muted)); }
        .vc-ticket small { display:block; color:var(--velora-text-muted); text-transform:uppercase; letter-spacing:.12em; font-size:10px; font-weight:800; }
        .vc-ticket strong { display:block; margin:5px 0; font-size:58px; line-height:1; background:var(--velora-gradient); background-clip:text; -webkit-background-clip:text; color:transparent; }
        .vc-reference { margin-top:12px; font:700 13px/1.2 ui-monospace,SFMono-Regular,Menlo,monospace; letter-spacing:.08em; color:var(--velora-text); }
        .vc-status { margin-top:8px; color:var(--velora-text-muted); font-size:12px; }
        .vc-details { display:grid; gap:0; }
        .vc-row { display:flex; justify-content:space-between; gap:16px; padding:13px 0; border-bottom:1px solid var(--velora-line); }
        .vc-row:last-child { border-bottom:0; }
        .vc-row span { color:var(--velora-text-muted); font-size:12px; }
        .vc-row strong { text-align:end; font-size:13px; }
        .vc-actions { display:grid; gap:10px; margin-top:18px; }
        .vc-action { display:flex; justify-content:center; align-items:center; min-height:50px; border-radius:14px; padding:0 16px; font:700 13px/1 inherit; text-decoration:none; cursor:pointer; border:1px solid var(--velora-line); color:var(--velora-text); background:var(--velora-surface-muted); }
        .vc-action.primary { border-color:transparent; color:#fff; background:var(--velora-gradient); }
        .vc-note { margin-top:14px; font-size:11px; color:var(--velora-text-muted); text-align:center; }
        .vc-error { padding:18px; border-radius:18px; border:1px solid rgba(214,48,49,.18); background:rgba(214,48,49,.06); }
        @media (max-width:760px){ .vc-page{padding:10px 12px 32px}.vc-header{border-radius:16px}.vc-grid{grid-template-columns:1fr}.vc-card{padding:18px;border-radius:20px}.vc-ticket strong{font-size:52px}.vc-row{align-items:flex-start}.vc-row strong{max-width:58%} }
        html.dark body{background:#080B18}.dark .vc-header,.dark .vc-card{background:#0D1226;border-color:#252E45}.dark .vc-ticket{background:#10172A}.dark .vc-logo{background:#151C32;border-color:#252E45}
    </style>
</head>
<body>
<div class="vc-page">
    <header class="vc-header">
        <a class="vc-brand" href="{{ url('/') }}">
            <img class="vc-logo" src="{{ $businessLogo ?? asset('logo-bais.png') }}" alt="">
            <span>
                <strong>{{ tenant()->name }}</strong>
                <span>{{ request()->getHost() }}</span>
            </span>
        </a>
        <a class="vc-action" href="{{ route('customer.queue.status', ['ref' => $reference]) }}">{{ __('Track queue') }}</a>
    </header>

    <main class="vc-main">
        <section class="vc-hero">
            <div class="vc-check" aria-hidden="true">✓</div>
            <div class="vc-eyebrow">{{ __('Confirmed') }}</div>
            <h1>{{ __('Your appointment is confirmed') }}</h1>
            <p>{{ __('Keep this reference to view your appointment and queue status at any time.') }}</p>
        </section>

        <section class="vc-grid">
            <div class="vc-card">
                <h2>{{ __('Appointment details') }}</h2>
                <div class="vc-details">
                    <div class="vc-row"><span>{{ __('Customer') }}</span><strong>{{ $customerName !== '' ? $customerName : __('Guest') }}</strong></div>
                    <div class="vc-row"><span>{{ __('Service') }}</span><strong>{{ $appointment->serviceName ?? $appointment->service?->name ?? '—' }}</strong></div>
                    <div class="vc-row"><span>{{ __('Staff') }}</span><strong>{{ $staffName }}</strong></div>
                    <div class="vc-row"><span>{{ __('Date') }}</span><strong>{{ optional($appointment->starts_at)->format('D, d M Y') }}</strong></div>
                    <div class="vc-row"><span>{{ __('Time') }}</span><strong>{{ optional($appointment->starts_at)->format('h:i A') }}</strong></div>
                    <div class="vc-row"><span>{{ __('Duration') }}</span><strong>{{ $appointment->service?->duration_minutes ?? '—' }} {{ __('min') }}</strong></div>
                </div>
                <div class="vc-actions">
                    <a class="vc-action primary" href="{{ route('customer.queue.status', ['ref' => $reference]) }}">{{ __('View live queue') }}</a>
                    <a class="vc-action" href="{{ url('/book') }}">{{ __('Book another appointment') }}</a>
                </div>
                <p class="vc-note">{{ __('Your booking reference is the secure code you can use to retrieve this appointment.') }}</p>
            </div>

            <aside class="vc-card">
                <h2>{{ __('Your ticket') }}</h2>
                <div class="vc-ticket">
                    <small>{{ __('Queue number') }}</small>
                    <strong>{{ $queue?->queue_number ?? '—' }}</strong>
                    <div class="vc-reference">{{ $reference }}</div>
                    <div class="vc-status">{{ __($queue?->status ?? 'confirmed') }}</div>
                </div>
            </aside>
        </section>
    </main>
</div>
</body>
</html>
