<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<style>
    body { font-family: 'Inter', -apple-system, sans-serif; background:#f4f4f8; margin:0; padding:0; }
    .container { max-width:580px; margin:40px auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
    .header-warning  { background:linear-gradient(135deg,#f59e0b,#fb923c); padding:32px 40px; text-align:center; }
    .header-danger   { background:linear-gradient(135deg,#ef4444,#f97316); padding:32px 40px; text-align:center; }
    .header-warning h1, .header-danger h1 { color:#fff; font-size:24px; font-weight:800; margin:0; }
    .body { padding:40px; }
    .text { color:#555; font-size:15px; line-height:1.7; margin-bottom:16px; }
    .alert-box { border-radius:12px; padding:20px 24px; margin:20px 0; text-align:center; }
    .alert-warning { background:#fffbeb; border:1px solid #fcd34d; }
    .alert-danger   { background:#fef2f2; border:1px solid #fca5a5; }
    .days-big { font-size:60px; font-weight:900; line-height:1; }
    .days-warning { color:#d97706; }
    .days-danger  { color:#dc2626; }
    .btn-warning { display:inline-block; background:linear-gradient(135deg,#f59e0b,#fb923c); color:#fff !important; font-weight:700; font-size:15px; padding:14px 32px; border-radius:12px; text-decoration:none; margin:16px 0; box-shadow:0 6px 24px rgba(245,158,11,0.35); }
    .btn-danger  { display:inline-block; background:linear-gradient(135deg,#ef4444,#f97316); color:#fff !important; font-weight:700; font-size:15px; padding:14px 32px; border-radius:12px; text-decoration:none; margin:16px 0; box-shadow:0 6px 24px rgba(239,68,68,0.35); }
    .footer { background:#f8f8ff; border-top:1px solid #e8e8f0; padding:24px 40px; text-align:center; }
    .footer p { color:#999; font-size:12px; margin:4px 0; }
    .footer a { color:#6C63FF; }
</style>
</head>
<body>
<div class="container">

    {{-- Header --}}
    @if($type === '3day_warning')
    <div class="header-warning">
        <div style="font-size:36px; margin-bottom:8px;">⏰</div>
        <h1>Your trial ends in {{ $daysLeft }} day(s)!</h1>
    </div>
    @else
    <div class="header-danger">
        <div style="font-size:36px; margin-bottom:8px;">🚨</div>
        <h1>Action required — access expires soon!</h1>
    </div>
    @endif

    <div class="body">
        <p style="font-size:16px; color:#1a1a2e; font-weight:600; margin-bottom:16px;">
            Hi {{ $businessName }},
        </p>

        @if($type === '3day_warning')
        <p class="text">
            Your Velora free trial is ending soon. Don't lose access to all the
            features you've set up.
        </p>

        <div class="alert-box alert-warning">
            <div class="days-big days-warning">{{ $daysLeft }}</div>
            <div style="color:#d97706; font-weight:600; font-size:14px; margin-top:4px;">
                DAYS REMAINING IN YOUR TRIAL
            </div>
        </div>

        <p class="text">
            Upgrade now to keep your booking page, appointments, staff, and settings
            exactly as they are. Choose a plan that fits your business.
        </p>

        <div style="text-align:center; margin:24px 0;">
            <a href="https://{{ $tenantId }}.velora.com/admin/subscription" class="btn-warning">
                ⚡ Upgrade Now — Keep Everything →
            </a>
        </div>

        @else
        <p class="text">
            Your subscription's grace period is ending in
            <strong style="color:#dc2626;">{{ $daysLeft }} day(s)</strong>.
            After this, your account will go into read-only mode and you won't be able to
            accept new appointments.
        </p>

        <div class="alert-box alert-danger">
            <div class="days-big days-danger">{{ $daysLeft }}</div>
            <div style="color:#dc2626; font-weight:600; font-size:14px; margin-top:4px;">
                DAYS BEFORE ACCOUNT LOCKOUT
            </div>
        </div>

        <p class="text">
            Your data is safe — we never delete anything. But to continue accepting bookings,
            please upgrade immediately.
        </p>

        <div style="text-align:center; margin:24px 0;">
            <a href="https://{{ $tenantId }}.velora.com/billing/expired" class="btn-danger">
                🚨 Upgrade Now — Restore Access →
            </a>
        </div>
        @endif

        <p class="text" style="color:#999; font-size:13px;">
            Questions? Reply to this email or
            <a href="https://wa.me/1234567890" style="color:#6C63FF;">chat with us on WhatsApp</a>.
        </p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Velora. All rights reserved.</p>
        <p><a href="https://velora.com">velora.com</a> · <a href="mailto:support@velora.com">support@velora.com</a></p>
    </div>
</div>
</body>
</html>
