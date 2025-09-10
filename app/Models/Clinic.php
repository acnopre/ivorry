<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $fillable = [
        'clinic_name',
        'registered_name',
        'clinic_owner_last',
        'clinic_owner_first',
        'clinic_owner_middle',
        'specializations',

        'prc_license_no',
        'prc_expiration_date',
        'ptr_no',
        'ptr_date_issued',

        'other_hmo_accreditation',
        'tax_identification_no',
        'tax_type',
        'business_type',
        'sec_registration_no',

        'clinic_address',
        'clinic_landline',
        'clinic_mobile',
        'viber_no',
        'clinic_email',

        'alt_address',

        'dentist_personal_no',
        'dentist_email',
        'clinic_schedule',
        'schedule_days',
        'number_of_chairs',
        'dental_xray_periapical',
        'dental_xray_panoramic',

        'clinic_staff_name',
        'clinic_staff_mobile',
        'clinic_staff_viber',
        'clinic_staff_email',

        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'bank_branch',
        'account_type',

        'status',
    ];

    public function dentists()
    {
        return $this->hasMany(Dentist::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function soas()
    {
        return $this->hasMany(Soa::class);
    }
}
