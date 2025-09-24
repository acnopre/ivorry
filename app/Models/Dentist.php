<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Dentist extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'clinic_id',
        'last_name',
        'first_name',
        'middle_initial',
        'prc_license_number',
        'prc_expiration_date',
        'is_owner',
    ];

    protected $with = ['specializations', 'basicDentalServices', 'planEnhancements'];

    protected $casts = [
        'prc_expiration_date' => 'date',
        'is_owner' => 'boolean',
    ];
    
    public function clinic()
    {
        return $this->belongsTo(Clinics::class, 'clinic_id');
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

    protected static function booted()
    {
        static::saving(function ($dentist) {
            if ($dentist->is_owner) {
                static::where('clinic_id', $dentist->clinic_id)
                    ->where('id', '!=', $dentist->id)
                    ->update(['is_owner' => false]);
            }
        });
    }
}

