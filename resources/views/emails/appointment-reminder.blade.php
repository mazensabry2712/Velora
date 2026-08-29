@component('mail::message')
# {{ __('notifications.appointment_reminder.subject', [], $locale) }}

{{ __('notifications.appointment_reminder.greeting', ['name' => $customerName], $locale) }}

{{ __('notifications.appointment_reminder.message', [], $locale) }}

## {{ __('notifications.appointment_reminder.details', [], $locale) }}

**{{ __('notifications.appointment_reminder.date', ['date' => $appointmentDate], $locale) }}**  
**{{ __('notifications.appointment_reminder.time', ['time' => $appointmentTime], $locale) }}**  
**{{ __('notifications.appointment_reminder.service', ['service' => $serviceName], $locale) }}**  
**{{ __('notifications.appointment_reminder.staff', ['staff' => $staffName], $locale) }}**  
**{{ __('notifications.appointment_reminder.tenant', ['tenant' => tenant()->name], $locale) }}**  
**{{ __('notifications.appointment_reminder.queue', ['number' => $queueNumber], $locale) }}**  
**{{ __('notifications.appointment_reminder.reference', ['reference' => $reference], $locale) }}**

@component('mail::button', ['url' => $trackingUrl])
{{ __('notifications.view_details', [], $locale) }}
@endcomponent

{{ __('notifications.appointment_reminder.tracking', ['url' => $trackingUrl], $locale) }}

{{ __('notifications.appointment_reminder.footer', [], $locale) }}

{{ __('notifications.regards', [], $locale) }},<br>
{{ tenant()->name }}
@endcomponent
