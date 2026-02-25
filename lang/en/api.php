<?php

return [
    'auth' => [
        'validation_failed' => 'Validation failed.',
        'registration_successful' => 'Registration successful.',
        'invalid_credentials' => 'Invalid email or password.',
        'inactive_account' => 'Your account is inactive. Contact support.',
        'login_successful' => 'Login successful.',
        'logout_successful' => 'Logout successful.',
        'unauthenticated' => 'Unauthenticated.',
        'authenticated_user' => 'Authenticated user.',
    ],

    'land_verification' => [
        'validation_failed' => 'Validation failed.',
        'unauthenticated' => 'Unauthenticated.',
        'plot_not_found' => 'Plot not found.',
        'plot_found' => 'Plot found.',
        'session_not_found_or_expired' => 'Verification session not found or expired.',
        'plot_linked_not_found' => 'Plot linked to this session was not found.',
        'plot_coordinates_unavailable' => 'Plot coordinates are not available for distance verification.',
        'gps_verification_failed' => 'GPS verification failed.',
        'gps_verification_passed' => 'GPS verification passed.',
        'gps_must_pass_before_nin_challenge' => 'GPS verification must pass before NIN challenge.',
        'unable_to_generate_identity_questions' => 'Unable to generate identity questions.',
        'insufficient_nida_data' => 'Insufficient NIDA profile data to generate 3 dynamic questions.',
        'dynamic_questions_generated' => 'Dynamic NIN questions generated.',
        'identity_challenge_not_generated' => 'Identity challenge has not been generated.',
        'gps_must_pass_before_answer_verification' => 'GPS verification must pass before answer verification.',
        'invalid_challenge_id' => 'Invalid challenge id.',
        'challenge_expired' => 'Challenge expired. Request new questions.',
        'maximum_attempts_reached' => 'Maximum attempts reached. Request new questions.',
        'challenge_data_invalid' => 'Challenge data is invalid. Request new questions.',
        'identity_challenge_failed' => 'Identity challenge failed.',
        'owner_linkage_failed' => 'Identity verified but owner linkage failed for this plot.',
        'missing_user_id' => 'user_id is missing in verification session.',
        'verification_completed' => 'Verification completed successfully.',

        'next_steps' => [
            'submit_gps' => 'Submit GPS coordinates for distance verification.',
            'submit_nin' => 'Submit NIN to generate dynamic security questions.',
            'submit_answers' => 'Submit answers to complete identity verification.',
        ],

        'gps' => [
            'plot_coordinates_missing' => 'Plot coordinates are missing.',
        ],

        'ai' => [
            'reasons' => 'Plot lookup, GPS check, dynamic NIN challenge, and owner linkage all passed.',
            'recommendation' => 'Verification completed successfully.',
        ],

        'risk_reasons' => [
            'loan_risk' => 'Active/defaulted encumbrances detected (active: :active, defaulted: :defaulted).',
            'ongoing_disputes' => ':count ongoing legal dispute(s) recorded.',
            'active_caveats' => ':count active caveat(s) recorded.',
            'double_allocation' => 'Plot is flagged for double allocation.',
            'ownership_changes' => 'Ownership changed :count time(s).',
            'plot_status' => 'Plot status is :status.',
            'no_land_rate_record' => 'No land-rate payment record found.',
            'land_rates_current' => 'Land rates are current.',
            'land_rates_overdue_12' => 'Land rates appear overdue by up to 12 months.',
            'land_rates_overdue_24' => 'Land rates appear overdue by more than 12 months.',
            'land_rates_overdue_24_plus' => 'Land rates appear overdue by more than 24 months.',
            'certificate_offer_letter' => 'Certificate type is Letter of Offer, which has elevated transfer risk.',
            'certificate_expired' => 'Certificate expired on :date.',
            'certificate_expiring_soon' => 'Certificate expires within 90 days (:date).',
            'double_allocation_with_legal_restrictions' => 'Double allocation appears together with an active dispute or caveat.',
            'revoked_with_expired_certificate' => 'Plot is revoked and certificate is expired.',
            'debt_and_long_overdue_rates' => 'Loan exposure co-exists with long overdue land rates.',
            'uncertainty_penalty' => 'Some critical risk fields were incomplete; a conservative uncertainty penalty was applied.',
            'no_major_red_flags' => 'No major legal or financial red flags were detected from available records.',
        ],

        'assessment' => [
            'verdict_labels' => [
                'safe' => 'Safe to Buy',
                'caution' => 'Buy with Caution',
                'do_not_buy' => 'Do Not Buy',
            ],
            'owner_link' => [
                'reasons' => [
                    'owner_mismatch' => 'Submitted NIN does not match the legal owner record for this plot.',
                    'history_mismatch' => 'Latest ownership transfer history is inconsistent with the submitted NIN.',
                ],
                'recommendation' => 'Do not buy. Do not proceed with purchase until ownership records are corrected and formally verified at the land office.',
            ],
        ],

        'assistant' => [
            'explained' => 'Detailed explanation generated successfully.',
            'verification_log_not_found' => 'Verification result not found for this user.',
            'default_reason' => 'No additional risk reasons were available in the stored result.',
        ],

        'prompts' => [
            'mother_middle_name' => [
                'Enter your mother middle name.',
                'What is your mother middle name as recorded by NIDA?',
            ],
            'mother_surname' => [
                'Enter your mother surname.',
                'What is your mother last name as recorded by NIDA?',
            ],
            'father_middle_name' => [
                'Enter your father middle name.',
                'What is your father middle name as recorded by NIDA?',
            ],
            'father_surname' => [
                'Enter your father surname.',
                'What is your father last name as recorded by NIDA?',
            ],
            'perm_ward' => [
                'Enter your permanent residence ward.',
                'Which ward is listed as your permanent residence?',
            ],
            'perm_mtaa' => [
                'Enter your permanent residence mtaa/village.',
                'Which mtaa or village is listed as your permanent residence?',
            ],
            'perm_district' => [
                'Enter your permanent residence district.',
                'Which district is listed as your permanent residence?',
            ],
            'res_ward' => [
                'Enter your current residence ward.',
                'Which ward is listed as your current residence?',
            ],
            'res_mtaa' => [
                'Enter your current residence mtaa/village.',
                'Which mtaa or village is listed as your current residence?',
            ],
            'res_district' => [
                'Enter your current residence district.',
                'Which district is listed as your current residence?',
            ],
            'identity_detail' => [
                'Enter the requested identity detail.',
            ],
        ],
    ],
];
