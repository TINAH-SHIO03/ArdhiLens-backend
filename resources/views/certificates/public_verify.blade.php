<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Certificate · ArdhiLens</title>
    <style>
        :root { --brand:#0A3D2E; --gold:#D4AF37; --bg:#F8F6F1; --ok:#166534; --bad:#B91C1C; }
        body { margin:0; font-family: Georgia, "Times New Roman", serif; background:linear-gradient(160deg,#0A3D2E 0%, #145C45 40%, #F8F6F1 40%); color:#1a1a1a; min-height:100vh; }
        .wrap { max-width:720px; margin:0 auto; padding:48px 20px; }
        .card { background:#fff; border-radius:18px; padding:28px; box-shadow:0 12px 40px rgba(0,0,0,.12); }
        h1 { margin:0 0 8px; color:var(--brand); font-size:28px; }
        .muted { color:#666; margin-bottom:20px; }
        .badge { display:inline-block; padding:8px 14px; border-radius:999px; font-weight:700; letter-spacing:.04em; }
        .ok { background:#ECFDF5; color:var(--ok); }
        .bad { background:#FEF2F2; color:var(--bad); }
        .row { display:flex; justify-content:space-between; gap:12px; padding:10px 0; border-bottom:1px solid #eee; font-size:14px; }
        .label { color:#777; }
        .value { font-weight:600; text-align:right; }
        .brand { color:var(--gold); font-weight:700; letter-spacing:.08em; text-transform:uppercase; font-size:12px; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="brand">ArdhiLens · Public Certificate Verify</div>
        <h1>{{ $certificateNumber }}</h1>
        @if(!$result)
            <p class="muted">No certificate was found for this number.</p>
            <span class="badge bad">NOT FOUND</span>
        @else
            @php
                $cert = $result['certificate'];
                $valid = $result['valid_signature'] && ! $result['is_expired'];
                $data = $cert->certificate_data ?? [];
            @endphp
            <p class="muted">Cryptographic verification of the digitally signed land verification certificate.</p>
            <span class="badge {{ $valid ? 'ok' : 'bad' }}">{{ $valid ? 'VALID SIGNATURE' : 'INVALID OR EXPIRED' }}</span>
            <div style="margin-top:22px;">
                <div class="row"><span class="label">Payload signature</span><span class="value">{{ $result['valid_signature'] ? 'Valid' : 'Invalid' }}</span></div>
                <div class="row"><span class="label">PDF hash signature</span><span class="value">{{ ($result['valid_pdf_signature'] ?? null) === true ? 'Valid' : (($result['valid_pdf_signature'] ?? null) === false ? 'Invalid' : 'N/A') }}</span></div>
                <div class="row"><span class="label">Expired</span><span class="value">{{ $result['is_expired'] ? 'Yes' : 'No' }}</span></div>
                <div class="row"><span class="label">Holder</span><span class="value">{{ $data['holder_name'] ?? '-' }}</span></div>
                <div class="row"><span class="label">Plot</span><span class="value">{{ $data['plot_reference'] ?? '-' }}</span></div>
                <div class="row"><span class="label">Verdict</span><span class="value">{{ $data['verdict'] ?? '-' }}</span></div>
                <div class="row"><span class="label">Risk score</span><span class="value">{{ $data['risk_score'] ?? '-' }}/100</span></div>
                <div class="row"><span class="label">Issued</span><span class="value">{{ optional($cert->issued_at)->toDayDateTimeString() }}</span></div>
                <div class="row"><span class="label">Expires</span><span class="value">{{ optional($cert->expires_at)->toDayDateTimeString() ?? 'N/A' }}</span></div>
                @if(!empty($cert->pdf_content_hash))
                    <div class="row"><span class="label">PDF SHA-256</span><span class="value" style="font-size:11px; word-break:break-all;">{{ $cert->pdf_content_hash }}</span></div>
                @endif
            </div>
        @endif
    </div>
</div>
</body>
</html>
