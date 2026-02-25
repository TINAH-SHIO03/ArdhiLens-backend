<?php

return [
    'auth' => [
        'validation_failed' => 'Uthibitishaji wa taarifa umeshindikana.',
        'registration_successful' => 'Usajili umefanikiwa.',
        'invalid_credentials' => 'Barua pepe au nenosiri si sahihi.',
        'inactive_account' => 'Akaunti yako haijawashwa. Wasiliana na msaada.',
        'login_successful' => 'Umeingia kwa mafanikio.',
        'logout_successful' => 'Umetoka kwenye akaunti kwa mafanikio.',
        'unauthenticated' => 'Hujaidhinishwa.',
        'authenticated_user' => 'Mtumiaji aliyeidhinishwa.',
    ],

    'land_verification' => [
        'validation_failed' => 'Uthibitishaji wa taarifa umeshindikana.',
        'unauthenticated' => 'Hujaidhinishwa.',
        'plot_not_found' => 'Kiwanja hakijapatikana.',
        'plot_found' => 'Kiwanja kimepatikana.',
        'session_not_found_or_expired' => 'Kikao cha uthibitishaji hakijapatikana au muda wake umeisha.',
        'plot_linked_not_found' => 'Kiwanja kilichounganishwa na kikao hiki hakijapatikana.',
        'plot_coordinates_unavailable' => 'Kuratibu za kiwanja hazipatikani kwa uthibitishaji wa umbali.',
        'gps_verification_failed' => 'Uthibitishaji wa GPS umeshindikana.',
        'gps_verification_passed' => 'Uthibitishaji wa GPS umefaulu.',
        'gps_must_pass_before_nin_challenge' => 'GPS lazima ifaulu kabla ya changamoto ya NIN.',
        'unable_to_generate_identity_questions' => 'Imeshindikana kutengeneza maswali ya utambulisho.',
        'insufficient_nida_data' => 'Taarifa za NIDA hazitoshi kutengeneza maswali 3 ya mabadiliko.',
        'dynamic_questions_generated' => 'Maswali ya NIN yametengenezwa.',
        'identity_challenge_not_generated' => 'Changamoto ya utambulisho haijatengenezwa.',
        'gps_must_pass_before_answer_verification' => 'GPS lazima ifaulu kabla ya kuthibitisha majibu.',
        'invalid_challenge_id' => 'Kitambulisho cha changamoto si sahihi.',
        'challenge_expired' => 'Muda wa changamoto umeisha. Omba maswali mapya.',
        'maximum_attempts_reached' => 'Umefikia idadi ya juu ya majaribio. Omba maswali mapya.',
        'challenge_data_invalid' => 'Taarifa za changamoto si sahihi. Omba maswali mapya.',
        'identity_challenge_failed' => 'Changamoto ya utambulisho imeshindikana.',
        'owner_linkage_failed' => 'Utambulisho umethibitishwa lakini uhusiano wa umiliki wa kiwanja umeshindikana.',
        'missing_user_id' => 'user_id haipo kwenye kikao cha uthibitishaji.',
        'verification_completed' => 'Uthibitishaji umekamilika kwa mafanikio.',

        'next_steps' => [
            'submit_gps' => 'Wasilisha kuratibu za GPS kwa uthibitishaji wa umbali.',
            'submit_nin' => 'Wasilisha NIN ili kutengeneza maswali ya usalama ya mabadiliko.',
            'submit_answers' => 'Wasilisha majibu ili kukamilisha uthibitishaji wa utambulisho.',
        ],

        'gps' => [
            'plot_coordinates_missing' => 'Kuratibu za kiwanja hazipo.',
        ],

        'ai' => [
            'reasons' => 'Utafutaji wa kiwanja, ukaguzi wa GPS, changamoto ya NIN, na uhusiano wa umiliki vyote vimefaulu.',
            'recommendation' => 'Uthibitishaji umekamilika kwa mafanikio.',
        ],

        'risk_reasons' => [
            'loan_risk' => 'Dhamana za benki zilizo hai/zilizoshindwa zimebainika (hai: :active, zilizoshindwa: :defaulted).',
            'ongoing_disputes' => 'Kuna mgogoro wa kisheria unaoendelea :count.',
            'active_caveats' => 'Kuna zuio (caveat) hai :count.',
            'double_allocation' => 'Kiwanja kimewekewa alama ya ugawaji wa mara mbili.',
            'ownership_changes' => 'Umiliki umebadilika mara :count.',
            'plot_status' => 'Hali ya kiwanja ni :status.',
            'no_land_rate_record' => 'Hakuna kumbukumbu ya malipo ya kodi ya ardhi.',
            'land_rates_current' => 'Kodi ya ardhi iko sawa kwa sasa.',
            'land_rates_overdue_12' => 'Kodi ya ardhi inaonekana kuchelewa hadi miezi 12.',
            'land_rates_overdue_24' => 'Kodi ya ardhi inaonekana kuchelewa zaidi ya miezi 12.',
            'land_rates_overdue_24_plus' => 'Kodi ya ardhi inaonekana kuchelewa zaidi ya miezi 24.',
            'certificate_offer_letter' => 'Aina ya cheti ni Letter of Offer, ambayo inaongeza hatari ya uhamisho.',
            'certificate_expired' => 'Cheti kimeisha muda tarehe :date.',
            'certificate_expiring_soon' => 'Cheti kitaisha ndani ya siku 90 (:date).',
            'double_allocation_with_legal_restrictions' => 'Ugawaji wa mara mbili umeonekana sambamba na mgogoro au zuio hai.',
            'revoked_with_expired_certificate' => 'Kiwanja kimefutwa na cheti pia kimeisha muda.',
            'debt_and_long_overdue_rates' => 'Mikopo inayoambatana na ucheleweshaji mkubwa wa kodi ya ardhi imeongeza hatari.',
            'uncertainty_penalty' => 'Baadhi ya taarifa muhimu za hatari hazijakamilika; adhabu ya tahadhari imeongezwa.',
            'no_major_red_flags' => 'Hakuna viashiria vikubwa vya hatari ya kisheria au kifedha vilivyobainika kwenye kumbukumbu zilizopo.',
        ],

        'assessment' => [
            'verdict_labels' => [
                'safe' => 'Salama Kununua',
                'caution' => 'Nunua kwa Tahadhari',
                'do_not_buy' => 'Usinunue',
            ],
            'owner_link' => [
                'reasons' => [
                    'owner_mismatch' => 'NIN iliyowasilishwa hailingani na kumbukumbu rasmi ya mmiliki wa kiwanja hiki.',
                    'history_mismatch' => 'Historia ya hivi karibuni ya uhamisho wa umiliki hailingani na NIN iliyowasilishwa.',
                ],
                'recommendation' => 'Usinunue. Usiendelee na ununuzi hadi kumbukumbu za umiliki zisahihishwe na kuthibitishwa rasmi katika ofisi ya ardhi.',
            ],
        ],

        'assistant' => [
            'explained' => 'Ufafanuzi wa kina umetengenezwa kwa mafanikio.',
            'verification_log_not_found' => 'Matokeo ya uthibitishaji hayajapatikana kwa mtumiaji huyu.',
            'default_reason' => 'Hakuna sababu za ziada za hatari zilizopatikana kwenye matokeo yaliyohifadhiwa.',
        ],

        'prompts' => [
            'mother_middle_name' => [
                'Weka jina la kati la mama yako.',
                'Jina la kati la mama yako lililoandikwa NIDA ni lipi?',
            ],
            'mother_surname' => [
                'Weka jina la mwisho la mama yako.',
                'Jina la mwisho la mama yako lililoandikwa NIDA ni lipi?',
            ],
            'father_middle_name' => [
                'Weka jina la kati la baba yako.',
                'Jina la kati la baba yako lililoandikwa NIDA ni lipi?',
            ],
            'father_surname' => [
                'Weka jina la mwisho la baba yako.',
                'Jina la mwisho la baba yako lililoandikwa NIDA ni lipi?',
            ],
            'perm_ward' => [
                'Weka kata ya makazi yako ya kudumu.',
                'Ni kata ipi imeandikwa kama makazi yako ya kudumu?',
            ],
            'perm_mtaa' => [
                'Weka mtaa au kijiji cha makazi yako ya kudumu.',
                'Ni mtaa au kijiji gani limeandikwa kama makazi yako ya kudumu?',
            ],
            'perm_district' => [
                'Weka wilaya ya makazi yako ya kudumu.',
                'Ni wilaya ipi imeandikwa kama makazi yako ya kudumu?',
            ],
            'res_ward' => [
                'Weka kata ya makazi yako ya sasa.',
                'Ni kata ipi imeandikwa kama makazi yako ya sasa?',
            ],
            'res_mtaa' => [
                'Weka mtaa au kijiji cha makazi yako ya sasa.',
                'Ni mtaa au kijiji gani limeandikwa kama makazi yako ya sasa?',
            ],
            'res_district' => [
                'Weka wilaya ya makazi yako ya sasa.',
                'Ni wilaya ipi imeandikwa kama makazi yako ya sasa?',
            ],
            'identity_detail' => [
                'Weka taarifa ya utambulisho inayohitajika.',
            ],
        ],
    ],
];
