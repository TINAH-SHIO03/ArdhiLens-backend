<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $data['certificate_title'] ?? 'Verification Certificate' }} — {{ $data['certificate_number'] }}</title>
</head>
<body style="margin:0; padding:0; background:#fff; font-family:DejaVu Sans, Arial, sans-serif; color:#1a1a2e; font-size:10px;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:8px;">
        <tr><td style="border:2px solid #0f3460; padding:0;">

            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr><td style="height:4px; background:#d4a843;">&nbsp;</td></tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:20px 28px;">
                <tr><td>

                    {{-- Title + Cert No + Verdict --}}
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
                        <tr>
                            <td width="60%" valign="top">
                                <span style="font-size:16px; font-weight:bold; color:#0f3460; letter-spacing:1.5px; text-transform:uppercase;">
                                    {{ $data['certificate_title'] ?? 'LAND VERIFICATION CERTIFICATE' }}
                                </span><br>
                                <span style="font-size:8px; color:#888; letter-spacing:1px;">No. {{ $data['certificate_number'] }}</span>
                            </td>
                            <td width="40%" valign="top" align="right">
                                @php
                                    $verdict = strtoupper($data['verdict'] ?? 'UNKNOWN');
                                    $badgeColor = match(true) {
                                        in_array($verdict, ['SAFE','APPROVED','PASS','PASSED']) => '#16a34a',
                                        in_array($verdict, ['DO_NOT_BUY','REJECTED','FAIL','FAILED']) => '#dc2626',
                                        default => '#d97706',
                                    };
                                    $badgeBg = match(true) {
                                        in_array($verdict, ['SAFE','APPROVED','PASS','PASSED']) => '#dcfce7',
                                        in_array($verdict, ['DO_NOT_BUY','REJECTED','FAIL','FAILED']) => '#fee2e2',
                                        default => '#fef3c7',
                                    };
                                    $riskScore = $data['risk_score'] ?? 0;
                                    $riskColor = $riskScore <= 30 ? '#16a34a' : ($riskScore <= 60 ? '#d97706' : '#dc2626');
                                @endphp
                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tr>
                                        <td style="background:{{ $badgeBg }}; border:1px solid {{ $badgeColor }}; border-radius:3px; padding:4px 12px; text-align:center;" width="50%">
                                            <span style="font-size:8px; color:#666;">VERDICT</span><br>
                                            <span style="font-size:12px; font-weight:bold; color:{{ $badgeColor }};">{{ $verdict }}</span>
                                        </td>
                                        <td style="padding-left:8px; text-align:center;" width="50%">
                                            <span style="font-size:8px; color:#666;">RISK</span><br>
                                            <span style="font-size:14px; font-weight:bold; color:{{ $riskColor }};">{{ $riskScore }}</span><span style="font-size:8px; color:#888;">/100</span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
                        <tr><td style="height:1px; background:#e0e0e0;">&nbsp;</td></tr>
                    </table>

                    {{-- Holder + Plot in one row --}}
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
                        <tr>
                            <td width="50%" valign="top" style="padding-right:8px;">
                                <span style="font-size:8px; font-weight:bold; color:#0f3460; letter-spacing:1px;">HOLDER</span>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:4px; background:#fafafa; border-left:3px solid #0f3460; padding:6px 10px;">
                                    <tr><td>
                                        <span style="font-size:9px; color:#888;">NAME</span><br>
                                        <span style="font-size:11px; font-weight:bold;">{{ $data['holder_name'] ?? 'N/A' }}</span><br>
                                        <span style="font-size:9px; color:#888; margin-top:4px; ">EMAIL</span><br>
                                        <span style="font-size:10px;">{{ $data['holder_email'] ?? 'N/A' }}</span>
                                    </td></tr>
                                </table>
                            </td>
                            <td width="50%" valign="top" style="padding-left:8px;">
                                <span style="font-size:8px; font-weight:bold; color:#0f3460; letter-spacing:1px;">PLOT</span>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:4px; background:#fafafa; border-left:3px solid #0f3460; padding:6px 10px;">
                                    <tr><td>
                                        <span style="font-size:9px; color:#888;">REFERENCE</span><br>
                                        <span style="font-size:11px; font-weight:bold;">{{ $data['plot_reference'] ?? 'N/A' }}</span><br>
                                        <span style="font-size:9px; color:#888; margin-top:4px; ">LOCATION</span><br>
                                        <span style="font-size:10px;">{{ $data['plot_location'] ?? 'N/A' }}</span><br>
                                        <span style="font-size:9px; color:#888; margin-top:4px; ">SIZE</span>
                                        <span style="font-size:10px; font-weight:bold;"> {{ $data['plot_size_hectares'] ?? 'N/A' }} ha</span>
                                        &nbsp;&bull;&nbsp;
                                        <span style="font-size:9px; color:#888;">USE</span>
                                        <span style="font-size:10px;"> {{ $data['land_use'] ?? 'N/A' }}</span>
                                    </td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    {{-- Verification checks --}}
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px; border:1px solid #e0e0e0; border-radius:3px;">
                        <tr>
                            @php
                                $checks = [
                                    'GPS' => $data['geolocation_passed'] ?? false,
                                    'NIDA' => $data['nida_passed'] ?? false,
                                    'CERT' => $data['certificate_passed'] ?? false,
                                    'OWNER' => $data['owner_link_passed'] ?? false,
                                ];
                            @endphp
                            @foreach($checks as $label => $passed)
                                <td width="25%" align="center" style="padding:6px 4px; background:#f7f9fc; {{ !$loop->last ? 'border-right:1px solid #e0e0e0;' : '' }}">
                                    <span style="font-size:8px; color:#888;">{{ $label }}</span><br>
                                    @if($passed)
                                        <span style="font-size:9px; font-weight:bold; color:#16a34a;">PASS</span>
                                    @else
                                        <span style="font-size:9px; font-weight:bold; color:#dc2626;">FAIL</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </table>

                    {{-- Validity + Signature side by side --}}
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;">
                        <tr>
                            <td width="40%" valign="top" style="padding-right:8px;">
                                <span style="font-size:8px; font-weight:bold; color:#0f3460; letter-spacing:1px;">VALIDITY</span>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:4px;">
                                    <tr><td>
                                        <span style="font-size:9px; color:#888;">ISSUED</span><br>
                                        <span style="font-size:10px; font-weight:bold;">{{ $certificate->issued_at->format('d M Y H:i') }} EAT</span><br>
                                        <span style="font-size:9px; color:#888; margin-top:4px; ">EXPIRES</span><br>
                                        <span style="font-size:10px; font-weight:bold;">{{ $certificate->expires_at ? $certificate->expires_at->format('d M Y') : 'No Expiry' }}</span>
                                    </td></tr>
                                </table>
                            </td>
                            <td width="60%" valign="top" style="padding-left:8px;">
                                <span style="font-size:8px; font-weight:bold; color:#0f3460; letter-spacing:1px;">DIGITAL SIGNATURE</span>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:4px; background:#f0f4ff; border-radius:3px; padding:6px 8px;">
                                    <tr><td>
                                        <span style="font-size:7px; color:#666;">KEY FINGERPRINT (SHA-256)</span><br>
                                        <span style="font-size:7px; color:#0f3460; font-family:monospace; ">{{ substr($fingerprint, 0, 48) }}...</span><br>
                                        <span style="font-size:7px; color:#666; margin-top:4px; ">ALGORITHM: RSA-2048 / SHA-256</span>
                                    </td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    {{-- QR + Footer --}}
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td width="90" valign="middle" style="padding-right:10px;">
                                @if(!empty($qr_image))
                                    <img src="{{ $qr_image }}" width="80" height="80" alt="QR" style="display:block;" />
                                @endif
                            </td>
                            <td valign="middle">
                                <span style="font-size:8px; color:#888;">SCAN TO VERIFY</span><br>
                                <span style="font-size:8px; color:#0f3460; font-weight:bold; ">{{ $qr_data }}</span>
                            </td>
                            <td width="120" valign="middle" align="right">
                                <table cellpadding="0" cellspacing="0" border="0">
                                    <tr><td valign="middle" style="padding-right:6px;">
                                        <table width="28" height="28" cellpadding="0" cellspacing="0" border="0" style="background:#0f3460; border-radius:4px;">
                                            <tr><td align="center" valign="middle" style="color:#d4a843; font-size:12px; font-weight:bold; line-height:28px;">AL</td></tr>
                                        </table>
                                    </td>
                                    <td valign="middle">
                                        <span style="font-size:10px; font-weight:bold; color:#0f3460;">ArdhiLens</span><br>
                                        <span style="font-size:7px; color:#888;">Angalia Ardhi</span>
                                    </td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                </td></tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr><td style="height:4px; background:#d4a843;">&nbsp;</td></tr>
            </table>

        </td></tr>
    </table>

</body>
</html>
