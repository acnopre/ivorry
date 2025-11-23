<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TaxType;
use App\Models\BusinessType;
use App\Models\AccountType;
use App\Models\AccreditationStatus;

class Clinic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'clinic_name',
        'registered_name',
        'prc_license_no',
        'prc_expiration_date',
        'ptr_no',
        'ptr_date_issued',
        'other_hmo_accreditation',
        'tax_identification_no',
        'is_branch',
        'update_info_1903',
        'complete_address',
        'witholding_tax',
        'tax_type',
        'business_type',
        'sec_registration_no',
        'vat_type',
        'street',
        'region_id',
        'province_id',
        'municipality_id',
        'barangay_id',
        'clinic_landline',
        'clinic_mobile',
        'viber_no',
        'clinic_email',
        'alt_address',
        'dentist_personal_no',
        'dentist_email',
        'clinic_staff_name',
        'clinic_staff_mobile',
        'clinic_staff_viber',
        'clinic_staff_email',
        'bank_account_name',
        'bank_account_number',
        'bank_name',
        'bank_branch',
        'account_type',
        'accreditation_status',
        'associate_dentists',
    ];

    // protected $with = ['specializations', 'basicDentalServices', 'planEnhancements'];

    public function dentists()
    {
        return $this->hasMany(Dentist::class, 'clinic_id');
    }

    public function procedures()
    {
        return $this->hasMany(Procedure::class);
    }

    public function specializations()
    {
        return $this->belongsToMany(
            Specializations::class,
            'dentist_specializations',
            'dentist_id',
            'specialization_id'
        );
    }

    public function accreditationStatus()
    {
        return $this->belongsTo(AccreditationStatus::class);
    }

    public function taxType()
    {
        return $this->belongsTo(TaxType::class);
    }

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }
    public function services()
    {
        return $this->belongsToMany(Service::class, 'clinic_service')->withPivot('fee')->withTimestamps();
    }
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }
}
