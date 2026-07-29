<?php

return [
    'max_distance_meters' => (float) env('LAND_VERIFICATION_MAX_DISTANCE_METERS', 250),
    'max_gps_accuracy_meters' => (float) env('LAND_VERIFICATION_MAX_GPS_ACCURACY_METERS', 50),
    // When true, challenge questions include plaintext demo_answer so testers can pass NIDA Q&A.
    'demo_hints' => filter_var(env('LAND_VERIFICATION_DEMO_HINTS', true), FILTER_VALIDATE_BOOL),
    'risk' => [
        'thresholds' => [
            'safe_max' => (int) env('LAND_RISK_SAFE_MAX_SCORE', 29),
            'caution_max' => (int) env('LAND_RISK_CAUTION_MAX_SCORE', 69),
        ],
        'owner_link_forced_score' => (int) env('LAND_VERIFICATION_OWNER_LINK_FORCED_SCORE', 95),
        'uncertainty_penalty' => (int) env('LAND_VERIFICATION_UNCERTAINTY_PENALTY', 8),
        'missing_docs_penalty' => (int) env('LAND_VERIFICATION_MISSING_DOCS_PENALTY', 8),
        'flagged_docs_penalty' => (int) env('LAND_VERIFICATION_FLAGGED_DOCS_PENALTY', 12),
    ],
];
