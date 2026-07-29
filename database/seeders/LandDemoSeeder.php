<?php

namespace Database\Seeders;

use App\Models\Nida;
use App\Models\Plot;
use App\Models\PlotOwnershipHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LandDemoSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            [
                'nin' => '19901215-25555-00001',
                'first_name' => 'Juma',
                'middle_name' => 'Hamisi',
                'surname' => 'Mwangi',
                'gender' => 'M',
                'date_of_birth' => '1990-12-15',
                'marital_status' => 'Married',
                'occupation' => 'Farmer',
                'father_first_name' => 'Hamisi',
                'father_middle_name' => 'Juma',
                'father_surname' => 'Mwangi',
                'mother_first_name' => 'Asha',
                'mother_middle_name' => 'Rehema',
                'mother_surname' => 'Omar',
                'highest_education' => 'Secondary',
                'res_region' => 'Dar es Salaam',
                'res_district' => 'Kinondoni',
                'res_ward' => 'Msasani',
                'res_mtaa' => 'Oysterbay',
                'phone_number' => '255712345678',
            ],
            [
                'nin' => '19880520-25555-00002',
                'first_name' => 'Neema',
                'middle_name' => 'Grace',
                'surname' => 'Kimaro',
                'gender' => 'F',
                'date_of_birth' => '1988-05-20',
                'marital_status' => 'Single',
                'occupation' => 'Teacher',
                'father_first_name' => 'Peter',
                'father_middle_name' => 'John',
                'father_surname' => 'Kimaro',
                'mother_first_name' => 'Maria',
                'mother_middle_name' => 'Anne',
                'mother_surname' => 'Lyimo',
                'highest_education' => 'Degree',
                'res_region' => 'Arusha',
                'res_district' => 'Arusha',
                'res_ward' => 'Sekei',
                'res_mtaa' => 'Clock Tower',
                'phone_number' => '255722334455',
            ],
            [
                'nin' => '19750310-25555-00003',
                'first_name' => 'Baraka',
                'middle_name' => 'Eliudi',
                'surname' => 'Massawe',
                'gender' => 'M',
                'date_of_birth' => '1975-03-10',
                'marital_status' => 'Married',
                'occupation' => 'Business Owner',
                'father_first_name' => 'Eliudi',
                'father_middle_name' => 'Paulo',
                'father_surname' => 'Massawe',
                'mother_first_name' => 'Halima',
                'mother_middle_name' => 'Said',
                'mother_surname' => 'Juma',
                'highest_education' => 'Diploma',
                'res_region' => 'Dodoma',
                'res_district' => 'Dodoma Urban',
                'res_ward' => 'Makulu',
                'res_mtaa' => 'Area C',
                'phone_number' => '255754112233',
            ],
            [
                'nin' => '19920822-25555-00004',
                'first_name' => 'Amina',
                'middle_name' => 'Salum',
                'surname' => 'Hassan',
                'gender' => 'F',
                'date_of_birth' => '1992-08-22',
                'marital_status' => 'Married',
                'occupation' => 'Nurse',
                'father_first_name' => 'Salum',
                'father_middle_name' => 'Ali',
                'father_surname' => 'Hassan',
                'mother_first_name' => 'Fatuma',
                'mother_middle_name' => 'Bakari',
                'mother_surname' => 'Omar',
                'highest_education' => 'Certificate',
                'res_region' => 'Mwanza',
                'res_district' => 'Nyamagana',
                'res_ward' => 'Isamilo',
                'res_mtaa' => 'Capri Point',
                'phone_number' => '255765443322',
            ],
            [
                'nin' => '19811105-25555-00005',
                'first_name' => 'Emmanuel',
                'middle_name' => 'John',
                'surname' => 'Mushi',
                'gender' => 'M',
                'date_of_birth' => '1981-11-05',
                'marital_status' => 'Divorced',
                'occupation' => 'Surveyor',
                'father_first_name' => 'John',
                'father_middle_name' => 'Petro',
                'father_surname' => 'Mushi',
                'mother_first_name' => 'Grace',
                'mother_middle_name' => 'Anna',
                'mother_surname' => 'Kimaro',
                'highest_education' => 'Degree',
                'res_region' => 'Kilimanjaro',
                'res_district' => 'Moshi Urban',
                'res_ward' => 'Bondeni',
                'res_mtaa' => 'Kiboriloni',
                'phone_number' => '255713998877',
            ],
            [
                'nin' => '19960718-25555-00006',
                'first_name' => 'Rehema',
                'middle_name' => 'Said',
                'surname' => 'Abdallah',
                'gender' => 'F',
                'date_of_birth' => '1996-07-18',
                'marital_status' => 'Single',
                'occupation' => 'Student',
                'father_first_name' => 'Said',
                'father_middle_name' => 'Omar',
                'father_surname' => 'Abdallah',
                'mother_first_name' => 'Zuhura',
                'mother_middle_name' => 'Hamisi',
                'mother_surname' => 'Ali',
                'highest_education' => 'Secondary',
                'res_region' => 'Mbeya',
                'res_district' => 'Mbeya Urban',
                'res_ward' => 'Iyunga',
                'res_mtaa' => 'Block T',
                'phone_number' => '255746221100',
            ],
            [
                'nin' => '19691230-25555-00007',
                'first_name' => 'Deogratias',
                'middle_name' => 'Michael',
                'surname' => 'Ngowi',
                'gender' => 'M',
                'date_of_birth' => '1969-12-30',
                'marital_status' => 'Widowed',
                'occupation' => 'Retired Civil Servant',
                'father_first_name' => 'Michael',
                'father_middle_name' => 'Joseph',
                'father_surname' => 'Ngowi',
                'mother_first_name' => 'Christina',
                'mother_middle_name' => 'Paulo',
                'mother_surname' => 'Lyimo',
                'highest_education' => 'Diploma',
                'res_region' => 'Tanga',
                'res_district' => 'Tanga',
                'res_ward' => 'Ngamiani',
                'res_mtaa' => 'Street 8',
                'phone_number' => '255678554433',
            ],
            [
                'nin' => '20010412-25555-00008',
                'first_name' => 'Lightness',
                'middle_name' => 'Paschal',
                'surname' => 'Kavishe',
                'gender' => 'F',
                'date_of_birth' => '2001-04-12',
                'marital_status' => 'Single',
                'occupation' => 'Shop Attendant',
                'father_first_name' => 'Paschal',
                'father_middle_name' => 'Ernest',
                'father_surname' => 'Kavishe',
                'mother_first_name' => 'Joyce',
                'mother_middle_name' => 'Mary',
                'mother_surname' => 'Shayo',
                'highest_education' => 'Form Four',
                'res_region' => 'Dar es Salaam',
                'res_district' => 'Temeke',
                'res_ward' => 'Mbagala',
                'res_mtaa' => 'Kizuiani',
                'phone_number' => '255625889900',
            ],
        ];

        foreach ($records as $record) {
            Nida::updateOrCreate(
                ['nin' => $record['nin']],
                [
                    'first_name' => $record['first_name'],
                    'middle_name' => $record['middle_name'],
                    'surname' => $record['surname'],
                    'gender' => $record['gender'],
                    'date_of_birth' => $record['date_of_birth'],
                    'nationality' => 'Tanzanian',
                    'marital_status' => $record['marital_status'],
                    'occupation' => $record['occupation'],
                    'father_first_name' => $record['father_first_name'],
                    'father_middle_name' => $record['father_middle_name'],
                    'father_surname' => $record['father_surname'],
                    'mother_first_name' => $record['mother_first_name'],
                    'mother_middle_name' => $record['mother_middle_name'],
                    'mother_surname' => $record['mother_surname'],
                    'highest_education' => $record['highest_education'],
                    'res_region' => $record['res_region'],
                    'res_district' => $record['res_district'],
                    'res_ward' => $record['res_ward'],
                    'res_mtaa' => $record['res_mtaa'],
                    'perm_region' => $record['res_region'],
                    'perm_district' => $record['res_district'],
                    'perm_ward' => $record['res_ward'],
                    'perm_mtaa' => $record['res_mtaa'],
                    'phone_number' => $record['phone_number'],
                    'status' => 'Active',
                    'issued_at' => now()->subYears(random_int(2, 8)),
                ]
            );
        }

        $sellerNin = '19901215-25555-00001';
        $otherNin = '19880520-25555-00002';
        $barakaNin = '19750310-25555-00003';
        $aminaNin = '19920822-25555-00004';
        $mushiNin = '19811105-25555-00005';

        $safePlot = Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-001'],
            [
                'owner_nida' => $sellerNin,
                'region' => 'Dar es Salaam',
                'district' => 'Kinondoni',
                'ward' => 'Msasani',
                'village_mtaa' => 'Oysterbay',
                'street' => 'Toure Drive',
                'gps_latitude' => -6.76490000,
                'gps_longitude' => 39.28010000,
                'boundary_geojson' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [39.27970, -6.76520],
                        [39.28050, -6.76520],
                        [39.28050, -6.76460],
                        [39.27970, -6.76460],
                        [39.27970, -6.76520],
                    ]],
                ],
                'boundary_buffer_meters' => 20,
                'size_hectares' => 0.2500,
                'land_use' => 'Residential',
                'tenure_type' => 'Granted',
                'certificate_type' => 'Title',
                'issue_date' => now()->subYears(4)->toDateString(),
                'expiry_date' => now()->addYears(95)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-RISK'],
            [
                'owner_nida' => $otherNin,
                'region' => 'Dar es Salaam',
                'district' => 'Ilala',
                'ward' => 'Kariakoo',
                'village_mtaa' => 'Congo Street',
                'street' => 'Msimbazi',
                'gps_latitude' => -6.81600000,
                'gps_longitude' => 39.28050000,
                'size_hectares' => 0.1200,
                'land_use' => 'Commercial',
                'tenure_type' => 'Leasehold',
                'certificate_type' => 'CCRO',
                'issue_date' => now()->subYears(2)->toDateString(),
                'expiry_date' => now()->addYears(30)->toDateString(),
                'zoning_compliant' => false,
                'development_conditions_met' => false,
                'double_allocation_flag' => true,
                'status' => 'Disputed',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-002'],
            [
                'owner_nida' => $barakaNin,
                'region' => 'Dodoma',
                'district' => 'Dodoma Urban',
                'ward' => 'Makulu',
                'village_mtaa' => 'Area C',
                'street' => 'Nyerere Road',
                'gps_latitude' => -6.16300000,
                'gps_longitude' => 35.75160000,
                'size_hectares' => 0.5000,
                'land_use' => 'Mixed',
                'tenure_type' => 'Granted',
                'certificate_type' => 'Title',
                'issue_date' => now()->subYears(6)->toDateString(),
                'expiry_date' => now()->addYears(90)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-003'],
            [
                'owner_nida' => $aminaNin,
                'region' => 'Mwanza',
                'district' => 'Nyamagana',
                'ward' => 'Isamilo',
                'village_mtaa' => 'Capri Point',
                'street' => 'Lake Road',
                'gps_latitude' => -2.51640000,
                'gps_longitude' => 32.91700000,
                'size_hectares' => 0.1800,
                'land_use' => 'Residential',
                'tenure_type' => 'Customary',
                'certificate_type' => 'CCRO',
                'issue_date' => now()->subYears(3)->toDateString(),
                'expiry_date' => now()->addYears(40)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => false,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-004'],
            [
                'owner_nida' => $mushiNin,
                'region' => 'Kilimanjaro',
                'district' => 'Moshi Urban',
                'ward' => 'Bondeni',
                'village_mtaa' => 'Kiboriloni',
                'street' => 'Market Street',
                'gps_latitude' => -3.33900000,
                'gps_longitude' => 37.34000000,
                'size_hectares' => 0.3200,
                'land_use' => 'Agricultural',
                'tenure_type' => 'Leasehold',
                'certificate_type' => 'Title',
                'issue_date' => now()->subYears(8)->toDateString(),
                'expiry_date' => now()->addYears(20)->toDateString(),
                'zoning_compliant' => false,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        $rehemaNin = '19960718-25555-00006';
        $ngowiNin = '19691230-25555-00007';
        $lightnessNin = '20010412-25555-00008';

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-005'],
            [
                'owner_nida' => $rehemaNin,
                'region' => 'Mbeya',
                'district' => 'Mbeya Urban',
                'ward' => 'Iyunga',
                'village_mtaa' => 'Block T',
                'street' => 'Sokoine Road',
                'gps_latitude' => -8.90940000,
                'gps_longitude' => 33.46070000,
                'size_hectares' => 0.2100,
                'land_use' => 'Residential',
                'tenure_type' => 'Granted',
                'certificate_type' => 'Title',
                'issue_date' => now()->subYears(5)->toDateString(),
                'expiry_date' => now()->addYears(80)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-006'],
            [
                'owner_nida' => $ngowiNin,
                'region' => 'Tanga',
                'district' => 'Tanga',
                'ward' => 'Ngamiani',
                'village_mtaa' => 'Street 8',
                'street' => 'Independence Ave',
                'gps_latitude' => -5.06890000,
                'gps_longitude' => 39.09880000,
                'size_hectares' => 0.4100,
                'land_use' => 'Commercial',
                'tenure_type' => 'Leasehold',
                'certificate_type' => 'Title',
                'issue_date' => now()->subYears(7)->toDateString(),
                'expiry_date' => now()->addYears(50)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-007'],
            [
                'owner_nida' => $lightnessNin,
                'region' => 'Dar es Salaam',
                'district' => 'Temeke',
                'ward' => 'Mbagala',
                'village_mtaa' => 'Kizuiani',
                'street' => 'Kilwa Road',
                'gps_latitude' => -6.91200000,
                'gps_longitude' => 39.27000000,
                'size_hectares' => 0.1500,
                'land_use' => 'Residential',
                'tenure_type' => 'Granted',
                'certificate_type' => 'CCRO',
                'issue_date' => now()->subYears(2)->toDateString(),
                'expiry_date' => now()->addYears(33)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => false,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-008'],
            [
                'owner_nida' => $sellerNin,
                'region' => 'Morogoro',
                'district' => 'Morogoro Urban',
                'ward' => 'Kihonda',
                'village_mtaa' => 'Mazimbu',
                'street' => 'University Road',
                'gps_latitude' => -6.82780000,
                'gps_longitude' => 37.65910000,
                'size_hectares' => 0.2800,
                'land_use' => 'Mixed',
                'tenure_type' => 'Granted',
                'certificate_type' => 'Title',
                'issue_date' => now()->subYears(4)->toDateString(),
                'expiry_date' => now()->addYears(70)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-011'],
            [
                'owner_nida' => $sellerNin,
                'region' => 'Pwani',
                'district' => 'Bagamoyo',
                'ward' => 'Dunda',
                'village_mtaa' => 'Kaole',
                'street' => 'Beach Road',
                'gps_latitude' => -6.43200000,
                'gps_longitude' => 38.89700000,
                'size_hectares' => 0.3500,
                'land_use' => 'Residential',
                'tenure_type' => 'Granted',
                'certificate_type' => 'Title',
                'issue_date' => now()->subYears(3)->toDateString(),
                'expiry_date' => now()->addYears(60)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-012'],
            [
                'owner_nida' => $sellerNin,
                'region' => 'Dar es Salaam',
                'district' => 'Ilala',
                'ward' => 'Upanga',
                'village_mtaa' => 'Upanga West',
                'street' => 'United Nations Road',
                'gps_latitude' => -6.80100000,
                'gps_longitude' => 39.28400000,
                'size_hectares' => 0.1600,
                'land_use' => 'Commercial',
                'tenure_type' => 'Leasehold',
                'certificate_type' => 'Title',
                'issue_date' => now()->subYears(5)->toDateString(),
                'expiry_date' => now()->addYears(45)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-013'],
            [
                'owner_nida' => $sellerNin,
                'region' => 'Dar es Salaam',
                'district' => 'Kinondoni',
                'ward' => 'Mikocheni',
                'village_mtaa' => 'Mikocheni B',
                'street' => 'Old Bagamoyo Road',
                'gps_latitude' => -6.76800000,
                'gps_longitude' => 39.24500000,
                'size_hectares' => 0.2000,
                'land_use' => 'Residential',
                'tenure_type' => 'Granted',
                'certificate_type' => 'Title',
                'issue_date' => now()->subYears(2)->toDateString(),
                'expiry_date' => now()->addYears(85)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-009'],
            [
                'owner_nida' => $barakaNin,
                'region' => 'Iringa',
                'district' => 'Iringa Urban',
                'ward' => 'Kihesa',
                'village_mtaa' => 'Mkwawa',
                'street' => 'Gangilonga',
                'gps_latitude' => -7.76690000,
                'gps_longitude' => 35.69200000,
                'size_hectares' => 0.3600,
                'land_use' => 'Agricultural',
                'tenure_type' => 'Customary',
                'certificate_type' => 'CCRO',
                'issue_date' => now()->subYears(6)->toDateString(),
                'expiry_date' => now()->addYears(40)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        Plot::updateOrCreate(
            ['plot_reference' => 'PLOT-010'],
            [
                'owner_nida' => $aminaNin,
                'region' => 'Zanzibar Urban West',
                'district' => 'Mjini',
                'ward' => 'Shangani',
                'village_mtaa' => 'Forodhani',
                'street' => 'Kenyatta Road',
                'gps_latitude' => -6.16590000,
                'gps_longitude' => 39.20260000,
                'size_hectares' => 0.0900,
                'land_use' => 'Commercial',
                'tenure_type' => 'Leasehold',
                'certificate_type' => 'Title',
                'issue_date' => now()->subYears(3)->toDateString(),
                'expiry_date' => now()->addYears(25)->toDateString(),
                'zoning_compliant' => true,
                'development_conditions_met' => true,
                'double_allocation_flag' => false,
                'status' => 'Active',
            ]
        );

        PlotOwnershipHistory::query()->updateOrCreate(
            [
                'plot_id' => $safePlot->id,
                'to_nida' => $sellerNin,
                'transfer_date' => now()->subYears(4)->toDateString(),
            ],
            [
                'from_nida' => null,
                'transfer_reason' => 'Sale',
                'notes' => 'Initial title allocation',
            ]
        );

        $buyer = User::firstOrCreate(
            ['email' => 'dickensmanyama8@gmail.com'],
            [
                'name' => 'Dickens Buyer',
                'password' => Hash::make('password'),
                'role' => 'buyer',
                'nin' => null,
                'is_active' => true,
            ]
        );
        // Never overwrite an existing password on re-seed.
        $buyer->forceFill([
            'name' => 'Dickens Buyer',
            'role' => 'buyer',
            'is_active' => true,
        ])->save();
        unset($buyer);

        $seller = User::firstOrCreate(
            ['email' => 'manyamadickens@gmail.com'],
            [
                'name' => 'Juma Seller',
                'password' => Hash::make('password'),
                'role' => 'seller',
                'nin' => $sellerNin,
                'is_active' => true,
            ]
        );
        $seller->forceFill([
            'name' => 'Juma Seller',
            'role' => 'seller',
            'nin' => $sellerNin,
            'is_active' => true,
        ])->save();

        // Seed current land-rate payments + approved docs so PLOT-001 stays SAFE
        // and the buyer can finish with a signed certificate.
        $this->seedSupportDataForPlot($safePlot, $seller->id, 'SAFE-001');
        foreach (['PLOT-002', 'PLOT-003', 'PLOT-004', 'PLOT-005', 'PLOT-006', 'PLOT-007', 'PLOT-008', 'PLOT-009', 'PLOT-010', 'PLOT-011', 'PLOT-012', 'PLOT-013'] as $ref) {
            $plot = Plot::query()->where('plot_reference', $ref)->first();
            if ($plot) {
                $this->seedSupportDataForPlot($plot, $seller->id, $ref);
            }
        }
    }

    private function seedSupportDataForPlot(Plot $plot, int $uploaderUserId, string $tag): void
    {
        \App\Models\PlotLandRate::updateOrCreate(
            [
                'plot_id' => $plot->id,
                'receipt_number' => "LR-{$tag}-2026",
            ],
            [
                'amount_paid' => 150000,
                'payment_date' => now()->subMonths(1)->toDateString(),
                'period_from' => now()->startOfYear()->toDateString(),
                'period_to' => now()->endOfYear()->toDateString(),
            ]
        );

        foreach ([
            ['certificate_of_occupancy', 'Title Deed'],
            ['survey_plan', 'Survey Plan'],
        ] as [$type, $label]) {
            \App\Models\Document::updateOrCreate(
                [
                    'plot_id' => $plot->id,
                    'document_type' => $type,
                    'original_name' => "{$label} {$tag}.pdf",
                ],
                [
                    'user_id' => $uploaderUserId,
                    'file_path' => "documents/demo/{$tag}-{$type}.pdf",
                    'mime_type' => 'application/pdf',
                    'size' => 1024,
                    'notes' => 'Demo seeded supporting document',
                    'review_status' => 'approved',
                    'authenticity_score' => 92,
                    'authenticity_notes' => 'Demo seed: approved for SAFE path testing',
                    'file_hash' => hash('sha256', "{$tag}-{$type}"),
                    'reviewed_at' => now(),
                ]
            );
        }
    }
}
