<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Clinics;
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
        $permissions = ['dentist.view', 'dentist.manage', 'chatbot.use'];
        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        // Loop through 5 clinics
        for ($c = 1; $c <= 5; $c++) {

            $clinicName = $faker->company . ' Dental Clinic';
            $registeredName = $clinicName . ' Inc.';

            // Create owner user
            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $middleInitial = strtoupper(substr($faker->firstName, 0, 1));
            $fullName = "$firstName $middleInitial. $lastName";

            $ownerUser = User::create([
                'name'     => $fullName,
                'email'    => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
            ]);

            $ownerUser->assignRole($role);

            // Create the clinic (linked to the owner)
            $clinic = Clinics::create([
                'user_id' => $ownerUser->id,
                'clinic_name' => $clinicName,
                'registered_name' => $registeredName,
                'ptr_no' => 'PTR-' . $faker->numerify('####'),
                'ptr_date_issued' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'other_hmo_accreditation' => implode(', ', $faker->randomElements(['Maxicare', 'Intellicare', 'MedCard', 'PhilCare'], 2)),
                'tax_identification_no' => $faker->numerify('###-###-###'),
                'tax_type' => $faker->randomElement(['VAT', 'NON-VAT']),
                'business_type' => $faker->randomElement(['SOLE PROPRIETOR', 'PARTNERSHIP', 'CORPORATION']),
                'sec_registration_no' => 'SEC-' . $faker->numerify('2024-###'),
                'clinic_address' => $faker->address,
                'clinic_landline' => $faker->numerify('02-8###-####'),
                'clinic_mobile' => $faker->numerify('09#########'),
                'viber_no' => $faker->numerify('09#########'),
                'clinic_email' => $faker->unique()->companyEmail,
                'alt_address' => $faker->secondaryAddress,
                'clinic_staff_name' => $faker->name,
                'clinic_staff_mobile' => $faker->numerify('09#########'),
                'clinic_staff_viber' => $faker->numerify('09#########'),
                'clinic_staff_email' => $faker->unique()->safeEmail,
                'bank_account_name' => $clinicName,
                'bank_account_number' => $faker->numerify('##########'),
                'bank_name' => $faker->randomElement(['BPI', 'BDO', 'Metrobank', 'UnionBank']),
                'bank_branch' => $faker->city,
                'account_type' => $faker->randomElement(['SAVINGS', 'CURRENT']),
                'accreditation_status' => $faker->randomElement(['ACTIVE', 'SILENT', 'INACTIVE', 'SPECIFIC ACCOUNT']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

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
