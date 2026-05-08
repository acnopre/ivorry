<?php

namespace App\Support;

use App\Models\Hip;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ImportTemplates
{
    public static function account(): array
    {
        $hips      = Hip::pluck('name')->toArray() ?: ['DEFAULT HIP'];
        $effective = Carbon::today()->format('Y-m-d');
        $expiry    = Carbon::today()->addYear()->subDay()->format('Y-m-d');

        return [
            'company_name'                                                 => 'Sample Company ' . strtoupper(Str::random(4)),
            'policy_code'                                                  => 'POL-' . strtoupper(Str::random(6)),
            'hip'                                                          => $hips[array_rand($hips)],
            'card_used'                                                    => 'IVORRY',
            'effective_date'                                               => $effective,
            'expiration_date'                                              => $expiry,
            'plan_type'                                                    => 'INDIVIDUAL',
            'coverage_type'                                                => 'ACCOUNT',
            'account_coverage_type'                                        => 'DEFAULT',
            'endorsement_type'                                             => 'NEW',
            'mbl_type'                                                     => 'Procedural',
            'mbl_amount'                                                   => '',
            'consultation'                                                 => 'unlimited',
            'treatment_of_sores_blisters'                                  => 'unlimited',
            'temporary_fillings'                                           => 'unlimited',
            'simple_tooth_extraction'                                      => 'unlimited',
            'recementation_of_fixed_bridges_crowns_jackets_inlays_onlays' => 'unlimited',
            'adjustment_of_dentures'                                       => 'unlimited',
            'oral_prophylaxis'                                             => 2,
            'permanent_filling_per_tooth'                                  => 2,
            'permanent_filling_per_surface'                                => 4,
            'desensitization_of_hypersensitive_teeth'                      => 2,
            'fluoride_brushing'                                            => 1,
            'incision_and_drainage'                                        => 1,
            'peri_apical_xray'                                             => 2,
            'panoramic_xray'                                               => 1,
            'complicated_difficult_extraction'                             => 1,
            'odontectomy_removal_of_impacted_tooth'                        => 1,
            'root_canal_treatment_per_canal'                               => 2,
            'root_canal_treatment_per_tooth'                               => 1,
            'jacket_crowns'                                                => 1,
            'dentures'                                                     => 1,
            'pit_and_fissure_sealants'                                     => 2,
            'topical_fluoride_application'                                 => 1,
            'minor_soft_tissue_surgery'                                    => 1,
        ];
    }

    public static function member(): array
    {
        return [
            'account_name'    => 'Sample Company ABC',
            'hip'             => 'Sample HIP',
            'first_name'      => 'Juan',
            'last_name'       => 'Dela Cruz',
            'middle_name'     => 'Santos',
            'suffix'          => '',
            'member_type'     => 'PRINCIPAL',
            'card_number'     => '101092000001',
            'old_card_number' => '',
            'birthdate'       => '1990-05-15',
            'gender'          => 'male',
            'email'           => 'juan.delacruz@example.com',
            'phone'           => '09171234567',
            'address'         => '123 Rizal St., Manila',
            'status'          => 'ACTIVE',
            'inactive_date'   => '',
            'effective_date'  => '',
            'expiration_date' => '',
        ];
    }

    public static function clinic(): array
    {
        return [
            'clinic_name'                                                  => 'Sample Dental Clinic',
            'registered_name'                                              => 'Sample Dental Clinic',
            'clinic_email'                                                 => 'sample.dental@example.com',
            'password'                                                     => 'password',
            'clinic_mobile'                                                => '09171234567',
            'clinic_landline'                                              => '',
            'complete_address'                                             => '123 Sample St., Manila',
            'street'                                                       => 'Sample St.',
            'region_name'                                                  => '',
            'province_name'                                                => '',
            'municipality_name'                                            => 'Manila',
            'barangay_name'                                                => '',
            'business_type'                                                => 'SOLE PROPRIETORSHIP',
            'vat_type'                                                     => 'VAT 12%',
            'withholding_tax'                                              => '2%',
            'tax_identification_no'                                        => '123-456-789',
            'sec_registration_no'                                          => '',
            'ptr_no'                                                       => 'PTR-123456',
            'ptr_date_issued'                                              => Carbon::today()->subMonths(6)->format('Y-m-d'),
            'accreditation_status'                                         => 'ACTIVE',
            'account_name'                                                 => '',
            'hip_name'                                                     => '',
            'is_branch'                                                    => 0,
            'bank_name'                                                    => 'BDO',
            'bank_branch'                                                  => 'Manila Branch',
            'bank_account_name'                                            => 'Sample Dental Clinic',
            'bank_account_number'                                          => '1234567890',
            'account_type'                                                 => 'SAVINGS',
            'owner_first_name'                                             => 'Maria',
            'owner_last_name'                                              => 'Santos',
            'owner_middle_initial'                                         => 'R',
            'owner_prc_license'                                            => 'PRC-123456',
            'owner_prc_expiration'                                         => Carbon::today()->addYears(2)->format('Y-m-d'),
            'clinic_staff_name'                                            => '',
            'clinic_staff_mobile'                                          => '',
            'clinic_staff_viber'                                           => '',
            'clinic_staff_email'                                           => '',
            'viber_no'                                                     => '',
            'alt_address'                                                  => '',
            'remarks'                                                      => '',
            'consultation'                                                 => 500,
            'treatment_of_sores_blisters'                                  => 300,
            'temporary_fillings'                                           => 400,
            'simple_tooth_extraction'                                      => 600,
            'recementation_of_fixed_bridges_crowns_jackets_inlays_onlays' => 400,
            'adjustment_of_dentures'                                       => 350,
            'oral_prophylaxis'                                             => 800,
            'permanent_filling_per_tooth'                                  => 1000,
            'permanent_filling_per_surface'                                => 600,
            'desensitization_of_hypersensitive_teeth'                      => 400,
            'fluoride_brushing'                                            => 350,
            'incision_and_drainage'                                        => 1000,
            'peri_apical_xray'                                             => 500,
            'panoramic_xray'                                               => 1200,
            'complicated_difficult_extraction'                             => 1500,
            'odontectomy_removal_of_impacted_tooth'                        => 4000,
            'root_canal_treatment_per_canal'                               => 2500,
            'root_canal_treatment_per_tooth'                               => 5000,
            'jacket_crowns'                                                => 5000,
            'dentures'                                                     => 10000,
            'pit_and_fissure_sealants'                                     => 500,
            'topical_fluoride_application'                                 => 400,
            'minor_soft_tissue_surgery'                                    => 2000,
        ];
    }

    public static function columns(string $type): array
    {
        return array_keys(match ($type) {
            'account' => static::account(),
            'member'  => static::member(),
            'clinic'  => static::clinic(),
            default   => [],
        });
    }
}
