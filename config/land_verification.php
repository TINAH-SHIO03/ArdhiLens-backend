<?php

return [
    'max_distance_meters' => (float) env('LAND_VERIFICATION_MAX_DISTANCE_METERS', 150),
    'risk' => [
        'thresholds' => [
            'safe_max' => (int) env('LAND_RISK_SAFE_MAX_SCORE', 29),
            'caution_max' => (int) env('LAND_RISK_CAUTION_MAX_SCORE', 69),
        ],
        'owner_link_forced_score' => (int) env('LAND_VERIFICATION_OWNER_LINK_FORCED_SCORE', 95),
        'uncertainty_penalty' => (int) env('LAND_VERIFICATION_UNCERTAINTY_PENALTY', 8),
    ],
];
