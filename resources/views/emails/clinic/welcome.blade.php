<x-mail::message>
# Welcome to IVORRY

Hello **{{ $name }}**,

Your clinic has been registered in the IVORRY system. To get started, please set up your password by clicking the button below.

Your temporary password is:

<x-mail::panel>
{{ $tempPassword }}
</x-mail::panel>

<x-mail::button :url="$setPasswordUrl" color="primary">
Set Your Password
</x-mail::button>

If you did not expect this email, you can safely ignore it.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
