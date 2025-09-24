<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Clinics extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'last_name',
        'first_name',
        'middle_initial',
        'suffix',
        'owner_last_name',
        'owner_first_name',
        'owner_middle_initial',
        'owner_suffix',
        'corporate_name',
        'clinic_name',
        'branch_code',
        'tin_number',
        'clinic_address',
        'barangay',
        'city',
        'province',
        'region',
        'landline',
        'mobile_number',
        'alternative_number',
        'accreditation_status',
        'bank_account_name',
        'bank_branch',
        'bank_account_number',
        'tax_registration',
        'withholding_tax',
    ];

    protected $with = ['specializations', 'basicDentalServices', 'planEnhancements'];

    public function specializations()
    {
        return $this->belongsToMany(
            Specializations::class,
            'dentist_specializations',
            'dentist_id',
            'specialization_id'
        );
    }


    public function basicDentalServices()
    {
        return $this->belongsToMany(BasicDentalService::class, 'dentist_basic_dental_service')
            ->withPivot('fee');
    }

    public function planEnhancements()
    {
        return $this->belongsToMany(PlanEnhancement::class, 'dentist_plan_enhancement')
            ->withPivot('fee');
    }

    public function accreditationStatus()
    {
        return $this->belongsTo(\App\Models\AccreditationStatus::class);
    }
}

