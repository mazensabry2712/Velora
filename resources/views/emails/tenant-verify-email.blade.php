<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your Velora email</title>
</head>
<body style="margin:0;background:#f8fafc;color:#0f172a;font-family:Arial,Helvetica,sans-serif;">
    <div style="max-width:620px;margin:0 auto;padding:40px 20px;">
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:20px;padding:32px;box-shadow:0 8px 30px rgba(15,23,42,.06);">
            <div style="font-size:14px;font-weight:700;color:#0284c7;margin-bottom:12px;">Velora</div>
            <h1 style="margin:0 0 12px;font-size:28px;line-height:1.2;">Verify your email address</h1>
            <p style="margin:0 0 18px;font-size:16px;line-height:1.7;color:#475569;">
                Welcome to Velora, {{ $businessName }}. Please verify this email address to continue to your workspace onboarding.
            </p>
            <a href="{{ $verificationUrl }}" style="display:inline-block;padding:14px 22px;border-radius:12px;background:#0284c7;color:#ffffff;text-decoration:none;font-weight:700;">
                Verify Email
            </a>
            <p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#64748b;">
                This verification link expires in {{ $expiresInHours }} hours and can only be used once.
            </p>
            <p style="margin:18px 0 0;font-size:13px;line-height:1.6;color:#94a3b8;word-break:break-all;">
                {{ $verificationUrl }}
            </p>
        </div>
    </div>
</body>
</html>
