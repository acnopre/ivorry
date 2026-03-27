<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Clinic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Faker\Factory as Faker;

class DentistSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Create the "Dentist" role
        $role = Role::firstOrCreate(['name' => 'Dentist']);

        // Loop through 5 clinics
        for ($c = 1; $c <= 5; $c++) {

            // First clinic has a real name, rest use faker
            if ($c === 1) {
                $clinicName = 'Bright Smile Dental Clinic';
                $registeredName = 'Bright Smile Dental Clinic Inc.';
            } else {
                $clinicName = $faker->company . ' Dental Clinic';
                $registeredName = $clinicName . ' Inc.';
            }

            // Create owner user
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $middleInitial = strtoupper(substr($faker->firstName, 0, 1));
            $fullName = "$firstName $middleInitial. $lastName";

            $ownerUser = User::create([
                'name'     => $fullName,
                'email'    => 'ivorry.dentist' . $c . '@example.com',
                'password' => Hash::make('password'),
            ]);

            $ownerUser->assignRole($role);

            // Create the clinic (linked to the owner)
            $clinic = Clinic::create([
                'user_id' => $ownerUser->id ?? 1,

                // Clinic info
                'clinic_name' => $clinicName,
                'registered_name' => $registeredName,

                // PTR
                'ptr_no' => 'PTR-' . $faker->numerify('####'),
                'ptr_date_issued' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),

                // Accreditation & Tax
                'other_hmo_accreditation' => implode(', ', $faker->randomElements(
                    ['Maxicare', 'Intellicare', 'MedCard', 'PhilCare'],
                    $faker->numberBetween(1, 3)
                )),
                'tax_identification_no' => $faker->numerify('###-###-###'),

                'is_branch' => $faker->boolean(),
                'complete_address' => $faker->address,

                // Update Info per BIR Form 1903
                'update_info_1903' => $faker->randomElement([
                    'CHANGE IN BUSINESS NAME',
                    'CHANGE IN ADDRESS',
                    'CHANGE IN TAX TYPE',
                    null
                ]),

                // Business type
                'business_type' => $faker->randomElement([
                    'SOLE PROPRIETORSHIP',
                    'PARTNERSHIP',
                    'GENERAL PROFESSIONAL PARTNERSHIP',
                    'CORPORATION',
                    'ONE PERSON CORPORATION',
                ]),

                // VAT TYPE
                'vat_type' => $faker->randomElement([
                    'VAT 12%',
                    'VAT ZERO',
                    'VAT EXEMPT',
                    'NON-VAT',
                ]),

                // Withholding Tax
                'withholding_tax' => $faker->randomElement([
                    'ZERO',
                    '2%',
                    '5%',
                    '10%',
                    '15%',
                ]),

                // SEC registration
                'sec_registration_no' => 'SEC-' . $faker->numerify('2024-###'),

                // Address Fields
                'street' => $faker->streetAddress,
                'region_id' => $faker->numberBetween(1, 17),
                'province_id' => $faker->numberBetween(1, 100),
                'municipality_id' => $faker->numberBetween(1, 500),
                'barangay_id' => $faker->numberBetween(1, 2000),

                // Contact Info
                'clinic_landline' => $faker->numerify('02-8###-####'),
                'clinic_mobile' => $faker->numerify('09#########'),
                'viber_no' => $faker->numerify('09#########'),
                'clinic_email' => $faker->unique()->companyEmail,

                // Alternative address/contact
                'alt_address' => $faker->secondaryAddress,

                // Clinic Staff
                'clinic_staff_name' => $faker->name,
                'clinic_staff_mobile' => $faker->numerify('09#########'),
                'clinic_staff_viber' => $faker->numerify('09#########'),
                'clinic_staff_email' => $faker->unique()->safeEmail,

                // Bank Info
                'bank_account_name' => $clinicName,
                'bank_account_number' => $faker->numerify('##########'),
                'bank_name' => $faker->randomElement(['BPI', 'BDO', 'Metrobank', 'UnionBank']),
                'bank_branch' => $faker->city,
                'account_type' => $faker->randomElement(['SAVINGS', 'CURRENT']),

                // Status
                'accreditation_status' => $faker->randomElement([
                    'ACTIVE',
                    // 'INACTIVE',
                    // 'SILENT',
                    // 'SPECIFIC ACCOUNT'
                ]),

                'remarks' => $faker->sentence(),
                'fee_approval' => 'APPROVED',
                'created_at' => now(),
                'updated_at' => now(),
            ]);


            $allServiceIds = DB::table('services')->pluck('id')->toArray();

            foreach ($allServiceIds as $serviceId) {
                DB::table('clinic_services')->insert([
                    'clinic_id' => $clinic->id,
                    'service_id' => $serviceId,
                    'fee' => $faker->randomFloat(2, 300, 3000), // assign random fee for each service
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Insert the owner dentist record
            DB::table('dentists')->insert([
                'clinic_id'           => $clinic->id,
                'last_name'           => $lastName,
                'first_name'          => $firstName,
                'middle_initial'      => $middleInitial,
                'prc_license_number'  => 'PRC-' . $faker->numerify('######'),
                'prc_expiration_date' => $faker->dateTimeBetween('now', '+5 years')->format('Y-m-d'),
                'is_owner'            => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // Create 2 associate dentists (no user account)
            for ($i = 1; $i <= 2; $i++) {
                DB::table('dentists')->insert([
                    'clinic_id'           => $clinic->id,
                    'last_name'           => $faker->lastName,
                    'first_name'          => $faker->firstName,
                    'middle_initial'      => strtoupper(substr($faker->firstName, 0, 1)),
                    'prc_license_number'  => 'PRC-' . $faker->numerify('######'),
                    'prc_expiration_date' => $faker->dateTimeBetween('now', '+5 years')->format('Y-m-d'),
                    'is_owner'            => 0,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]);
            }
        }

        $this->command->info('✅ Dentists and clinics (with owners) seeded successfully!');
    }
}
