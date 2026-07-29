<x-mail::message>
# {{ $alertTitle }}

Hello **{{ $userName }}** ({{ strtoupper($role) }}),

{{ $alertBody }}

## Recommended land procedure

{{ $procedureGuide }}

@if(!empty($payload['plot_reference']))
**Plot reference:** {{ $payload['plot_reference'] }}
@endif

@if(!empty($payload['verdict']))
**Verdict:** {{ $payload['verdict'] }}
@endif

@if(isset($payload['risk_score']))
**Risk score:** {{ $payload['risk_score'] }}/100
@endif

<x-mail::button :url="config('app.url')">
Open ArdhiLens
</x-mail::button>

This email matches the in-app notification in your ArdhiLens account.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
