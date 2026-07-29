<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ownership Attestation — {{ $data['certificate_number'] }}</title>
</head>
<body style="margin:0; padding:0; font-family: DejaVu Sans, Arial, sans-serif; color:#1a1a2e;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:10px;">
        <tr>
            <td style="border:3px solid #0a3d2e; padding:24px 32px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <span style="font-size:22px; font-weight:bold; color:#0a3d2e;">ArdhiLens</span><br>
                            <span style="font-size:11px; color:#666;">Ownership Attestation Certificate</span>
                        </td>
                        <td align="right">
                            <span style="font-size:12px; font-weight:bold; color:#0a3d2e;">{{ $data['verdict_label'] ?? 'OWNERSHIP VERIFIED' }}</span>
                        </td>
                    </tr>
                </table>
                <hr style="border:none;border-top:1px solid #e0e0e0;margin:16px 0;">
                <p style="font-size:14px;line-height:1.6;">
                    This document attests that <strong>{{ $data['holder_name'] }}</strong>
                    ({{ $data['holder_email'] }}) has proven identity and ownership linkage
                    for plot <strong>{{ $data['plot_reference'] }}</strong> at
                    {{ $data['plot_location'] ?? '' }}.
                </p>
                <p style="font-size:12px;color:#555;">
                    {{ $data['purpose'] ?? 'Confirms registered owner identity for listing and buyer inquiries.' }}
                </p>
                <table width="100%" style="margin:16px 0;background:#f5f5f5;">
                    <tr>
                        <td style="padding:12px;font-size:12px;">
                            <strong>Certificate No:</strong> {{ $data['certificate_number'] }}<br>
                            <strong>Issued:</strong> {{ $data['issued_at'] ?? '' }}<br>
                            <strong>NIDA verified:</strong> {{ ($data['nida_passed'] ?? false) ? 'Yes' : 'No' }}<br>
                            <strong>GPS verified:</strong> {{ ($data['geolocation_passed'] ?? false) ? 'Yes' : 'No' }}<br>
                            <strong>Owner link:</strong> {{ ($data['owner_link_passed'] ?? false) ? 'Yes' : 'No' }}
                        </td>
                    </tr>
                </table>
                @if(!empty($qr_image))
                <table width="100%"><tr><td align="center">
                    <img src="{{ $qr_image }}" width="120" height="120" alt="QR">
                    <p style="font-size:9px;color:#888;">Scan to verify authenticity</p>
                </td></tr></table>
                @endif
                <p style="font-size:10px;color:#666;margin-top:20px;">
                    <strong>Digital fingerprint (SHA-256):</strong><br>
                    <span style="font-family:monospace;font-size:9px;word-break:break-all;">{{ $fingerprint }}</span>
                </p>
                <p style="font-size:9px;color:#999;margin-top:12px;">
                    Signed with {{ $signing_algorithm ?? 'RSA-2048 / SHA-256' }}. This is not a purchase recommendation for buyers.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
