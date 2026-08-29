@component('mail::message')
# {{ __('public_booking.confirmation.title', [], $locale) }}

{{ __('public_booking.confirmation.greeting', ['name' => $customerName], $locale) }}

{{ __('public_booking.confirmation.message', [], $locale) }}

## {{ __('public_booking.confirmation.details', [], $locale) }}

**{{ __('public_booking.confirmation.service', [], $locale) }}:** {{ $serviceName }}  
**{{ __('public_booking.confirmation.staff', [], $locale) }}:** {{ $staffName }}  
**{{ __('public_booking.confirmation.date', [], $locale) }}:** {{ $appointmentDate }}  
**{{ __('public_booking.confirmation.time', [], $locale) }}:** {{ $appointmentTime }}  
**{{ __('public_booking.confirmation.duration', [], $locale) }}:** {{ $duration }}  
**{{ __('public_booking.confirmation.queue', [], $locale) }}:** {{ $queueNumber }}  
**{{ __('public_booking.confirmation.reference', [], $locale) }}:** `{{ $reference }}`

@component('mail::button', ['url' => $trackingUrl])
{{ __('public_booking.confirmation.track', [], $locale) }}
@endcomponent

{{ __('public_booking.confirmation.keep_reference', [], $locale) }}

{{ __('notifications.regards', [], $locale) }},  
{{ $tenantName }}
@endcomponent
