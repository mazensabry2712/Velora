@php
    $direction = $direction ?? ($locale === 'ar' ? 'rtl' : 'ltr');
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $direction }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('notifications.queue_' . $updateType . '.subject', [], $locale) }}</title>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#1f2937;">
    <div style="max-width:640px;margin:32px auto;background:#ffffff;border-radius:16px;padding:32px;box-sizing:border-box;">
        <h1 style="margin:0 0 20px;font-size:24px;">
            {{ __('notifications.queue_' . $updateType . '.subject', [], $locale) }}
        </h1>

        <p style="margin:0 0 12px;">
            {{ __('notifications.queue_' . $updateType . '.greeting', ['name' => $customerName], $locale) }}
        </p>

        <p style="margin:0 0 20px;">
            {{ __('notifications.queue_' . $updateType . '.message', [], $locale) }}
        </p>

        <div style="background:#f3f4f6;border-radius:12px;padding:18px;margin-bottom:20px;">
            <p style="margin:0 0 10px;font-weight:700;">
                {{ __('notifications.queue_' . $updateType . '.queue_number', ['number' => $queueNumber], $locale) }}
            </p>

            @if ($updateType === 'position_update' && $position !== null)
                <p style="margin:0;">
                    {{ __('notifications.queue_position_update.position', ['position' => $position], $locale) }}
                </p>
            @elseif ($updateType === 'ready')
                <p style="margin:0;">
                    {{ __('notifications.queue_ready.position', [], $locale) }}
                </p>
            @endif
        </div>

        <p style="margin:0 0 20px;">
            {{ __('notifications.queue_' . $updateType . '.footer', [], $locale) }}
        </p>

        <p style="margin:0 0 8px;">
            {{ __('notifications.thank_you', [], $locale) }}
        </p>

        <p style="margin:0 0 8px;">
            {{ __('notifications.regards', [], $locale) }}
        </p>

        <p style="margin:0;font-weight:700;">
            {{ $tenantName }}
        </p>
    </div>
</body>
</html>
