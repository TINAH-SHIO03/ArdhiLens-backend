<x-mail::message>
# {{ $isReset ? 'Password reset' : 'Verify your email' }}

Use this ArdhiLens security code:

# {{ $code }}

It expires in **15 minutes**. Do not share it with anyone.

@if($isReset)
Enter the code in the app to set a new password.
@else
Enter the code in the app to verify your email address.
@endif

Thanks,<br>
{{ config('app.name', 'ArdhiLens') }}
</x-mail::message>
