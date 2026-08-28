<!doctype html>
@php $mailLocale = $locale; $isRtl = in_array($mailLocale, ['ar','he','fa'], true); app()->setLocale($mailLocale); @endphp
<html lang="{{ $mailLocale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ __('password_reset.email_subject') }}</title></head>
<body style="margin:0;background:#f5f7fa;color:#0d1226;font-family:Arial,Helvetica,sans-serif">
<div style="max-width:600px;margin:40px auto;padding:24px">
  <div style="background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:32px">
    <img src="{{ asset('logo-bais.png') }}" alt="Velora" style="height:44px;width:auto;display:block;margin-bottom:24px">
    <h1 style="margin:0 0 12px;font-size:28px">{{ __('password_reset.heading') }}</h1>
    <p style="font-size:15px;line-height:1.7">{{ __('password_reset.secure_recovery') }} — {{ $name }}</p>
    <p style="font-size:15px;line-height:1.7">{{ __('password_reset.description') }}</p>
    <p style="margin:28px 0"><a href="{{ $resetUrl }}" style="display:inline-block;background:#006cff;color:#fff;text-decoration:none;padding:14px 22px;border-radius:12px;font-weight:700">{{ __('password_reset.send_link') }}</a></p>
    <p style="font-size:12px;line-height:1.7;color:#667085">{{ __('password_reset.token_note') }}</p>
  </div>
</div>
</body></html>
