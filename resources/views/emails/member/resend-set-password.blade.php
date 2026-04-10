@component('mail::message')
# Hello, {{ $name }}

You attempted to log in but your password needs to be changed.

Please click the button below to set your new password. You'll need to enter your current temporary password as your "Current Password."

@component('mail::button', ['url' => $loginUrl])
Set Your Password
@endcomponent

If you did not attempt to log in, please ignore this email.

Thanks,
{{ config('app.name') }}
@endcomponent
