<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Founder Alert — {{ $businessName }}</title>
<style>
  body { font-family:'Segoe UI',sans-serif; background:#f8fafc; margin:0; padding:20px; }
  .wrap { max-width:600px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); }
  .header { background:linear-gradient(135deg,#7c3aed,#6d28d9); padding:28px 36px; }
  .header h1 { color:#fff; margin:0; font-size:20px; }
  .header .subtitle { color:#ddd6fe; font-size:13px; margin-top:6px; }
  .body { padding:32px 36px; }
  .body p { color:#374151; line-height:1.7; margin:0 0 14px; font-size:14px; }
  .trigger-badge { display:inline-block; background:#ede9fe; color:#5b21b6; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; letter-spacing:.5px; }
  .tenant-card { background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:20px; margin:20px 0; }
  .tenant-card .row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f3f4f6; font-size:13px; }
  .tenant-card .row:last-child { border-bottom:none; }
  .tenant-card .label { color:#6b7280; }
  .tenant-card .value { color:#111827; font-weight:600; }
  .kpi-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin:20px 0; }
  .kpi-box { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px; text-align:center; }
  .kpi-box .num { font-size:24px; font-weight:700; color:#16a34a; }
  .kpi-box .lbl { font-size:11px; color:#6b7280; margin-top:2px; }
  .btn { display:block; width:fit-content; margin:20px auto 0; background:#7c3aed; color:#fff; text-decoration:none;
         padding:12px 30px; border-radius:8px; font-size:14px; font-weight:600; }
  .footer { background:#f9fafb; padding:16px 36px; text-align:center; font-size:11px; color:#9ca3af; border-top:1px solid #e5e7eb; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>🔔 Founder Alert: {{ $businessName }}</h1>
    <div class="subtitle">High-intent trigger detected — consider reaching out</div>
  </div>
  <div class="body">
    <p>
      A trial user has triggered a conversion signal.
      <span class="trigger-badge">{{ $triggerReason }}</span>
    </p>

    <div class="tenant-card">
      <div class="row">
        <span class="label">Business</span>
        <span class="value">{{ $businessName }}</span>
      </div>
      <div class="row">
        <span class="label">Owner Email</span>
        <span class="value">{{ $ownerEmail }}</span>
      </div>
      <div class="row">
        <span class="label">Tenant ID</span>
        <span class="value">{{ $tenantId }}</span>
      </div>
      <div class="row">
        <span class="label">Trial Days Left</span>
        <span class="value">{{ $trialDaysLeft }} days</span>
      </div>
    </div>

    @if(!empty($stats))
    <div class="kpi-grid">
      @foreach($stats as $label => $value)
      <div class="kpi-box">
        <div class="num">{{ $value }}</div>
        <div class="lbl">{{ $label }}</div>
      </div>
      @endforeach
    </div>
    @endif

    <p><strong>Suggested action:</strong> Send a personal voice note / WhatsApp / email offering a 15-min demo or a 20% discount coupon.</p>
    <p style="font-size:13px;color:#6b7280;">This alert will only fire once per tenant.</p>
  </div>
  <div class="footer">Velora Founder Dashboard — Internal Notification</div>
</div>
</body>
</html>
