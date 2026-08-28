<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<style>
    body { font-family: 'Inter', -apple-system, sans-serif; background:#f4f4f8; margin:0; padding:0; }
    .container { max-width:580px; margin:40px auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
    .header-warning { background:linear-gradient(135deg,#f59e0b,#fb923c); padding:32px 40px; text-align:center; }
    .header-danger { background:linear-gradient(135deg,#ef4444,#f97316); padding:32px 40px; text-align:center; }
    .header-info { background:linear-gradient(135deg,#6C63FF,#8b5cf6); padding:32px 40px; text-align:center; }
    .header-warning h1, .header-danger h1, .header-info h1 { color:#fff; font-size:24px; font-weight:800; margin:0; }
    .body { padding:40px; }
    .text { color:#555; font-size:15px; line-height:1.7; margin-bottom:16px; }
    .alert-box { border-radius:12px; padding:20px 24px; margin:20px 0; text-align:center; }
    .alert-warning { background:#fffbeb; border:1px solid #fcd34d; }
    .alert-danger { background:#fef2f2; border:1px solid #fca5a5; }
    .alert-info { background:#f5f3ff; border:1px solid #c4b5fd; }
    .days-big { font-size:60px; font-weight:900; line-height:1; }
    .days-warning { color:#d97706; }
    .days-danger { color:#dc2626; }
    .days-info { color:#6C63FF; }
    .btn-warning, .btn-danger, .btn-info { display:inline-block; color:#fff !important; font-weight:700; font-size:15px; padding:14px 32px; border-radius:12px; text-decoration:none; margin:16px 0; }
    .btn-warning { background:linear-gradient(135deg,#f59e0b,#fb923c); }
    .btn-danger { background:linear-gradient(135deg,#ef4444,#f97316); }
    .btn-info { background:linear-gradient(135deg,#6C63FF,#8b5cf6); }
    .footer { background:#f8f8ff; border-top:1px solid #e8e8f0; padding:24px 40px; text-align:center; }
    .footer p { color:#999; font-size:12px; margin:4px 0; }
    .footer a { color:#6C63FF; }
</style>
</head>
<body>
<div class="container">

    @if($type === '3day_warning')
    <div class="header-warning">
        <div style="font-size:36px; margin-bottom:8px;">⏰</div>
        <h1>Your trial ends in {{ $daysLeft }} day(s)!</h1>
    </div>
    <div class="body">
        <p style="font-size:16px; color:#1a1a2e; font-weight:600;">Hi {{ $businessName }},</p>
        <p class="text">Your Velora free trial is ending soon. Everything you've configured is ready for your business.</p>
        <div class="alert-box alert-warning">
            <div class="days-big days-warning">{{ $daysLeft }}</div>
            <div style="color:#d97706; font-weight:600; font-size:14px; margin-top:4px;">DAYS REMAINING IN YOUR TRIAL</div>
        </div>
        <p class="text">Upgrade before the trial ends to keep full access to adding, editing and operating your workspace.</p>
        <div style="text-align:center; margin:24px 0;">
            <a href="https://{{ $tenantId }}.velora.com/admin/subscription" class="btn-warning">⚡ Upgrade Now →</a>
        </div>
    </div>

    @elseif($type === 'read_only_warning')
    <div class="header-info">
        <div style="font-size:36px; margin-bottom:8px;">🔒</div>
        <h1>Your account becomes read-only in {{ $daysLeft }} day(s)</h1>
    </div>
    <div class="body">
        <p style="font-size:16px; color:#1a1a2e; font-weight:600;">Hi {{ $businessName }},</p>
        <p class="text">Your 7-day trial has ended. Your workspace will remain available in read-only mode for 14 days, but changes will no longer be allowed.</p>
        <div class="alert-box alert-info">
            <div class="days-big days-info">{{ $daysLeft }}</div>
            <div style="color:#6C63FF; font-weight:600; font-size:14px; margin-top:4px;">DAYS UNTIL READ-ONLY</div>
        </div>
        <div style="text-align:center; margin:24px 0;">
            <a href="https://{{ $tenantId }}.velora.com/admin/subscription/upgrade" class="btn-info">Restore Full Access →</a>
        </div>
    </div>

    @elseif($type === 'lock_warning')
    <div class="header-danger">
        <div style="font-size:36px; margin-bottom:8px;">🚨</div>
        <h1>Your account will be locked in {{ $daysLeft }} day(s)</h1>
    </div>
    <div class="body">
        <p style="font-size:16px; color:#1a1a2e; font-weight:600;">Hi {{ $businessName }},</p>
        <p class="text">Your 14-day read-only period is ending. After that, your workspace will be locked and its content will require an active subscription to view.</p>
        <div class="alert-box alert-danger">
            <div class="days-big days-danger">{{ $daysLeft }}</div>
            <div style="color:#dc2626; font-weight:600; font-size:14px; margin-top:4px;">DAYS UNTIL LOCK</div>
        </div>
        <div style="text-align:center; margin:24px 0;">
            <a href="https://{{ $tenantId }}.velora.com/admin/subscription/upgrade" class="btn-danger">🚨 Pay & Restore Access →</a>
        </div>
    </div>

    @else
    <div class="header-danger">
        <div style="font-size:36px; margin-bottom:8px;">⚠️</div>
        <h1>Your account will be permanently deleted in {{ $daysLeft }} day(s)</h1>
    </div>
    <div class="body">
        <p style="font-size:16px; color:#1a1a2e; font-weight:600;">Hi {{ $businessName }},</p>
        <p class="text">Your Velora account is currently locked. This is your final retention window. At the end of the locked period, the tenant, its database, files and stored data will be permanently deleted.</p>
        <div class="alert-box alert-danger">
            <div class="days-big days-danger">{{ $daysLeft }}</div>
            <div style="color:#dc2626; font-weight:600; font-size:14px; margin-top:4px;">DAYS UNTIL PERMANENT DELETION</div>
        </div>
        <div style="text-align:center; margin:24px 0;">
            <a href="https://{{ $tenantId }}.velora.com/admin/subscription/upgrade" class="btn-danger">💳 Upgrade Before Deletion →</a>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>© {{ date('Y') }} Velora. All rights reserved.</p>
        <p><a href="https://velora.com">velora.com</a> · <a href="mailto:support@velora.com">support@velora.com</a></p>
    </div>
</div>
</body>
</html>
