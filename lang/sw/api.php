<?php

return [
    'auth' => [
        'validation_failed' => 'Uthibitishaji wa taarifa umeshindikana.',
        'registration_successful' => 'Usajili umefanikiwa.',
        'invalid_credentials' => 'Barua pepe au nenosiri si sahihi.',
        'inactive_account' => 'Akaunti yako haijawashwa. Wasiliana na msaada.',
        'admin_web_only' => 'Akaunti za msimamizi huingia kwenye paneli ya wavuti tu (/admin), si kwenye programu ya simu.',
        'login_successful' => 'Umeingia kwa mafanikio.',
        'logout_successful' => 'Umetoka kwenye akaunti kwa mafanikio.',
        'unauthenticated' => 'Hujaidhinishwa.',
        'authenticated_user' => 'Mtumiaji aliyeidhinishwa.',
        'profile_updated' => 'Wasifu umesasishwa kwa mafanikio.',
    ],

    'land_verification' => [
        'validation_failed' => 'Uthibitishaji wa taarifa umeshindikana.',
        'unauthenticated' => 'Hujaidhinishwa.',
        'plot_not_found' => 'Kiwanja hakijapatikana.',
        'seller_plot_not_linked' => 'Kiwanja hiki hakijaunganishwa na NIN yako. Kamilisha KYC ya muuzaji au wasiliana na msimamizi.',
        'plot_found' => 'Kiwanja kimepatikana.',
        'session_not_found_or_expired' => 'Kikao cha uthibitishaji hakijapatikana au muda wake umeisha.',
        'plot_linked_not_found' => 'Kiwanja kilichounganishwa na kikao hiki hakijapatikana.',
        'plot_coordinates_unavailable' => 'Kuratibu za kiwanja hazipatikani kwa uthibitishaji wa umbali.',
        'gps_verification_failed' => 'Uthibitishaji wa GPS umeshindikana.',
        'gps_verification_passed' => 'Uthibitishaji wa GPS umefaulu.',
        'gps_verification_remote' => 'Ukaguzi wa mbali wa kiwanja umefaulu. Unaweza kuendelea bila kuwa kwenye kiwanja.',
        'gps_verification_on_site' => 'Ukaguzi wa GPS wa eneo umefaulu. Eneo lako linalingana na kiwanja kilichosajiliwa.',
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
            'submit_gps' => 'Thibitisha eneo la kiwanja (ukaguzi wa mbali unaruhusiwa) au hiari thibitisha kuwa uko kwenye kiwanja.',
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
                'Weka jina la kati la mama yako TU kama lilivyoandikwa NIDA (si jina lote). Mfano: Jina lake kamili ni "Maria Agnes Mwakalinga", weka "Agnes".',
                'Jina la kati la mama yako ni lipi kama lilivyoandikwa kwenye rekodi za NIDA? Weka jina la kati pekee.',
            ],
            'mother_surname' => [
                'Weka jina la mwisho (surname) la mama yako TU kama lilivyoandikwa NIDA. Mfano: Jina lake kamili ni "Maria Agnes Mwakalinga", weka "Mwakalinga".',
                'Jina la mwisho (surname) la mama yako ni lipi kama lilivyoandikwa kwenye rekodi za NIDA?',
            ],
            'father_middle_name' => [
                'Weka jina la kati la baba yako TU kama lilivyoandikwa NIDA (si jina lote). Mfano: Jina lake kamili ni "Joseph Peter Mwakalinga", weka "Peter".',
                'Jina la kati la baba yako ni lipi kama lilivyoandikwa kwenye rekodi za NIDA? Weka jina la kati pekee.',
            ],
            'father_surname' => [
                'Weka jina la mwisho (surname) la baba yako TU kama lilivyoandikwa NIDA. Mfano: Jina lake kamili ni "Joseph Peter Mwakalinga", weka "Mwakalinga".',
                'Jina la mwisho (surname) la baba yako ni lipi kama lilivyoandikwa kwenye rekodi za NIDA?',
            ],
            'perm_ward' => [
                'Weka jina la kata TU lililoandikwa kama makazi yako ya kudumu kwenye NIDA. Usijumuisha wilaya wala mkoa.',
                'Ni kata ipi imeandikwa kama makazi yako ya kudumu kwenye NIDA? Weka jina la kata pekee.',
            ],
            'perm_mtaa' => [
                'Weka jina la mtaa au kijiji TU lililoandikwa kama makazi yako ya kudumu kwenye NIDA.',
                'Ni mtaa au kijiji gani limeandikwa kama makazi yako ya kudumu kwenye NIDA? Weka jina la mtaa/kijiji pekee.',
            ],
            'perm_district' => [
                'Weka jina la wilaya TU lililoandikwa kama makazi yako ya kudumu kwenye NIDA.',
                'Ni wilaya ipi imeandikwa kama makazi yako ya kudumu kwenye NIDA? Weka jina la wilaya pekee.',
            ],
            'res_ward' => [
                'Weka jina la kata TU lililoandikwa kama makazi yako ya sasa kwenye NIDA.',
                'Ni kata ipi imeandikwa kama makazi yako ya sasa kwenye NIDA? Weka jina la kata pekee.',
            ],
            'res_mtaa' => [
                'Weka jina la mtaa au kijiji TU lililoandikwa kama makazi yako ya sasa kwenye NIDA.',
                'Ni mtaa au kijiji gani limeandikwa kama makazi yako ya sasa kwenye NIDA? Weka jina la mtaa/kijiji pekee.',
            ],
            'res_district' => [
                'Weka jina la wilaya TU lililoandikwa kama makazi yako ya sasa kwenye NIDA.',
                'Ni wilaya ipi imeandikwa kama makazi yako ya sasa kwenye NIDA? Weka jina la wilaya pekee.',
            ],
            'identity_detail' => [
                'Weka taarifa ya utambulisho inayohitajika kama ilivyoandikwa NIDA.',
            ],
        ],
    ],

    'notifications' => [
        'validation_failed' => 'Uthibitishaji wa taarifa umeshindikana.',
        'unauthenticated' => 'Hujaidhinishwa.',
        'list_fetched' => 'Taarifa zimepatikana kwa mafanikio.',
        'unread_count_fetched' => 'Idadi ya taarifa ziszosomwa imepatikana.',
        'marked_as_read' => 'Taarifa imewekwa kama imesomwa.',
        'all_marked_as_read' => 'Taarifa zote zimewekwa kama zimesomwa.',
        'deleted' => 'Taarifa imefutwa.',
        'not_found' => 'Taarifa haijapatikana.',
        'device_token_registered' => 'Tokeni ya kifaa imesajiliwa kwa mafanikio.',
        'device_token_removed' => 'Tokeni ya kifaa imeondolewa.',
        'verification_complete_title' => 'Uthibitisho Umekamilika - :plot',
        'verification_complete_body_buyer' => 'Uthibitisho wa ardhi wa kiwanja :plot umekamilika. Hukumu: :verdict (Alama ya Hatari: :score/100).',
        'verification_complete_body_seller' => 'Uthibitisho wa ardhi wa kiwanja :plot umekamilika. Hukumu: :verdict (Hatari: :score/100). Kagua nia ya mnunuzi na nyaraka zako za umiliki.',
        'seller_buyer_verified_title' => 'Mnunuzi amethibitisha kiwanja chako - :plot',
        'seller_buyer_verified_body' => 'Mnunuzi :buyer amekamilisha uthibitisho wa kiwanja :plot. Hukumu: :verdict (Hatari: :score/100). Andaa nyaraka za mauzo ikiwa unataka kuendelea.',
        'plot_status_title' => 'Hali ya Kiwanja Imesasishwa - :plot',
        'plot_status_body' => 'Hali ya kiwanja :plot imebadilika kutoka :old hadi :new.',
        'risk_alert_title' => 'Tahadhari ya Hatari Kubwa - :plot',
        'risk_alert_body' => 'Onyo: Kiwanja :plot kina alama ya hatari ya juu :score/100. Hukumu: :verdict. Soma tathmini kamili kabla ya kuendelea.',
        'verdict_safe' => 'Salama Kununua',
        'verdict_caution' => 'Nunua kwa Tahadhari',
        'verdict_do_not_buy' => 'Usinunue',
        'procedure_seller_verification' => 'Hatua za muuzaji: 1) Thibitisha NIDA/umiliki. 2) Andaa Cheti cha Umiliki / hati miliki na mkataba wa mauzo. 3) Ondoa mizozo, vizuizi, au madeni yaliyoonyeshwa. 4) Kutana na mnunuzi na nyaraka asilia katika ofisi ya ardhi.',
        'procedure_seller_risk' => 'Hatua za muuzaji: Hatari kubwa imebainika. Tatua mizozo/vizuizi/madeni au sasisha hali ya kiwanja katika ofisi ya ardhi kabla ya kupokea malipo.',
        'procedure_seller_status' => 'Hatua za muuzaji: Hali ya kiwanja chako imebadilika. Fungua taarifa za ArdhiLens, kagua hali mpya, na wasiliana na rejista ya ardhi ikiwa mabadiliko hayakutegemewa.',
        'procedure_seller_default' => 'Hatua za muuzaji: Weka nyaraka za umiliki tayari, fuatilia uthibitisho wa wanunuzi, na uhamishe tu baada ya uthibitisho rasmi wa rejista.',
        'procedure_buyer_verification' => 'Hatua za mnunuzi: 1) Soma moduli za GPS, NIDA, umiliki, na hatari kwenye cheti. 2) Ikiwa SALAMA/TAHADHARI, omba nyaraka asilia kutoka kwa muuzaji. 3) Usilipe kabla ya uthibitisho wa rejista. 4) Hifadhi cheti kilichotiwa saini kidijitali.',
        'procedure_buyer_risk' => 'Hatua za mnunuzi: Usiendelee na malipo. Omba ufafanuzi kutoka kwa muuzaji na wasiliana na ofisi ya ardhi kuhusu hatari zilizobainika.',
        'procedure_buyer_status' => 'Hatua za mnunuzi: Hali ya kiwanja imebadilika. Thibitisha tena maelezo kabla ya malipo yoyote.',
        'procedure_buyer_default' => 'Hatua za mnunuzi: Tumia matokeo ya ArdhiLens kama mwongozo, kisha thibitisha na nyaraka rasmi za rejista ya ardhi kabla ya kulipa.',
    ],

    'certificates' => [
        'validation_failed' => 'Uthibitishaji wa taarifa umeshindikana.',
        'unauthenticated' => 'Hujaidhinishwa.',
        'certificate_generated' => 'Cheti kimetengenezwa kwa mafanikio.',
        'certificate_already_exists' => 'Cheti tayari kipo kwa uthibitishaji huu.',
        'certificate_not_found' => 'Cheti haijapatikana.',
        'certificate_not_eligible' => 'Cheti kinapatikana tu kwa uthibitishaji wa SALAMA au TAHADHARI.',
        'certificate_verified' => 'Uthibitishaji wa cheti umekamilika.',
        'verification_log_not_found' => 'Kumbukumbu ya uthibitishaji haijapatikana.',
        'verification_not_completed' => 'Uthibitishaji haujakamilika.',
        'plot_not_found' => 'Kiwanja kilichounganishwa haijapatikana.',
        'pdf_generation_failed' => 'Utengenezaji wa PDF umeshindikana.',
        'list_fetched' => 'Vyeti vimepatikana kwa mafanio.',
    ],

    'documents' => [
        'validation_failed' => 'Uthibitishaji wa taarifa umeshindikana.',
        'unauthenticated' => 'Hujaidhinishwa.',
        'uploaded' => 'Hati imepakiwa kwa mafanikio.',
        'list_fetched' => 'Hati zimepatikana kwa mafanikio.',
        'not_found' => 'Haijapatikana.',
        'file_not_found' => 'Faili haijapatikana kwenye seva.',
        'deleted' => 'Hati imefutwa.',
    ],
];
