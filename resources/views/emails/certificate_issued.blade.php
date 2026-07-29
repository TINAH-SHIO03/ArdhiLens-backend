<x-mail::message>
# Your certificate is ready

Hello **{{ $userName }}**,

Your **{{ $certificateTitle }}** for plot **{{ $plotReference }}** has been issued.

**Certificate number:** {{ $certificateNumber }}  
**Verdict:** {{ $verdict }}  
**Issued:** {{ $issuedAt }}

The signed PDF is attached to this email. You can also open it anytime from the **Certificates** section in the ArdhiLens app.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
