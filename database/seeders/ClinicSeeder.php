<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('clinics')->insert([
            [
                'id' => 1,
                'clinic_name' => 'Smile Dental Clinic',
                'registered_name' => 'Smile Dental Care Inc.',
                'clinic_owner_last' => 'Dela Cruz',
                'clinic_owner_first' => 'Juan',
                'clinic_owner_middle' => 'Santos',
                'specializations' => 'Orthodontics, Pediatric Dentistry',

                // PRC / PTR
                'prc_license_no' => 'PRC-123456',
                'prc_expiration_date' => '2026-12-31',
                'ptr_no' => 'PTR-987654',
                'ptr_date_issued' => '2025-01-15',

                // Accreditation & Tax
                'other_hmo_accreditation' => 'Maxicare, Intellicare',
                'tax_identification_no' => '123-456-789',
                'tax_type' => 'vat',
                'business_type' => 'corporation',
                'sec_registration_no' => 'SEC-2025-00123',

                // Address / Contact
                'clinic_address' => '123 Main Street, Manila',
                'clinic_landline' => '02-8123-4567',
                'clinic_mobile' => '09171234567',
                'viber_no' => '09171234567',
                'clinic_email' => 'info@smiledental.com',

                // Alternative Contact
                'alt_address' => 'Unit 456, Ayala Ave, Makati City',

                // Dentist Info
                'dentist_personal_no' => '09991234567',
                'dentist_email' => 'dr.juan@smiledental.com',
                'clinic_schedule' => 'by_appointment',
                'schedule_days' => 'Mon-Fri',
                'number_of_chairs' => 4,
                'dental_xray_periapical' => true,
                'dental_xray_panoramic' => true,

                // Clinic Staff
                'clinic_staff_name' => 'Ana Lopez',
                'clinic_staff_mobile' => '09181234567',
                'clinic_staff_viber' => '09181234567',
                'clinic_staff_email' => 'ana.staff@smiledental.com',

                // Bank Information
                'bank_account_name' => 'Smile Dental Care Inc.',
                'bank_account_number' => '1234567890',
                'bank_name' => 'BDO',
                'bank_branch' => 'Makati Branch',
                'account_type' => 'savings',

                // Status
                'status' => 'active',

                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
