<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Certificate Validity Period
    |--------------------------------------------------------------------------
    |
    | How long a certificate remains valid after issuance (in months).
    |
    */
    'validity_months' => (int) env('CERTIFICATE_VALIDITY_MONTHS', 12),

    /*
    |--------------------------------------------------------------------------
    | Verification Domain
    |--------------------------------------------------------------------------
    |
    | The domain used in QR codes for certificate verification URLs.
    |
    */
    'verification_domain' => env('CERTIFICATE_VERIFICATION_DOMAIN', rtrim((string) env('APP_URL', 'http://127.0.0.1:8000'), '/').'/verify'),

    /*
    |--------------------------------------------------------------------------
    | Key Storage
    |--------------------------------------------------------------------------
    |
    | Subdirectory under storage/app for RSA key pair storage.
    |
    */
    'key_directory' => env('CERTIFICATE_KEY_DIRECTORY', 'certs'),
];
