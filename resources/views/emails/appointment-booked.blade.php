@component('mail::message')
# {{ __('notifications.appointment_booked.subject', [], $locale) }}

{{ __('notifications.appointment_booked.greeting', ['name' => $user->name], $locale) }}

{{ __('notifications.appointment_booked.message', [], $locale) }}

## {{ __('notifications.appointment_booked.details', [], $locale) }}

**{{ __('notifications.appointment_booked.date', ['date' => $appointment->appointment_date->format('Y-m-d')], $locale) }}**
**{{ __('notifications.appointment_booked.time', ['time' => $appointment->appointment_date->format('H:i')], $locale) }}**
**{{ __('notifications.appointment_booked.tenant', ['tenant' => tenant()->name], $locale) }}**

@component('mail::button', ['url' => config('app.url')])
{{ __('notifications.view_details', [], $locale) }}
@endcomponent

{{ __('notifications.appointment_booked.footer', [], $locale) }}

{{ __('notifications.regards', [], $locale) }},<br>
{{ tenant()->name }}
@endcomponent
