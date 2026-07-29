<x-mail::message>
# Karibu {{ $userName }}!

Welcome to **ArdhiLens** — Tanzania land verification before you buy or sell.

You registered as a **{{ $role }}** with `{{ $email }}`.

@if($role === 'seller')
**Next steps for sellers**
1. Complete NIDA KYC in your Seller Workspace  
2. Confirm your plots are linked to your NIN  
3. Keep ownership documents ready for buyers  
@else
**Next steps for buyers**
1. Start a land verification (Plot → GPS → NIDA → Risk)  
2. Review the digitally signed certificate  
3. Do not pay before registry confirmation  
@endif

<x-mail::button :url="config('app.url')">
Open ArdhiLens
</x-mail::button>

Angalia Ardhi · Verify before you buy.<br>
{{ config('app.name', 'ArdhiLens') }}
</x-mail::message>
