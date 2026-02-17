@component('mail::message')
# {{ __('notifications.appointment_reminder.subject', [], $locale) }}

{{ __('notifications.appointment_reminder.greeting', ['name' => $user->name], $locale) }}

{{ __('notifications.appointment_reminder.message', [], $locale) }}

## {{ __('notifications.appointment_reminder.details', [], $locale) }}

**{{ __('notifications.appointment_reminder.date', ['date' => $appointment->appointment_date->format('Y-m-d')], $locale) }}**
**{{ __('notifications.appointment_reminder.time', ['time' => $appointment->appointment_date->format('H:i')], $locale) }}**
**{{ __('notifications.appointment_reminder.tenant', ['tenant' => tenant()->name], $locale) }}**

@component('mail::button', ['url' => config('app.url')])
{{ __('notifications.view_details', [], $locale) }}
@endcomponent

{{ __('notifications.appointment_reminder.footer', [], $locale) }}

{{ __('notifications.regards', [], $locale) }},<br>
{{ tenant()->name }}
@endcomponent
