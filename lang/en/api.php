<?php

return [
    'auth' => [
        'validation_failed' => 'Validation failed.',
        'registration_successful' => 'Registration successful.',
        'invalid_credentials' => 'Invalid email or password.',
        'inactive_account' => 'Your account is inactive. Contact support.',
        'admin_web_only' => 'Admin accounts sign in on the web panel only (/admin), not in the mobile app.',
        'login_successful' => 'Login successful.',
        'logout_successful' => 'Logout successful.',
        'unauthenticated' => 'Unauthenticated.',
        'authenticated_user' => 'Authenticated user.',
        'profile_updated' => 'Profile updated successfully.',
    ],

    'land_verification' => [
        'validation_failed' => 'Validation failed.',
        'unauthenticated' => 'Unauthenticated.',
        'plot_not_found' => 'Plot not found.',
        'seller_plot_not_linked' => 'This plot is not linked to your NIN. Complete seller KYC or contact admin.',
        'plot_found' => 'Plot found.',
        'session_not_found_or_expired' => 'Verification session not found or expired.',
        'plot_linked_not_found' => 'Plot linked to this session was not found.',
        'plot_coordinates_unavailable' => 'Plot coordinates are not available for distance verification.',
        'gps_verification_failed' => 'GPS verification failed.',
        'gps_verification_passed' => 'GPS verification passed.',
        'gps_verification_remote' => 'Remote plot check passed. You can continue without being at the plot.',
        'gps_verification_on_site' => 'On-site GPS check passed. Your location matches the registered plot.',
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
            'submit_gps' => 'Confirm plot location (remote check allowed) or optionally prove you are on-site.',
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
                'Enter ONLY your mother\'s middle name as recorded by NIDA (not full name). Example: If her full name is "Maria Agnes Mwakalinga", enter "Agnes".',
                'What is your mother\'s middle name exactly as it appears on your NIDA record? Enter the middle name only.',
            ],
            'mother_surname' => [
                'Enter ONLY your mother\'s surname/last name as recorded by NIDA. Example: If her full name is "Maria Agnes Mwakalinga", enter "Mwakalinga".',
                'What is your mother\'s surname (last name) exactly as it appears on your NIDA record?',
            ],
            'father_middle_name' => [
                'Enter ONLY your father\'s middle name as recorded by NIDA (not full name). Example: If his full name is "Joseph Peter Mwakalinga", enter "Peter".',
                'What is your father\'s middle name exactly as it appears on your NIDA record? Enter the middle name only.',
            ],
            'father_surname' => [
                'Enter ONLY your father\'s surname/last name as recorded by NIDA. Example: If his full name is "Joseph Peter Mwakalinga", enter "Mwakalinga".',
                'What is your father\'s surname (last name) exactly as it appears on your NIDA record?',
            ],
            'perm_ward' => [
                'Enter ONLY the ward (kata) name listed as your permanent residence on your NIDA record. Do not include district or region.',
                'Which ward (kata) is listed as your permanent residence on your NIDA record? Enter the ward name only.',
            ],
            'perm_mtaa' => [
                'Enter ONLY the mtaa/village name listed as your permanent residence on your NIDA record.',
                'Which mtaa or village is listed as your permanent residence on your NIDA record? Enter the mtaa/village name only.',
            ],
            'perm_district' => [
                'Enter ONLY the district name listed as your permanent residence on your NIDA record.',
                'Which district is listed as your permanent residence on your NIDA record? Enter the district name only.',
            ],
            'res_ward' => [
                'Enter ONLY the ward (kata) name listed as your current residence on your NIDA record.',
                'Which ward (kata) is listed as your current residence on your NIDA record? Enter the ward name only.',
            ],
            'res_mtaa' => [
                'Enter ONLY the mtaa/village name listed as your current residence on your NIDA record.',
                'Which mtaa or village is listed as your current residence on your NIDA record? Enter the mtaa/village name only.',
            ],
            'res_district' => [
                'Enter ONLY the district name listed as your current residence on your NIDA record.',
                'Which district is listed as your current residence on your NIDA record? Enter the district name only.',
            ],
            'identity_detail' => [
                'Enter the requested identity detail exactly as recorded by NIDA.',
            ],
        ],
    ],

    'notifications' => [
        'validation_failed' => 'Validation failed.',
        'unauthenticated' => 'Unauthenticated.',
        'list_fetched' => 'Notifications fetched successfully.',
        'unread_count_fetched' => 'Unread count fetched.',
        'marked_as_read' => 'Notification marked as read.',
        'all_marked_as_read' => 'All notifications marked as read.',
        'deleted' => 'Notification deleted.',
        'not_found' => 'Notification not found.',
        'device_token_registered' => 'Device token registered successfully.',
        'device_token_removed' => 'Device token removed.',
        'verification_complete_title' => 'Verification Complete - :plot',
        'verification_complete_body_buyer' => 'Your land verification for plot :plot is complete. Verdict: :verdict (Risk Score: :score/100).',
        'verification_complete_body_seller' => 'A land verification for plot :plot finished. Verdict: :verdict (Risk: :score/100). Review buyer interest and your ownership documents.',
        'seller_buyer_verified_title' => 'Buyer verified your plot - :plot',
        'seller_buyer_verified_body' => 'Buyer :buyer completed verification on plot :plot. Verdict: :verdict (Risk: :score/100). Prepare sale documents if you intend to proceed.',
        'plot_status_title' => 'Plot Status Updated - :plot',
        'plot_status_body' => 'The status of plot :plot changed from :old to :new.',
        'risk_alert_title' => 'High Risk Alert - :plot',
        'risk_alert_body' => 'Warning: Plot :plot has a high risk score of :score/100. Verdict: :verdict. Review the full assessment before proceeding.',
        'verdict_safe' => 'Safe to Buy',
        'verdict_caution' => 'Buy with Caution',
        'verdict_do_not_buy' => 'Do Not Buy',
        'procedure_seller_verification' => 'Seller procedure: 1) Confirm your NIDA/ownership match. 2) Prepare Certificate of Occupancy / title and sale agreement. 3) Clear any caveats, disputes, or encumbrances shown in the report. 4) Meet the buyer with original documents at the land office.',
        'procedure_seller_risk' => 'Seller procedure: High risk was flagged. Resolve disputes/caveats/encumbrances or update plot status with the land office before accepting payment.',
        'procedure_seller_status' => 'Seller procedure: Your plot status changed. Open ArdhiLens notifications, review the new status, and contact the land registry if the change was unexpected.',
        'procedure_seller_default' => 'Seller procedure: Keep ownership documents ready, monitor buyer verification activity, and only transfer after official registry confirmation.',
        'procedure_buyer_verification' => 'Buyer procedure: 1) Review GPS, NIDA, ownership, and risk modules on the certificate. 2) If SAFE/CAUTION, request original title documents from the seller. 3) Do not pay before registry confirmation. 4) Keep the digitally signed certificate for your records.',
        'procedure_buyer_risk' => 'Buyer procedure: Do not proceed with payment. Request clarification from the seller and consult the land office about the flagged risks.',
        'procedure_buyer_status' => 'Buyer procedure: Plot status changed. Re-check verification details before any payment.',
        'procedure_buyer_default' => 'Buyer procedure: Use ArdhiLens results as guidance, then confirm with official land registry documents before paying.',
    ],

    'certificates' => [
        'validation_failed' => 'Validation failed.',
        'unauthenticated' => 'Unauthenticated.',
        'certificate_generated' => 'Certificate generated successfully.',
        'certificate_already_exists' => 'A certificate already exists for this verification.',
        'certificate_not_found' => 'Certificate not found.',
        'certificate_not_eligible' => 'Certificate is only available for SAFE or CAUTION verifications.',
        'certificate_verified' => 'Certificate verification completed.',
        'verification_log_not_found' => 'Verification log not found.',
        'verification_not_completed' => 'Verification has not been completed.',
        'plot_not_found' => 'Associated plot not found.',
        'pdf_generation_failed' => 'PDF generation failed.',
        'list_fetched' => 'Certificates fetched successfully.',
    ],

    'documents' => [
        'validation_failed' => 'Validation failed.',
        'unauthenticated' => 'Unauthenticated.',
        'uploaded' => 'Document uploaded successfully.',
        'list_fetched' => 'Documents fetched successfully.',
        'not_found' => 'Document not found.',
        'file_not_found' => 'File not found on server.',
        'deleted' => 'Document deleted.',
    ],
];
