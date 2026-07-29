<?php

return [
    'channels' => [
        'in_app' => env('NOTIFICATION_IN_APP', true),
        'email' => env('NOTIFICATION_EMAIL', true),
        'push' => env('NOTIFICATION_PUSH', true),
        'sms' => env('NOTIFICATION_SMS', false),
    ],

    'risk_alert_threshold' => (int) env('NOTIFICATION_RISK_ALERT_THRESHOLD', 30),
    'max_per_user' => (int) env('NOTIFICATION_MAX_PER_USER', 100),
    'email_from' => env('NOTIFICATION_EMAIL_FROM', 'noreply@ardhilens.tz'),

    'fcm' => [
        'server_key' => env('NOTIFICATION_FCM_SERVER_KEY'),
    ],

    'sms' => [
        'provider' => env('NOTIFICATION_SMS_PROVIDER', 'log'),
        'twilio_sid' => env('TWILIO_SID'),
        'twilio_token' => env('TWILIO_TOKEN'),
        'twilio_from' => env('TWILIO_FROM'),
    ],
];
