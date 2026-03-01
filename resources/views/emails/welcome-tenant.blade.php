<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<style>
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f4f4f8; margin:0; padding:0; }
    .container { max-width:580px; margin:40px auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08); }
    .header { background:linear-gradient(135deg,#6C63FF,#8b76ff); padding:40px 40px 32px; text-align:center; }
    .logo { display:inline-flex; align-items:center; gap:10px; margin-bottom:20px; }
    .logo-icon { width:40px; height:40px; background:rgba(255,255,255,0.2); border-radius:10px; display:inline-flex; align-items:center; justify-content:center; }
    .logo-text { color:#fff; font-size:22px; font-weight:800; }
    .header h1 { color:#fff; font-size:26px; font-weight:800; margin:0; line-height:1.3; }
    .body { padding:40px; }
    .greeting { font-size:16px; color:#1a1a2e; font-weight:600; margin-bottom:16px; }
    .text { color:#555; font-size:15px; line-height:1.7; margin-bottom:16px; }
    .highlight-box { background:linear-gradient(135deg,#f0eeff,#e8f4ff); border:1px solid #d0c9ff; border-radius:12px; padding:20px 24px; margin:24px 0; text-align:center; }
    .days { font-size:56px; font-weight:900; color:#6C63FF; line-height:1; }
    .days-label { color:#6C63FF; font-weight:600; font-size:14px; margin-top:4px; }
    .url-box { background:#f8f8ff; border:2px solid #6C63FF; border-radius:10px; padding:14px 20px; text-align:center; margin:20px 0; }
    .url-box a { color:#6C63FF; font-weight:700; font-size:16px; text-decoration:none; }
    .btn { display:inline-block; background:linear-gradient(135deg,#6C63FF,#8b76ff); color:#fff !important; font-weight:700; font-size:15px; padding:14px 32px; border-radius:12px; text-decoration:none; margin:8px 0; box-shadow:0 6px 24px rgba(108,99,255,0.35); }
    .features { background:#fafafa; border-radius:12px; padding:20px 24px; margin:20px 0; }
    .feature-item { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #eee; font-size:14px; color:#333; }
    .feature-item:last-child { border-bottom:none; }
    .check { color:#22c55e; font-weight:bold; font-size:16px; }
    .footer { background:#f8f8ff; border-top:1px solid #e8e8f0; padding:24px 40px; text-align:center; }
    .footer p { color:#999; font-size:12px; margin:4px 0; }
    .footer a { color:#6C63FF; }
</style>
</head>
<body>
<div class="container">
    {{-- Header --}}
    <div class="header">
        <div class="logo">
            <div class="logo-icon">📅</div>
            <span class="logo-text">Velora</span>
        </div>
        <h1>Welcome to Velora! 🎉<br/>Your free trial has started.</h1>
    </div>

    {{-- Body --}}
    <div class="body">
        <p class="greeting">Hi {{ $businessName }},</p>
        <p class="text">
            Congratulations! Your Velora account is ready. You now have full access to
            all features during your free trial period.
        </p>

        {{-- Trial Countdown --}}
        <div class="highlight-box">
            <div class="days">{{ $trialDays }}</div>
            <div class="days-label">DAYS FREE TRIAL</div>
        </div>

        <p class="text">Your dedicated booking URL is ready:</p>

        <div class="url-box">
            <a href="https://{{ $fullDomain }}/admin/dashboard">
                https://{{ $fullDomain }}
            </a>
        </div>

        <div style="text-align:center; margin:24px 0;">
            <a href="https://{{ $fullDomain }}/admin/dashboard" class="btn">
                🚀 Go to My Dashboard →
            </a>
        </div>

        {{-- What's Included --}}
        <div class="features">
            <p style="font-weight:700; color:#1a1a2e; margin:0 0 12px;">Everything included in your trial:</p>
            @foreach([
                'Appointment booking & management',
                'Real-time queue system with QR codes',
                'Staff management & scheduling',
                'Customer-facing booking page',
                'Analytics & reports dashboard',
                'Email reminders (reduce no-shows)',
                'Multi-language support (10 languages)',
            ] as $feature)
            <div class="feature-item">
                <span class="check">✓</span>
                {{ $feature }}
            </div>
            @endforeach
        </div>

        <p class="text">
            No credit card required during your trial. After {{ $trialDays }} days,
            you'll be prompted to choose a plan. If you don't upgrade, you get an additional
            <strong>3-day grace period</strong> before any restrictions apply.
        </p>

        <p class="text" style="color:#999; font-size:13px;">
            Questions? Reply to this email or
            <a href="https://wa.me/1234567890" style="color:#6C63FF;">chat with us on WhatsApp</a>.
            We usually respond within a few hours.
        </p>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>© {{ date('Y') }} Velora. All rights reserved.</p>
        <p><a href="https://velora.com">velora.com</a> · <a href="mailto:support@velora.com">support@velora.com</a></p>
        <p style="color:#ccc; font-size:11px; margin-top:8px;">
            You're receiving this because you signed up for Velora.
            <a href="#" style="color:#6C63FF;">Unsubscribe</a>
        </p>
    </div>
</div>
</body>
</html>
