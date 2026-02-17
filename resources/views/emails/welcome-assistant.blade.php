@component('mail::message')
# {{ __('Welcome to :tenant!', ['tenant' => $tenant->name], $locale) }}

{{ __('Hello :name,', ['name' => $assistant->name], $locale) }}

{{ __('Your assistant account has been created. You can now login to the system using the following credentials:', [], $locale) }}

## {{ __('Your Login Details', [], $locale) }}

**{{ __('Email') }}:** {{ $assistant->email }}

**{{ __('Password') }}:** {{ $password }}

@component('mail::button', ['url' => 'http://' . $tenant->domains->first()?->domain . '/login'])
{{ __('Login Now', [], $locale) }}
@endcomponent

{{ __('Please change your password after your first login for security reasons.', [], $locale) }}

{{ __('If you have any questions, please contact your administrator.', [], $locale) }}

{{ __('Best Regards') }},<br>
{{ $tenant->name }}
@endcomponent
