@component('mail::message')
# {{ __('notifications.public_booking_confirmation.title', [], $locale) }}

{{ __('notifications.public_booking_confirmation.greeting', ['name' => $customerName], $locale) }}

{{ __('notifications.public_booking_confirmation.message', [], $locale) }}

## {{ __('notifications.public_booking_confirmation.details', [], $locale) }}

**{{ __('notifications.public_booking_confirmation.service', [], $locale) }}:** {{ $serviceName }}  
**{{ __('notifications.public_booking_confirmation.staff', [], $locale) }}:** {{ $staffName }}  
**{{ __('notifications.public_booking_confirmation.date', [], $locale) }}:** {{ $appointmentDate }}  
**{{ __('notifications.public_booking_confirmation.time', [], $locale) }}:** {{ $appointmentTime }}  
**{{ __('notifications.public_booking_confirmation.duration', [], $locale) }}:** {{ $duration }}  
**{{ __('notifications.public_booking_confirmation.queue', [], $locale) }}:** {{ $queueNumber }}  
**{{ __('notifications.public_booking_confirmation.reference', [], $locale) }}:** `{{ $reference }}`

@component('mail::button', ['url' => $trackingUrl])
{{ __('notifications.public_booking_confirmation.track', [], $locale) }}
@endcomponent

{{ __('notifications.public_booking_confirmation.keep_reference', [], $locale) }}

{{ __('notifications.regards', [], $locale) }},  
{{ $tenantName }}
@endcomponent
