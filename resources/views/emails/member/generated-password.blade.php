@component('mail::message')
# Welcome, {{ $name }}

Your account has been created successfully.

We generated a **temporary password** for you:

@component('mail::panel')
{{ $password }}
@endcomponent

To secure your account, please click the button below and set your own password.
You’ll need to enter the above temporary password as your "Current Password."

@component('mail::button', ['url' => $loginUrl])
Set Your Password
@endcomponent

If you did not request this account, please ignore this email.

Thanks,
{{ config('app.name') }}
@endcomponent
