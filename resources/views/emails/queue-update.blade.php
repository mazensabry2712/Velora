@component('mail::message')
# {{ __('notifications.queue_' . $updateType . '.subject', [], $locale) }}

{{ __('notifications.queue_' . $updateType . '.greeting', ['name' => $user->name], $locale) }}

{{ __('notifications.queue_' . $updateType . '.message', [], $locale) }}

**{{ __('notifications.queue_' . $updateType . '.queue_number', ['number' => $queue->queue_number], $locale) }}**

@if($updateType === 'position_update')
**{{ __('notifications.queue_position_update.position', ['position' => $queue->position ?? 0], $locale) }}**
**{{ __('notifications.queue_position_update.estimated_wait', ['time' => $queue->estimated_wait_time ?? 0], $locale) }}**
@endif

@if($updateType === 'ready')
**{{ __('notifications.queue_ready.position', [], $locale) }}**
@endif

@component('mail::button', ['url' => config('app.url')])
{{ __('notifications.view_details', [], $locale) }}
@endcomponent

{{ __('notifications.queue_' . $updateType . '.footer', [], $locale) }}

{{ __('notifications.regards', [], $locale) }},<br>
{{ tenant()->name }}
@endcomponent
