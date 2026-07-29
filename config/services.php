<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
        'timeout_seconds' => (int) env('GEMINI_TIMEOUT_SECONDS', 15),
        'retries' => (int) env('GEMINI_RETRIES', 1),
        'temperature' => (float) env('GEMINI_TEMPERATURE', 0.1),
        'enable_contradiction_check' => filter_var(
            env('GEMINI_ENABLE_CONTRADICTION_CHECK', true),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],

    'nida' => [
        'driver' => env('NIDA_DRIVER', 'local'), // local | http
        'base_url' => env('NIDA_BASE_URL'),
        'api_key' => env('NIDA_API_KEY'),
    ],

    'face_match' => [
        'provider' => env('FACE_MATCH_PROVIDER', 'heuristic'), // heuristic | http
        'api_url' => env('FACE_MATCH_API_URL'),
        'api_key' => env('FACE_MATCH_API_KEY'),
        'pass_threshold' => (float) env('FACE_MATCH_PASS_THRESHOLD', 62),
    ],

];
